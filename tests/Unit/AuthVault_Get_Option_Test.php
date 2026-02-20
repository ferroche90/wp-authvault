<?php
/**
 * Unit tests for authvault_get_option (returns default when option not set).
 *
 * @package AuthVault
 */

namespace AuthVault\Tests\Unit;

use AuthVault\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Tests for authvault_get_option helper.
 */
class AuthVault_Get_Option_Test extends TestCase {

	/**
	 * Test that authvault_get_option returns default when option is not set.
	 *
	 * @return void
	 */
	public function test_authvault_get_option_returns_default_when_option_not_set() {
		Functions\when( 'get_option' )->alias( function ( $option ) {
			if ( 'authvault_settings' === $option ) {
				return array();
			}
			return null;
		} );

		$value = authvault_get_option( 'nonexistent_key', 'my_default' );
		$this->assertSame( 'my_default', $value );
	}

	/**
	 * Test that authvault_get_option returns merged default for known key when settings empty.
	 *
	 * @return void
	 */
	public function test_authvault_get_option_returns_default_for_known_key_when_empty() {
		Functions\when( 'get_option' )->alias( function ( $option, $default = null ) {
			if ( 'authvault_settings' === $option ) {
				return false;
			}
			if ( 'users_can_register' === $option ) {
				return false;
			}
			return $default;
		} );

		$value = authvault_get_option( 'custom_login_slug', 'fallback' );
		$this->assertSame( 'login', $value, 'Known key should return default from authvault_get_settings_defaults()' );
	}
}
