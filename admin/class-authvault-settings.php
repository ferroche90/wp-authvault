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
 */
class AuthVault_Settings {

	/**
	 * Option group for register_setting.
	 *
	 * @var string
	 */
	const OPTION_GROUP = 'authvault_settings_group';

	/**
	 * Option name (single array stored in wp_options).
	 *
	 * @var string
	 */
	const OPTION_NAME = 'authvault_settings';

	/**
	 * Settings page slug.
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'authvault-settings';

	/**
	 * Nonce action for main form save.
	 *
	 * @var string
	 */
	const NONCE_ACTION = 'authvault_settings_save';

	/**
	 * Nonce action for reset to defaults.
	 *
	 * @var string
	 */
	const RESET_NONCE_ACTION = 'authvault_settings_reset';

	/**
	 * Add menu and register settings.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_settings_page' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'register_settings' ), 10, 0 );
		add_action( 'admin_init', array( $this, 'handle_reset' ), 5, 0 );
	}

	/**
	 * Add the settings page under Settings.
	 *
	 * @return void
	 */
	public function add_settings_page() {
		add_options_page(
			__( 'AuthVault', 'authvault' ),
			__( 'AuthVault', 'authvault' ),
			'manage_options',
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register setting, sections, and fields.
	 *
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

		// Section 1 — Page Assignments.
		add_settings_section(
			'authvault_page_assignments',
			__( 'Page Assignments', 'authvault' ),
			array( $this, 'section_page_assignments_callback' ),
			self::PAGE_SLUG
		);

		$this->add_page_field( 'login_page_id', __( 'Login Page', 'authvault' ) );
		$this->add_page_field( 'register_page_id', __( 'Register Page', 'authvault' ) );
		$this->add_page_field( 'password_reset_page_id', __( 'Password Reset Page', 'authvault' ) );
		$this->add_page_field( 'password_reset_confirm_page_id', __( 'Password Reset Confirm Page', 'authvault' ) );
		$this->add_page_field( 'login_redirect_page_id', __( 'Login Redirect Page', 'authvault' ) );
		$this->add_page_field( 'logout_redirect_page_id', __( 'Logout Redirect Page', 'authvault' ) );

		// Section 2 — URL Security.
		add_settings_section(
			'authvault_url_security',
			__( 'URL Security', 'authvault' ),
			array( $this, 'section_url_security_callback' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'custom_login_slug',
			__( 'Custom Login URL slug', 'authvault' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'authvault_url_security',
			array(
				'label_for' => 'authvault_custom_login_slug',
				'key'       => 'custom_login_slug',
				'default'   => 'login',
			)
		);

		add_settings_field(
			'enable_login_url_hiding',
			__( 'Enable login URL hiding', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_url_security',
			array(
				'label_for' => 'authvault_enable_login_url_hiding',
				'key'       => 'enable_login_url_hiding',
			)
		);

		add_settings_field(
			'wp_login_access_behavior',
			__( 'Behavior when wp-login.php is accessed directly', 'authvault' ),
			array( $this, 'render_wp_login_behavior' ),
			self::PAGE_SLUG,
			'authvault_url_security',
			array(
				'label_for' => 'authvault_wp_login_access_behavior',
				'key'       => 'wp_login_access_behavior',
			)
		);

		add_settings_field(
			'wp_login_redirect_page_id',
			__( 'Custom redirect page for blocked wp-login.php access', 'authvault' ),
			array( $this, 'render_page_field' ),
			self::PAGE_SLUG,
			'authvault_url_security',
			array(
				'label_for' => 'authvault_wp_login_redirect_page_id',
				'key'       => 'wp_login_redirect_page_id',
			)
		);

		// Section 3 — Registration.
		add_settings_section(
			'authvault_registration',
			__( 'Registration', 'authvault' ),
			array( $this, 'section_registration_callback' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'enable_user_registration',
			__( 'Enable user registration', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_registration',
			array(
				'label_for' => 'authvault_enable_user_registration',
				'key'       => 'enable_user_registration',
			)
		);

		add_settings_field(
			'default_role',
			__( 'Default role', 'authvault' ),
			array( $this, 'render_default_role' ),
			self::PAGE_SLUG,
			'authvault_registration',
			array(
				'label_for' => 'authvault_default_role',
				'key'       => 'default_role',
			)
		);

		// Section 4 — Security.
		add_settings_section(
			'authvault_security',
			__( 'Security', 'authvault' ),
			array( $this, 'section_security_callback' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'max_login_attempts',
			__( 'Max login attempts before lockout', 'authvault' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_max_login_attempts',
				'key'       => 'max_login_attempts',
				'default'   => 5,
				'min'       => 1,
				'max'       => 100,
			)
		);

		add_settings_field(
			'lockout_duration_minutes',
			__( 'Lockout duration in minutes', 'authvault' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_lockout_duration_minutes',
				'key'       => 'lockout_duration_minutes',
				'default'   => 15,
				'min'       => 1,
				'max'       => 1440,
			)
		);

		add_settings_field(
			'enable_lockout',
			__( 'Enable lockout', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_enable_lockout',
				'key'       => 'enable_lockout',
			)
		);

		add_settings_field(
			'min_password_length',
			__( 'Minimum password length', 'authvault' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_min_password_length',
				'key'       => 'min_password_length',
				'default'   => 8,
				'min'       => 1,
				'max'       => 128,
			)
		);

		add_settings_field(
			'allow_weak_passwords',
			__( 'Allow weak passwords', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_allow_weak_passwords',
				'key'       => 'allow_weak_passwords',
				'description' => __( 'If unchecked, the set-new-password form only accepts medium or strong passwords (weak and very weak are blocked).', 'authvault' ),
			)
		);

		add_settings_field(
			'reset_rate_limit_max',
			__( 'Max password reset requests per IP', 'authvault' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_reset_rate_limit_max',
				'key'       => 'reset_rate_limit_max',
				'default'   => 5,
				'min'       => 1,
				'max'       => 100,
			)
		);

		add_settings_field(
			'reset_rate_limit_window_minutes',
			__( 'Password reset rate limit window (minutes)', 'authvault' ),
			array( $this, 'render_number_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_reset_rate_limit_window_minutes',
				'key'       => 'reset_rate_limit_window_minutes',
				'default'   => 15,
				'min'       => 1,
				'max'       => 1440,
			)
		);

		add_settings_field(
			'enable_login_log',
			__( 'Log login attempts', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_enable_login_log',
				'key'       => 'enable_login_log',
			)
		);

		add_settings_field(
			'recaptcha_enabled',
			__( 'Google reCAPTCHA v3', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_recaptcha_enabled',
				'key'       => 'recaptcha_enabled',
			)
		);

		add_settings_field(
			'recaptcha_site_key',
			__( 'reCAPTCHA Site Key', 'authvault' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_recaptcha_site_key',
				'key'       => 'recaptcha_site_key',
			)
		);

		add_settings_field(
			'recaptcha_secret_key',
			__( 'reCAPTCHA Secret Key', 'authvault' ),
			array( $this, 'render_password_field' ),
			self::PAGE_SLUG,
			'authvault_security',
			array(
				'label_for' => 'authvault_recaptcha_secret_key',
				'key'       => 'recaptcha_secret_key',
			)
		);

		// Section 5 — Email.
		add_settings_section(
			'authvault_email',
			__( 'Email', 'authvault' ),
			array( $this, 'section_email_callback' ),
			self::PAGE_SLUG
		);

		add_settings_field(
			'override_lost_password_email',
			__( 'Override WordPress default "lost password" email', 'authvault' ),
			array( $this, 'render_checkbox' ),
			self::PAGE_SLUG,
			'authvault_email',
			array(
				'label_for' => 'authvault_override_lost_password_email',
				'key'       => 'override_lost_password_email',
			)
		);

		add_settings_field(
			'email_from_name',
			__( '"From" name', 'authvault' ),
			array( $this, 'render_text_field' ),
			self::PAGE_SLUG,
			'authvault_email',
			array(
				'label_for' => 'authvault_email_from_name',
				'key'       => 'email_from_name',
			)
		);

		add_settings_field(
			'email_from_email',
			__( '"From" email', 'authvault' ),
			array( $this, 'render_email_field' ),
			self::PAGE_SLUG,
			'authvault_email',
			array(
				'label_for' => 'authvault_email_from_email',
				'key'       => 'email_from_email',
			)
		);

		// Section 6 — Messages.
		add_settings_section(
			'authvault_messages',
			__( 'Messages', 'authvault' ),
			array( $this, 'section_messages_callback' ),
			self::PAGE_SLUG
		);

		$message_fields = array(
			'msg_login_error'               => __( 'Login error', 'authvault' ),
			'msg_login_lockout'             => __( 'Login lockout (%d = minutes)', 'authvault' ),
			'msg_login_registered'          => __( 'Registration success (shown on login page)', 'authvault' ),
			'msg_login_password_reset'      => __( 'Password reset success (shown on login page)', 'authvault' ),
			'msg_register_error'            => __( 'Registration error', 'authvault' ),
			'msg_reset_sent'                => __( 'Password reset email sent', 'authvault' ),
			'msg_reset_invalid_key'         => __( 'Invalid / expired reset link', 'authvault' ),
			'msg_confirm_invalid_link'      => __( 'Confirm page: invalid link', 'authvault' ),
			'msg_confirm_password_empty'    => __( 'Confirm page: empty password', 'authvault' ),
			'msg_confirm_password_mismatch' => __( 'Confirm page: passwords do not match', 'authvault' ),
			'msg_confirm_password_weak'     => __( 'Confirm page: password too short (%d = length)', 'authvault' ),
			'msg_confirm_password_too_weak'   => __( 'Confirm page: password too weak (require medium or strong)', 'authvault' ),
		);

		foreach ( $message_fields as $key => $label ) {
			add_settings_field(
				$key,
				$label,
				array( $this, 'render_text_field' ),
				self::PAGE_SLUG,
				'authvault_messages',
				array(
					'label_for' => 'authvault_' . $key,
					'key'       => $key,
					'default'   => '',
				)
			);
		}
	}

	/**
	 * Add a page selector field (used for multiple keys).
	 *
	 * @param string $key    Option key.
	 * @param string $label Field label.
	 * @return void
	 */
	private function add_page_field( $key, $label ) {
		add_settings_field(
			$key,
			$label,
			array( $this, 'render_page_field' ),
			self::PAGE_SLUG,
			'authvault_page_assignments',
			array(
				'label_for' => 'authvault_' . $key,
				'key'       => $key,
			)
		);
	}

	/**
	 * Sanitize and validate the settings array on save.
	 *
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
		$output  = array_merge( $defaults, $input );

		// Page IDs (absint).
		$page_keys = array(
			'login_page_id',
			'register_page_id',
			'password_reset_page_id',
			'password_reset_confirm_page_id',
			'login_redirect_page_id',
			'logout_redirect_page_id',
			'wp_login_redirect_page_id',
		);
		foreach ( $page_keys as $k ) {
			$output[ $k ] = isset( $input[ $k ] ) ? absint( $input[ $k ] ) : $defaults[ $k ];
		}

		// URL Security.
		$output['custom_login_slug']        = isset( $input['custom_login_slug'] ) ? sanitize_title( $input['custom_login_slug'] ) : $defaults['custom_login_slug'];
		$output['enable_login_url_hiding']  = isset( $input['enable_login_url_hiding'] );
		$output['wp_login_access_behavior']  = isset( $input['wp_login_access_behavior'] ) && in_array( $input['wp_login_access_behavior'], array( '404', 'home', 'page' ), true )
			? $input['wp_login_access_behavior']
			: $defaults['wp_login_access_behavior'];
		$output['wp_login_redirect_page_id'] = isset( $input['wp_login_redirect_page_id'] ) ? absint( $input['wp_login_redirect_page_id'] ) : $defaults['wp_login_redirect_page_id'];

		// Registration.
		$output['enable_user_registration'] = isset( $input['enable_user_registration'] );
		$editable_roles                    = array_keys( get_editable_roles() );
		$output['default_role']            = isset( $input['default_role'] ) && in_array( $input['default_role'], $editable_roles, true )
			? $input['default_role']
			: $defaults['default_role'];

		// Sync WP option for registration.
		update_option( 'users_can_register', $output['enable_user_registration'] ? '1' : '0' );

		// Security.
		$output['max_login_attempts']       = isset( $input['max_login_attempts'] ) ? absint( $input['max_login_attempts'] ) : $defaults['max_login_attempts'];
		$output['max_login_attempts']       = max( 1, min( 100, $output['max_login_attempts'] ) );
		$output['lockout_duration_minutes'] = isset( $input['lockout_duration_minutes'] ) ? absint( $input['lockout_duration_minutes'] ) : $defaults['lockout_duration_minutes'];
		$output['lockout_duration_minutes'] = max( 1, min( 1440, $output['lockout_duration_minutes'] ) );
		$output['enable_lockout']          = isset( $input['enable_lockout'] );
		$output['min_password_length']     = isset( $input['min_password_length'] ) ? absint( $input['min_password_length'] ) : $defaults['min_password_length'];
		$output['min_password_length']     = max( 1, min( 128, $output['min_password_length'] ) );
		$output['allow_weak_passwords']    = isset( $input['allow_weak_passwords'] );
		$output['reset_rate_limit_max']    = isset( $input['reset_rate_limit_max'] ) ? absint( $input['reset_rate_limit_max'] ) : $defaults['reset_rate_limit_max'];
		$output['reset_rate_limit_max']    = max( 1, min( 100, $output['reset_rate_limit_max'] ) );
		$output['reset_rate_limit_window_minutes'] = isset( $input['reset_rate_limit_window_minutes'] ) ? absint( $input['reset_rate_limit_window_minutes'] ) : $defaults['reset_rate_limit_window_minutes'];
		$output['reset_rate_limit_window_minutes'] = max( 1, min( 1440, $output['reset_rate_limit_window_minutes'] ) );
		$output['enable_login_log']        = isset( $input['enable_login_log'] );
		$output['recaptcha_enabled']        = isset( $input['recaptcha_enabled'] );
		$output['recaptcha_site_key']      = isset( $input['recaptcha_site_key'] ) ? sanitize_text_field( $input['recaptcha_site_key'] ) : $defaults['recaptcha_site_key'];
		$output['recaptcha_secret_key']    = isset( $input['recaptcha_secret_key'] ) ? sanitize_text_field( $input['recaptcha_secret_key'] ) : $defaults['recaptcha_secret_key'];

		// Email.
		$output['override_lost_password_email'] = isset( $input['override_lost_password_email'] );
		$output['email_from_name']              = isset( $input['email_from_name'] ) ? sanitize_text_field( $input['email_from_name'] ) : $defaults['email_from_name'];
		$output['email_from_email']             = isset( $input['email_from_email'] ) ? sanitize_email( $input['email_from_email'] ) : $defaults['email_from_email'];

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

	/**
	 * Handle reset to defaults (own nonce, then redirect).
	 *
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
	 * Get the URL of the settings page.
	 *
	 * @return string
	 */
	private function get_settings_url() {
		return admin_url( 'options-general.php?page=' . self::PAGE_SLUG );
	}

	/**
	 * Section callback: Page Assignments.
	 *
	 * @return void
	 */
	public function section_page_assignments_callback() {
		echo '<p>' . esc_html__( 'Assign WordPress pages to AuthVault login, registration, and password reset flows.', 'authvault' ) . '</p>';
	}

	/**
	 * Section callback: URL Security.
	 *
	 * @return void
	 */
	public function section_url_security_callback() {
		echo '<p>' . esc_html__( 'Customize the login URL and what happens when wp-login.php is accessed directly.', 'authvault' ) . '</p>';
	}

	/**
	 * Section callback: Registration.
	 *
	 * @return void
	 */
	public function section_registration_callback() {
		echo '<p>' . esc_html__( 'Control user registration and default role.', 'authvault' ) . '</p>';
	}

	/**
	 * Section callback: Security.
	 *
	 * @return void
	 */
	public function section_security_callback() {
		echo '<p>' . esc_html__( 'Login lockout and reCAPTCHA v3 settings.', 'authvault' ) . '</p>';
	}

	/**
	 * Section callback: Email.
	 *
	 * @return void
	 */
	public function section_email_callback() {
		echo '<p>' . esc_html__( 'Override the default "lost password" email sender.', 'authvault' ) . '</p>';
	}

	/**
	 * Messages section description.
	 *
	 * @return void
	 */
	public function section_messages_callback() {
		echo '<p>' . esc_html__( 'Customize user-facing messages. Leave blank to use the default text.', 'authvault' ) . '</p>';
	}

	/**
	 * Render page dropdown.
	 *
	 * @param array<string, mixed> $args Field args with 'key'.
	 * @return void
	 */
	public function render_page_field( array $args ) {
		$key   = $args['key'];
		$id    = 'authvault_' . $key;
		$value = authvault_get_option( $key, 0 );
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '">';
		echo '<option value="0">' . esc_html__( '— Select —', 'authvault' ) . '</option>';
		foreach ( $pages as $page ) {
			$selected = ( (int) $value === (int) $page->ID ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( (string) $page->ID ) . '"' . $selected . '>' . esc_html( $page->post_title ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render text input.
	 *
	 * @param array<string, mixed> $args Field args with 'key', optional 'default'.
	 * @return void
	 */
	public function render_text_field( array $args ) {
		$key     = $args['key'];
		$id      = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$default = isset( $args['default'] ) ? $args['default'] : '';
		$value   = authvault_get_option( $key, $default );
		echo '<input type="text" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text" />';
	}

	/**
	 * Render number input.
	 *
	 * @param array<string, mixed> $args Field args with 'key', 'default', 'min', 'max'.
	 * @return void
	 */
	public function render_number_field( array $args ) {
		$key     = $args['key'];
		$id      = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$default = isset( $args['default'] ) ? $args['default'] : 0;
		$min     = isset( $args['min'] ) ? (int) $args['min'] : 0;
		$max     = isset( $args['max'] ) ? (int) $args['max'] : 999999;
		$value   = authvault_get_option( $key, $default );
		$value   = absint( $value );
		$value   = max( $min, min( $max, $value ) );
		echo '<input type="number" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" min="' . esc_attr( (string) $min ) . '" max="' . esc_attr( (string) $max ) . '" class="small-text" />';
	}

	/**
	 * Render checkbox.
	 *
	 * @param array<string, mixed> $args Field args with 'key'.
	 * @return void
	 */
	public function render_checkbox( array $args ) {
		$key    = $args['key'];
		$id     = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$value  = authvault_get_option( $key, false );
		$checked = $value ? ' checked="checked"' : '';
		echo '<input type="checkbox" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="1"' . $checked . ' />';
		if ( ! empty( $args['description'] ) ) {
			echo '<p class="description">' . esc_html( $args['description'] ) . '</p>';
		}
	}

	/**
	 * Render email input.
	 *
	 * @param array<string, mixed> $args Field args with 'key'.
	 * @return void
	 */
	public function render_email_field( array $args ) {
		$key   = $args['key'];
		$id    = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$value = authvault_get_option( $key, '' );
		echo '<input type="email" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text" />';
	}

	/**
	 * Render password input (for secret key).
	 *
	 * @param array<string, mixed> $args Field args with 'key'.
	 * @return void
	 */
	public function render_password_field( array $args ) {
		$key   = $args['key'];
		$id    = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$value = authvault_get_option( $key, '' );
		echo '<input type="password" id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '" value="' . esc_attr( (string) $value ) . '" class="regular-text" autocomplete="off" />';
	}

	/**
	 * Render wp-login.php behavior dropdown.
	 *
	 * @param array<string, mixed> $args Field args with 'key'.
	 * @return void
	 */
	public function render_wp_login_behavior( array $args ) {
		$key    = $args['key'];
		$id     = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$value  = authvault_get_option( $key, '404' );
		$opts   = array(
			'404'  => __( 'Show 404', 'authvault' ),
			'home' => __( 'Redirect to homepage', 'authvault' ),
			'page' => __( 'Redirect to custom page', 'authvault' ),
		);
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '">';
		foreach ( $opts as $opt_value => $label ) {
			$selected = ( $value === $opt_value ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( $opt_value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render default role dropdown.
	 *
	 * @param array<string, mixed> $args Field args with 'key'.
	 * @return void
	 */
	public function render_default_role( array $args ) {
		$key   = $args['key'];
		$id    = isset( $args['label_for'] ) ? $args['label_for'] : 'authvault_' . $key;
		$value = authvault_get_option( $key, 'subscriber' );
		$roles = get_editable_roles();
		echo '<select id="' . esc_attr( $id ) . '" name="' . esc_attr( self::OPTION_NAME . '[' . $key . ']' ) . '">';
		foreach ( $roles as $role_key => $role ) {
			$selected = ( $value === $role_key ) ? ' selected="selected"' : '';
			echo '<option value="' . esc_attr( $role_key ) . '"' . $selected . '>' . esc_html( translate_user_role( $role['name'] ) ) . '</option>';
		}
		echo '</select>';
	}

	/**
	 * Render the settings page (with capability check and notices).
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'authvault' ) );
		}

		$this->render_admin_notices();

		echo '<div class="wrap authvault-settings-wrap">';
		echo '<h1 class="authvault-settings-title">' . esc_html__( 'AuthVault Settings', 'authvault' ) . '</h1>';

		echo '<form method="post" action="options.php" id="authvault-settings-form">';
		settings_fields( self::OPTION_GROUP );
		do_settings_sections( self::PAGE_SLUG );
		submit_button( __( 'Save Settings', 'authvault' ) );
		echo '</form>';

		echo '<form method="post" action="" id="authvault-reset-form" class="authvault-reset-form">';
		wp_nonce_field( self::RESET_NONCE_ACTION, self::RESET_NONCE_ACTION );
		echo '<p class="submit">';
		echo '<button type="submit" name="authvault_reset_submit" class="button button-secondary" onclick="return confirm(\'' . esc_js( __( 'Reset all settings to defaults?', 'authvault' ) ) . '\');">' . esc_html__( 'Reset to defaults', 'authvault' ) . '</button>';
		echo '</p>';
		echo '</form>';

		echo '</div>';
	}

	/**
	 * Output success/error admin notices for save and reset.
	 *
	 * @return void
	 */
	private function render_admin_notices() {
		// Settings API success is shown by settings_errors() when options.php redirects back with settings-updated.
		if ( isset( $_GET['settings-updated'] ) && 'true' === sanitize_text_field( wp_unslash( $_GET['settings-updated'] ) ) ) {
			add_settings_error(
				self::OPTION_GROUP,
				'settings_updated',
				__( 'Settings saved.', 'authvault' ),
				'success'
			);
		}

		$reset_param = isset( $_GET['authvault_reset'] ) ? sanitize_text_field( wp_unslash( $_GET['authvault_reset'] ) ) : '';
		if ( '' !== $reset_param ) {
			if ( 'success' === $reset_param ) {
				add_settings_error(
					self::OPTION_GROUP,
					'reset_success',
					__( 'Settings reset to defaults.', 'authvault' ),
					'success'
				);
			}
			if ( 'nonce_fail' === $reset_param ) {
				add_settings_error(
					self::OPTION_GROUP,
					'reset_nonce',
					__( 'Reset failed: security check failed. Please try again.', 'authvault' ),
					'error'
				);
			}
		}

		settings_errors( self::OPTION_GROUP );
	}

	/**
	 * Get the settings page hook suffix (screen ID) for conditional enqueue.
	 *
	 * @return string
	 */
	public static function get_page_hook() {
		return 'settings_page_' . self::PAGE_SLUG;
	}
}
