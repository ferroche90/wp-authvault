<?php
/**
 * Elementor widget: AuthVault Login form.
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
 * Elementor widget that renders the AuthVault login form.
 */
class AuthVault_Widget_Login extends Widget_Base {

	/**
	 * Get widget name.
	 *
	 * @return string
	 */
	public function get_name() {
		return 'authvault-login';
	}

	/**
	 * Get widget title.
	 *
	 * @return string
	 */
	public function get_title() {
		return __( 'AuthVault Login', 'authvault' );
	}

	/**
	 * Get widget icon.
	 *
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-lock-user';
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
	 * Register content controls.
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
			'show_form_title',
			array(
				'label'   => __( 'Show form title', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'form_title_text',
			array(
				'label'     => __( 'Form title text', 'authvault' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Log in', 'authvault' ),
				'condition' => array( 'show_form_title' => 'yes' ),
			)
		);

		$this->add_control(
			'show_form_description',
			array(
				'label'   => __( 'Show form description', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'form_description',
			array(
				'label'     => __( 'Form description', 'authvault' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => __( 'Enter your credentials to access your account', 'authvault' ),
				'condition' => array( 'show_form_description' => 'yes' ),
			)
		);

		$this->add_control(
			'show_email_icon',
			array(
				'label'   => __( 'Show email icon', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'show_password_icon',
			array(
				'label'   => __( 'Show password (lock) icon', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => '',
			)
		);

		$this->add_control(
			'show_password_toggle',
			array(
				'label'       => __( 'Show password visibility toggle', 'authvault' ),
				'type'        => Controls_Manager::SWITCHER,
				'default'     => '',
				'description' => __( 'Eye icon to show/hide password. Style via Style tab.', 'authvault' ),
			)
		);

		$this->add_control(
			'show_labels',
			array(
				'label'   => __( 'Show labels', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_placeholders',
			array(
				'label'   => __( 'Show placeholders', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'username_label',
			array(
				'label'   => __( 'Username / email label', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Username or email', 'authvault' ),
			)
		);

		$this->add_control(
			'username_placeholder',
			array(
				'label'   => __( 'Username / email placeholder', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Username or email', 'authvault' ),
			)
		);

		$this->add_control(
			'password_label',
			array(
				'label'   => __( 'Password label', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Password', 'authvault' ),
			)
		);

		$this->add_control(
			'password_placeholder',
			array(
				'label'   => __( 'Password placeholder', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Password', 'authvault' ),
			)
		);

		$this->add_control(
			'submit_button_text',
			array(
				'label'   => __( 'Submit button text', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Log in', 'authvault' ),
			)
		);

		$login_redirect = admin_url();
		$page_id        = (int) authvault_get_option( 'login_redirect_page_id', 0 );
		if ( 0 < $page_id ) {
			$url = get_permalink( $page_id );
			if ( is_string( $url ) && '' !== $url ) {
				$login_redirect = $url;
			}
		}
		$this->add_control(
			'redirect_after_success',
			array(
				'label'   => __( 'Redirect after success', 'authvault' ),
				'type'    => Controls_Manager::URL,
				'default' => array( 'url' => $login_redirect ),
			)
		);

		$this->add_control(
			'show_remember_me',
			array(
				'label'   => __( 'Show "Remember me" checkbox', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_forgot_password_link',
			array(
				'label'   => __( 'Show "Forgot password" link', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'forgot_password_link_text',
			array(
				'label'     => __( 'Forgot password link text', 'authvault' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Forgot password?', 'authvault' ),
				'condition' => array( 'show_forgot_password_link' => 'yes' ),
			)
		);

		$this->add_control(
			'show_register_link',
			array(
				'label'   => __( 'Show "Register" link', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'register_link_text',
			array(
				'label'     => __( 'Register link text', 'authvault' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Register', 'authvault' ),
				'condition' => array( 'show_register_link' => 'yes' ),
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
			'section_style_form_container',
			array(
				'label' => __( 'Form Container', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'form_container_background',
			array(
				'label'     => __( 'Background color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form-wrapper' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'form_container_padding',
			array(
				'label'      => __( 'Padding', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form-wrapper' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'form_container_border_radius',
			array(
				'label'      => __( 'Border radius', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form-wrapper' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'form_container_box_shadow',
				'selector' => '{{WRAPPER}} .authvault-form-wrapper',
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
				'selector' => '{{WRAPPER}} .authvault-form-title',
			)
		);
		$this->add_control(
			'title_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form-title' => 'color: {{VALUE}};' ),
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
				'selectors' => array( '{{WRAPPER}} .authvault-form-title' => 'text-align: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_description',
			array(
				'label' => __( 'Description', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'description_typography',
				'selector' => '{{WRAPPER}} .authvault-form-desc',
			)
		);
		$this->add_control(
			'description_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form-desc' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_labels',
			array(
				'label' => __( 'Labels', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'labels_typography',
				'selector' => '{{WRAPPER}} .authvault-form label',
			)
		);
		$this->add_control(
			'labels_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form label' => 'color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_inputs',
			array(
				'label' => __( 'Input Fields', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'input_background',
			array(
				'label'     => __( 'Background color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'input_text_color',
			array(
				'label'     => __( 'Text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'input_border_heading',
			array(
				'label' => __( 'Border', 'authvault' ),
				'type'  => Controls_Manager::HEADING,
			)
		);
		$this->add_control(
			'input_border_type',
			array(
				'label'     => __( 'Type', 'authvault' ),
				'type'      => Controls_Manager::SELECT,
				'options'   => array(
					'none' => __( 'None', 'authvault' ),
					'solid' => __( 'Solid', 'authvault' ),
					'double' => __( 'Double', 'authvault' ),
					'dotted' => __( 'Dotted', 'authvault' ),
					'dashed' => __( 'Dashed', 'authvault' ),
				),
				'default'   => 'solid',
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'border-style: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'input_border_width',
			array(
				'label'      => __( 'Width', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'input_border_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'input_border_radius',
			array(
				'label'      => __( 'Border radius', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'input_padding',
			array(
				'label'      => __( 'Padding', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"], {{WRAPPER}} .authvault-form input[type="email"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'input_focus_border_color',
			array(
				'label'     => __( 'Focus border color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form input:focus' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_submit',
			array(
				'label' => __( 'Submit Button', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'submit_bg_color',
			array(
				'label'     => __( 'Background color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form .authvault-submit' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'submit_bg_color_hover',
			array(
				'label'     => __( 'Background color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form .authvault-submit:hover' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'submit_text_color',
			array(
				'label'     => __( 'Text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form .authvault-submit' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'submit_text_color_hover',
			array(
				'label'     => __( 'Text color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form .authvault-submit:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'submit_typography',
				'selector' => '{{WRAPPER}} .authvault-form .authvault-submit',
			)
		);
		$this->add_responsive_control(
			'submit_border_radius',
			array(
				'label'      => __( 'Border radius', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form .authvault-submit' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'submit_padding',
			array(
				'label'      => __( 'Padding', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form .authvault-submit' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'submit_transition_duration',
			array(
				'label'     => __( 'Transition duration (ms)', 'authvault' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 300,
				'selectors' => array( '{{WRAPPER}} .authvault-form .authvault-submit' => 'transition-duration: {{VALUE}}ms;' ),
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
				'selectors' => array( '{{WRAPPER}} .authvault-form a' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'links_color_hover',
			array(
				'label'     => __( 'Color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form a:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'links_typography',
				'selector' => '{{WRAPPER}} .authvault-form a',
			)
		);
		$this->end_controls_section();

		$this->start_controls_section(
			'section_style_icons',
			array(
				'label' => __( 'Field Icons & Toggle', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'field_icons_color',
			array(
				'label'     => __( 'Email & lock icon color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .authvault-field-icon--email' => 'color: {{VALUE}};',
					'{{WRAPPER}} .authvault-field-icon--password' => 'color: {{VALUE}};',
				),
			)
		);
		$this->add_control(
			'toggle_color',
			array(
				'label'     => __( 'Password toggle (eye) color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-toggle-password' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'toggle_color_hover',
			array(
				'label'     => __( 'Password toggle color (hover/active)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .authvault-toggle-password:hover' => 'color: {{VALUE}};',
					'{{WRAPPER}} .authvault-toggle-password.is-active' => 'color: {{VALUE}};',
				),
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
		$settings = $this->get_settings_for_display();
		$args     = array(
			'show_form_title'           => 'yes' === $settings['show_form_title'],
			'form_title_text'           => $settings['form_title_text'],
			'show_form_description'     => 'yes' === $settings['show_form_description'],
			'form_description'          => isset( $settings['form_description'] ) ? $settings['form_description'] : '',
			'show_email_icon'           => 'yes' === $settings['show_email_icon'],
			'show_password_icon'        => 'yes' === $settings['show_password_icon'],
			'show_password_toggle'      => 'yes' === $settings['show_password_toggle'],
			'show_labels'               => 'yes' === $settings['show_labels'],
			'show_placeholders'         => 'yes' === $settings['show_placeholders'],
			'username_label'           => isset( $settings['username_label'] ) ? $settings['username_label'] : '',
			'username_placeholder'     => isset( $settings['username_placeholder'] ) ? $settings['username_placeholder'] : '',
			'password_label'            => isset( $settings['password_label'] ) ? $settings['password_label'] : '',
			'password_placeholder'      => isset( $settings['password_placeholder'] ) ? $settings['password_placeholder'] : '',
			'submit_button_text'        => $settings['submit_button_text'],
			'redirect_after_success'    => isset( $settings['redirect_after_success']['url'] ) ? $settings['redirect_after_success']['url'] : '',
			'show_remember_me'          => 'yes' === $settings['show_remember_me'],
			'show_forgot_password_link' => 'yes' === $settings['show_forgot_password_link'],
			'forgot_password_link_text' => $settings['forgot_password_link_text'],
			'show_register_link'        => 'yes' === $settings['show_register_link'],
			'register_link_text'        => $settings['register_link_text'],
		);
		$this->add_render_attribute( 'wrapper', 'class', 'authvault-form-wrapper authvault-elementor-login' );
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php authvault_get_login_form( $args, true ); ?>
		</div>
		<?php
	}
}
