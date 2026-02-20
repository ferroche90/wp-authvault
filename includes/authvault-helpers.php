<?php
/**
 * Helper functions for WP AuthVault.
 *
 * @package AuthVault
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get a single setting from the plugin options.
 *
 * Delegates to AuthVault_Options::get() so the canonical default values
 * and option name are defined in exactly one place.
 *
 * @param string     $key     Option key (e.g. 'login_page_id', 'custom_login_slug').
 * @param mixed|null $default Optional. Override fallback when key is absent even after merge.
 * @return mixed
 */
function authvault_get_option( string $key, mixed $default = null ): mixed {
	$options = new \AuthVault\AuthVault_Options();
	return $options->get( $key, $default );
}

/**
 * Get the full array of default setting values.
 *
 * Used by authvault_get_option(), the settings page reset, and the activator.
 *
 * @return array<string, mixed>
 */
function authvault_get_settings_defaults() {
	$options = new \AuthVault\AuthVault_Options();
	return $options->get_defaults();
}

/**
 * Build a string of HTML attributes from an associative array.
 *
 * Used by form templates to render wrapper_attributes as safe HTML.
 *
 * @param array<string, string|array> $attrs   Attribute name => value (value may be array for class).
 * @param array<string>               $exclude Keys to exclude from output (e.g. 'class' if output separately).
 * @return string Escaped attribute string, e.g. ' data-foo="bar"'.
 */
function authvault_attributes_string( $attrs, $exclude = array() ) {
	if ( ! is_array( $attrs ) || empty( $attrs ) ) {
		return '';
	}
	$out = array();
	foreach ( $attrs as $key => $value ) {
		if ( in_array( $key, $exclude, true ) ) {
			continue;
		}
		if ( is_array( $value ) ) {
			$value = implode( ' ', $value );
		}
		$value = (string) $value;
		if ( '' !== $value ) {
			$out[] = $key . '="' . esc_attr( $value ) . '"';
		}
	}
	return $out ? ' ' . implode( ' ', $out ) : '';
}
