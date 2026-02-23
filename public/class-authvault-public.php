<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package AuthVault
 */

namespace AuthVault\Public_Area;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the public area.
 */
class AuthVault_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @var string
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @var string
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @param string $plugin_name The name of the plugin.
	 * @param string $version     The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
		$this->register_shortcodes();
	}

	/**
	 * Register AuthVault shortcodes for use in default pages.
	 *
	 * @return void
	 */
	public function register_shortcodes() {
		add_shortcode( 'authvault_login', array( $this, 'shortcode_login' ) );
		add_shortcode( 'authvault_register', array( $this, 'shortcode_register' ) );
		add_shortcode( 'authvault_reset_password', array( $this, 'shortcode_reset_password' ) );
		add_shortcode( 'authvault_reset_password_confirm', array( $this, 'shortcode_reset_password_confirm' ) );
	}

	/**
	 * Shortcode callback: output the login form.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes (unused).
	 * @return string HTML output.
	 */
	public function shortcode_login( $atts = array() ) {
		return authvault_get_login_form( array(), false );
	}

	/**
	 * Shortcode callback: output the register form.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes (unused).
	 * @return string HTML output.
	 */
	public function shortcode_register( $atts = array() ) {
		return authvault_get_register_form( array(), false );
	}

	/**
	 * Shortcode callback: output the password reset request form.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes (unused).
	 * @return string HTML output.
	 */
	public function shortcode_reset_password( $atts = array() ) {
		return authvault_get_reset_form( array(), false );
	}

	/**
	 * Shortcode callback: output the password reset confirm (set new password) form.
	 * Reads key and login from the URL (from the email reset link). Use on the Password Reset Confirm page.
	 *
	 * @param array<string, mixed> $atts Shortcode attributes (unused).
	 * @return string HTML output.
	 */
	public function shortcode_reset_password_confirm( $atts = array() ) {
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ), true ) : '';

		if ( '' === $key || '' === $login ) {
			$reset_page_id = (int) authvault_get_option( 'password_reset_page_id', 0 );
			$reset_url     = ( 0 < $reset_page_id ) ? get_permalink( $reset_page_id ) : home_url( '/wp-login.php?action=lostpassword' );
			if ( ! is_string( $reset_url ) || '' === $reset_url ) {
				$reset_url = home_url();
			}
			return '<p class="authvault-reset-confirm-invalid-link">' . esc_html__( 'This link is invalid or has expired. Please request a new password reset.', 'authvault' ) . ' <a href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Request password reset', 'authvault' ) . '</a></p>';
		}

		return authvault_get_reset_confirm_form(
			array(
				'rp_key'   => $key,
				'rp_login' => $login,
			),
			false
		);
	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
			$this->plugin_name . '-public',
			authvault_asset_url( 'assets/css/authvault-public.css' ),
			array(),
			$this->version
		);
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script(
			$this->plugin_name . '-public',
			authvault_asset_url( 'assets/js/authvault-public.js' ),
			array( 'jquery' ),
			$this->version,
			true
		);
	}
}
