<?php
/**
 * Elementor widget: AuthVault Logout.
 *
 * @package AuthVault
 */

namespace AuthVault\Elementor\Widgets;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;

/**
 * Elementor widget that renders the AuthVault logout block.
 */
class AuthVault_Widget_Logout extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'authvault-logout';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'AuthVault Logout', 'authvault' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-log-out';
	}

	/**
	 * Get widget categories.
	 *
	 * @return array<string>
	 */
	public function get_categories() {
		return array( 'authvault' );
	}

	/**
	 * Register controls.
	 *
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
	 * Register Content tab controls.
	 *
	 * @return void
	 */
	protected function register_content_controls() {
		$this->start_controls_section(
			'section_general',
			array(
				'label' => __( 'General', 'authvault' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'show_title',
			array(
				'label'   => __( 'Show title', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'title_text',
			array(
				'label'     => __( 'Title text', 'authvault' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'You are logged in', 'authvault' ),
				'condition' => array( 'show_title' => 'yes' ),
			)
		);

		$this->add_control(
			'button_text',
			array(
				'label'   => __( 'Logout button text', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Log out', 'authvault' ),
			)
		);

		$this->add_control(
			'show_username',
			array(
				'label'   => __( 'Show username', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'redirect_after_logout',
			array(
				'label'   => __( 'Redirect after logout', 'authvault' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => home_url() ),
			)
		);

		$this->add_control(
			'show_login_link',
			array(
				'label'   => __( 'Show login link', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'login_link_text',
			array(
				'label'     => __( 'Login link text', 'authvault' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Back to login', 'authvault' ),
				'condition' => array( 'show_login_link' => 'yes' ),
			)
		);

		$this->end_controls_section();
	}

	/**
	 * Register Style tab controls.
	 *
	 * @return void
	 */
	protected function register_style_controls() {
		$this->start_controls_section(
			'section_style_button_container',
			array(
				'label' => __( 'Button Container', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'button_container_background',
			array(
				'label'     => __( 'Background color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-wrapper' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'button_container_padding',
			array(
				'label'      => __( 'Padding', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-logout-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'button_container_border_radius',
			array(
				'label'      => __( 'Border radius', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-logout-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'button_container_box_shadow',
				'selector' => '{{WRAPPER}} .authvault-logout-wrapper',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_title',
			array(
				'label' => __( 'Title', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'selector' => '{{WRAPPER}} .authvault-logout-title',
			)
		);
		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-title' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'title_align',
			array(
				'label'     => __( 'Alignment', 'authvault' ),
				'type'      => Controls_Manager::CHOOSE,
				'options'   => array(
					'left'   => array( 'title' => __( 'Left', 'authvault' ), 'icon' => 'eicon-text-align-left' ),
					'center' => array( 'title' => __( 'Center', 'authvault' ), 'icon' => 'eicon-text-align-center' ),
					'right'  => array( 'title' => __( 'Right', 'authvault' ), 'icon' => 'eicon-text-align-right' ),
				),
				'selectors' => array( '{{WRAPPER}} .authvault-logout-title' => 'text-align: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_username',
			array(
				'label' => __( 'Username', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'username_typography',
				'selector' => '{{WRAPPER}} .authvault-logout-username',
			)
		);
		$this->add_control(
			'username_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-username' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_button',
			array(
				'label' => __( 'Logout Button', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'button_bg_color',
			array(
				'label'     => __( 'Background color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-btn' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_bg_color_hover',
			array(
				'label'     => __( 'Background color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-btn:hover' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_text_color',
			array(
				'label'     => __( 'Text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-btn' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'button_text_color_hover',
			array(
				'label'     => __( 'Text color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-btn:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'button_typography',
				'selector' => '{{WRAPPER}} .authvault-logout-btn',
			)
		);
		$this->add_responsive_control(
			'button_border_radius',
			array(
				'label'      => __( 'Border radius', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-logout-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'button_padding',
			array(
				'label'      => __( 'Padding', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-logout-btn' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'button_transition_duration',
			array(
				'label'     => __( 'Transition duration (ms)', 'authvault' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 300,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-btn' => 'transition-duration: {{VALUE}}ms;' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_links',
			array(
				'label' => __( 'Links', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'links_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-wrapper a' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'links_color_hover',
			array(
				'label'     => __( 'Color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-logout-wrapper a:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'links_typography',
				'selector' => '{{WRAPPER}} .authvault-logout-wrapper a',
			)
		);
		$this->end_controls_section();
	}

	/**
	 * Render widget output.
	 *
	 * @return void
	 */
	protected function render() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$settings = $this->get_settings_for_display();

		$redirect = home_url();
		if ( isset( $settings['redirect_after_logout']['url'] ) && $settings['redirect_after_logout']['url'] !== '' ) {
			$redirect = $settings['redirect_after_logout']['url'];
		}

		$args = array(
			'show_title'           => 'yes' === $settings['show_title'],
			'title_text'           => $settings['title_text'],
			'button_text'          => $settings['button_text'],
			'show_username'        => 'yes' === $settings['show_username'],
			'redirect_after_logout' => $redirect,
			'show_login_link'      => 'yes' === $settings['show_login_link'],
			'login_link_text'      => $settings['login_link_text'],
		);

		$this->add_render_attribute( 'wrapper', 'class', 'authvault-logout-wrapper authvault-elementor-logout' );
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php authvault_get_logout_block( $args, true, false ); ?>
		</div>
		<?php
	}
}
