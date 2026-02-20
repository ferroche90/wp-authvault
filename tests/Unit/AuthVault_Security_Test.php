<?php
/**
 * Unit tests for AuthVault_Security (lockout, clear_attempts).
 *
 * @package AuthVault
 */

namespace AuthVault\Tests\Unit;

use AuthVault\Tests\TestCase;
use AuthVault\AuthVault_Security;
use Brain\Monkey\Functions;

/**
 * Tests for AuthVault_Security.
 */
class AuthVault_Security_Test extends TestCase {

	/**
	 * Test that record_attempt increments attempt count and returns new count.
	 *
	 * @return void
	 */
	public function test_lockout_increments_correctly() {
		Functions\when( 'authvault_get_option' )->alias( function ( $key, $default = null ) {
			if ( 'enable_lockout' === $key ) {
				return true;
			}
			if ( 'lockout_duration_minutes' === $key ) {
				return 15;
			}
			if ( 'max_login_attempts' === $key ) {
				return 5;
			}
			return $default;
		} );
		$attempt_count = 0;
		Functions\when( 'get_transient' )->alias( function ( $key ) use ( &$attempt_count ) {
			if ( strpos( $key, AuthVault_Security::ATTEMPTS_TRANSIENT_PREFIX ) === 0 ) {
				return $attempt_count;
			}
			return false;
		} );
		Functions\when( 'set_transient' )->alias( function ( $key, $value ) use ( &$attempt_count ) {
			if ( strpos( $key, AuthVault_Security::ATTEMPTS_TRANSIENT_PREFIX ) === 0 ) {
				$attempt_count = (int) $value;
			}
			return true;
		} );
		Functions\when( 'delete_transient' )->justReturn( true );

		$security = new AuthVault_Security();
		$id       = 'hash1';

		$first = $security->record_attempt( $id );
		$this->assertSame( 1, $first );

		$second = $security->record_attempt( $id );
		$this->assertSame( 2, $second );
	}

	/**
	 * Test that after max_attempts, check_lockout returns true (user is blocked).
	 *
	 * @return void
	 */
	public function test_lockout_blocks_after_max_attempts() {
		$future_ts = time() + 900;
		Functions\when( 'authvault_get_option' )->alias( function ( $key ) {
			return ( 'enable_lockout' === $key ) ? true : null;
		} );
		Functions\when( 'get_transient' )->alias( function ( $key ) use ( $future_ts ) {
			if ( strpos( $key, AuthVault_Security::LOCKOUT_TRANSIENT_PREFIX ) === 0 ) {
				return (string) $future_ts;
			}
			return false;
		} );

		$security = new AuthVault_Security();
		$this->assertTrue( $security->check_lockout( 'hash1' ) );
	}

	/**
	 * Test that clear_attempts removes attempt transient (reset for successful login).
	 *
	 * @return void
	 */
	public function test_clear_attempts_resets() {
		Functions\expect( 'delete_transient' )
			->once()
			->with( AuthVault_Security::ATTEMPTS_TRANSIENT_PREFIX . 'hash1' )
			->andReturn( true );

		$security = new AuthVault_Security();
		$security->clear_attempts( 'hash1' );
	}
}
