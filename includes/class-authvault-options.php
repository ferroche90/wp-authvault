<?php
/**
 * Options and settings manager for the plugin.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options and settings manager for the plugin.
 *
 * Handles reading, writing, and default values for all plugin options
 * stored as a single serialised row in wp_options.
 */
class AuthVault_Options {

	/**
	 * Option name used to store plugin settings in wp_options.
	 *
	 * @var string
	 */
	const OPTION_NAME = 'authvault_settings';

	/**
	 * Get a single option value, or all options merged with defaults.
	 *
	 * @param string|null $key     Optional. Option key. Null returns the full array.
	 * @param mixed       $default Optional. Fallback when key is absent even after merge.
	 * @return mixed
	 */
	public function get( $key = null, $default = null ) {
		$options = get_option( self::OPTION_NAME, array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		$options = array_merge( $this->get_defaults(), $options );
		if ( null === $key ) {
			return $options;
		}
		return array_key_exists( $key, $options ) ? $options[ $key ] : $default;
	}

	/**
	 * Update one or more options.
	 *
	 * @param string|array<string, mixed> $key   Option key or associative array of key => value pairs.
	 * @param mixed                       $value Optional. Value when $key is a string.
	 * @return bool True on success, false on failure.
	 */
	public function set( $key, $value = null ) {
		$options = $this->get();
		if ( is_array( $key ) ) {
			$options = array_merge( $options, $key );
		} else {
			$options[ $key ] = $value;
		}
		return update_option( self::OPTION_NAME, $options );
	}

	/**
	 * Get default option values for every setting the plugin supports.
	 *
	 * Every key here must be:
	 *  - registered in AuthVault_Settings::register_settings()
	 *  - sanitised in AuthVault_Settings::sanitize_settings()
	 *  - consumed by at least one class (Router, Auth, Security, Widgets)
	 *
	 * @return array<string, mixed>
	 */
	public function get_defaults() {
		return array(
			// Page Assignments.
			'login_page_id'                  => 0,
			'register_page_id'               => 0,
			'password_reset_page_id'         => 0,
			'password_reset_confirm_page_id' => 0,
			'logout_redirect_page_id'        => 0,
			'login_redirect_page_id'         => 0,

			// URL Security.
			'custom_login_slug'              => 'login',
			'enable_login_url_hiding'        => false,
			'wp_login_access_behavior'       => '404',
			'wp_login_redirect_page_id'      => 0,

			// Registration.
			'enable_user_registration'       => false,
			'default_role'                   => 'subscriber',

			// Brute Force / Security.
			'enable_lockout'                 => true,
			'max_login_attempts'             => 5,
			'lockout_duration_minutes'       => 15,
			'recaptcha_enabled'              => false,
			'recaptcha_site_key'             => '',
			'recaptcha_secret_key'           => '',

			// Email.
			'override_lost_password_email'   => false,
			'email_from_name'                => '',
			'email_from_email'               => '',

			// Login Attempt Logging.
			'enable_login_log'               => false,
		);
	}
}
