<?php
/**
 * Define the internationalization functionality.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for the plugin
 * so that it is ready for translation.
 */
class AuthVault_I18n {

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @return void
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			'authvault',
			false,
			dirname( AUTHVAULT_PLUGIN_BASENAME ) . '/languages/'
		);
	}
}
