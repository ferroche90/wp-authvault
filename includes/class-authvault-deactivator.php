<?php
/**
 * Fired during plugin deactivation.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation.
 *
 * Flushes rewrite rules. Does not delete any data or options.
 */
class AuthVault_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Flush rewrite rules so that the custom login slug rule is no longer active.
	 * Does not delete options, transients, or the login log table.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules( false );
	}
}
