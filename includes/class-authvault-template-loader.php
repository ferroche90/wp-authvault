<?php
/**
 * Template loader: locate and load auth form templates with theme override support.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locates template files in child theme, theme, then plugin templates folder.
 * Loads a template with extracted $args for use in the template file.
 */
class AuthVault_Template_Loader {

	/**
	 * Subdirectory name for theme overrides (under theme root).
	 *
	 * @var string
	 */
	const THEME_OVERRIDE_DIR = 'authvault';

	/**
	 * Locate a template file: child theme → theme → plugin.
	 *
	 * @param string $template_name Template name with or without .php (e.g. 'login' or 'login.php').
	 * @return string Full path to the template file, or empty string if not found.
	 */
	public static function locate_template( $template_name ) {
		if ( '' === (string) $template_name ) {
			return '';
		}
		$template_name = str_replace( '.php', '', $template_name );
		$template_name = $template_name . '.php';

		$paths = array();

		if ( is_child_theme() ) {
			$paths[] = get_stylesheet_directory() . '/' . self::THEME_OVERRIDE_DIR . '/' . $template_name;
		}
		$paths[] = get_template_directory() . '/' . self::THEME_OVERRIDE_DIR . '/' . $template_name;
		$paths[] = AUTHVAULT_PLUGIN_DIR . 'templates/' . $template_name;

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				return $path;
			}
		}
		return '';
	}

	/**
	 * Load a template file with extracted $args (variables available in template).
	 *
	 * @param string              $template_name Template name (e.g. 'login', 'register').
	 * @param array<string, mixed> $args          Associative array of variables to extract.
	 * @return void
	 */
	public static function load_template( $template_name, $args = array() ) {
		$path = self::locate_template( $template_name );
		if ( '' === $path ) {
			return;
		}
		if ( ! is_array( $args ) ) {
			$args = array();
		}
		$args = array_merge(
			array(
				'messages' => array(),
				'wrapper_attributes' => array(),
			),
			$args
		);
		extract( $args, EXTR_SKIP ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
		include $path;
	}
}
