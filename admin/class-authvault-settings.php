<?php
/**
 * Settings page and Settings API for WP AuthVault.
 *
 * @package AuthVault
 */

namespace AuthVault\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and renders the Settings > AuthVault page using the WordPress Settings API.
 *
 * The page is organised into six horizontal tabs:
 *  1. General   — Page assignments, registration
 *  2. Security  — Brute force, password policy, rate limiting, reCAPTCHA
 *  3. Access    — URL hiding, wp-login behaviour, logged-in redirect
 *  4. Email     — Sender override, password-reset email template
 *  5. Messages  — Customisable user-facing strings
 *  6. Logs      — Login logging settings, log viewer, filters, CSV export
 */
class AuthVault_Settings {

	const OPTION_GROUP      = 'authvault_settings_group';
	const OPTION_NAME       = 'authvault_settings';
	const PAGE_SLUG         = 'authvault-settings';
	const NONCE_ACTION      = 'authvault_settings_save';
	const RESET_NONCE_ACTION = 'authvault_settings_reset';

	/**
	 * Tab definitions: id => label.
	 *
	 * @var array<string, string>
	 */
	private $tabs = array();

	/**
	 * Cron hook name for daily login log cleanup.
	 *
	 * @var string
	 */
	const LOG_CLEANUP_CRON_HOOK = 'authvault_cleanup_login_log';

