<?php
/**
 * Elementor widget: AuthVault Set New Password (reset confirm) form.
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
 * Elementor widget that renders the AuthVault password reset confirm form.
 */
class AuthVault_Widget_Reset_Password_Confirm extends Widget_Base {

	/**
	 * @return string
	 */
	public function get_name() {
		return 'authvault-reset-password-confirm';
	}

	/**
	 * @return string
	 */
	public function get_title() {
		return __( 'AuthVault Set New Password', 'authvault' );
	}

	/**
	 * @return string
	 */
	public function get_icon() {
		return 'eicon-lock';
	}

	/**
	 * @return array<string>
	 */
	public function get_categories() {
		return array( 'authvault' );
	}

	/**
	 * @return void
	 */
	protected function register_controls() {
		$this->register_content_controls();
		$this->register_style_controls();
	}

	/**
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
				'default'   => __( 'Set new password', 'authvault' ),
				'condition' => array( 'show_form_title' => 'yes' ),
			)
		);

		$this->add_control(
			'show_form_description',
			array(
				'label'   => __( 'Show form description', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'form_description',
			array(
				'label'     => __( 'Form description', 'authvault' ),
				'type'      => Controls_Manager::TEXTAREA,
				'default'   => __( 'Enter a new password below or use the generated one.', 'authvault' ),
				'condition' => array( 'show_form_description' => 'yes' ),
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
			'submit_button_text',
			array(
				'label'   => __( 'Submit button text', 'authvault' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Save password', 'authvault' ),
			)
		);

		$this->add_control(
			'show_strength_meter',
			array(
				'label'   => __( 'Show password strength meter', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_generate_button',
			array(
				'label'   => __( 'Show "Generate password" button', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->add_control(
			'show_hint_text',
			array(
				'label'   => __( 'Show password hint text', 'authvault' ),
				'type'    => Controls_Manager::SWITCHER,
				'default' => 'yes',
			)
		);

		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function register_style_controls() {
		// Form Container.
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

		// Title.
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

		// Labels.
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

		// Input Fields.
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
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'input_text_color',
			array(
				'label'     => __( 'Text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'color: {{VALUE}};' ),
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
					'none'   => __( 'None', 'authvault' ),
					'solid'  => __( 'Solid', 'authvault' ),
					'double' => __( 'Double', 'authvault' ),
					'dotted' => __( 'Dotted', 'authvault' ),
					'dashed' => __( 'Dashed', 'authvault' ),
				),
				'default'   => 'solid',
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'border-style: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'input_border_width',
			array(
				'label'      => __( 'Width', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'border-width: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_control(
			'input_border_color',
			array(
				'label'     => __( 'Color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'border-color: {{VALUE}};' ),
			)
		);
		$this->add_responsive_control(
			'input_border_radius',
			array(
				'label'      => __( 'Border radius', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
			)
		);
		$this->add_responsive_control(
			'input_padding',
			array(
				'label'      => __( 'Padding', 'authvault' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array( '{{WRAPPER}} .authvault-form input[type="text"], {{WRAPPER}} .authvault-form input[type="password"]' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};' ),
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

		// Submit Button.
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

		// Messages.
		$this->start_controls_section(
			'section_style_messages',
			array(
				'label' => __( 'Messages', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'messages_typography',
				'selector' => '{{WRAPPER}} .authvault-messages__item',
			)
		);
		$this->add_control(
			'messages_error_color',
			array(
				'label'     => __( 'Error text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-messages__item--error' => 'color: {{VALUE}}; border-left-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'messages_success_color',
			array(
				'label'     => __( 'Success text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-messages__item--success' => 'color: {{VALUE}}; border-left-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		// Strength Meter.
		$this->start_controls_section(
			'section_style_strength',
			array(
				'label' => __( 'Strength Meter', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'strength_bar_height',
			array(
				'label'     => __( 'Bar height (px)', 'authvault' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 6,
				'selectors' => array( '{{WRAPPER}} .authvault-strength__bar' => 'height: {{VALUE}}px;' ),
			)
		);
		$this->add_control(
			'strength_bar_background',
			array(
				'label'     => __( 'Bar track color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-strength__bar' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->end_controls_section();

		// Generate Button.
		$this->start_controls_section(
			'section_style_generate',
			array(
				'label' => __( 'Generate Button', 'authvault' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'generate_text_color',
			array(
				'label'     => __( 'Text color', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-generate' => 'color: {{VALUE}}; border-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'generate_bg_hover',
			array(
				'label'     => __( 'Background color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-generate:hover' => 'background-color: {{VALUE}};' ),
			)
		);
		$this->add_control(
			'generate_text_color_hover',
			array(
				'label'     => __( 'Text color (hover)', 'authvault' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array( '{{WRAPPER}} .authvault-generate:hover' => 'color: {{VALUE}};' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'generate_typography',
				'selector' => '{{WRAPPER}} .authvault-generate',
			)
		);
		$this->end_controls_section();
	}

	/**
	 * @return void
	 */
	protected function render() {
		$settings = $this->get_settings_for_display();

		$key   = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';
		$login = isset( $_GET['login'] ) ? sanitize_user( wp_unslash( $_GET['login'] ), true ) : '';

		$confirm_errors = \AuthVault\AuthVault_Auth::get_confirm_errors();

		$args = array(
			'show_form_title'       => 'yes' === $settings['show_form_title'],
			'form_title_text'       => $settings['form_title_text'],
			'show_form_description' => 'yes' === $settings['show_form_description'],
			'form_description'      => isset( $settings['form_description'] ) ? $settings['form_description'] : '',
			'show_labels'           => 'yes' === $settings['show_labels'],
			'submit_button_text'    => $settings['submit_button_text'],
			'show_strength_meter'   => 'yes' === $settings['show_strength_meter'],
			'show_generate_button'  => 'yes' === $settings['show_generate_button'],
			'show_hint_text'        => 'yes' === $settings['show_hint_text'],
			'rp_key'                => $key,
			'rp_login'              => $login,
			'messages'              => $confirm_errors,
		);

		$this->add_render_attribute( 'wrapper', 'class', 'authvault-form-wrapper authvault-elementor-reset-password-confirm' );
		?>
		<div <?php $this->print_render_attribute_string( 'wrapper' ); ?>>
			<?php
			if ( '' === $key || '' === $login ) {
				$reset_page_id = (int) authvault_get_option( 'password_reset_page_id', 0 );
				$reset_url     = ( 0 < $reset_page_id ) ? get_permalink( $reset_page_id ) : home_url( '/wp-login.php?action=lostpassword' );
				if ( ! is_string( $reset_url ) || '' === $reset_url ) {
					$reset_url = home_url();
				}
				$msg = authvault_get_message( 'msg_confirm_invalid_link', __( 'This link is invalid or has expired. Please request a new password reset.', 'authvault' ) );
				echo '<p class="authvault-reset-confirm-invalid-link">' . esc_html( $msg ) . ' <a href="' . esc_url( $reset_url ) . '">' . esc_html__( 'Request password reset', 'authvault' ) . '</a></p>';
			} else {
				authvault_get_reset_confirm_form( $args, true );
			}
			?>
		</div>
		<?php
	}
}
