<?php
/**
 * PHPUnit bootstrap for WP AuthVault.
 *
 * Defines constants and loads plugin autoloader so unit tests can run without WordPress.
 *
 * @package AuthVault
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

require_once __DIR__ . '/../vendor/autoload.php';

if ( ! defined( 'AUTHVAULT_VERSION' ) ) {
	define( 'AUTHVAULT_VERSION', '1.0.0' );
}
if ( ! defined( 'AUTHVAULT_PLUGIN_DIR' ) ) {
	define( 'AUTHVAULT_PLUGIN_DIR', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'AUTHVAULT_PLUGIN_URL' ) ) {
	define( 'AUTHVAULT_PLUGIN_URL', 'https://example.com/wp-content/plugins/wp-authvault/' );
}
if ( ! defined( 'AUTHVAULT_MIN_PHP' ) ) {
	define( 'AUTHVAULT_MIN_PHP', '8.0' );
}
if ( ! defined( 'AUTHVAULT_MIN_WP' ) ) {
	define( 'AUTHVAULT_MIN_WP', '6.4' );
}
if ( ! defined( 'AUTHVAULT_DB_VERSION' ) ) {
	define( 'AUTHVAULT_DB_VERSION', '1.0' );
}
