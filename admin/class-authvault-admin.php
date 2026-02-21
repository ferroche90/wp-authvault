<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package AuthVault
 */

namespace AuthVault\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and hooks for the admin area.
 */
class AuthVault_Admin {

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
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * Enqueues only on the AuthVault settings page (Settings > AuthVault).
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_styles( $hook_suffix ) {
		if ( AuthVault_Settings::get_page_hook() !== $hook_suffix ) {
			return;
		}
		wp_enqueue_style(
			$this->plugin_name . '-admin',
			authvault_asset_url( 'assets/css/authvault-admin.css' ),
			array(),
			$this->version
		);
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * Enqueues only on the AuthVault settings page (Settings > AuthVault).
	 *
	 * @param string $hook_suffix The current admin page hook suffix.
	 * @return void
	 */
	public function enqueue_scripts( $hook_suffix ) {
		if ( AuthVault_Settings::get_page_hook() !== $hook_suffix ) {
			return;
		}
		wp_enqueue_script(
			$this->plugin_name . '-admin',
			authvault_asset_url( 'assets/js/authvault-admin.js' ),
			array( 'jquery' ),
			$this->version,
			true
		);
	}
}
