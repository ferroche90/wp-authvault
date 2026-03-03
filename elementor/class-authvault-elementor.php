<?php
/**
 * Elementor integration: register AuthVault category and widgets.
 *
 * @package AuthVault
 */

namespace AuthVault\Elementor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers AuthVault widget category and widgets with Elementor.
 * Enqueues widget styles on the frontend. Shows admin notice if Elementor is not active.
 */
class AuthVault_Elementor {

	/**
	 * Widget category slug.
	 *
	 * @var string
	 */
	const CATEGORY_SLUG = 'authvault';

	/**
	 * Constructor: hook into Elementor or show notice.
	 */
	public function __construct() {
		if ( did_action( 'elementor/loaded' ) ) {
			$this->init();
			return;
		}
		add_action( 'elementor/loaded', array( $this, 'init' ), 10, 0 );
		add_action( 'admin_notices', array( $this, 'maybe_show_elementor_notice' ), 10, 0 );
	}

	/**
	 * Initialize: register category, widgets, and enqueue styles.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_categories' ), 10, 1 );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ), 10, 1 );
		add_action( 'elementor/frontend/after_enqueue_styles', array( $this, 'enqueue_widget_styles' ), 10, 0 );
	}

	/**
	 * Show admin notice if Elementor is not active (only when not loaded yet).
	 *
	 * @return void
	 */
	public function maybe_show_elementor_notice() {
		if ( did_action( 'elementor/loaded' ) ) {
			return;
		}
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( null === $screen || ( 'plugins' !== $screen->id && 'dashboard' !== $screen->id ) ) {
			return;
		}
		echo '<div class="notice notice-warning"><p>';
		echo esc_html__( 'WP AuthVault Elementor widgets require Elementor to be installed and active.', 'authvault' );
		echo '</p></div>';
	}

	/**
	 * Register the AuthVault widget category.
	 *
	 * @param \Elementor\Elements_Manager $elements_manager Elementor elements manager.
	 * @return void
	 */
	public function register_categories( $elements_manager ) {
		$elements_manager->add_category(
			self::CATEGORY_SLUG,
			array(
				'title' => __( 'AuthVault', 'authvault' ),
				'icon'  => 'eicon-lock-user',
			)
		);
	}

	/**
	 * Register AuthVault widgets with Elementor.
	 *
	 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
	 * @return void
	 */
	public function register_widgets( $widgets_manager ) {
		$widgets_manager->register( new Widgets\AuthVault_Widget_Login() );
		$widgets_manager->register( new Widgets\AuthVault_Widget_Register() );
		$widgets_manager->register( new Widgets\AuthVault_Widget_Reset_Password() );
		$widgets_manager->register( new Widgets\AuthVault_Widget_Reset_Password_Confirm() );
		$widgets_manager->register( new Widgets\AuthVault_Widget_Logout() );
	}

	/**
	 * Enqueue widget styles on the frontend.
	 *
	 * @return void
	 */
	public function enqueue_widget_styles() {
		wp_enqueue_style(
			'authvault-elementor',
			authvault_asset_url( 'assets/css/authvault-elementor.css' ),
			array(),
			AUTHVAULT_VERSION
		);
	}
}
