<?php
/**
 * Fired during plugin activation.
 *
 * @package AuthVault
 */

namespace AuthVault;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation.
 *
 * Handles version checks, DB table creation, default options,
 * rewrite flush, and creation of default Login/Register/Reset pages.
 */
class AuthVault_Activator {

	/**
	 * Post meta key marking a page as created by AuthVault (for safe uninstall cleanup).
	 *
	 * @var string
	 */
	const CREATED_BY_PLUGIN_META_KEY = '_authvault_created_by_plugin';

	/**
	 * Activate the plugin.
	 *
	 * Checks PHP/WP versions, creates login log table, saves DB version,
	 * ensures default options, flushes rewrite rules, creates default pages.
	 *
	 * @return void
	 */
	public static function activate() {
		self::check_requirements();
		self::create_login_log_table();
		self::save_db_version();
		self::ensure_default_options();
		self::create_default_pages();
		flush_rewrite_rules( false );
	}

	/**
	 * Check PHP and WordPress version requirements; wp_die if not met.
	 *
	 * @return void
	 */
	protected static function check_requirements() {
		if ( version_compare( PHP_VERSION, AUTHVAULT_MIN_PHP, '<' ) ) {
			wp_die(
				esc_html( sprintf(
					/* translators: 1: required PHP version, 2: current PHP version */
					__( 'WP AuthVault requires PHP %1$s or higher (you have %2$s).', 'authvault' ),
					AUTHVAULT_MIN_PHP,
					PHP_VERSION
				) ),
				esc_html__( 'Plugin Activation Error', 'authvault' ),
				array( 'back_link' => true )
			);
		}
		if ( version_compare( get_bloginfo( 'version' ), AUTHVAULT_MIN_WP, '<' ) ) {
			wp_die(
				esc_html( sprintf(
					/* translators: 1: required WP version, 2: current WP version */
					__( 'WP AuthVault requires WordPress %1$s or higher (you have %2$s).', 'authvault' ),
					AUTHVAULT_MIN_WP,
					get_bloginfo( 'version' )
				) ),
				esc_html__( 'Plugin Activation Error', 'authvault' ),
				array( 'back_link' => true )
			);
		}
	}

	/**
	 * Create the authvault_login_log table using dbDelta().
	 *
	 * @return void
	 */
	public static function create_login_log_table() {
		global $wpdb;
		$table           = $wpdb->prefix . 'authvault_login_log';
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			user_login varchar(60) NOT NULL DEFAULT '',
			ip_hash varchar(64) NOT NULL DEFAULT '',
			status varchar(10) NOT NULL DEFAULT 'fail',
			attempted_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY ip_hash (ip_hash),
			KEY attempted_at (attempted_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Save the current DB version option for future migrations.
	 *
	 * @return void
	 */
	protected static function save_db_version() {
		update_option( 'authvault_db_version', AUTHVAULT_DB_VERSION );
	}

	/**
	 * Set default plugin options if not already stored.
	 *
	 * Uses AuthVault_Options as the single source of truth for defaults.
	 * Does NOT overwrite existing settings on re-activation.
	 *
	 * @return void
	 */
	protected static function ensure_default_options() {
		$opts = new AuthVault_Options();

		if ( false !== get_option( AuthVault_Options::OPTION_NAME, false ) ) {
			return;
		}

		update_option( AuthVault_Options::OPTION_NAME, $opts->get_defaults() );
	}

	/**
	 * Create default Login, Register, and Password Reset pages with shortcodes if they don't exist.
	 * Saves their IDs in authvault_settings.
	 *
	 * @return void
	 */
	protected static function create_default_pages() {
		$opts     = new AuthVault_Options();
		$settings = $opts->get();

		$pages_to_create = array(
			'login_page_id'                    => array(
				'title'   => __( 'Login', 'authvault' ),
				'content' => '[authvault_login]',
			),
			'register_page_id'                 => array(
				'title'   => __( 'Register', 'authvault' ),
				'content' => '[authvault_register]',
			),
			'password_reset_page_id'           => array(
				'title'   => __( 'Password Reset', 'authvault' ),
				'content' => '[authvault_reset_password]',
			),
			'password_reset_confirm_page_id'   => array(
				'title'   => __( 'Set New Password', 'authvault' ),
				'content' => '[authvault_reset_password_confirm]',
			),
		);

		foreach ( $pages_to_create as $option_key => $page_config ) {
			$current_id = isset( $settings[ $option_key ] ) ? (int) $settings[ $option_key ] : 0;
			if ( 0 < $current_id ) {
				$post = get_post( $current_id );
				if ( $post instanceof \WP_Post && 'page' === $post->post_type && 'publish' === $post->post_status ) {
					continue;
				}
			}
			$page_id = self::create_page_if_not_exists( $page_config['title'], $page_config['content'] );
			if ( 0 < $page_id ) {
				$settings[ $option_key ] = $page_id;
			}
		}

		update_option( AuthVault_Options::OPTION_NAME, $settings );
	}

	/**
	 * Create a single page with given title and content if no published page with that title exists.
	 *
	 * Uses WP_Query instead of the deprecated get_page_by_title().
	 *
	 * @param string $title   Page title.
	 * @param string $content Page content (e.g. shortcode).
	 * @return int Post ID or 0 on failure.
	 */
	protected static function create_page_if_not_exists( $title, $content ) {
		$query = new \WP_Query(
			array(
				'post_type'              => 'page',
				'title'                  => $title,
				'post_status'            => 'publish',
				'posts_per_page'         => 1,
				'no_found_rows'          => true,
				'ignore_sticky_posts'    => true,
				'update_post_term_cache' => false,
				'update_post_meta_cache' => false,
			)
		);

		if ( $query->have_posts() ) {
			return 0;
		}

		$page_id = wp_insert_post(
			array(
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
				'post_type'    => 'page',
				'post_author'  => 1,
			),
			true
		);

		if ( is_wp_error( $page_id ) || 1 > $page_id ) {
			return 0;
		}

		update_post_meta( $page_id, self::CREATED_BY_PLUGIN_META_KEY, '1' );
		return (int) $page_id;
	}
}
