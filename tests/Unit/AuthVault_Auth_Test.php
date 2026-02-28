<?php
/**
 * Unit tests for AuthVault_Auth (nonce failure, generic error, wp_signon).
 *
 * @package AuthVault
 */

namespace AuthVault\Tests\Unit;

use AuthVault\Tests\TestCase;
use AuthVault\AuthVault_Auth;
use Brain\Monkey\Functions;

/**
 * Tests for AuthVault_Auth.
 */
class AuthVault_Auth_Test extends TestCase {

	/**
	 * Test that process_login with invalid nonce redirects and does not call wp_signon.
	 *
	 * @return void
	 */
	public function test_nonce_failure_redirects_without_calling_wp_signon() {
		$_POST['authvault_login_nonce'] = 'bad_nonce';
		$_POST['username']              = 'user';
		$_POST['password']              = 'pass';

		Functions\when( 'wp_verify_nonce' )->justReturn( false );
		Functions\when( 'authvault_get_option' )->justReturn( 0 );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/login/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		$redirect_called = false;
		Functions\when( 'wp_safe_redirect' )->alias( function () use ( &$redirect_called ) {
			$redirect_called = true;
		} );
		Functions\when( 'exit' )->alias( function () {} );

		$auth    = new AuthVault_Auth();
		$auth->process_login();

		$this->assertTrue( $redirect_called, 'Nonce failure should trigger redirect' );
	}

	/**
	 * Test that login error redirect uses generic authvault_error=1 (no user enumeration).
	 *
	 * @return void
	 */
	public function test_login_error_uses_generic_message_no_enumeration() {
		$_POST['authvault_login_nonce'] = 'valid_nonce';
		$_POST['username']              = 'existing_user';
		$_POST['password']              = 'wrong';
		$_POST['g-recaptcha-response']   = 'token';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'authvault_get_option' )->alias( function ( $key ) {
			return ( 'login_page_id' === $key ) ? 1 : false;
		} );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/login/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		Functions\when( 'wp_hash' )->justReturn( 'ip_hash' );
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'wp_signon' )->justReturn( new \WP_Error( 'invalid_username', 'Invalid username.' ) );
		$redirect_url = '';
		Functions\when( 'wp_safe_redirect' )->alias( function ( $url ) use ( &$redirect_url ) {
			$redirect_url = $url;
		} );
		Functions\when( 'exit' )->alias( function () {} );

		$auth = new AuthVault_Auth();
		$auth->process_login();

		$this->assertStringContainsString( 'authvault_error=1', $redirect_url );
		$this->assertStringNotContainsString( 'invalid_username', $redirect_url );
	}

	/**
	 * Test that successful login calls wp_signon and redirects.
	 *
	 * @return void
	 */
	public function test_successful_login_calls_wp_signon() {
		$_POST['authvault_login_nonce'] = 'valid_nonce';
		$_POST['username']               = 'user';
		$_POST['password']               = 'pass';
		$_POST['g-recaptcha-response']   = 'token';

		$user = (object) array( 'ID' => 1, 'user_login' => 'user' );
		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'authvault_get_option' )->alias( function ( $key ) {
			if ( 'login_page_id' === $key ) {
				return 0;
			}
			if ( 'enable_lockout' === $key ) {
				return false;
			}
			return null;
		} );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/login/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		Functions\when( 'is_ssl' )->justReturn( true );
		Functions\expect( 'wp_signon' )->once()->andReturn( $user );
		Functions\when( 'wp_safe_redirect' )->justReturn( null );
		Functions\when( 'exit' )->alias( function () {} );

		$auth = new AuthVault_Auth();
		$auth->process_login();
	}

	/**
	 * Test that register redirects with generic error when reCAPTCHA is enabled and token is missing.
	 *
	 * @return void
	 */
	public function test_register_fails_with_generic_error_when_recaptcha_fails() {
		$_POST['authvault_register_nonce'] = 'valid_nonce';
		$_POST['username']                 = 'new_user';
		$_POST['email']                    = 'new@example.com';
		$_POST['g-recaptcha-response']     = '';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'get_option' )->alias(
			function ( $key, $default = null ) {
				if ( 'users_can_register' === $key ) {
					return true;
				}
				return $default;
			}
		);
		Functions\when( 'authvault_get_option' )->alias(
			function ( $key ) {
				if ( 'register_page_id' === $key ) {
					return 1;
				}
				if ( 'recaptcha_enabled' === $key ) {
					return true;
				}
				if ( 'recaptcha_secret_key' === $key ) {
					return 'secret';
				}
				return null;
			}
		);
		Functions\when( 'wp_remote_post' )->justReturn( new \WP_Error( 'http_error', 'boom' ) );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/register/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		$redirect_url = '';
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) use ( &$redirect_url ) {
				$redirect_url = $url;
			}
		);
		Functions\when( 'exit' )->alias( function () {} );

		$auth = new AuthVault_Auth();
		$auth->process_register();

		$this->assertStringContainsString( 'authvault_register_error=1', $redirect_url );
	}

	/**
	 * Test that reset request keeps generic response when reCAPTCHA verification fails.
	 *
	 * @return void
	 */
	public function test_reset_request_still_redirects_generic_when_recaptcha_fails() {
		$_POST['authvault_reset_nonce']  = 'valid_nonce';
		$_POST['user_login']             = 'user@example.com';
		$_POST['g-recaptcha-response']   = '';

		Functions\when( 'wp_verify_nonce' )->justReturn( true );
		Functions\when( 'authvault_get_option' )->alias(
			function ( $key ) {
				if ( 'password_reset_page_id' === $key ) {
					return 1;
				}
				if ( 'recaptcha_enabled' === $key ) {
					return true;
				}
				if ( 'recaptcha_secret_key' === $key ) {
					return 'secret';
				}
				return null;
			}
		);
		Functions\when( 'wp_remote_post' )->justReturn( new \WP_Error( 'http_error', 'boom' ) );
		Functions\when( 'get_permalink' )->justReturn( 'https://example.com/reset/' );
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		$redirect_url = '';
		Functions\when( 'wp_safe_redirect' )->alias(
			function ( $url ) use ( &$redirect_url ) {
				$redirect_url = $url;
			}
		);
		Functions\when( 'exit' )->alias( function () {} );

		$auth = new AuthVault_Auth();
		$auth->process_reset_request();

		$this->assertStringContainsString( 'authvault_reset_sent=1', $redirect_url );
	}
}
