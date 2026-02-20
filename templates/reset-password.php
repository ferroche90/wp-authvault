<?php
/**
 * Password reset request form template.
 *
 * @package AuthVault
 *
 * @var bool   $show_form_title
 * @var string $form_title_text
 * @var bool   $show_labels
 * @var bool   $show_placeholders
 * @var string $submit_button_text
 * @var string $redirect_after_success
 * @var bool   $show_back_to_login_link
 * @var string $back_to_login_link_text
 * @var array  $messages
 * @var array  $wrapper_attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_attr  = isset( $wrapper_attributes ) && is_array( $wrapper_attributes ) ? $wrapper_attributes : array();
$wrapper_class = 'authvault-form authvault-form--reset';
if ( ! empty( $wrapper_attr['class'] ) ) {
	$wrapper_class .= ' ' . ( is_array( $wrapper_attr['class'] ) ? implode( ' ', $wrapper_attr['class'] ) : $wrapper_attr['class'] );
}
$login_url = wp_login_url();
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>" <?php echo authvault_attributes_string( $wrapper_attr, array( 'class' ) ); ?>>
	<?php if ( ! empty( $messages ) && is_array( $messages ) ) : ?>
		<div class="authvault-messages" role="alert">
			<?php foreach ( $messages as $message ) : ?>
				<p class="authvault-messages__item"><?php echo esc_html( $message ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $show_form_title ) && ! empty( $form_title_text ) ) : ?>
		<h2 class="authvault-form__title authvault-form-title"><?php echo esc_html( $form_title_text ); ?></h2>
	<?php endif; ?>

	<form class="authvault-form__inner" method="post" action="" novalidate>
		<input type="hidden" name="authvault_action" value="authvault_reset" />
		<?php wp_nonce_field( 'authvault_reset', 'authvault_reset_nonce' ); ?>

		<fieldset class="authvault-fieldset">
			<div class="authvault-field authvault-field--user-login">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-reset-user_login" class="authvault-field__label"><?php echo esc_html__( 'Username or email', 'authvault' ); ?></label>
				<?php endif; ?>
				<input type="text" id="authvault-reset-user_login" name="user_login" class="authvault-field__input authvault-input" autocomplete="username"
					<?php echo ( ! empty( $show_placeholders ) ) ? ' placeholder="' . esc_attr__( 'Username or email', 'authvault' ) . '"' : ''; ?>
				/>
			</div>
			<div class="authvault-field authvault-field--submit">
				<button type="submit" class="authvault-button authvault-submit"><?php echo esc_html( $submit_button_text ); ?></button>
			</div>
		</fieldset>

		<?php if ( ! empty( $show_back_to_login_link ) ) : ?>
			<nav class="authvault-form__links" aria-label="<?php echo esc_attr__( 'Reset form links', 'authvault' ); ?>">
				<a href="<?php echo esc_url( $login_url ); ?>" class="authvault-form__link"><?php echo esc_html( $back_to_login_link_text ); ?></a>
			</nav>
		<?php endif; ?>
	</form>
</div>
