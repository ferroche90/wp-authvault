<?php
/**
 * Email template and token handling for AuthVault auth-related emails.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles token replacement and custom templates for password reset and other auth emails.
 */
class AuthVault_Email {

	/**
	 * Replace tokens in a string with values from context.
	 *
	 * Tokens are in the form {token_name}. Unknown tokens are left as-is.
	 *
	 * @param string $string  Template string (e.g. subject or body).
	 * @param array  $context Associative array of token name => value (e.g. 'site_name' => 'My Site').
	 * @return string String with tokens replaced.
	 */
	public static function replace_tokens( $string, array $context ) {
		if ( ! is_string( $string ) || '' === $string ) {
			return $string;
		}
		foreach ( $context as $token => $value ) {
			if ( is_scalar( $value ) ) {
				$string = str_replace( '{' . $token . '}', (string) $value, $string );
			}
		}
		return $string;
	}

	/**
	 * Filter retrieve_password_title to use custom subject when set.
	 *
	 * @param string  $title      Default email subject.
	 * @param string  $user_login User login.
	 * @param \WP_User $user_data  User object.
	 * @return string Filtered subject.
	 */
	public function filter_reset_email_title( $title, $user_login, $user_data ) {
		$custom = authvault_get_option( 'reset_email_subject', '' );
		if ( ! is_string( $custom ) || '' === trim( $custom ) ) {
			return $title;
		}
		$site_name = is_multisite() ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		return self::replace_tokens( $custom, array(
			'site_name'   => $site_name,
			'user_login'  => $user_login,
		) );
	}

	/**
	 * Filter retrieve_password_message to use custom body when set.
	 *
	 * Builds the reset link the same way core does so Router filters apply.
	 *
	 * @param string  $message    Default email message.
	 * @param string  $key        Password reset key.
	 * @param string  $user_login User login.
	 * @param \WP_User $user_data User object.
	 * @return string Filtered message.
	 */
	public function filter_reset_email_message( $message, $key, $user_login, $user_data ) {
		$custom = authvault_get_option( 'reset_email_body', '' );
		if ( ! is_string( $custom ) || '' === trim( $custom ) ) {
			return $message;
		}
		$site_name = is_multisite() ? get_network()->site_name : wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
		$locale    = get_user_locale( $user_data );
		$reset_link = network_site_url( 'wp-login.php?login=' . rawurlencode( $user_login ) . '&key=' . $key . '&action=rp', 'login' ) . '&wp_lang=' . $locale;

		return self::replace_tokens( $custom, array(
			'site_name'   => $site_name,
			'user_login'  => $user_login,
			'reset_link'  => $reset_link,
		) );
	}
}
