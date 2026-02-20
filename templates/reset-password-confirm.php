<?php
/**
 * Password reset confirm (new password) form template.
 *
 * @package AuthVault
 *
 * @var bool   $show_form_title
 * @var string $form_title_text
 * @var bool   $show_labels
 * @var string $submit_button_text
 * @var string $rp_key
 * @var string $rp_login
 * @var array  $messages
 * @var array  $wrapper_attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_attr  = isset( $wrapper_attributes ) && is_array( $wrapper_attributes ) ? $wrapper_attributes : array();
$wrapper_class = 'authvault-form authvault-form--reset-confirm';
if ( ! empty( $wrapper_attr['class'] ) ) {
	$wrapper_class .= ' ' . ( is_array( $wrapper_attr['class'] ) ? implode( ' ', $wrapper_attr['class'] ) : $wrapper_attr['class'] );
}
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

	<form class="authvault-form__inner" method="post" action="" novalidate autocomplete="off">
		<input type="hidden" name="authvault_action" value="authvault_reset_confirm" />
		<?php wp_nonce_field( 'authvault_reset_confirm', 'authvault_reset_confirm_nonce' ); ?>
		<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
		<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>" />

		<fieldset class="authvault-fieldset">
			<div class="authvault-field authvault-field--pass1">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-reset-pass1" class="authvault-field__label"><?php echo esc_html__( 'New password', 'authvault' ); ?></label>
				<?php endif; ?>
				<input type="password" id="authvault-reset-pass1" name="pass1" class="authvault-field__input authvault-input" autocomplete="new-password" required minlength="1" />
			</div>
			<div class="authvault-field authvault-field--pass2">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-reset-pass2" class="authvault-field__label"><?php echo esc_html__( 'Confirm new password', 'authvault' ); ?></label>
				<?php endif; ?>
				<input type="password" id="authvault-reset-pass2" name="pass2" class="authvault-field__input authvault-input" autocomplete="new-password" required minlength="1" />
			</div>
			<div class="authvault-field authvault-field--submit">
				<button type="submit" class="authvault-button authvault-submit"><?php echo esc_html( $submit_button_text ); ?></button>
			</div>
		</fieldset>
	</form>
</div>