	/**
	 * AJAX action for CSV export of login log.
	 *
	 * @var string
	 */
	const EXPORT_AJAX_ACTION = 'authvault_export_login_log';

	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'handle_reset' ), 5, 0 );
		add_action( 'wp_ajax_' . self::EXPORT_AJAX_ACTION, array( $this, 'handle_export_login_log' ) );
	}

	/**
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'WP AuthVault', 'authvault' ),
			__( 'WP AuthVault', 'authvault' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/* =====================================================================
	   Settings API registration (still needed for sanitize_callback)
	   ===================================================================== */

	/**
	 * @return void
	 */
	public function register_settings() {
		register_setting(
			self::OPTION_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize_settings' ),
			)
		);
	}

	/* =====================================================================
	   Sanitisation
	   ===================================================================== */

	/**
	 * @param array<string, mixed>|null $input Raw POST input.
	 * @return array<string, mixed>
	 */
	public function sanitize_settings( $input ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return (array) get_option( self::OPTION_NAME, array() );
		}
		if ( ! is_array( $input ) ) {
			$input = array();
		}
		$defaults = authvault_get_settings_defaults();
		$output   = array_merge( $defaults, $input );

		// Page IDs.
		$page_keys = array(
			'login_page_id',
			'register_page_id',
			'password_reset_page_id',
			'password_reset_confirm_page_id',
			'login_redirect_page_id',
			'logout_redirect_page_id',
			'wp_login_redirect_page_id',
			'logged_in_redirect_page_id',
		);
		foreach ( $page_keys as $k ) {
			$output[ $k ] = isset( $input[ $k ] ) ? absint( $input[ $k ] ) : $defaults[ $k ];
		}

		// Access Control / URL Security.
		$output['custom_login_slug']       = isset( $input['custom_login_slug'] ) ? sanitize_title( $input['custom_login_slug'] ) : $defaults['custom_login_slug'];
		$output['enable_login_url_hiding'] = isset( $input['enable_login_url_hiding'] );

		$valid_behaviors = array( '404', 'home', 'page' );
		$output['wp_login_access_behavior'] = isset( $input['wp_login_access_behavior'] ) && in_array( $input['wp_login_access_behavior'], $valid_behaviors, true )
			? $input['wp_login_access_behavior']
			: $defaults['wp_login_access_behavior'];

		$valid_logged_in = array( 'home', 'dashboard', 'page' );
		$output['logged_in_redirect_behavior'] = isset( $input['logged_in_redirect_behavior'] ) && in_array( $input['logged_in_redirect_behavior'], $valid_logged_in, true )
			? $input['logged_in_redirect_behavior']
			: $defaults['logged_in_redirect_behavior'];

		// Registration.
		$output['enable_user_registration'] = isset( $input['enable_user_registration'] );
		$editable_roles                     = array_keys( get_editable_roles() );
		$output['default_role']             = isset( $input['default_role'] ) && in_array( $input['default_role'], $editable_roles, true )
			? $input['default_role']
			: $defaults['default_role'];
		update_option( 'users_can_register', $output['enable_user_registration'] ? '1' : '0' );

		// Security — Brute Force.
		$output['enable_lockout']          = isset( $input['enable_lockout'] );
		$output['max_login_attempts']      = isset( $input['max_login_attempts'] ) ? absint( $input['max_login_attempts'] ) : $defaults['max_login_attempts'];
		$output['max_login_attempts']      = max( 1, min( 100, $output['max_login_attempts'] ) );
		$output['lockout_duration_minutes'] = isset( $input['lockout_duration_minutes'] ) ? absint( $input['lockout_duration_minutes'] ) : $defaults['lockout_duration_minutes'];
		$output['lockout_duration_minutes'] = max( 1, min( 1440, $output['lockout_duration_minutes'] ) );
		$output['lockout_admin_email_notification'] = isset( $input['lockout_admin_email_notification'] );
		$output['lockout_notification_email']       = isset( $input['lockout_notification_email'] ) ? sanitize_email( $input['lockout_notification_email'] ) : $defaults['lockout_notification_email'];

		// Security — Password Policy.
		$output['allow_weak_passwords'] = isset( $input['allow_weak_passwords'] );
		$output['min_password_length']  = isset( $input['min_password_length'] ) ? absint( $input['min_password_length'] ) : $defaults['min_password_length'];
		$output['min_password_length']  = max( 1, min( 128, $output['min_password_length'] ) );
		if ( ! $output['allow_weak_passwords'] ) {
			$output['min_password_length'] = max( 10, $output['min_password_length'] );
		}

		// Security — Rate Limiting.
		$output['reset_rate_limit_max']            = isset( $input['reset_rate_limit_max'] ) ? absint( $input['reset_rate_limit_max'] ) : $defaults['reset_rate_limit_max'];
		$output['reset_rate_limit_max']            = max( 1, min( 100, $output['reset_rate_limit_max'] ) );
		$output['reset_rate_limit_window_minutes'] = isset( $input['reset_rate_limit_window_minutes'] ) ? absint( $input['reset_rate_limit_window_minutes'] ) : $defaults['reset_rate_limit_window_minutes'];
		$output['reset_rate_limit_window_minutes'] = max( 1, min( 1440, $output['reset_rate_limit_window_minutes'] ) );

		// Logs — Login Logging.
		$output['enable_login_log']         = isset( $input['enable_login_log'] );
		$output['login_log_retention_days'] = isset( $input['login_log_retention_days'] ) ? absint( $input['login_log_retention_days'] ) : $defaults['login_log_retention_days'];
		$output['login_log_retention_days'] = max( 1, min( 365, $output['login_log_retention_days'] ) );

		$old = (array) get_option( self::OPTION_NAME, array() );
		$old_retention = isset( $old['login_log_retention_days'] ) ? (int) $old['login_log_retention_days'] : $defaults['login_log_retention_days'];
		if ( $output['login_log_retention_days'] !== $old_retention ) {
			\AuthVault\AuthVault_Security::cleanup_old_login_log_entries();
		}

		// Security — reCAPTCHA.
		$output['recaptcha_enabled']    = isset( $input['recaptcha_enabled'] );
		$output['recaptcha_site_key']   = isset( $input['recaptcha_site_key'] ) ? sanitize_text_field( $input['recaptcha_site_key'] ) : $defaults['recaptcha_site_key'];
		$output['recaptcha_secret_key'] = isset( $input['recaptcha_secret_key'] ) ? sanitize_text_field( $input['recaptcha_secret_key'] ) : $defaults['recaptcha_secret_key'];
		$output['recaptcha_min_score']  = isset( $input['recaptcha_min_score'] ) ? (float) $input['recaptcha_min_score'] : $defaults['recaptcha_min_score'];
		$output['recaptcha_min_score']  = max( 0.0, min( 1.0, $output['recaptcha_min_score'] ) );

		// Email.
		$output['override_lost_password_email'] = isset( $input['override_lost_password_email'] );
		$output['email_from_name']              = isset( $input['email_from_name'] ) ? sanitize_text_field( $input['email_from_name'] ) : $defaults['email_from_name'];
		$output['email_from_email']             = isset( $input['email_from_email'] ) ? sanitize_email( $input['email_from_email'] ) : $defaults['email_from_email'];
		$output['reset_email_subject']          = isset( $input['reset_email_subject'] ) ? sanitize_text_field( $input['reset_email_subject'] ) : $defaults['reset_email_subject'];
		$output['reset_email_body']             = isset( $input['reset_email_body'] ) ? wp_kses_post( $input['reset_email_body'] ) : $defaults['reset_email_body'];

		// Messages.
		$message_keys = array(
			'msg_login_error',
			'msg_login_lockout',
			'msg_login_registered',
			'msg_login_password_reset',
			'msg_register_error',
			'msg_reset_sent',
			'msg_reset_invalid_key',
			'msg_confirm_invalid_link',
			'msg_confirm_password_empty',
			'msg_confirm_password_mismatch',
			'msg_confirm_password_weak',
			'msg_confirm_password_too_weak',
		);
		foreach ( $message_keys as $mk ) {
			$output[ $mk ] = isset( $input[ $mk ] ) ? sanitize_text_field( $input[ $mk ] ) : $defaults[ $mk ];
		}

		return $output;
	}

	/* =====================================================================
	   Reset handler
	   ===================================================================== */

	/**
	 * @return void
	 */
	public function handle_reset() {
		if ( ! isset( $_POST[ self::RESET_NONCE_ACTION ] ) || ! is_string( $_POST[ self::RESET_NONCE_ACTION ] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::RESET_NONCE_ACTION ] ) ), self::RESET_NONCE_ACTION ) ) {
			wp_safe_redirect( add_query_arg( 'authvault_reset', 'nonce_fail', $this->get_settings_url() ) );
			exit;
		}
		$defaults = authvault_get_settings_defaults();
		update_option( self::OPTION_NAME, $defaults );
		wp_safe_redirect( add_query_arg( 'authvault_reset', 'success', $this->get_settings_url() ) );
		exit;
	}

	/**
	 * @return string
	 */
	private function get_settings_url() {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
	}

	/* =====================================================================
	   Page rendering — tabbed layout
	   ===================================================================== */

	/**
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'authvault' ) );
		}

		$this->tabs = array(
			'general'  => __( 'General', 'authvault' ),
			'security' => __( 'Security', 'authvault' ),
			'access'   => __( 'Access Control', 'authvault' ),
			'email'    => __( 'Email', 'authvault' ),
			'messages' => __( 'Messages', 'authvault' ),
			'logs'     => __( 'Logs', 'authvault' ),
		);

		$this->render_admin_notices();

		echo '<div class="wrap authvault-settings-wrap">';
		echo '<h1 class="authvault-settings-title">' . esc_html__( 'AuthVault Settings', 'authvault' ) . '</h1>';

		$this->render_tabs_nav();

		echo '<form method="post" action="options.php" id="authvault-settings-form">';
		settings_fields( self::OPTION_GROUP );

		foreach ( $this->tabs as $tab_id => $tab_label ) {
			$method = 'render_tab_' . $tab_id;
			echo '<div class="authvault-tab-panel" id="authvault-tab-' . esc_attr( $tab_id ) . '" data-tab="' . esc_attr( $tab_id ) . '">';
			if ( method_exists( $this, $method ) ) {
				$this->$method();
			}
			echo '</div>';
		}

		echo '<div class="authvault-form-actions">';
		submit_button( __( 'Save Settings', 'authvault' ), 'primary', 'submit', false );
		echo '</div>';
		echo '</form>';

		echo '<form method="post" action="" id="authvault-reset-form" class="authvault-reset-form">';
		wp_nonce_field( self::RESET_NONCE_ACTION, self::RESET_NONCE_ACTION );
		$reset_confirm = __(
			'Reset all WP AuthVault settings to their default values? This will clear your page assignments, security options (lockout, reCAPTCHA, passwords), access control, email settings, and all custom messages. This cannot be undone. Continue?',
			'authvault'
		);
		echo '<button type="submit" name="authvault_reset_submit" class="button button-secondary" onclick="return confirm(\'' . esc_js( $reset_confirm ) . '\');">';
		echo esc_html__( 'Reset to defaults', 'authvault' );
		echo '</button>';
		echo '</form>';

		echo '</div>';
	}

	/**
	 * @return void
	 */
	private function render_tabs_nav() {
		echo '<nav class="authvault-tabs-nav" role="tablist">';
		$first = true;
		foreach ( $this->tabs as $tab_id => $label ) {
			$active = $first ? ' authvault-tab-active' : '';
			echo '<a href="#' . esc_attr( $tab_id ) . '" class="authvault-tab-link' . $active . '" role="tab" data-tab="' . esc_attr( $tab_id ) . '">';
			echo esc_html( $label );
			echo '</a>';
			$first = false;
		}
		echo '</nav>';
	}

	/* =====================================================================
	   Tab 1 — General
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_tab_general() {
		// --- Page Assignments ---
		$this->render_section_heading(
			__( 'Page Assignments', 'authvault' ),
			__( 'Assign WordPress pages to AuthVault authentication flows. Pages with a selected assignment show a green indicator.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_page_row( 'login_page_id', __( 'Login Page', 'authvault' ), __( 'The page containing your login form.', 'authvault' ) );
		$this->render_page_row( 'register_page_id', __( 'Register Page', 'authvault' ), __( 'The page containing your registration form.', 'authvault' ) );
		$this->render_page_row( 'password_reset_page_id', __( 'Password Reset Page', 'authvault' ), __( 'Where users request a password reset link.', 'authvault' ) );
		$this->render_page_row( 'password_reset_confirm_page_id', __( 'Set New Password Page', 'authvault' ), __( 'Where users set their new password after clicking the email link.', 'authvault' ) );
		$this->render_page_row( 'login_redirect_page_id', __( 'After Login Redirect', 'authvault' ), __( 'Where users are sent after a successful login. Defaults to home page.', 'authvault' ) );
		$this->render_page_row( 'logout_redirect_page_id', __( 'After Logout Redirect', 'authvault' ), __( 'Where users are sent after logging out. Defaults to home page.', 'authvault' ) );
		echo '</table>';

		// --- Registration ---
		$this->render_section_heading(
			__( 'Registration', 'authvault' ),
			__( 'Control whether visitors can create new accounts and which role they receive.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_checkbox_row( 'enable_user_registration', __( 'Allow registration', 'authvault' ), __( 'Syncs with the WordPress "Anyone can register" setting.', 'authvault' ) );
		$this->render_role_row( 'default_role', __( 'Default role', 'authvault' ), __( 'Role assigned to newly registered users.', 'authvault' ) );
		echo '</table>';
	}

	/* =====================================================================
	   Tab 2 — Security
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_tab_security() {
		// --- Brute Force Protection ---
		$this->render_section_heading(
			__( 'Brute Force Protection', 'authvault' ),
			__( 'Temporarily lock out IP addresses after repeated failed login attempts.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_checkbox_row( 'enable_lockout', __( 'Enable lockout', 'authvault' ), __( 'When enabled, IPs are locked after exceeding the attempt limit below.', 'authvault' ) );
		$this->render_number_row( 'max_login_attempts', __( 'Max login attempts', 'authvault' ), 5, 1, 100, __( 'Failed attempts before a lockout is triggered.', 'authvault' ), 'authvault-lockout-dependent' );
		$this->render_number_row( 'lockout_duration_minutes', __( 'Lockout duration (minutes)', 'authvault' ), 15, 1, 1440, __( 'How long an IP is locked out.', 'authvault' ), 'authvault-lockout-dependent' );
		$this->render_checkbox_row( 'lockout_admin_email_notification', __( 'Email admin on lockout', 'authvault' ), __( 'Send an email to the address below whenever an IP is locked out.', 'authvault' ), 'authvault-lockout-dependent' );
		$this->render_email_row( 'lockout_notification_email', __( 'Notification email', 'authvault' ), __( 'Leave blank to use the site admin email.', 'authvault' ), 'authvault-lockout-notify-dependent' );
		echo '</table>';

		// --- Password Policy ---
		$this->render_section_heading(
			__( 'Password Policy', 'authvault' ),
			__( 'Enforce password strength requirements on the set-new-password form.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$allow_weak = (bool) authvault_get_option( 'allow_weak_passwords', false );
		$min_length_min = $allow_weak ? 1 : 10;
		$this->render_number_row(
			'min_password_length',
			__( 'Minimum password length', 'authvault' ),
			10,
			$min_length_min,
			128,
			__( 'Passwords shorter than this are rejected. When weak passwords are not allowed, minimum is 10.', 'authvault' )
		);
		$this->render_checkbox_row( 'allow_weak_passwords', __( 'Allow weak passwords', 'authvault' ), __( 'If unchecked, only medium or strong passwords are accepted and minimum length is 10.', 'authvault' ) );
		echo '</table>';
		$password_policy_notice_visible = ! $allow_weak;
		echo '<div id="authvault-password-policy-weak-notice" class="notice notice-warning inline authvault-settings-notice" style="' . ( $password_policy_notice_visible ? '' : 'display:none;' ) . '">';
		echo '<p>';
		echo esc_html__( 'When "Allow weak passwords" is off, minimum length cannot be set below 10. To use a lower minimum (e.g. 8), enable "Allow weak passwords" above.', 'authvault' );
		echo ' <strong>';
		echo esc_html__( 'We recommend keeping weak passwords disabled for better site security.', 'authvault' );
		echo '</strong></p>';
		echo '</div>';

		// --- Rate Limiting ---
		$this->render_section_heading(
			__( 'Rate Limiting', 'authvault' ),
			__( 'Limit how many password-reset requests a single IP can make.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_number_row( 'reset_rate_limit_max', __( 'Max reset requests per IP', 'authvault' ), 5, 1, 100, __( 'Number of password-reset requests allowed within the window below.', 'authvault' ) );
		$this->render_number_row( 'reset_rate_limit_window_minutes', __( 'Rate limit window (minutes)', 'authvault' ), 15, 1, 1440, __( 'Time window for the rate limit counter.', 'authvault' ) );
		echo '</table>';

		// --- reCAPTCHA ---
		$this->render_section_heading(
			__( 'Google reCAPTCHA v3', 'authvault' ),
			__( 'Protect forms with invisible reCAPTCHA. Obtain keys from the Google reCAPTCHA admin console.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_checkbox_row( 'recaptcha_enabled', __( 'Enable reCAPTCHA', 'authvault' ), __( 'When enabled, login and registration forms require reCAPTCHA verification.', 'authvault' ) );
		$this->render_text_row( 'recaptcha_site_key', __( 'Site Key', 'authvault' ), '', __( 'The public site key from Google reCAPTCHA.', 'authvault' ), 'authvault-recaptcha-dependent' );
		$this->render_password_row( 'recaptcha_secret_key', __( 'Secret Key', 'authvault' ), __( 'The private secret key from Google reCAPTCHA.', 'authvault' ), 'authvault-recaptcha-dependent' );
		$this->render_score_row( 'recaptcha_min_score', __( 'Minimum score', 'authvault' ), 0.5, __( 'Score between 0.0 and 1.0. Requests scoring below this threshold are rejected. Default: 0.5.', 'authvault' ), 'authvault-recaptcha-dependent' );
		echo '</table>';
	}

	/* =====================================================================
	   Tab 3 — Access Control
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_tab_access() {
		// --- Custom Login URL ---
		$this->render_section_heading(
			__( 'Custom Login URL', 'authvault' ),
			__( 'Replace the default wp-login.php URL with a custom slug.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_text_row( 'custom_login_slug', __( 'Login URL slug', 'authvault' ), 'login', __( 'Visitors can access your login page at yourdomain.com/this-slug.', 'authvault' ) );
		$this->render_checkbox_row( 'enable_login_url_hiding', __( 'Hide wp-login.php', 'authvault' ), __( 'Block direct access to wp-login.php and wp-admin for logged-out users.', 'authvault' ) );
		echo '</table>';

		// --- Blocked URL Behavior ---
		$this->render_section_heading(
			__( 'Blocked URL Behavior', 'authvault' ),
			__( 'What happens when someone tries to access wp-login.php directly (requires URL hiding above).', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table" id="authvault-url-hiding-dependent">';
		$this->render_select_row(
			'wp_login_access_behavior',
			__( 'wp-login.php behavior', 'authvault' ),
			array(
				'404'  => __( 'Show 404 page', 'authvault' ),
				'home' => __( 'Redirect to homepage', 'authvault' ),
				'page' => __( 'Redirect to custom page', 'authvault' ),
			),
			'404',
			__( 'Action taken when a logged-out user visits wp-login.php.', 'authvault' )
		);
		$this->render_page_row( 'wp_login_redirect_page_id', __( 'Custom redirect page', 'authvault' ), __( 'Only used when "Redirect to custom page" is selected above.', 'authvault' ), 'authvault-wplogin-page-dependent' );
		echo '</table>';

		// --- Logged-in User Redirect ---
		$this->render_section_heading(
			__( 'Logged-in User Redirect', 'authvault' ),
			__( 'Where to send already logged-in users who visit the login or register page.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_select_row(
			'logged_in_redirect_behavior',
			__( 'Redirect to', 'authvault' ),
			array(
				'home'      => __( 'Homepage', 'authvault' ),
				'dashboard' => __( 'WordPress Dashboard', 'authvault' ),
				'page'      => __( 'Custom page', 'authvault' ),
			),
			'dashboard',
			__( 'Where logged-in users are redirected when they visit the login or register page.', 'authvault' )
		);
		$this->render_page_row( 'logged_in_redirect_page_id', __( 'Custom redirect page', 'authvault' ), __( 'Only used when "Custom page" is selected above.', 'authvault' ), 'authvault-loggedin-page-dependent' );
		echo '</table>';
	}

	/* =====================================================================
	   Tab 4 — Email
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_tab_email() {
		// --- Sender Override ---
		$this->render_section_heading(
			__( 'Email Sender', 'authvault' ),
			__( 'Override the "From" name and address used by WordPress for password-reset emails.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_checkbox_row( 'override_lost_password_email', __( 'Override email sender', 'authvault' ), __( 'When enabled, AuthVault replaces the default WordPress "From" name and address for lost-password emails.', 'authvault' ) );
		$this->render_text_row( 'email_from_name', __( '"From" name', 'authvault' ), '', __( 'Name shown in the "From" field of outgoing emails.', 'authvault' ), 'authvault-email-override-dependent' );
		$this->render_email_row( 'email_from_email', __( '"From" email address', 'authvault' ), __( 'Email address used as the sender.', 'authvault' ), 'authvault-email-override-dependent' );
		echo '</table>';

		// --- Password Reset Email Template ---
		$this->render_section_heading(
			__( 'Password Reset Email', 'authvault' ),
			__( 'Customise the subject and body of the password-reset email. Leave blank to use the WordPress default.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_text_row( 'reset_email_subject', __( 'Email subject', 'authvault' ), '', __( 'Available tokens: {site_name}, {user_login}', 'authvault' ), 'authvault-email-override-dependent' );
		$this->render_textarea_row( 'reset_email_body', __( 'Email body', 'authvault' ), __( 'Available tokens: {site_name}, {user_login}, {reset_link}. Use line breaks for formatting.', 'authvault' ), 'authvault-email-override-dependent' );
		echo '</table>';
	}

	/* =====================================================================
	   Tab 5 — Messages
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_tab_messages() {
		$defaults = authvault_get_settings_defaults();

		$default_messages = array(
			'msg_login_error'               => __( 'Invalid username or password.', 'authvault' ),
			'msg_login_lockout'             => __( 'Too many failed attempts. Please try again in %d minutes.', 'authvault' ),
			'msg_login_registered'          => __( 'Registration successful! You can now log in.', 'authvault' ),
			'msg_login_password_reset'      => __( 'Your password has been reset. You can now log in.', 'authvault' ),
			'msg_register_error'            => __( 'Registration failed. Please try again.', 'authvault' ),
			'msg_reset_sent'                => __( 'If that email is registered, a reset link has been sent.', 'authvault' ),
			'msg_reset_invalid_key'         => __( 'This reset link is invalid or has expired.', 'authvault' ),
			'msg_confirm_invalid_link'      => __( 'This reset link is invalid or has expired.', 'authvault' ),
			'msg_confirm_password_empty'    => __( 'Please enter a new password.', 'authvault' ),
			'msg_confirm_password_mismatch' => __( 'Passwords do not match.', 'authvault' ),
			'msg_confirm_password_weak'     => __( 'Password must be at least %d characters.', 'authvault' ),
			'msg_confirm_password_too_weak' => __( 'Password is too weak. Please choose a stronger password.', 'authvault' ),
		);

		// --- Login Messages ---
		$this->render_section_heading(
			__( 'Login Messages', 'authvault' ),
			__( 'Messages displayed on the login form. Leave blank to use the default shown as placeholder.', 'authvault' )
		);
		echo '<table class="form-table authvault-form-table">';
		$this->render_message_row( 'msg_login_error', __( 'Login error', 'authvault' ), $default_messages['msg_login_error'] );
		$this->render_message_row( 'msg_login_lockout', __( 'Lockout notice', 'authvault' ), $default_messages['msg_login_lockout'], __( '%d is replaced by lockout duration in minutes.', 'authvault' ) );
		$this->render_message_row( 'msg_login_registered', __( 'Registration success', 'authvault' ), $default_messages['msg_login_registered'] );
		$this->render_message_row( 'msg_login_password_reset', __( 'Password reset success', 'authvault' ), $default_messages['msg_login_password_reset'] );
		echo '</table>';

		// --- Registration Messages ---
		$this->render_section_heading(
			__( 'Registration Messages', 'authvault' ),
			__( 'Messages displayed on the registration form.', 'authvault' )
		);
		echo '<table class="form-table authvault-form-table">';
		$this->render_message_row( 'msg_register_error', __( 'Registration error', 'authvault' ), $default_messages['msg_register_error'] );
		echo '</table>';

		// --- Password Reset Messages ---
		$this->render_section_heading(
			__( 'Password Reset Messages', 'authvault' ),
			__( 'Messages displayed on the password reset request form.', 'authvault' )
		);
		echo '<table class="form-table authvault-form-table">';
		$this->render_message_row( 'msg_reset_sent', __( 'Reset link sent', 'authvault' ), $default_messages['msg_reset_sent'] );
		$this->render_message_row( 'msg_reset_invalid_key', __( 'Invalid reset link', 'authvault' ), $default_messages['msg_reset_invalid_key'] );
		echo '</table>';

		// --- Set New Password Messages ---
		$this->render_section_heading(
			__( 'Set New Password Messages', 'authvault' ),
			__( 'Messages displayed on the set-new-password (confirm) form.', 'authvault' )
		);
		echo '<table class="form-table authvault-form-table">';
		$this->render_message_row( 'msg_confirm_invalid_link', __( 'Invalid link', 'authvault' ), $default_messages['msg_confirm_invalid_link'] );
		$this->render_message_row( 'msg_confirm_password_empty', __( 'Empty password', 'authvault' ), $default_messages['msg_confirm_password_empty'] );
		$this->render_message_row( 'msg_confirm_password_mismatch', __( 'Password mismatch', 'authvault' ), $default_messages['msg_confirm_password_mismatch'] );
		$this->render_message_row( 'msg_confirm_password_weak', __( 'Password too short', 'authvault' ), $default_messages['msg_confirm_password_weak'], __( '%d is replaced by the minimum length.', 'authvault' ) );
		$this->render_message_row( 'msg_confirm_password_too_weak', __( 'Password too weak', 'authvault' ), $default_messages['msg_confirm_password_too_weak'] );
		echo '</table>';
	}

	/* =====================================================================
	   Tab 6 — Logs
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_tab_logs() {
		// --- Login Log Settings ---
		$this->render_section_heading(
			__( 'Login Logging', 'authvault' ),
			__( 'Record login attempts in the database. No raw IP addresses are stored — only hashes.', 'authvault' )
		);

		echo '<table class="form-table authvault-form-table">';
		$this->render_checkbox_row( 'enable_login_log', __( 'Enable login logging', 'authvault' ), __( 'Log every login attempt (success and failure) to the database.', 'authvault' ) );
		$this->render_number_row( 'login_log_retention_days', __( 'Retention period (days)', 'authvault' ), 90, 1, 365, __( 'Entries older than this are automatically deleted via a daily cron job.', 'authvault' ), 'authvault-log-dependent' );
		echo '</table>';

		// --- Log Viewer ---
		$logging_enabled = (bool) authvault_get_option( 'enable_login_log', false );
		if ( ! $logging_enabled ) {
			echo '<div class="notice notice-info inline authvault-settings-notice"><p>';
			echo esc_html__( 'Enable login logging above to start recording login attempts. The log viewer will appear here once logging is active.', 'authvault' );
			echo '</p></div>';
			return;
		}

		$this->render_section_heading(
			__( 'Login Log', 'authvault' ),
			__( 'Recent login attempts recorded by the plugin.', 'authvault' )
		);

		$this->render_log_filters();
		$this->render_log_table();
	}

	/**
	 * Render filter controls above the log table.
	 *
	 * @return void
	 */
	private function render_log_filters() {
		$current_status = isset( $_GET['log_status'] ) ? sanitize_text_field( wp_unslash( $_GET['log_status'] ) ) : '';
		$current_search = isset( $_GET['log_search'] ) ? sanitize_text_field( wp_unslash( $_GET['log_search'] ) ) : '';
		$current_from   = isset( $_GET['log_from'] ) ? sanitize_text_field( wp_unslash( $_GET['log_from'] ) ) : '';
		$current_to     = isset( $_GET['log_to'] ) ? sanitize_text_field( wp_unslash( $_GET['log_to'] ) ) : '';

		$base_url = add_query_arg( array( 'page' => self::PAGE_SLUG ), admin_url( 'options-general.php' ) );

		echo '<div class="authvault-log-filters">';
		echo '<form method="get" action="' . esc_url( admin_url( 'options-general.php' ) ) . '" class="authvault-log-filter-form">';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::PAGE_SLUG ) . '" />';

		echo '<div class="authvault-log-filter-group">';
		echo '<label for="authvault-log-status">' . esc_html__( 'Status', 'authvault' ) . '</label>';
		echo '<select id="authvault-log-status" name="log_status">';
		echo '<option value="">' . esc_html__( 'All', 'authvault' ) . '</option>';
		echo '<option value="success"' . selected( $current_status, 'success', false ) . '>' . esc_html__( 'Success', 'authvault' ) . '</option>';
		echo '<option value="fail"' . selected( $current_status, 'fail', false ) . '>' . esc_html__( 'Fail', 'authvault' ) . '</option>';
		echo '</select>';
		echo '</div>';

		echo '<div class="authvault-log-filter-group">';
		echo '<label for="authvault-log-search">' . esc_html__( 'User', 'authvault' ) . '</label>';
		echo '<input type="text" id="authvault-log-search" name="log_search" value="' . esc_attr( $current_search ) . '" placeholder="' . esc_attr__( 'Username or email', 'authvault' ) . '" />';
		echo '</div>';

		echo '<div class="authvault-log-filter-group">';
		echo '<label for="authvault-log-from">' . esc_html__( 'From', 'authvault' ) . '</label>';
		echo '<input type="date" id="authvault-log-from" name="log_from" value="' . esc_attr( $current_from ) . '" />';
		echo '</div>';

		echo '<div class="authvault-log-filter-group">';
		echo '<label for="authvault-log-to">' . esc_html__( 'To', 'authvault' ) . '</label>';
		echo '<input type="date" id="authvault-log-to" name="log_to" value="' . esc_attr( $current_to ) . '" />';
		echo '</div>';

		echo '<div class="authvault-log-filter-actions">';
		echo '<button type="submit" class="button">' . esc_html__( 'Filter', 'authvault' ) . '</button>';

		$has_filters = '' !== $current_status || '' !== $current_search || '' !== $current_from || '' !== $current_to;
		if ( $has_filters ) {
			echo '<a href="' . esc_url( $base_url ) . '#logs" class="button">' . esc_html__( 'Clear', 'authvault' ) . '</a>';
		}

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'action'     => self::EXPORT_AJAX_ACTION,
					'log_status' => $current_status,
					'log_search' => $current_search,
					'log_from'   => $current_from,
					'log_to'     => $current_to,
				),
				admin_url( 'admin-ajax.php' )
			),
			self::EXPORT_AJAX_ACTION
		);
		echo '<a href="' . esc_url( $export_url ) . '" class="button">' . esc_html__( 'Export CSV', 'authvault' ) . '</a>';
		echo '</div>';

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Render the login log table with pagination.
	 *
	 * @return void
	 */
	private function render_log_table() {
		global $wpdb;

		$per_page = 20;
		$paged    = isset( $_GET['log_paged'] ) ? max( 1, absint( $_GET['log_paged'] ) ) : 1;
		$offset   = ( $paged - 1 ) * $per_page;

		$table = \AuthVault\AuthVault_Security::get_log_table_name();
		$where = array();
		$args  = array();

		$status_filter = isset( $_GET['log_status'] ) ? sanitize_text_field( wp_unslash( $_GET['log_status'] ) ) : '';
		if ( 'success' === $status_filter || 'fail' === $status_filter ) {
			$where[] = 'status = %s';
			$args[]  = $status_filter;
		}

		$search_filter = isset( $_GET['log_search'] ) ? sanitize_text_field( wp_unslash( $_GET['log_search'] ) ) : '';
		if ( '' !== $search_filter ) {
			$where[] = 'user_login LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $search_filter ) . '%';
		}

		$from_filter = isset( $_GET['log_from'] ) ? sanitize_text_field( wp_unslash( $_GET['log_from'] ) ) : '';
		if ( '' !== $from_filter && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_filter ) ) {
			$where[] = 'attempted_at >= %s';
			$args[]  = $from_filter . ' 00:00:00';
		}

		$to_filter = isset( $_GET['log_to'] ) ? sanitize_text_field( wp_unslash( $_GET['log_to'] ) ) : '';
		if ( '' !== $to_filter && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_filter ) ) {
			$where[] = 'attempted_at <= %s';
			$args[]  = $to_filter . ' 23:59:59';
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe from get_log_table_name().
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( ! empty( $args ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $args );
		}
		$total = (int) $wpdb->get_var( $count_sql );

		$query_args   = array_merge( $args, array( $per_page, $offset ) );
		$rows_sql     = "SELECT id, user_login, ip_hash, status, attempted_at FROM {$table} {$where_sql} ORDER BY attempted_at DESC LIMIT %d OFFSET %d";
		$rows         = $wpdb->get_results( $wpdb->prepare( $rows_sql, $query_args ) );
		// phpcs:enable

		$total_pages = (int) ceil( $total / $per_page );

		echo '<div class="authvault-log-viewer">';

		if ( empty( $rows ) ) {
			echo '<p class="description">' . esc_html__( 'No log entries found.', 'authvault' ) . '</p>';
			echo '</div>';
			return;
		}

		echo '<p class="description">';
		printf(
			/* translators: 1: total entries, 2: current page, 3: total pages */
			esc_html__( 'Showing %1$s entries (page %2$d of %3$d)', 'authvault' ),
			'<strong>' . esc_html( number_format_i18n( $total ) ) . '</strong>',
			$paged,
			max( 1, $total_pages )
		);
		echo '</p>';

		echo '<table class="widefat fixed striped authvault-log-table">';
		echo '<thead><tr>';
		echo '<th>' . esc_html__( 'Date / Time', 'authvault' ) . '</th>';
		echo '<th>' . esc_html__( 'User Login', 'authvault' ) . '</th>';
		echo '<th>' . esc_html__( 'Status', 'authvault' ) . '</th>';
		echo '<th>' . esc_html__( 'IP Hash', 'authvault' ) . '</th>';
		echo '</tr></thead>';
		echo '<tbody>';

		foreach ( $rows as $row ) {
			echo '<tr>';

			echo '<td>' . esc_html( $row->attempted_at ) . '</td>';

			$user_obj = get_user_by( 'login', $row->user_login );
			if ( $user_obj && 'success' === $row->status ) {
				echo '<td><a href="' . esc_url( get_edit_user_link( $user_obj->ID ) ) . '">' . esc_html( $row->user_login ) . '</a></td>';
			} else {
				echo '<td>' . esc_html( $row->user_login ) . '</td>';
			}

			$status_label = 'success' === $row->status
				? '<span class="authvault-log-status-success">' . esc_html__( 'Success', 'authvault' ) . '</span>'
				: '<span class="authvault-log-status-fail">' . esc_html__( 'Fail', 'authvault' ) . '</span>';
			echo '<td>' . $status_label . '</td>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.

			$ip_short = '' !== $row->ip_hash ? substr( $row->ip_hash, -8 ) : '—';
			echo '<td><code>' . esc_html( $ip_short ) . '</code></td>';

			echo '</tr>';
		}

		echo '</tbody></table>';

		if ( $total_pages > 1 ) {
			$this->render_log_pagination( $paged, $total_pages );
		}

		echo '</div>';
	}

	/**
	 * Render pagination links for the log table.
	 *
	 * @param int $current Current page.
	 * @param int $total   Total pages.
	 * @return void
	 */
	private function render_log_pagination( $current, $total ) {
		$base_url = add_query_arg(
			array(
				'page'       => self::PAGE_SLUG,
				'log_status' => isset( $_GET['log_status'] ) ? sanitize_text_field( wp_unslash( $_GET['log_status'] ) ) : '',
				'log_search' => isset( $_GET['log_search'] ) ? sanitize_text_field( wp_unslash( $_GET['log_search'] ) ) : '',
				'log_from'   => isset( $_GET['log_from'] ) ? sanitize_text_field( wp_unslash( $_GET['log_from'] ) ) : '',
				'log_to'     => isset( $_GET['log_to'] ) ? sanitize_text_field( wp_unslash( $_GET['log_to'] ) ) : '',
			),
			admin_url( 'options-general.php' )
		);

		echo '<div class="authvault-log-pagination">';

		if ( $current > 1 ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( 'log_paged', $current - 1, $base_url ) . '#logs' ) . '">&laquo; ' . esc_html__( 'Previous', 'authvault' ) . '</a>';
		}

		for ( $i = 1; $i <= $total; $i++ ) {
			if ( $i === $current ) {
				echo '<span class="button button-primary disabled">' . esc_html( (string) $i ) . '</span>';
			} elseif ( $i <= 2 || $i > $total - 2 || abs( $i - $current ) <= 2 ) {
				echo '<a class="button" href="' . esc_url( add_query_arg( 'log_paged', $i, $base_url ) . '#logs' ) . '">' . esc_html( (string) $i ) . '</a>';
			} elseif ( $i === 3 && $current > 5 ) {
				echo '<span class="button disabled">&hellip;</span>';
			} elseif ( $i === $total - 2 && $current < $total - 4 ) {
				echo '<span class="button disabled">&hellip;</span>';
			}
		}

		if ( $current < $total ) {
			echo '<a class="button" href="' . esc_url( add_query_arg( 'log_paged', $current + 1, $base_url ) . '#logs' ) . '">' . esc_html__( 'Next', 'authvault' ) . ' &raquo;</a>';
		}

		echo '</div>';
	}

	/**
	 * Handle AJAX CSV export of login log entries.
	 *
	 * @return void
	 */
	public function handle_export_login_log() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized', 'authvault' ), 403 );
		}
		check_ajax_referer( self::EXPORT_AJAX_ACTION );

		global $wpdb;
		$table = \AuthVault\AuthVault_Security::get_log_table_name();
		$where = array();
		$args  = array();

		$status_filter = isset( $_GET['log_status'] ) ? sanitize_text_field( wp_unslash( $_GET['log_status'] ) ) : '';
		if ( 'success' === $status_filter || 'fail' === $status_filter ) {
			$where[] = 'status = %s';
			$args[]  = $status_filter;
		}

		$search_filter = isset( $_GET['log_search'] ) ? sanitize_text_field( wp_unslash( $_GET['log_search'] ) ) : '';
		if ( '' !== $search_filter ) {
			$where[] = 'user_login LIKE %s';
			$args[]  = '%' . $wpdb->esc_like( $search_filter ) . '%';
		}

		$from_filter = isset( $_GET['log_from'] ) ? sanitize_text_field( wp_unslash( $_GET['log_from'] ) ) : '';
		if ( '' !== $from_filter && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $from_filter ) ) {
			$where[] = 'attempted_at >= %s';
			$args[]  = $from_filter . ' 00:00:00';
		}

		$to_filter = isset( $_GET['log_to'] ) ? sanitize_text_field( wp_unslash( $_GET['log_to'] ) ) : '';
		if ( '' !== $to_filter && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $to_filter ) ) {
			$where[] = 'attempted_at <= %s';
			$args[]  = $to_filter . ' 23:59:59';
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		$max_rows = 10000;
		$args[]   = $max_rows;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT user_login, status, attempted_at, ip_hash FROM {$table} {$where_sql} ORDER BY attempted_at DESC LIMIT %d",
			$args
		) );
		// phpcs:enable

		$filename = 'authvault-login-log-' . gmdate( 'Y-m-d' ) . '.csv';
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=' . $filename );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' );
		fputcsv( $output, array( 'Date/Time', 'User Login', 'Status', 'IP Hash (last 8)' ) );
		foreach ( $rows as $row ) {
			fputcsv( $output, array(
				$row->attempted_at,
				$row->user_login,
				$row->status,
				'' !== $row->ip_hash ? substr( $row->ip_hash, -8 ) : '',
			) );
		}
		fclose( $output );
		exit;
	}

	/* =====================================================================
	   Admin notices
	   ===================================================================== */

	/**
	 * @return void
	 */
	private function render_admin_notices() {
		if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) {
			add_settings_error(
				self::OPTION_GROUP,
				'settings_updated',
				__( 'Settings saved.', 'authvault' ),
				'success'
			);
		}

		$reset_param = isset( $_GET['authvault_reset'] ) ? sanitize_text_field( wp_unslash( $_GET['authvault_reset'] ) ) : '';
		if ( 'success' === $reset_param ) {
			add_settings_error( self::OPTION_GROUP, 'reset_success', __( 'Settings reset to defaults.', 'authvault' ), 'success' );
		}
		if ( 'nonce_fail' === $reset_param ) {
			add_settings_error( self::OPTION_GROUP, 'reset_nonce', __( 'Reset failed: security check failed. Please try again.', 'authvault' ), 'error' );
		}

		settings_errors( self::OPTION_GROUP );
	}

	/* =====================================================================
	   Rendering helpers — section headings
	   ===================================================================== */

	/**
	 * @param string $title       Section title.
	 * @param string $description Section description.
	 * @return void
	 */
	private function render_section_heading( $title, $description = '' ) {
		echo '<div class="authvault-section-heading">';
		echo '<h2>' . esc_html( $title ) . '</h2>';
		if ( '' !== $description ) {
			echo '<p>' . esc_html( $description ) . '</p>';
		}
		echo '</div>';
	}

	/* =====================================================================
	   Rendering helpers — table rows
	   ===================================================================== */

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class for the <tr>.
	 * @return void
	 */
	private function render_page_row( $key, $label, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = (int) authvault_get_option( $key, 0 );
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		$status_class = ( 0 < $value ) ? 'authvault-status-ok' : 'authvault-status-missing';

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<div class="authvault-field-with-status">';
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '">';
		echo '<option value="0">' . esc_html__( '— Select —', 'authvault' ) . '</option>';
		foreach ( $pages as $page ) {
			$selected = ( $value === (int) $page->ID ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( (string) $page->ID ) . '"' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
		}
		echo '</select>';
		echo '<span class="authvault-status-dot ' . esc_attr( $status_class ) . '"></span>';
		echo '</div>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_checkbox_row( $key, $label, $description = '', $row_class = '' ) {
		$id      = 'authvault_' . $key;
		$value   = authvault_get_option( $key, false );
		$checked = $value ? ' checked="checked"' : '';

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<label class="authvault-toggle">';
		echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="1"' . $checked . ' />';
		echo '<span class="authvault-toggle-slider"></span>';
		echo '</label>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param int    $default     Default value.
	 * @param int    $min         Minimum.
	 * @param int    $max         Maximum.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_number_row( $key, $label, $default = 0, $min = 0, $max = 999999, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = absint( authvault_get_option( $key, $default ) );
		$value = max( $min, min( $max, $value ) );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" class="small-text" />';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $default     Default value.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_text_row( $key, $label, $default = '', $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, $default );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text" />';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_email_row( $key, $label, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, '' );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<input type="email" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text" />';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_password_row( $key, $label, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, '' );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<input type="password" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text" autocomplete="off" />';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Render a score/range input row (0.0 – 1.0 step 0.1).
	 *
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param float  $default     Default value.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_score_row( $key, $label, $default = 0.5, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = (float) authvault_get_option( $key, $default );
		$value = max( 0.0, min( 1.0, $value ) );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( number_format( $value, 1 ) ) . '" min="0" max="1" step="0.1" class="small-text" />';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string              $key         Option key.
	 * @param string              $label       Field label.
	 * @param array<string,string> $options    value => display text.
	 * @param string              $default     Default value.
	 * @param string              $description Help text.
	 * @param string              $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_select_row( $key, $label, $options, $default = '', $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, $default );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '">';
		foreach ( $options as $opt_value => $opt_label ) {
			$selected = ( (string) $value === (string) $opt_value ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( $opt_value ) . '"' . $selected . '>' . esc_html( $opt_label ) . '</option>';
		}
		echo '</select>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_role_row( $key, $label, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, 'subscriber' );
		$roles = get_editable_roles();

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '">';
		foreach ( $roles as $role_key => $role ) {
			$selected = ( $value === $role_key ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( $role_key ) . '"' . $selected . '>' . esc_html( translate_user_role( $role['name'] ) ) . '</option>';
		}
		echo '</select>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * @param string $key         Option key.
	 * @param string $label       Field label.
	 * @param string $description Help text.
	 * @param string $row_class   Extra CSS class.
	 * @return void
	 */
	private function render_textarea_row( $key, $label, $description = '', $row_class = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, '' );

		echo '<tr' . ( '' !== $row_class ? ' class="' . esc_attr( $row_class ) . '"' : '' ) . '>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<textarea id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" rows="6" class="large-text">' . esc_textarea( (string) $value ) . '</textarea>';
		if ( '' !== $description ) {
			echo '<p class="description">' . esc_html( $description ) . '</p>';
		}
		echo '</td></tr>';
	}

	/**
	 * Message row with placeholder showing the default text.
	 *
	 * @param string $key             Option key.
	 * @param string $label           Field label.
	 * @param string $placeholder     Default message shown as placeholder.
	 * @param string $extra_desc      Additional description below the field.
	 * @return void
	 */
	private function render_message_row( $key, $label, $placeholder = '', $extra_desc = '' ) {
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, '' );

		echo '<tr>';
		echo '<th scope="row"><label for="' . esc_attr( $id ) . '">' . esc_html( $label ) . '</label></th>';
		echo '<td>';
		echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" placeholder="' . esc_attr( $placeholder ) . '" class="regular-text" />';
		if ( '' !== $extra_desc ) {
			echo '<p class="description">' . esc_html( $extra_desc ) . '</p>';
		}
		echo '</td></tr>';
	}

	/* =====================================================================
	   Static helpers
	   ===================================================================== */

	/**
	 * @return string
	 */
	public static function get_page_hook() {
		return 'settings_page_' . self::PAGE_SLUG;
	}
}
