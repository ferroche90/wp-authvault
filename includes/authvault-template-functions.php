<?php
/**
 * Template functions for AuthVault forms (used by Elementor widgets and shortcodes).
 *
 * @package AuthVault
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Locate a template file (child theme → theme → plugin).
 *
 * @param string $template_name Template name with or without .php (e.g. 'login' or 'login.php').
 * @return string Full path to the template file, or empty string if not found.
 */
function authvault_locate_template( $template_name ) {
	return \AuthVault\AuthVault_Template_Loader::locate_template( $template_name );
}

/**
 * Load a template with extracted $args.
 *
 * @param string              $template_name Template name (e.g. 'login', 'register').
 * @param array<string, mixed> $args          Variables to extract for the template.
 * @return void
 */
function authvault_load_template( $template_name, $args = array() ) {
	\AuthVault\AuthVault_Template_Loader::load_template( $template_name, $args );
}

/**
 * Get login form markup via template (output buffering).
 *
 * @param array<string, mixed> $args Form settings (show_form_title, form_title_text, show_labels,
 *                                   show_placeholders, submit_button_text, redirect_after_success,
 *                                   show_remember_me, show_forgot_password_link, forgot_password_link_text,
 *                                   show_register_link, register_link_text, messages, wrapper_attributes).
 * @param bool                 $echo Whether to echo (true) or return (false).
 * @return string Empty string if $echo true, else form HTML.
 */
function authvault_get_login_form( array $args = array(), $echo = true ) {
	\AuthVault\AuthVault_Security::maybe_enqueue_recaptcha_script();
	$args = array_merge(
		array(
			'show_form_title'           => true,
			'form_title_text'           => __( 'Log in', 'authvault' ),
			'show_labels'               => true,
			'show_placeholders'          => true,
			'submit_button_text'        => __( 'Log in', 'authvault' ),
			'redirect_after_success'    => '',
			'show_remember_me'          => true,
			'show_forgot_password_link' => true,
			'forgot_password_link_text' => __( 'Forgot password?', 'authvault' ),
			'show_register_link'        => true,
			'register_link_text'       => __( 'Register', 'authvault' ),
			'messages'                  => array(),
			'wrapper_attributes'        => array(),
		),
		$args
	);
	$lockout_mins = isset( $_GET['authvault_lockout_minutes'] ) ? absint( wp_unslash( $_GET['authvault_lockout_minutes'] ) ) : 0;
	if ( 0 < $lockout_mins ) {
		$args['messages'] = array_merge(
			(array) $args['messages'],
			array( sprintf( __( 'Too many failed attempts. Try again in %d minute(s).', 'authvault' ), $lockout_mins ) )
		);
	} elseif ( isset( $_GET['authvault_error'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['authvault_error'] ) ) ) {
		$args['messages'] = array_merge(
			(array) $args['messages'],
			array( __( 'The credentials you entered are incorrect. Please try again.', 'authvault' ) )
		);
	}
	if ( true === $echo ) {
		ob_start();
		authvault_load_template( 'login', $args );
		$html = ob_get_clean();
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes.
		return '';
	}
	ob_start();
	authvault_load_template( 'login', $args );
	return ob_get_clean();
}

/**
 * Get register form markup via template.
 *
 * @param array<string, mixed> $args Form settings (show_form_title, form_title_text, show_labels,
 *                                   show_placeholders, submit_button_text, redirect_after_success,
 *                                   show_username_field, show_login_link, login_link_text, messages, wrapper_attributes).
 * @param bool                 $echo Whether to echo (true) or return (false).
 * @return string Empty string if $echo true, else form HTML.
 */
function authvault_get_register_form( array $args = array(), $echo = true ) {
	\AuthVault\AuthVault_Security::maybe_enqueue_recaptcha_script();
	$args = array_merge(
		array(
			'show_form_title'        => true,
			'form_title_text'        => __( 'Register', 'authvault' ),
			'show_labels'             => true,
			'show_placeholders'       => true,
			'submit_button_text'     => __( 'Register', 'authvault' ),
			'redirect_after_success' => '',
			'show_username_field'    => true,
			'show_login_link'        => true,
			'login_link_text'        => __( 'Log in', 'authvault' ),
			'messages'               => array(),
			'wrapper_attributes'      => array(),
		),
		$args
	);
	if ( true === $echo ) {
		ob_start();
		authvault_load_template( 'register', $args );
		$html = ob_get_clean();
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes.
		return '';
	}
	ob_start();
	authvault_load_template( 'register', $args );
	return ob_get_clean();
}

/**
 * Get password reset (request) form markup via template.
 *
 * @param array<string, mixed> $args Form settings (show_form_title, form_title_text, show_labels,
 *                                   show_placeholders, submit_button_text, redirect_after_success,
 *                                   show_back_to_login_link, back_to_login_link_text, messages, wrapper_attributes).
 * @param bool                 $echo Whether to echo (true) or return (false).
 * @return string Empty string if $echo true, else form HTML.
 */
function authvault_get_reset_form( array $args = array(), $echo = true ) {
	\AuthVault\AuthVault_Security::maybe_enqueue_recaptcha_script();
	$args = array_merge(
		array(
			'show_form_title'         => true,
			'form_title_text'         => __( 'Reset password', 'authvault' ),
			'show_labels'             => true,
			'show_placeholders'        => true,
			'submit_button_text'      => __( 'Get new password', 'authvault' ),
			'redirect_after_success'  => '',
			'show_back_to_login_link' => true,
			'back_to_login_link_text' => __( 'Back to login', 'authvault' ),
			'messages'                => array(),
			'wrapper_attributes'      => array(),
		),
		$args
	);
	if ( true === $echo ) {
		ob_start();
		authvault_load_template( 'reset-password', $args );
		$html = ob_get_clean();
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes.
		return '';
	}
	ob_start();
	authvault_load_template( 'reset-password', $args );
	return ob_get_clean();
}

/**
 * Get password reset confirm (new password) form markup via template.
 *
 * @param array<string, mixed> $args Form settings (show_form_title, form_title_text, show_labels,
 *                                   submit_button_text, rp_key, rp_login, messages, wrapper_attributes).
 * @param bool                 $echo Whether to echo (true) or return (false).
 * @return string Empty string if $echo true, else form HTML.
 */
function authvault_get_reset_confirm_form( array $args = array(), $echo = true ) {
	$args = array_merge(
		array(
			'show_form_title'    => true,
			'form_title_text'    => __( 'Set new password', 'authvault' ),
			'show_labels'        => true,
			'submit_button_text' => __( 'Reset password', 'authvault' ),
			'rp_key'             => '',
			'rp_login'           => '',
			'messages'           => array(),
			'wrapper_attributes' => array(),
		),
		$args
	);
	if ( true === $echo ) {
		ob_start();
		authvault_load_template( 'reset-password-confirm', $args );
		$html = ob_get_clean();
		echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- template escapes.
		return '';
	}
	ob_start();
	authvault_load_template( 'reset-password-confirm', $args );
	return ob_get_clean();
}
