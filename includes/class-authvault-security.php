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
 * Handles lockout/attempts, reCAPTCHA verification, and optional login
 * attempt logging to a custom table.
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
	 * Allowed reCAPTCHA action names used by AuthVault forms.
	 *
	 * @var array<int, string>
	 */
	const RECAPTCHA_ALLOWED_ACTIONS = array( 'login', 'register', 'forgot_password' );

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
	 * @param string $identifier      wp_hash() of IP address.
	 * @param string $attempted_login  Optional. Username or email used in the failed attempt (for lockout notification).
	 * @return int New attempt count after increment.
	 */
	public function record_attempt( $identifier, $attempted_login = '' ) {
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
			if ( authvault_get_option( 'lockout_admin_email_notification', false ) ) {
				$this->send_lockout_notification_email( $duration_min, $attempted_login );
			}
		}
		return $current;
	}

	/**
	 * Send one email to the admin (or lockout_notification_email) when an IP is locked out.
	 * Only called when lockout_admin_email_notification is enabled.
	 *
	 * @param int    $duration_minutes Lockout duration in minutes (for the message).
	 * @param string $attempted_login  Optional. Username or email used in the failed attempt.
	 * @return void
	 */
	private function send_lockout_notification_email( $duration_minutes, $attempted_login = '' ) {
		$to = authvault_get_option( 'lockout_notification_email', '' );
		if ( '' === $to || ! is_email( $to ) ) {
			$to = get_option( 'admin_email', '' );
		}
		if ( '' === $to || ! is_email( $to ) ) {
			return;
		}
		$context   = $this->get_lockout_context( $attempted_login );
		$site_name = get_bloginfo( 'name' );
		$subject   = sprintf(
			/* translators: %s: site name */
			__( '[%s] Login lockout triggered', 'authvault' ),
			$site_name
		);
		$message = sprintf(
			/* translators: 1: site name, 2: lockout duration in minutes */
			__( 'An IP address has been locked out due to too many failed login attempts on %1$s. The lockout lasts %2$d minute(s).', 'authvault' ),
			$site_name,
			$duration_minutes
		);
		$message .= "\n\n" . $this->format_lockout_context_for_email( $context );
		wp_mail( $to, $subject, $message );
	}

	/**
	 * Gather all available request/context information for the locked-out party.
	 * Uses only data from the current request (no storage). Safe to use when sending lockout notifications.
	 *
	 * @param string $attempted_login Optional. Username or email they attempted to log in with.
	 * @return array Associative array with keys: ip_address, user_agent, referrer, request_uri, request_method, request_time, attempted_login, and optionally forwarded_for.
	 */
	public function get_lockout_context( $attempted_login = '' ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
			: '';
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) && is_string( $_SERVER['HTTP_USER_AGENT'] )
			? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) )
			: '';
		$referrer = isset( $_SERVER['HTTP_REFERER'] ) && is_string( $_SERVER['HTTP_REFERER'] )
			? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) )
			: '';
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) )
			: '';
		$request_method = isset( $_SERVER['REQUEST_METHOD'] ) && is_string( $_SERVER['REQUEST_METHOD'] )
			? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: '';
		$forwarded_for = '';
		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) && is_string( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$forwarded_for = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) && is_string( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$forwarded_for = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		}
		$attempted_login = is_string( $attempted_login ) ? sanitize_text_field( $attempted_login ) : '';
		return array(
			'ip_address'      => $ip,
			'user_agent'     => $user_agent,
			'referrer'       => $referrer,
			'request_uri'    => $request_uri,
			'request_method' => $request_method,
			'request_time'   => gmdate( 'Y-m-d H:i:s' ),
			'attempted_login' => $attempted_login,
			'forwarded_for'  => $forwarded_for,
		);
	}

	/**
	 * Format the lockout context array as plain text for inclusion in the notification email.
	 *
	 * @param array $context Result of get_lockout_context().
	 * @return string Multi-line plain text (no HTML).
	 */
	private function format_lockout_context_for_email( array $context ) {
		$lines = array(
			__( 'Details:', 'authvault' ),
			'- ' . __( 'IP address:', 'authvault' ) . ' ' . ( $context['ip_address'] !== '' ? $context['ip_address'] : '—' ),
			'- ' . __( 'Attempted login:', 'authvault' ) . ' ' . ( $context['attempted_login'] !== '' ? $context['attempted_login'] : '—' ),
			'- ' . __( 'Time (UTC):', 'authvault' ) . ' ' . ( isset( $context['request_time'] ) ? $context['request_time'] : '—' ),
			'- ' . __( 'Request:', 'authvault' ) . ' ' . ( isset( $context['request_method'] ) ? $context['request_method'] : '—' ) . ' ' . ( isset( $context['request_uri'] ) ? $context['request_uri'] : '' ),
			'- ' . __( 'User agent:', 'authvault' ) . ' ' . ( $context['user_agent'] !== '' ? $context['user_agent'] : '—' ),
			'- ' . __( 'Referrer:', 'authvault' ) . ' ' . ( $context['referrer'] !== '' ? $context['referrer'] : '—' ),
		);
		if ( ! empty( $context['forwarded_for'] ) ) {
			$lines[] = '- ' . __( 'X-Forwarded-For / X-Real-IP:', 'authvault' ) . ' ' . $context['forwarded_for'];
		}
		return implode( "\n", $lines );
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
	 * Verify reCAPTCHA v3 token with Google.
	 *
	 * Returns true when reCAPTCHA is disabled, or when token verification succeeds,
	 * score is above threshold, and optional action matches.
	 *
	 * @param string $token           reCAPTCHA response token from frontend.
	 * @param string $expected_action Optional. Expected action name (login/register/forgot_password).
	 * @return bool True if verification passed or reCAPTCHA disabled, false otherwise.
	 */
	public function verify_recaptcha( $token, $expected_action = '' ) {
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
		if ( '' !== $expected_action ) {
			$action = isset( $json['action'] ) ? sanitize_key( (string) $json['action'] ) : '';
			if ( ! in_array( $expected_action, self::RECAPTCHA_ALLOWED_ACTIONS, true ) || $action !== $expected_action ) {
				return false;
			}
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

		$config = array(
			'siteKey' => (string) $site_key,
			'actions' => array(
				'authvault_login'    => 'login',
				'authvault_register' => 'register',
				'authvault_reset'    => 'forgot_password',
			),
			'messages' => array(
				'verifying' => __( 'Verifying...', 'authvault' ),
				'failed'    => __( 'Verification failed. Please try again.', 'authvault' ),
			),
		);
		$inline = 'window.authvaultRecaptchaLoaded=false;';
		$inline .= 'window.authvaultRecaptchaOnload=function(){window.authvaultRecaptchaLoaded=true;};';
		$inline .= 'window.authvaultRecaptchaConfig=' . wp_json_encode( $config ) . ';';
		wp_add_inline_script( 'authvault-recaptcha', $inline, 'before' );

		self::$recaptcha_enqueued = true;
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

	/**
	 * Delete login log entries older than the configured retention period.
	 *
	 * Safe to call even when logging is disabled (it still cleans up old data).
	 *
	 * @return int Number of rows deleted.
	 */
	public static function cleanup_old_login_log_entries() {
		$days = (int) authvault_get_option( 'login_log_retention_days', 90 );
		$days = max( 1, $days );

		global $wpdb;
		$table    = self::get_log_table_name();
		$cutoff   = gmdate( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is safe.
		$deleted = $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE attempted_at < %s", $cutoff ) );
		return is_int( $deleted ) ? $deleted : 0;
	}
}
