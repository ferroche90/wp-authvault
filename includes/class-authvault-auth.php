<?php
/**
 * Authentication form processing: login, register, password reset, logout.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles form submission and processing for login, registration,
 * password reset request, reset confirm, and logout.
 */
class AuthVault_Auth {

	/**
	 * Nonce action for login form.
	 *
	 * @var string
	 */
	const LOGIN_NONCE_ACTION = 'authvault_login';

	/**
	 * Nonce action for register form.
	 *
	 * @var string
	 */
	const REGISTER_NONCE_ACTION = 'authvault_register';

	/**
	 * Nonce action for password reset request form.
	 *
	 * @var string
	 */
	const RESET_NONCE_ACTION = 'authvault_reset';

	/**
	 * Nonce action for password reset confirm (new password) form.
	 *
	 * @var string
	 */
	const RESET_CONFIRM_NONCE_ACTION = 'authvault_reset_confirm';

	/**
	 * Inline errors for the password reset confirm form (not redirected).
	 *
	 * @var array<array{type: string, text: string}>
	 */
	private static $confirm_errors = array();

	/**
	 * Get confirm form inline errors stored during this request.
	 *
	 * @return array<array{type: string, text: string}>
	 */
	public static function get_confirm_errors() {
		return self::$confirm_errors;
	}

	/**
	 * Dispatch to the appropriate form handler on init (priority 1).
	 *
	 * @return void
	 */
	public function maybe_process_forms() {
		// Logout: GET with action=logout and WordPress log-out nonce.
		if ( isset( $_GET['action'] ) && 'logout' === sanitize_text_field( wp_unslash( $_GET['action'] ) ) ) {
			$this->process_logout();
			return;
		}

		if ( ! isset( $_POST ) || ! is_array( $_POST ) || count( $_POST ) === 0 ) {
			return;
		}

		if ( $this->is_login_post() ) {
			$this->process_login();
			return;
		}
		if ( $this->is_register_post() ) {
			$this->process_register();
			return;
		}
		if ( $this->is_reset_request_post() ) {
			$this->process_reset_request();
			return;
		}
		if ( $this->is_reset_confirm_post() ) {
			$this->process_reset_confirm();
			return;
		}
	}

	/**
	 * Whether the request is a login form submission.
	 *
	 * @return bool
	 */
	private function is_login_post() {
		return isset( $_POST['authvault_login_nonce'] ) && is_string( $_POST['authvault_login_nonce'] );
	}

	/**
	 * Whether the request is a register form submission.
	 *
	 * @return bool
	 */
	private function is_register_post() {
		return isset( $_POST['authvault_register_nonce'] ) && is_string( $_POST['authvault_register_nonce'] );
	}

	/**
	 * Whether the request is a password reset request form submission.
	 *
	 * @return bool
	 */
	private function is_reset_request_post() {
		return isset( $_POST['authvault_reset_nonce'] ) && is_string( $_POST['authvault_reset_nonce'] );
	}

	/**
	 * Whether the request is a password reset confirm form submission.
	 *
	 * @return bool
	 */
	private function is_reset_confirm_post() {
		return isset( $_POST['authvault_reset_confirm_nonce'] ) && is_string( $_POST['authvault_reset_confirm_nonce'] );
	}

