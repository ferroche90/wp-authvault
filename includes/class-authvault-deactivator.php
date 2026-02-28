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
 * Flushes rewrite rules and unschedules cron events. Does not delete any data or options.
 */
class AuthVault_Deactivator {

	/**
	 * Deactivate the plugin.
	 *
	 * Flush rewrite rules so that the custom login slug rule is no longer active.
	 * Unschedule the login log cleanup cron.
	 * Does not delete options, transients, or the login log table.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$timestamp = wp_next_scheduled( AuthVault_Activator::LOG_CLEANUP_CRON_HOOK );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, AuthVault_Activator::LOG_CLEANUP_CRON_HOOK );
		}
		flush_rewrite_rules( false );
	}
}
