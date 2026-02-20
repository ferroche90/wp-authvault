<?php
/**
 * Unit tests for AuthVault_Router (wp-login intercept, redirect_to from trusted source).
 *
 * @package AuthVault
 */

namespace AuthVault\Tests\Unit;

use AuthVault\Tests\TestCase;
use AuthVault\AuthVault_Router;
use Brain\Monkey\Functions;

/**
 * Tests for AuthVault_Router.
 */
class AuthVault_Router_Test extends TestCase {

	/**
	 * Test that wp-login.php request is intercepted when URL hiding is enabled.
	 *
	 * @return void
	 */
	public function test_wp_login_php_is_intercepted_when_url_hiding_enabled() {
		$_SERVER['REQUEST_URI'] = '/wp-login.php';
		Functions\when( 'authvault_get_option' )->alias( function ( $key ) {
			if ( 'enable_login_url_hiding' === $key ) {
				return true;
			}
			if ( 'wp_login_access_behavior' === $key ) {
				return '404';
			}
			return null;
		} );
		Functions\when( 'wp_doing_cron' )->justReturn( false );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\expect( 'wp_die' )->once();
		Functions\when( 'exit' )->alias( function () {} );

		$router = new AuthVault_Router();
		$router->intercept_blocked_urls();
	}

	/**
	 * Test that build_safe_redirect_to uses home_url only (trusted source).
	 *
	 * @return void
	 */
	public function test_build_safe_redirect_to_uses_trusted_source_only() {
		Functions\when( 'home_url' )->alias( function ( $path = '' ) {
			return 'https://example.com' . ( '' !== $path ? '/' . ltrim( $path, '/' ) : '' );
		} );

		$url = AuthVault_Router::build_safe_redirect_to( 'dashboard' );
		$this->assertStringStartsWith( 'https://example.com', $url );
		$this->assertStringContainsString( 'dashboard', $url );
	}

	/**
	 * Test that build_safe_redirect_to with empty path returns home_url only.
	 *
	 * @return void
	 */
	public function test_build_safe_redirect_to_empty_path_returns_home() {
		Functions\when( 'home_url' )->justReturn( 'https://example.com/' );
		$url = AuthVault_Router::build_safe_redirect_to( '' );
		$this->assertSame( 'https://example.com/', $url );
	}
}
