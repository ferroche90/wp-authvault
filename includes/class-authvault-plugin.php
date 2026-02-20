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
	 * Initialize security layer (site_url filter, etc.).
	 *
	 * @return void
	 */
	private function define_security() {
		AuthVault_Security::get_instance();
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
