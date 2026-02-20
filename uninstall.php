<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Removes options, DB table, transients, and plugin-created pages.
 *
 * @package AuthVault
 */

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

global $wpdb;

delete_option( 'authvault_settings' );
delete_option( 'authvault_db_version' );

$table_name = $wpdb->prefix . 'authvault_login_log';
$wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table_name ) );

/** Delete transients with prefix authvault_ (_transient_authvault_* and _transient_timeout_authvault_*). */
$like_transient = $wpdb->esc_like( '_transient_authvault_' ) . '%';
$like_timeout   = $wpdb->esc_like( '_transient_timeout_authvault_' ) . '%';
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", $like_transient, $like_timeout ) );

/** Delete only pages created by the plugin (post meta _authvault_created_by_plugin = 1). */
$created = $wpdb->get_col(
	$wpdb->prepare(
		"SELECT p.ID FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s AND pm.meta_value = %s
		WHERE p.post_type = %s",
		'_authvault_created_by_plugin',
		'1',
		'page'
	)
);
if ( is_array( $created ) && count( $created ) > 0 ) {
	foreach ( $created as $post_id ) {
		wp_delete_post( (int) $post_id, true );
	}
}
