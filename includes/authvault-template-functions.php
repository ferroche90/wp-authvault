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
			'show_placeholders'         => true,
			'submit_button_text'        => __( 'Log in', 'authvault' ),
			'redirect_after_success'    => '',
			'show_remember_me'          => true,
			'show_forgot_password_link' => true,
			'forgot_password_link_text' => __( 'Forgot password?', 'authvault' ),
			'show_register_link'        => true,
			'register_link_text'        => __( 'Register', 'authvault' ),
			'show_form_description'     => false,
			'form_description'          => '',
			'show_email_icon'           => false,
			'show_password_icon'        => false,
			'show_password_toggle'      => false,
			'username_label'            => '',
			'username_placeholder'      => '',
			'password_label'            => '',
			'password_placeholder'      => '',
			'messages'                  => array(),
			'wrapper_attributes'        => array(),
		),
		$args
	);
	$lockout_mins = isset( $_GET['authvault_lockout_minutes'] ) ? absint( wp_unslash( $_GET['authvault_lockout_minutes'] ) ) : 0;
	if ( 0 < $lockout_mins ) {
		$args['messages'][] = array(
			'type' => 'error',
			'text' => sprintf( authvault_get_message( 'msg_login_lockout', __( 'Too many failed attempts. Try again in %d minute(s).', 'authvault' ) ), $lockout_mins ),
		);
	} elseif ( isset( $_GET['authvault_error'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['authvault_error'] ) ) ) {
		$args['messages'][] = array(
			'type' => 'error',
			'text' => authvault_get_message( 'msg_login_error', __( 'The credentials you entered are incorrect. Please try again.', 'authvault' ) ),
		);
	}
	if ( isset( $_GET['registered'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['registered'] ) ) ) {
		$args['messages'][] = array(
			'type' => 'success',
			'text' => authvault_get_message( 'msg_login_registered', __( 'Registration complete. Please check your email for your password.', 'authvault' ) ),
		);
	}
	if ( isset( $_GET['password_reset'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['password_reset'] ) ) ) {
		$args['messages'][] = array(
			'type' => 'success',
			'text' => authvault_get_message( 'msg_login_password_reset', __( 'Your password has been reset. You can now log in with your new password.', 'authvault' ) ),
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
	if ( isset( $_GET['authvault_register_error'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['authvault_register_error'] ) ) ) {
		$args['messages'][] = array(
			'type' => 'error',
			'text' => authvault_get_message( 'msg_register_error', __( 'Registration failed. Please try again.', 'authvault' ) ),
		);
	}
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
 * @param array<string, mixed> $args Form settings (show_form_title, form_title_text, show_form_description,
 *                                   form_description, show_email_icon, show_labels, show_placeholders,
 *                                   username_label, username_placeholder, submit_button_text,
 *                                   redirect_after_success, show_back_to_login_link, back_to_login_link_text,
 *                                   messages, wrapper_attributes).
 * @param bool                 $echo Whether to echo (true) or return (false).
 * @return string Empty string if $echo true, else form HTML.
 */
function authvault_get_reset_form( array $args = array(), $echo = true ) {
	\AuthVault\AuthVault_Security::maybe_enqueue_recaptcha_script();
	$args = array_merge(
		array(
			'show_form_title'         => true,
			'form_title_text'         => __( 'Reset password', 'authvault' ),
			'show_form_description'   => false,
			'form_description'        => '',
			'show_email_icon'         => false,
			'show_labels'             => true,
			'show_placeholders'       => true,
			'username_label'         => __( 'Username or email', 'authvault' ),
			'username_placeholder'   => __( 'Username or email', 'authvault' ),
			'submit_button_text'      => __( 'Get new password', 'authvault' ),
			'redirect_after_success'  => '',
			'show_back_to_login_link' => true,
			'back_to_login_link_text' => __( 'Back to login', 'authvault' ),
			'messages'                => array(),
			'wrapper_attributes'      => array(),
		),
		$args
	);
	if ( isset( $_GET['authvault_reset_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['authvault_reset_sent'] ) ) ) {
		$args['messages'][] = array(
			'type' => 'success',
			'text' => authvault_get_message( 'msg_reset_sent', __( 'If an account exists for the provided details, you will receive a password reset email shortly.', 'authvault' ) ),
		);
	}
	if ( isset( $_GET['error'] ) && 'invalidkey' === sanitize_text_field( wp_unslash( $_GET['error'] ) ) ) {
		$args['messages'][] = array(
			'type' => 'error',
			'text' => authvault_get_message( 'msg_reset_invalid_key', __( 'This password reset link is invalid or has expired. Please request a new one.', 'authvault' ) ),
		);
	}
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
	$min_len = (int) authvault_get_option( 'min_password_length', 8 );
	$min_len = max( 1, min( 128, $min_len ) );
	$allow_weak = (bool) authvault_get_option( 'allow_weak_passwords', false );

	$args = array_merge(
		array(
			'show_form_title'      => true,
			'form_title_text'      => __( 'Set new password', 'authvault' ),
			'show_form_description' => true,
			'form_description'     => __( 'Enter a new password below or use the generated one.', 'authvault' ),
			'show_labels'          => true,
			'submit_button_text'   => __( 'Save password', 'authvault' ),
			'show_strength_meter'  => true,
			'show_generate_button' => true,
			'show_hint_text'       => true,
			'rp_key'               => '',
			'rp_login'             => '',
			'min_password_length'  => $min_len,
			'allow_weak_passwords' => $allow_weak,
			'generated_password'   => wp_generate_password( 24, true, true ),
			'messages'             => array(),
			'wrapper_attributes'   => array(),
		),
		$args
	);

	wp_enqueue_script( 'password-strength-meter' );
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
