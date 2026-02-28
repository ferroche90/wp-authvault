<?php
/**
 * Plugin Name:       WP AuthVault
 * Plugin URI:       https://zuiven.com/wp-authvault
 * Description:       Custom authentication pages with Elementor widget support and security hardening. Replaces WordPress native login, register, and password reset flows with fully Elementor-styleable pages.
 * Version:           1.4.0
 * Requires at least: 6.4
 * Requires PHP:      8.0
 * Author:            WP AuthVault
 * Author URI:        https://zuiven.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       authvault
 * Domain Path:       /languages
 *
 * @package AuthVault
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'AUTHVAULT_VERSION', '1.4.0' );
define( 'AUTHVAULT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'AUTHVAULT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'AUTHVAULT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Minimum PHP version required.
 */
define( 'AUTHVAULT_MIN_PHP', '8.0' );

/**
 * Minimum WordPress version required.
 */
define( 'AUTHVAULT_MIN_WP', '6.4' );

/**
 * Database schema version for authvault_login_log (incremented on migrations).
 */
define( 'AUTHVAULT_DB_VERSION', '1.0' );

/**
 * Check minimum PHP and WordPress versions before loading the plugin.
 * Shows an admin notice and bails if requirements are not met.
 */
if ( version_compare( PHP_VERSION, AUTHVAULT_MIN_PHP, '<' ) || version_compare( get_bloginfo( 'version' ), AUTHVAULT_MIN_WP, '<' ) ) {
	add_action(
		'admin_notices',
		function () {
			$php_ok = version_compare( PHP_VERSION, AUTHVAULT_MIN_PHP, '>=' );
			$wp_ok  = version_compare( get_bloginfo( 'version' ), AUTHVAULT_MIN_WP, '>=' );
			echo '<div class="notice notice-error"><p>';
			if ( ! $php_ok && ! $wp_ok ) {
				printf(
					/* translators: 1: required PHP version, 2: current PHP version, 3: required WP version, 4: current WP version */
					esc_html__( 'WP AuthVault requires PHP %1$s or higher (you have %2$s) and WordPress %3$s or higher (you have %4$s). Plugin has been deactivated.', 'authvault' ),
					esc_html( AUTHVAULT_MIN_PHP ),
					esc_html( PHP_VERSION ),
					esc_html( AUTHVAULT_MIN_WP ),
					esc_html( get_bloginfo( 'version' ) )
				);
			} elseif ( ! $php_ok ) {
				printf(
					/* translators: 1: required PHP version, 2: current PHP version */
					esc_html__( 'WP AuthVault requires PHP %1$s or higher (you have %2$s). Plugin has been deactivated.', 'authvault' ),
					esc_html( AUTHVAULT_MIN_PHP ),
					esc_html( PHP_VERSION )
				);
			} else {
				printf(
					/* translators: 1: required WP version, 2: current WP version */
					esc_html__( 'WP AuthVault requires WordPress %1$s or higher (you have %2$s). Plugin has been deactivated.', 'authvault' ),
					esc_html( AUTHVAULT_MIN_WP ),
					esc_html( get_bloginfo( 'version' ) )
				);
			}
			echo '</p></div>';
		}
	);
	return;
}

$authvault_autoloader = AUTHVAULT_PLUGIN_DIR . 'vendor/autoload.php';
if ( ! file_exists( $authvault_autoloader ) ) {
	add_action(
		'admin_notices',
		function () {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'WP AuthVault could not load: run "composer install" in the plugin directory.', 'authvault' ) . '</p></div>';
		}
	);
	return;
}

require_once $authvault_autoloader;

register_activation_hook( __FILE__, array( 'AuthVault\AuthVault_Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'AuthVault\AuthVault_Deactivator', 'deactivate' ) );

/**
 * Bootstrap the plugin and return the main plugin instance.
 *
 * @return \AuthVault\AuthVault_Plugin Plugin instance.
 */
function authvault() {
	static $plugin = null;
	if ( null === $plugin ) {
		$plugin = new \AuthVault\AuthVault_Plugin();
		$plugin->run();
	}
	return $plugin;
}

authvault();
