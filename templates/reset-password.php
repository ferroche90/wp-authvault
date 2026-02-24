<?php
/**
 * Password reset request form template.
 *
 * @package AuthVault
 *
 * @var bool   $show_form_title
 * @var string $form_title_text
 * @var bool   $show_form_description
 * @var string $form_description
 * @var bool   $show_email_icon
 * @var bool   $show_labels
 * @var bool   $show_placeholders
 * @var string $username_label
 * @var string $username_placeholder
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
if ( ! empty( $show_email_icon ) ) {
	$wrapper_class .= ' authvault-form--has-email-icon';
}
$login_url = wp_login_url();

$username_label_text       = isset( $username_label ) && '' !== (string) $username_label ? $username_label : __( 'Username or email', 'authvault' );
$username_placeholder_text = isset( $username_placeholder ) && '' !== (string) $username_placeholder ? $username_placeholder : __( 'Username or email', 'authvault' );
?>
<div class="<?php echo esc_attr( $wrapper_class ); ?>" <?php echo authvault_attributes_string( $wrapper_attr, array( 'class' ) ); ?>>
	<?php if ( ! empty( $messages ) && is_array( $messages ) ) : ?>
		<div class="authvault-messages" role="alert">
			<?php
			foreach ( $messages as $message ) :
				$msg_type = 'info';
				$msg_text = '';
				if ( is_string( $message ) ) {
					$msg_text = $message;
				} elseif ( is_array( $message ) ) {
					$msg_type = isset( $message['type'] ) ? $message['type'] : 'info';
					$msg_text = isset( $message['text'] ) ? $message['text'] : '';
				}
				if ( '' === $msg_text ) {
					continue;
				}
			?>
				<p class="authvault-messages__item authvault-messages__item--<?php echo esc_attr( $msg_type ); ?>"><?php echo esc_html( $msg_text ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( ! empty( $show_form_title ) && ! empty( $form_title_text ) ) : ?>
		<h2 class="authvault-form__title authvault-form-title"><?php echo esc_html( $form_title_text ); ?></h2>
	<?php endif; ?>

	<?php if ( ! empty( $show_form_description ) && isset( $form_description ) && '' !== (string) $form_description ) : ?>
		<p class="authvault-form__desc authvault-form-desc"><?php echo esc_html( $form_description ); ?></p>
	<?php endif; ?>

	<form class="authvault-form__inner" method="post" action="" novalidate>
		<input type="hidden" name="authvault_action" value="authvault_reset" />
		<?php wp_nonce_field( 'authvault_reset', 'authvault_reset_nonce' ); ?>

		<fieldset class="authvault-fieldset">
			<div class="authvault-field authvault-field--user-login">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-reset-user_login" class="authvault-field__label"><?php echo esc_html( $username_label_text ); ?></label>
				<?php endif; ?>
				<div class="authvault-field-input-wrap">
					<?php if ( ! empty( $show_email_icon ) ) : ?>
						<span class="authvault-field-icon authvault-field-icon--email" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
						</span>
					<?php endif; ?>
					<input type="text" id="authvault-reset-user_login" name="user_login" class="authvault-field__input authvault-input" autocomplete="username"
						<?php echo ( ! empty( $show_placeholders ) ) ? ' placeholder="' . esc_attr( $username_placeholder_text ) . '"' : ''; ?>
					/>
				</div>
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
