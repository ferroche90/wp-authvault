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
 * Retrieve a message option, falling back to the provided default when the stored value is empty.
 *
 * @param string $key     Option key (e.g. 'msg_login_error').
 * @param string $default Default message text when the option is blank.
 * @return string
 */
function authvault_get_message( string $key, string $default = '' ): string {
	$value = authvault_get_option( $key, '' );
	return ( is_string( $value ) && '' !== $value ) ? $value : $default;
}

/**
 * Whether the current request is in the Elementor editor or preview iframe.
 *
 * Used to show the confirm form (or invalid-link message) in the editor for styling
 * when key/login are not in the URL.
 *
 * @return bool
 */
function authvault_is_elementor_editor_or_preview() {
	if ( ! class_exists( '\Elementor\Plugin' ) ) {
		return false;
	}
	$plugin = \Elementor\Plugin::$instance;
	if ( isset( $plugin->editor ) && $plugin->editor->is_edit_mode() ) {
		return true;
	}
	if ( isset( $plugin->preview ) && $plugin->preview->is_preview_mode() ) {
		return true;
	}
	return false;
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
 * Get the URL for an asset, preferring the minified version when available.
 *
 * When a .min.css or .min.js file exists on disk and SCRIPT_DEBUG is not set,
 * returns the URL to the minified file; otherwise returns the URL to the
 * original file.
 *
 * @param string $relative_path Path relative to plugin root (e.g. 'assets/css/authvault-public.css').
 * @return string URL for the asset.
 */
function authvault_asset_url( $relative_path ) {
	$relative_path = ltrim( $relative_path, '/' );
	$use_min       = ( ! defined( 'SCRIPT_DEBUG' ) || ! SCRIPT_DEBUG )
		&& ( str_ends_with( $relative_path, '.css' ) || str_ends_with( $relative_path, '.js' ) );

	if ( $use_min ) {
		$ext = str_ends_with( $relative_path, '.css' ) ? '.css' : '.js';
		$min_path = substr( $relative_path, 0, -strlen( $ext ) ) . '.min' . $ext;
		$min_file = AUTHVAULT_PLUGIN_DIR . $min_path;
		if ( is_readable( $min_file ) ) {
			return AUTHVAULT_PLUGIN_URL . $min_path;
		}
	}

	return AUTHVAULT_PLUGIN_URL . $relative_path;
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