	/**
	 * Get hashed IP for transient keys (no raw IP stored).
	 *
	 * @return string
	 */
	private function get_ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) && is_string( $_SERVER['REMOTE_ADDR'] )
			? wp_unslash( $_SERVER['REMOTE_ADDR'] )
			: '';
		return wp_hash( $ip );
	}

	/**
	 * Process login form: verify nonce, optional lockout, wp_signon, redirect or set error.
	 *
	 * @return void
	 */
	public function process_login() {
		$nonce = isset( $_POST['authvault_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['authvault_login_nonce'] ) ) : '';

		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::LOGIN_NONCE_ACTION ) ) {
			$this->redirect_login_with_error();
			return;
		}

		$security = AuthVault_Security::get_instance();
		
		if ( ! $security->verify_recaptcha( isset( $_POST['g-recaptcha-response'] ) ? sanitize_text_field( wp_unslash( $_POST['g-recaptcha-response'] ) ) : '' ) ) {
			$this->redirect_login_with_error();
			return;
		}

		$ip_hash = $this->get_ip_hash();
		if ( $security->check_lockout( $ip_hash ) ) {
			$mins = $security->get_lockout_remaining_minutes( $ip_hash );
			$this->redirect_login_with_error( $mins );
			return;
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$password = isset( $_POST['password'] ) && is_string( $_POST['password'] ) ? wp_unslash( $_POST['password'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- passwords must not be sanitised; wp_signon hashes internally.

		if ( '' === $username || '' === $password ) {
			$this->redirect_login_with_error();
			return;
		}

		$user = wp_signon(
			array(
				'user_login'    => $username,
				'user_password' => $password,
				'remember'      => isset( $_POST['remember'] ),
			),
			is_ssl()
		);

		if ( is_wp_error( $user ) ) {
			$security->record_attempt( $ip_hash );
			$security->log_login_attempt( $username, $ip_hash, 'fail' );
			$this->redirect_login_with_error();
			return;
		}

		$security->clear_attempts( $ip_hash );
		$security->log_login_attempt( $username, $ip_hash, 'success' );

		$redirect_to = isset( $_POST['redirect_to'] ) ? sanitize_text_field( wp_unslash( $_POST['redirect_to'] ) ) : '';
		if ( '' !== $redirect_to && wp_http_validate_url( $redirect_to ) ) {
			wp_safe_redirect( $redirect_to );
			exit;
		}

		$page_id = (int) authvault_get_option( 'login_redirect_page_id', 0 );
		if ( 0 < $page_id ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && '' !== $url ) {
				wp_safe_redirect( $url );
				exit;
			}
		}

		wp_safe_redirect( home_url() );
		exit;
	}

	/**
	 * Redirect to login page with generic error query arg (no user enumeration).
	 *
	 * @param int $lockout_remaining_minutes Optional. When locked out, remaining minutes to show in message.
	 * @return void
	 */
	private function redirect_login_with_error( $lockout_remaining_minutes = 0 ) {
		$login_page_id = (int) authvault_get_option( 'login_page_id', 0 );
		$url           = ( 0 < $login_page_id ) ? get_permalink( $login_page_id ) : home_url();
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url();
		}
		$url = add_query_arg( 'authvault_error', '1', $url );
		if ( 0 < $lockout_remaining_minutes ) {
			$url = add_query_arg( 'authvault_lockout_minutes', (int) $lockout_remaining_minutes, $url );
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Process registration form: verify nonce, sanitize, register_new_user, redirect.
	 *
	 * @return void
	 */
	public function process_register() {
		$nonce = isset( $_POST['authvault_register_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['authvault_register_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::REGISTER_NONCE_ACTION ) ) {
			$this->redirect_register_with_error();
			return;
		}

		if ( ! get_option( 'users_can_register', false ) ) {
			$this->redirect_register_with_error();
			return;
		}

		$username = isset( $_POST['username'] ) ? sanitize_user( wp_unslash( $_POST['username'] ) ) : '';
		$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';

		if ( '' === $username || '' === $email ) {
			$this->redirect_register_with_error();
			return;
		}

		$user_id = register_new_user( $username, $email );
		if ( is_wp_error( $user_id ) ) {
			$this->redirect_register_with_error();
			return;
		}

		$default_role = authvault_get_option( 'default_role', 'subscriber' );
		$editable     = function_exists( 'get_editable_roles' )
			? array_keys( \get_editable_roles() )
			: array_keys( \wp_roles()->roles );
		if ( in_array( $default_role, $editable, true ) ) {
			$user = get_userdata( $user_id );
			if ( $user instanceof \WP_User ) {
				$user->set_role( $default_role );
			}
		}

		$login_page_id = (int) authvault_get_option( 'login_page_id', 0 );
		$url           = ( 0 < $login_page_id ) ? get_permalink( $login_page_id ) : home_url();
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url();
		}
		$url = add_query_arg( 'registered', '1', $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirect to register page with generic error query arg (no enumeration).
	 *
	 * @return void
	 */
	private function redirect_register_with_error() {
		$register_page_id = (int) authvault_get_option( 'register_page_id', 0 );
		$url              = ( 0 < $register_page_id ) ? get_permalink( $register_page_id ) : home_url();
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url();
		}
		$url = add_query_arg( 'authvault_register_error', '1', $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Process password reset request: verify nonce, sanitize, retrieve_password(), generic success.
	 *
	 * @return void
	 */
	public function process_reset_request() {
		$nonce = isset( $_POST['authvault_reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['authvault_reset_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::RESET_NONCE_ACTION ) ) {
			$this->redirect_reset_request_with_message();
			return;
		}

		$login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';
		if ( '' === $login ) {
			$this->redirect_reset_request_with_message();
			return;
		}

		if ( ! $this->check_reset_rate_limit() ) {
			$this->redirect_reset_request_with_message();
			return;
		}

		retrieve_password( $login );
		$this->redirect_reset_request_with_message();
		return;
	}

	/**
	 * Check IP-based rate limit for password reset requests.
	 *
	 * @return bool True if within limits, false if rate-limited.
	 */
	private function check_reset_rate_limit() {
		$ip_hash = $this->get_ip_hash();
		if ( '' === $ip_hash ) {
			return true;
		}
		$max    = (int) authvault_get_option( 'reset_rate_limit_max', 5 );
		$max    = max( 1, $max );
		$window = (int) authvault_get_option( 'reset_rate_limit_window_minutes', 15 );
		$window = max( 1, $window ) * 60;

		$key   = 'authvault_rrl_' . $ip_hash;
		$count = (int) get_transient( $key );
		if ( $count >= $max ) {
			return false;
		}
		set_transient( $key, $count + 1, $window );
		return true;
	}

	/**
	 * Redirect to password reset page with generic success query arg.
	 *
	 * @return void
	 */
	private function redirect_reset_request_with_message() {
		$reset_page_id = (int) authvault_get_option( 'password_reset_page_id', 0 );
		$url           = ( 0 < $reset_page_id ) ? get_permalink( $reset_page_id ) : home_url();
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url();
		}
		$url = add_query_arg( 'authvault_reset_sent', '1', $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Process password reset confirm (new password): validate key/login, nonce, reset_password(), redirect.
	 *
	 * Unrecoverable errors (bad nonce, expired key) redirect. Password validation
	 * errors are stored in self::$confirm_errors so the form re-renders with feedback.
	 *
	 * @return void
	 */
	public function process_reset_confirm() {
		$nonce = isset( $_POST['authvault_reset_confirm_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['authvault_reset_confirm_nonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::RESET_CONFIRM_NONCE_ACTION ) ) {
			$this->redirect_reset_confirm_error( 'invalidkey' );
			return;
		}

		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : ( isset( $_POST['rp_key'] ) ? sanitize_text_field( wp_unslash( $_POST['rp_key'] ) ) : '' );
		$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : ( isset( $_POST['rp_login'] ) ? sanitize_user( wp_unslash( $_POST['rp_login'] ) ) : '' );
		if ( '' === $key || '' === $login ) {
			$this->redirect_reset_confirm_error( 'invalidkey' );
			return;
		}

		$user = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			$this->redirect_reset_confirm_error( 'invalidkey' );
			return;
		}

		$pass1 = isset( $_POST['pass1'] ) ? wp_unslash( $_POST['pass1'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$pass2 = isset( $_POST['pass2'] ) ? wp_unslash( $_POST['pass2'] ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

		if ( ! is_string( $pass1 ) || '' === $pass1 ) {
			self::$confirm_errors[] = array(
				'type' => 'error',
				'text' => authvault_get_message( 'msg_confirm_password_empty', __( 'Please enter your new password.', 'authvault' ) ),
			);
			return;
		}

		if ( ! is_string( $pass2 ) || $pass1 !== $pass2 ) {
			self::$confirm_errors[] = array(
				'type' => 'error',
				'text' => authvault_get_message( 'msg_confirm_password_mismatch', __( 'Passwords do not match. Please try again.', 'authvault' ) ),
			);
			return;
		}

		$min_length = (int) authvault_get_option( 'min_password_length', 8 );
		if ( strlen( $pass1 ) < $min_length ) {
			self::$confirm_errors[] = array(
				'type' => 'error',
				'text' => sprintf(
					authvault_get_message( 'msg_confirm_password_weak', __( 'Password must be at least %d characters long.', 'authvault' ) ),
					$min_length
				),
			);
			return;
		}

		$allow_weak = (bool) authvault_get_option( 'allow_weak_passwords', false );
		if ( ! $allow_weak ) {
			$strength = isset( $_POST['authvault_password_strength'] ) ? (int) $_POST['authvault_password_strength'] : -1;
			if ( $strength < 3 ) {
				self::$confirm_errors[] = array(
					'type' => 'error',
					'text' => authvault_get_message( 'msg_confirm_password_too_weak', __( 'Please choose a stronger password. Use a mix of upper and lower case letters, numbers, and symbols.', 'authvault' ) ),
				);
				return;
			}
		}

		reset_password( $user, $pass1 );

		$login_page_id = (int) authvault_get_option( 'login_page_id', 0 );
		$url            = ( 0 < $login_page_id ) ? get_permalink( $login_page_id ) : home_url();
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url();
		}
		$url = add_query_arg( 'password_reset', '1', $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Redirect to password reset (request) page with error=invalidkey.
	 *
	 * @param string $error Error code (e.g. invalidkey).
	 * @return void
	 */
	private function redirect_reset_confirm_error( $error ) {
		$reset_page_id = (int) authvault_get_option( 'password_reset_page_id', 0 );
		$url           = ( 0 < $reset_page_id ) ? get_permalink( $reset_page_id ) : home_url();
		if ( ! is_string( $url ) || '' === $url ) {
			$url = home_url();
		}
		$url = add_query_arg( 'error', $error, $url );
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Process logout: verify nonce, wp_logout(), redirect to configured logout page.
	 *
	 * @return void
	 */
	public function process_logout() {
		if ( ! is_user_logged_in() ) {
			$this->redirect_after_logout();
			return;
		}
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'log-out' ) ) {
			$this->redirect_after_logout();
			return;
		}
		wp_logout();
		$this->redirect_after_logout();
		return;
	}

	/**
	 * Redirect to configured logout redirect page or home.
	 *
	 * @return void
	 */
	private function redirect_after_logout() {
		$page_id = (int) authvault_get_option( 'logout_redirect_page_id', 0 );
		if ( 0 < $page_id ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && '' !== $url ) {
				wp_safe_redirect( $url );
				exit;
			}
		}
		wp_safe_redirect( home_url() );
		exit;
	}

	/**
	 * On the password reset confirm page, validate GET key and login; redirect if invalid/expired.
	 *
	 * @return void
	 */
	public function validate_reset_key_on_confirm_page() {
		$confirm_page_id = (int) authvault_get_option( 'password_reset_confirm_page_id', 0 );
		if ( 0 >= $confirm_page_id ) {
			return;
		}
		if ( get_queried_object_id() !== $confirm_page_id ) {
			return;
		}
		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ) ) : '';
		if ( '' === $key || '' === $login ) {
			return;
		}
		$user = check_password_reset_key( $key, $login );
		if ( is_wp_error( $user ) ) {
			$this->redirect_reset_confirm_error( 'invalidkey' );
			return;
		}
	}
}
