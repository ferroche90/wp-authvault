<?php
/**
 * Password reset confirm (new password) form template.
 *
 * @package AuthVault
 *
 * @var bool   $show_form_title
 * @var string $form_title_text
 * @var bool   $show_form_description
 * @var string $form_description
 * @var bool   $show_labels
 * @var string $submit_button_text
 * @var bool   $show_strength_meter
 * @var bool   $show_generate_button
 * @var bool   $show_hint_text
 * @var string $rp_key
 * @var string $rp_login
 * @var int    $min_password_length
 * @var bool   $allow_weak_passwords
 * @var string $generated_password
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

$min_len     = isset( $min_password_length ) ? (int) $min_password_length : 8;
$allow_weak  = isset( $allow_weak_passwords ) ? (bool) $allow_weak_passwords : false;
$generated   = isset( $generated_password ) ? $generated_password : '';
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

	<form class="authvault-form__inner" method="post" action="" novalidate autocomplete="off" id="authvault-reset-confirm-form" data-allow-weak-passwords="<?php echo $allow_weak ? '1' : '0'; ?>" data-strength-placeholder="<?php echo esc_attr__( '—', 'authvault' ); ?>">
		<input type="hidden" name="authvault_action" value="authvault_reset_confirm" />
		<?php wp_nonce_field( 'authvault_reset_confirm', 'authvault_reset_confirm_nonce' ); ?>
		<input type="hidden" name="rp_key" value="<?php echo esc_attr( $rp_key ); ?>" />
		<input type="hidden" name="rp_login" value="<?php echo esc_attr( $rp_login ); ?>" />
		<input type="hidden" name="pass2" id="authvault-reset-pass2" value="" />
		<input type="hidden" name="authvault_password_strength" id="authvault-password-strength" value="0" />

		<fieldset class="authvault-fieldset">
			<div class="authvault-field authvault-field--pass1">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-reset-pass1" class="authvault-field__label"><?php echo esc_html__( 'New password', 'authvault' ); ?></label>
				<?php endif; ?>
				<div class="authvault-field-password-wrap authvault-field-input-wrap">
					<input
						type="text"
						id="authvault-reset-pass1"
						name="pass1"
						class="authvault-field__input authvault-input authvault-input--password"
						autocomplete="new-password"
						spellcheck="false"
						value="<?php echo esc_attr( $generated ); ?>"
						data-min-length="<?php echo esc_attr( (string) $min_len ); ?>"
						required
						minlength="<?php echo esc_attr( (string) $min_len ); ?>"
					/>
					<button type="button" class="authvault-toggle-password is-active" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'authvault' ); ?>">
						<svg class="authvault-icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
						<svg class="authvault-icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
					</button>
				</div>
			</div>

			<?php if ( ! empty( $show_strength_meter ) ) : ?>
				<div class="authvault-strength" id="authvault-strength">
					<div class="authvault-strength__bar">
						<div class="authvault-strength__fill" id="authvault-strength-fill"></div>
					</div>
					<span class="authvault-strength__label authvault-strength__label--empty" id="authvault-strength-label" aria-live="polite">—</span>
				</div>

				<p class="authvault-weak-message" id="authvault-weak-message" role="alert" aria-live="polite">
					<?php
					printf(
						esc_html__( 'Please choose a stronger password. Use at least %d characters with a mix of upper and lower case letters, numbers, and symbols.', 'authvault' ),
						$min_len
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $show_hint_text ) ) : ?>
				<p class="authvault-hint">
					<?php
					printf(
						esc_html__( 'Hint: The password should be at least %d characters long. To make it stronger, use upper and lower case letters, numbers, and symbols like ! " ? $ %% ^ &.', 'authvault' ),
						$min_len
					);
					?>
				</p>
			<?php endif; ?>

			<?php if ( ! empty( $show_generate_button ) ) : ?>
				<button type="button" class="authvault-generate" id="authvault-generate">
					<?php echo esc_html__( 'Generate password', 'authvault' ); ?>
				</button>
			<?php endif; ?>

			<div class="authvault-field authvault-field--submit">
				<button type="submit" class="authvault-button authvault-submit" id="authvault-confirm-submit"><?php echo esc_html( $submit_button_text ); ?></button>
			</div>
		</fieldset>
	</form>
</div>
