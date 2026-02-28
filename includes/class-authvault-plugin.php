<?php
/**
 * The core plugin class.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The core plugin class.
 *
 * Used for defining hooks and loading the plugin components.
 */
class AuthVault_Plugin {

	/**
	 * The loader that's responsible for maintaining and registering all hooks.
	 *
	 * @var AuthVault_Loader
	 */
	protected $loader;

	/**
	 * The unique identifier of the plugin.
	 *
	 * @var string
	 */
	protected $plugin_name = 'authvault';

	/**
	 * The current version of the plugin.
	 *
	 * @var string
	 */
	protected $version = '';

	/**
	 * Define the core behavior of the plugin.
	 */
	public function __construct() {
		$this->version = AUTHVAULT_VERSION;
		$this->load_dependencies();
		$this->set_locale();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_router_hooks();
		$this->define_security();
		$this->define_auth_hooks();
		$this->define_email_hooks();
		$this->define_elementor_integration();
	}

	/**
	 * Load the required dependencies for the plugin.
	 *
	 * @return void
	 */
	private function load_dependencies() {
		$this->loader = new AuthVault_Loader();
	}

	/**
	 * Define the locale for internationalization.
	 *
	 * @return void
	 */
	private function set_locale() {
		$plugin_i18n = new AuthVault_I18n();
		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'load_plugin_textdomain', 10, 0 );
	}

	/**
	 * Register all of the admin-related hooks.
	 *
	 * @return void
	 */
	private function define_admin_hooks() {
		$plugin_admin = new \AuthVault\Admin\AuthVault_Admin( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', 10, 1 );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', 10, 1 );
		new \AuthVault\Admin\AuthVault_Settings();
	}

	/**
	 * Register all of the public-facing hooks.
	 *
	 * @return void
	 */
	private function define_public_hooks() {
		$plugin_public = new \AuthVault\Public_Area\AuthVault_Public( $this->get_plugin_name(), $this->get_version() );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles', 10, 0 );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts', 10, 0 );
	}

	/**
	 * Register router hooks for URL hiding and auth URL overrides.
	 *
	 * @return void
	 */
	private function define_router_hooks() {
		$router = new AuthVault_Router();
		$this->loader->add_action( 'init', $router, 'intercept_blocked_urls', 1, 0 );
		$this->loader->add_action( 'init', $router, 'add_rewrite_rules', 10, 0 );
		$this->loader->add_filter( 'query_vars', $router, 'add_query_vars', 10, 1 );
		$this->loader->add_action( 'template_redirect', $router, 'redirect_custom_login_slug', 1, 0 );
		$this->loader->add_action( 'template_redirect', $router, 'protect_auth_pages', 5, 0 );
		$this->loader->add_filter( 'login_url', $router, 'filter_login_url', 10, 3 );
		$this->loader->add_filter( 'logout_url', $router, 'filter_logout_url', 10, 2 );
		$this->loader->add_filter( 'register_url', $router, 'filter_register_url', 10, 1 );
		$this->loader->add_filter( 'lostpassword_url', $router, 'filter_lostpassword_url', 10, 2 );
		$this->loader->add_filter( 'site_url', $router, 'filter_site_url_wp_login', 10, 4 );
		$this->loader->add_filter( 'network_site_url', $router, 'filter_network_site_url', 10, 3 );
		$this->loader->add_filter( 'login_redirect', $router, 'filter_login_redirect', 10, 3 );
	}

	/**
	 * Load Elementor integration (category, widgets, styles). Shows admin notice if Elementor not active.
	 *
	 * @return void
	 */
	private function define_elementor_integration() {
		new \AuthVault\Elementor\AuthVault_Elementor();
	}

	/**
	 * Initialize security layer and register log cleanup cron handler.
	 *
	 * @return void
	 */
	private function define_security() {
		AuthVault_Security::get_instance();

		$this->loader->add_action( AuthVault_Activator::LOG_CLEANUP_CRON_HOOK, $this, 'run_login_log_cleanup', 10, 0 );

		AuthVault_Activator::schedule_log_cleanup();
	}

	/**
	 * Cron callback: delete old login log entries.
	 *
	 * @return void
	 */
	public function run_login_log_cleanup() {
		AuthVault_Security::cleanup_old_login_log_entries();
	}

	/**
	 * Register auth form processing hooks (login, register, reset, logout).
	 *
	 * @return void
	 */
	private function define_auth_hooks() {
		$auth = new AuthVault_Auth();
		$this->loader->add_action( 'init', $auth, 'maybe_process_forms', 1, 0 );
		$this->loader->add_action( 'template_redirect', $auth, 'validate_reset_key_on_confirm_page', 2, 0 );
	}

	/**
	 * Register wp_mail "From" name and address overrides when enabled in settings.
	 *
	 * @return void
	 */
	private function define_email_hooks() {
		$this->loader->add_filter( 'wp_mail_from', $this, 'filter_mail_from_email', 10, 1 );
		$this->loader->add_filter( 'wp_mail_from_name', $this, 'filter_mail_from_name', 10, 1 );
		$this->loader->add_filter( 'retrieve_password_message', $this, 'filter_password_email_separator', 10, 4 );
		$this->loader->add_filter( 'wp_new_user_notification_email', $this, 'filter_new_user_email_separator', 10, 3 );
	}

	/**
	 * Override the "From" email address used by wp_mail when the setting is enabled.
	 *
	 * @param string $from_email Default sender email.
	 * @return string Filtered sender email.
	 */
	public function filter_mail_from_email( $from_email ) {
		if ( ! authvault_get_option( 'override_lost_password_email', false ) ) {
			return $from_email;
		}
		$custom = authvault_get_option( 'email_from_email', '' );
		if ( '' !== $custom && false !== is_email( $custom ) ) {
			return $custom;
		}
		return $from_email;
	}

	/**
	 * Override the "From" name used by wp_mail when the setting is enabled.
	 *
	 * @param string $from_name Default sender name.
	 * @return string Filtered sender name.
	 */
	public function filter_mail_from_name( $from_name ) {
		if ( ! authvault_get_option( 'override_lost_password_email', false ) ) {
			return $from_name;
		}
		$custom = authvault_get_option( 'email_from_name', '' );
		if ( '' !== $custom ) {
			return $custom;
		}
		return $from_name;
	}

	/**
	 * Insert a clear separator between the set-password link and the login page link in password-related emails,
	 * so email clients do not treat both URLs as a single link.
	 *
	 * @param string $message   Email message body.
	 * @param string $key       Password reset key (unused).
	 * @param string $user_login User login (unused).
	 * @param \WP_User $user_data User object (unused).
	 * @return string Filtered message.
	 */
	public function filter_password_email_separator( $message, $key, $user_login, $user_data ) {
		return $this->insert_login_link_separator( $message );
	}

	/**
	 * Insert a clear separator between the set-password link and the login page link in new user notification emails.
	 *
	 * @param array    $wp_new_user_notification_email Email data (to, subject, message, headers).
	 * @param \WP_User $user                            New user.
	 * @param string   $blogname                        Site title (unused).
	 * @return array Filtered email data.
	 */
	public function filter_new_user_email_separator( $wp_new_user_notification_email, $user, $blogname ) {
		if ( ! empty( $wp_new_user_notification_email['message'] ) && is_string( $wp_new_user_notification_email['message'] ) ) {
			$wp_new_user_notification_email['message'] = $this->insert_login_link_separator( $wp_new_user_notification_email['message'] );
		}
		return $wp_new_user_notification_email;
	}

	/**
	 * In a message that contains the AuthVault login page URL, replace the run of whitespace before that URL
	 * with a clear separator line so the set-password link and login link are not merged by email clients.
	 *
	 * @param string $message Email body.
	 * @return string Message with separator inserted when applicable.
	 */
	private function insert_login_link_separator( $message ) {
		$login_page_id = (int) authvault_get_option( 'login_page_id', 0 );
		if ( 0 >= $login_page_id ) {
			return $message;
		}
		$login_url = get_permalink( $login_page_id );
		if ( ! is_string( $login_url ) || '' === $login_url ) {
			return $message;
		}
		$separator = "\n\n" . __( 'After setting your password, you can log in here:', 'authvault' ) . "\n\n";
		// Match whitespace before the login URL (with or without trailing slash).
		$login_url_variants = array( $login_url, rtrim( $login_url, '/' ), $login_url . '/' );
		$login_url_variants = array_unique( array_filter( $login_url_variants ) );
		foreach ( $login_url_variants as $variant ) {
			$escaped  = preg_quote( $variant, '/' );
			$pattern  = '/\s+' . $escaped . '/';
			$replacement = $separator . $login_url;
			$filtered = preg_replace( $pattern, $replacement, $message, 1 );
			if ( is_string( $filtered ) && $filtered !== $message ) {
				return $filtered;
			}
		}
		return $message;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @return void
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it.
	 *
	 * @return string
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks.
	 *
	 * @return AuthVault_Loader
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}
}
