<?php
/**
 * URL routing: hide wp-login.php, custom login slug, and override auth URLs.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles login URL hiding, rewrite rules for the custom login slug,
 * filters for login/logout/register/lostpassword URLs, and redirects.
 */
class AuthVault_Router {

	/**
	 * Query var used for the custom login slug rewrite rule.
	 *
	 * @var string
	 */
	const LOGIN_QUERY_VAR = 'authvault_login';

	/**
	 * Intercept wp-login.php and wp-admin when URL hiding is enabled.
	 * Runs early on init.
	 *
	 * @return void
	 */
	public function intercept_blocked_urls() {
		if ( ! authvault_get_option( 'enable_login_url_hiding', false ) ) {
			return;
		}

		if ( $this->is_allowed_request() ) {
			return;
		}

		if ( ! $this->is_blocked_login_or_admin_request() ) {
			return;
		}

		$this->apply_blocked_url_behavior();
	}

	/**
	 * Whether the current request should be allowed (AJAX, cron, etc.).
	 *
	 * @return bool
	 */
	private function is_allowed_request() {
		if ( wp_doing_cron() ) {
			return true;
		}
		if ( wp_doing_ajax() ) {
			return true;
		}
		$uri = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] )
			? wp_unslash( $_SERVER['REQUEST_URI'] )
			: '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( '' !== $path && strpos( $path, 'admin-ajax.php' ) !== false ) {
			return true;
		}
		$slug = authvault_get_option( 'custom_login_slug', 'login' );
		if ( '' !== $slug && $this->request_path_matches_slug( $path, $slug ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the request path is for wp-login.php or wp-admin (non-logged-in).
	 *
	 * @return bool
	 */
	private function is_blocked_login_or_admin_request() {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) && is_string( $_SERVER['REQUEST_URI'] )
			? wp_unslash( $_SERVER['REQUEST_URI'] )
			: '';
		$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
		if ( '' === $path ) {
			return false;
		}
		$path = trim( $path, '/' );
		$path_lower = strtolower( $path );
		if ( 'wp-login.php' === $path_lower || strpos( $path_lower, 'wp-login.php' ) !== false ) {
			return true;
		}
		if ( is_user_logged_in() ) {
			return false;
		}
		if ( 'wp-admin' === $path_lower || strpos( $path_lower, 'wp-admin/' ) === 0 ) {
			return true;
		}
		return false;
	}

	/**
	 * Apply 404, home, or custom redirect for blocked wp-login/wp-admin access.
	 *
	 * @return void
	 */
	private function apply_blocked_url_behavior() {
		$behavior = authvault_get_option( 'wp_login_access_behavior', '404' );
		
		if ( '404' === $behavior ) {
			global $wp_query;
			status_header( 404 );
			nocache_headers();
			$wp_query->set_404();
			$template = get_query_template( '404' );
			
			if ( is_string( $template ) && '' !== $template && is_readable( $template ) ) {
				include $template;
			} else {
				// No theme 404 template: output minimal 404 with no WordPress-specific message.
				header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
				echo '<!DOCTYPE html><html><head><meta charset="' . esc_attr( get_bloginfo( 'charset' ) ) . '"><title>404</title></head><body><h1>404</h1><p>Page not found.</p></body></html>';
			}
			
			exit;
		}
		if ( 'home' === $behavior ) {
			wp_safe_redirect( home_url() );
			exit;
		}
		if ( 'page' === $behavior ) {
			$page_id = (int) authvault_get_option( 'wp_login_redirect_page_id', 0 );
			$url    = ( 0 < $page_id ) ? get_permalink( $page_id ) : home_url();
			if ( is_string( $url ) && '' !== $url ) {
				wp_safe_redirect( $url );
				exit;
			}
			wp_safe_redirect( home_url() );
			exit;
		}
		wp_safe_redirect( home_url() );
		exit;
	}

	/**
	 * Register rewrite rule and query var for the custom login slug.
	 *
	 * @return void
	 */
	public function add_rewrite_rules() {
		$slug = authvault_get_option( 'custom_login_slug', 'login' );
		if ( '' === $slug ) {
			return;
		}
		$slug_esc = preg_quote( $slug, '#' );
		add_rewrite_rule( '^' . $slug_esc . '/?$', 'index.php?' . self::LOGIN_QUERY_VAR . '=1', 'top' );
	}

	/**
	 * Add the custom login slug rewrite rule and flush rules.
	 * Call from activator so the rule is available after activation.
	 *
	 * @return void
	 */
	public static function flush_rewrite_rules() {
		$slug = authvault_get_option( 'custom_login_slug', 'login' );
		if ( '' !== $slug ) {
			$slug_esc = preg_quote( $slug, '#' );
			add_rewrite_rule( '^' . $slug_esc . '/?$', 'index.php?' . self::LOGIN_QUERY_VAR . '=1', 'top' );
		}
		flush_rewrite_rules();
	}

	/**
	 * Add our query var to the allowed list.
	 *
	 * @param array<string> $vars Existing query vars.
	 * @return array<string>
	 */
	public function add_query_vars( $vars ) {
		if ( ! is_array( $vars ) ) {
			$vars = array();
		}
		$vars[] = self::LOGIN_QUERY_VAR;
		return $vars;
	}

	/**
	 * Redirect custom login slug request to the assigned login page URL.
	 *
	 * @return void
	 */
	public function redirect_custom_login_slug() {
		if ( ! get_query_var( self::LOGIN_QUERY_VAR, false ) ) {
			return;
		}
		$page_id = (int) authvault_get_option( 'login_page_id', 0 );
		if ( 0 >= $page_id ) {
			return;
		}
		$url = get_permalink( $page_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}
		wp_safe_redirect( $url );
		exit;
	}

	/**
	 * Check if the request path matches the given slug (leading segment or exact).
	 *
	 * @param string $path Request path (no leading/trailing slashes).
	 * @param string $slug Custom login slug.
	 * @return bool
	 */
	private function request_path_matches_slug( $path, $slug ) {
		if ( '' === $slug ) {
			return false;
		}
		$path = trim( $path, '/' );
		if ( $slug === $path ) {
			return true;
		}
		return strpos( $path, $slug . '/' ) === 0;
	}

	/**
	 * Filter login_url to point to the AuthVault login page.
	 *
	 * @param string $login_url    Default login URL.
	 * @param string $redirect     Redirect URL after login.
	 * @param bool   $force_reauth Whether to force reauth.
	 * @return string
	 */
	public function filter_login_url( $login_url, $redirect, $force_reauth ) {
		$page_id = (int) authvault_get_option( 'login_page_id', 0 );
		if ( 0 >= $page_id ) {
			return $login_url;
		}
		$url = get_permalink( $page_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return $login_url;
		}
		if ( '' !== $redirect && wp_http_validate_url( $redirect ) ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );
		}
		return $url;
	}

	/**
	 * Filter logout_url to point to login page (or home) with action=logout and nonce.
	 * When URL hiding is on, wp-login.php is blocked; AuthVault_Auth handles logout on our URL.
	 *
	 * @param string $logout_url Default logout URL (wp-login.php).
	 * @param string $redirect   Redirect URL after logout (unused; Auth class uses settings).
	 * @return string
	 */
	public function filter_logout_url( $logout_url, $redirect ) {
		$page_id = (int) authvault_get_option( 'login_page_id', 0 );
		$base    = ( 0 < $page_id ) ? get_permalink( $page_id ) : home_url();
		if ( ! is_string( $base ) || '' === $base ) {
			$base = home_url();
		}
		return add_query_arg(
			array(
				'action'   => 'logout',
				'_wpnonce' => wp_create_nonce( 'log-out' ),
			),
			$base
		);
	}

	/**
	 * Get the URL to redirect to after logout (saved page or home).
	 *
	 * @return string
	 */
	private function get_logout_redirect_url() {
		$page_id = (int) authvault_get_option( 'logout_redirect_page_id', 0 );
		if ( 0 < $page_id ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}
		return home_url();
	}

	/**
	 * Filter register_url to point to the AuthVault register page.
	 *
	 * @param string $register_url Default registration URL.
	 * @return string
	 */
	public function filter_register_url( $register_url ) {
		$page_id = (int) authvault_get_option( 'register_page_id', 0 );
		if ( 0 >= $page_id ) {
			return $register_url;
		}
		$url = get_permalink( $page_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return $register_url;
		}
		return $url;
	}

	/**
	 * Filter lostpassword_url to point to the AuthVault password reset page.
	 *
	 * @param string $lostpassword_url Default lost password URL.
	 * @param string $redirect         Redirect URL after reset.
	 * @return string
	 */
	public function filter_lostpassword_url( $lostpassword_url, $redirect ) {
		$page_id = (int) authvault_get_option( 'password_reset_page_id', 0 );
		if ( 0 >= $page_id ) {
			return $lostpassword_url;
		}
		$url = get_permalink( $page_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return $lostpassword_url;
		}
		if ( '' !== $redirect && wp_http_validate_url( $redirect ) ) {
			$url = add_query_arg( 'redirect_to', urlencode( $redirect ), $url );
		}
		return $url;
	}

	/**
	 * Filter network_site_url for wp-login.php to point to AuthVault pages.
	 * - action=rp (reset password confirm): use Password Reset Confirm page with key and login.
	 * - Otherwise: use Login page.
	 *
	 * @param string $url    Full URL.
	 * @param string $path   Path (e.g. wp-login.php or wp-login.php?action=rp&key=...&login=...).
	 * @param string $scheme Scheme.
	 * @return string
	 */
	public function filter_network_site_url( $url, $path, $scheme ) {
		if ( 'wp-login.php' !== $path && strpos( $path, 'wp-login.php' ) !== 0 ) {
			return $url;
		}

		// Password reset confirm link (from lost password email): point to confirm page with key and login.
		if ( strpos( $path, 'action=rp' ) !== false ) {
			$confirm_page_id = (int) authvault_get_option( 'password_reset_confirm_page_id', 0 );
			if ( 0 < $confirm_page_id ) {
				$confirm_url = get_permalink( $confirm_page_id );
				if ( is_string( $confirm_url ) && '' !== $confirm_url ) {
					$query = parse_url( $path, PHP_URL_QUERY );
					if ( is_string( $query ) && '' !== $query ) {
						$query_vars = array();
						wp_parse_str( $query, $query_vars );
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
			// No confirm page or missing key/login: return original URL (do not strip query).
			return $url;
		}

		// Login, logout, etc.: point to AuthVault login page.
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
	 * Filter login_redirect to send user to saved login redirect page or home.
	 *
	 * @param string        $redirect_to           Default redirect URL.
	 * @param string        $requested_redirect_to redirect_to from request (if valid).
	 * @param \WP_User|false $user                  Logged-in user or false.
	 * @return string
	 */
	public function filter_login_redirect( $redirect_to, $requested_redirect_to, $user ) {
		if ( '' !== $requested_redirect_to && wp_http_validate_url( $requested_redirect_to ) ) {
			return $requested_redirect_to;
		}
		$page_id = (int) authvault_get_option( 'login_redirect_page_id', 0 );
		if ( 0 < $page_id ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && '' !== $url ) {
				return $url;
			}
		}
		return home_url();
	}

	/**
	 * Redirect logged-in users away from login and register pages.
	 *
	 * The destination is controlled by the logged_in_redirect_behavior option:
	 *  - 'home'      — site home page
	 *  - 'dashboard' — WordPress admin dashboard (default)
	 *  - 'page'      — a specific page (logged_in_redirect_page_id)
	 *
	 * @return void
	 */
	public function protect_auth_pages() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$login_page_id    = (int) authvault_get_option( 'login_page_id', 0 );
		$register_page_id = (int) authvault_get_option( 'register_page_id', 0 );

		if ( 0 >= $login_page_id && 0 >= $register_page_id ) {
			return;
		}

		$elementor_preview = isset( $_GET['elementor-preview'] ) ? sanitize_text_field( wp_unslash( $_GET['elementor-preview'] ) ) : '';

		if ( current_user_can( 'edit_pages' ) && '' !== $elementor_preview ) {
			return;
		}

		$current_page_id = get_queried_object_id();

		if ( 0 >= $current_page_id ) {
			return;
		}

		if ( $current_page_id !== $login_page_id && $current_page_id !== $register_page_id ) {
			return;
		}

		$behavior     = authvault_get_option( 'logged_in_redirect_behavior', 'dashboard' );
		$redirect_url = home_url();

		if ( 'dashboard' === $behavior ) {
			$redirect_url = admin_url();
		} elseif ( 'page' === $behavior ) {
			$page_id = (int) authvault_get_option( 'logged_in_redirect_page_id', 0 );
			if ( 0 < $page_id ) {
				$url = get_permalink( $page_id );
				if ( is_string( $url ) && '' !== $url ) {
					$redirect_url = $url;
				}
			}
		}

		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Build a safe redirect_to URL from trusted components (home + current path).
	 * Never use raw REQUEST_URI for redirect_to; use this or wp_safe_redirect only.
	 *
	 * @param string $path Optional. Path to append (e.g. from $wp->request).
	 * @return string
	 */
	public static function build_safe_redirect_to( $path = '' ) {
		if ( '' === $path || ! is_string( $path ) ) {
			return home_url();
		}
		return esc_url_raw( home_url( '/' . ltrim( $path, '/' ) ) );
	}
}
