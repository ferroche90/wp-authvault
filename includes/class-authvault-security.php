<?php
/**
 * Security layer: brute force protection, reCAPTCHA v3, URL hardening, login logging.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles lockout/attempts, reCAPTCHA verification, site_url wp-login replacement,
 * and optional login attempt logging to a custom table.
 */
class AuthVault_Security {

	/**
	 * Transient key prefix for attempt count (suffix: identifier / IP hash).
	 *
	 * @var string
	 */
	const ATTEMPTS_TRANSIENT_PREFIX = 'authvault_attempts_';

	/**
	 * Transient key prefix for lockout (suffix: identifier).
	 *
	 * @var string
	 */
	const LOCKOUT_TRANSIENT_PREFIX = 'authvault_lockout_';

	/**
	 * reCAPTCHA siteverify API URL.
	 *
	 * @var string
	 */
	const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

	/**
	 * Fallback minimum score for reCAPTCHA v3 (used when the option is missing).
	 *
	 * @var float
	 */
	const RECAPTCHA_MIN_SCORE_FALLBACK = 0.5;

	/**
	 * Login log table name (without prefix).
	 *
	 * @var string
	 */
	const LOG_TABLE_NAME = 'authvault_login_log';

	/**
	 * Whether reCAPTCHA script has been enqueued this request.
	 *
	 * @var bool
	 */
	private static $recaptcha_enqueued = false;

	/**
	 * Singleton instance.
	 *
	 * @var AuthVault_Security|null
	 */
	private static $instance = null;

	/**
	 * Get the singleton instance.
	 *
	 * @return AuthVault_Security
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor: register filters and actions.
	 */
	public function __construct() {
		add_filter( 'site_url', array( $this, 'filter_site_url_wp_login' ), 10, 4 );
	}

	/**
	 * Check if the given identifier (IP hash) is currently locked out.
	 *
	 * Only runs when enable_lockout setting is on.
	 *
	 * @param string $identifier wp_hash() of IP address.
	 * @return bool True if locked out, false otherwise.
	 */
	public function check_lockout( $identifier ) {
		if ( ! authvault_get_option( 'enable_lockout', false ) ) {
			return false;
		}
		if ( '' === $identifier || ! is_string( $identifier ) ) {
			return false;
		}
		$key = self::LOCKOUT_TRANSIENT_PREFIX . $identifier;
		$val = get_transient( $key );
		if ( false === $val ) {
			return false;
		}
		$expiry = is_numeric( $val ) ? (int) $val : 0;
		return time() < $expiry;
	}

	/**
	 * Get remaining lockout time in minutes for the identifier.
	 *
	 * @param string $identifier wp_hash() of IP address.
	 * @return int Remaining minutes (0 if not locked out).
	 */
	public function get_lockout_remaining_minutes( $identifier ) {
		if ( '' === $identifier || ! is_string( $identifier ) ) {
			return 0;
		}
		$key    = self::LOCKOUT_TRANSIENT_PREFIX . $identifier;
		$expiry = get_transient( $key );
		if ( false === $expiry || ! is_numeric( $expiry ) ) {
			return 0;
		}
		$remaining = (int) $expiry - time();
		return $remaining > 0 ? (int) ceil( $remaining / 60 ) : 0;
	}

	/**
	 * Record a failed login attempt for the identifier. Sets lockout if max attempts exceeded.
	 *
	 * Only runs when enable_lockout setting is on.
	 *
	 * @param string $identifier wp_hash() of IP address.
	 * @return int New attempt count after increment.
	 */
	public function record_attempt( $identifier ) {
		if ( ! authvault_get_option( 'enable_lockout', false ) ) {
			return 0;
		}
		if ( '' === $identifier || ! is_string( $identifier ) ) {
			return 0;
		}
		$duration_min = (int) authvault_get_option( 'lockout_duration_minutes', 15 );
		$duration_min = max( 1, min( 1440, $duration_min ) );
		$duration_sec = $duration_min * 60;

		$attempt_key = self::ATTEMPTS_TRANSIENT_PREFIX . $identifier;
		$current     = (int) get_transient( $attempt_key );
		$current++;
		set_transient( $attempt_key, $current, $duration_sec );

		$max = (int) authvault_get_option( 'max_login_attempts', 5 );
		$max = max( 1, min( 100, $max ) );
		if ( $current >= $max ) {
			$lockout_key = self::LOCKOUT_TRANSIENT_PREFIX . $identifier;
			$expiry_ts   = time() + $duration_sec;
			set_transient( $lockout_key, $expiry_ts, $duration_sec );
		}
		return $current;
	}

	/**
	 * Clear attempt count (and do not clear lockout) for the identifier.
	 *
	 * Call on successful login.
	 *
	 * @param string $identifier wp_hash() of IP address.
	 * @return void
	 */
	public function clear_attempts( $identifier ) {
		if ( '' === $identifier || ! is_string( $identifier ) ) {
			return;
		}
		$key = self::ATTEMPTS_TRANSIENT_PREFIX . $identifier;
		delete_transient( $key );
	}

	/**
	 * Verify reCAPTCHA v3 token with Google. Returns true if disabled or verification passes with score >= 0.5.
	 *
	 * @param string $token reCAPTCHA response token from frontend.
	 * @return bool True if verification passed or reCAPTCHA disabled, false otherwise.
	 */
	public function verify_recaptcha( $token ) {
		if ( ! authvault_get_option( 'recaptcha_enabled', false ) ) {
			return true;
		}
		$secret = authvault_get_option( 'recaptcha_secret_key', '' );
		if ( '' === $secret || ! is_string( $token ) || '' === $token ) {
			return false;
		}
		$response = wp_remote_post(
			self::RECAPTCHA_VERIFY_URL,
			array(
				'body' => array(
					'secret'   => $secret,
					'response' => $token,
				),
				'timeout' => 10,
			)
		);
		if ( is_wp_error( $response ) ) {
			return false;
		}
		$code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $code ) {
			return false;
		}
		$body = wp_remote_retrieve_body( $response );
		$json = json_decode( $body, true );
		if ( ! is_array( $json ) || empty( $json['success'] ) ) {
			return false;
		}
		$score     = isset( $json['score'] ) && is_numeric( $json['score'] ) ? (float) $json['score'] : 0.0;
		$min_score = (float) authvault_get_option( 'recaptcha_min_score', self::RECAPTCHA_MIN_SCORE_FALLBACK );
		return $score >= $min_score;
	}

	/**
	 * Enqueue reCAPTCHA v3 script when AuthVault forms are present. Safe to call multiple times.
	 *
	 * Only enqueues if reCAPTCHA is enabled and site key is set. Script is loaded only once per request.
	 *
	 * @return void
	 */
	public static function maybe_enqueue_recaptcha_script() {
		if ( self::$recaptcha_enqueued ) {
			return;
		}
		if ( ! authvault_get_option( 'recaptcha_enabled', false ) ) {
			return;
		}
		$site_key = authvault_get_option( 'recaptcha_site_key', '' );
		if ( '' === $site_key ) {
			return;
		}
		$src = add_query_arg(
			array(
				'render' => $site_key,
				'onload' => 'authvaultRecaptchaOnload',
			),
			'https://www.google.com/recaptcha/api.js'
		);
		wp_enqueue_script(
			'authvault-recaptcha',
			$src,
			array(),
			AUTHVAULT_VERSION,
			true
		);
		self::$recaptcha_enqueued = true;
	}

	/**
	 * Filter site_url to replace wp-login.php with custom login page URL when URL hiding is enabled.
	 *
	 * Excludes REST and admin-ajax.php from replacement.
	 *
	 * @param string $url     Full URL.
	 * @param string $path   Path (e.g. wp-login.php).
	 * @param string $scheme Scheme.
	 * @param string|null $blog_id Blog ID (for multisite).
	 * @return string
	 */
	public function filter_site_url_wp_login( $url, $path, $scheme, $blog_id = null ) {
		if ( ! is_string( $path ) ) {
			return $url;
		}
		if ( strpos( $path, 'wp-login.php' ) === false ) {
			return $url;
		}

		// Password reset confirm links must always rewrite to the confirm page
		// (regardless of whether login URL hiding is enabled), because on
		// single-site installs the network_site_url filter never fires.
		if ( strpos( $path, 'action=rp' ) !== false || strpos( $path, 'action=resetpass' ) !== false ) {
			$confirm_page_id = (int) authvault_get_option( 'password_reset_confirm_page_id', 0 );
			if ( 0 < $confirm_page_id ) {
				$confirm_url = get_permalink( $confirm_page_id );
				if ( is_string( $confirm_url ) && '' !== $confirm_url ) {
					$query_string = wp_parse_url( $path, PHP_URL_QUERY );
					if ( is_string( $query_string ) && '' !== $query_string ) {
						$query_vars = array();
						wp_parse_str( $query_string, $query_vars );
						if ( ! empty( $query_vars['key'] ) && ! empty( $query_vars['login'] ) ) {
							return add_query_arg(
								array(
									'key'   => $query_vars['key'],
									'login' => $query_vars['login'],
								),
								$confirm_url
							);
						}
					}
				}
			}
			return $url;
		}

		if ( ! authvault_get_option( 'enable_login_url_hiding', false ) ) {
			return $url;
		}
		if ( strpos( $path, 'admin-ajax.php' ) !== false ) {
			return $url;
		}
		$parsed = wp_parse_url( $url );
		if ( ! empty( $parsed['path'] ) && strpos( $parsed['path'], 'wp-json' ) !== false ) {
			return $url;
		}
		$page_id = (int) authvault_get_option( 'login_page_id', 0 );
		if ( 0 >= $page_id ) {
			return $url;
		}
		$login_url = get_permalink( $page_id );
		if ( ! is_string( $login_url ) || '' === $login_url ) {
			return $url;
		}
		return $login_url;
	}

	/**
	 * Log a login attempt to the authvault_login_log table (if logging is enabled).
	 *
	 * Never stores raw IP; only ip_hash (wp_hash of IP).
	 *
	 * @param string $user_login Username or email attempted.
	 * @param string $ip_hash    wp_hash() of REMOTE_ADDR.
	 * @param string $status     'success' or 'fail'.
	 * @return bool True on success, false on failure or when logging disabled.
	 */
	public function log_login_attempt( $user_login, $ip_hash, $status ) {
		if ( ! authvault_get_option( 'enable_login_log', false ) ) {
			return false;
		}
		if ( '' === $ip_hash || ! is_string( $ip_hash ) ) {
			return false;
		}
		if ( 'success' !== $status && 'fail' !== $status ) {
			return false;
		}
		global $wpdb;
		$table = $wpdb->prefix . self::LOG_TABLE_NAME;
		$result = $wpdb->insert(
			$table,
			array(
				'user_login'   => is_string( $user_login ) ? $user_login : '',
				'ip_hash'      => $ip_hash,
				'status'       => $status,
				'attempted_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s' )
		);
		return false !== $result;
	}

	/**
	 * Get the login log table name (with prefix).
	 *
	 * @return string
	 */
	public static function get_log_table_name() {
		global $wpdb;
		return $wpdb->prefix . self::LOG_TABLE_NAME;
	}
}
