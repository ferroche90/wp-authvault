<?php
/**
 * Login form template.
 *
 * @package AuthVault
 *
 * @var bool   $show_form_title
 * @var string $form_title_text
 * @var bool   $show_labels
 * @var bool   $show_placeholders
 * @var string $submit_button_text
 * @var string $redirect_after_success
 * @var bool   $show_remember_me
 * @var bool   $show_forgot_password_link
 * @var string $forgot_password_link_text
 * @var bool   $show_register_link
 * @var string $register_link_text
 * @var bool   $show_form_description
 * @var string $form_description
 * @var bool   $show_email_icon
 * @var bool   $show_password_icon
 * @var bool   $show_password_toggle
 * @var string $username_label
 * @var string $username_placeholder
 * @var string $password_label
 * @var string $password_placeholder
 * @var array  $messages
 * @var array  $wrapper_attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_attr  = isset( $wrapper_attributes ) && is_array( $wrapper_attributes ) ? $wrapper_attributes : array();
$wrapper_class = 'authvault-form authvault-form--login';
if ( ! empty( $wrapper_attr['class'] ) ) {
	$wrapper_class .= ' ' . ( is_array( $wrapper_attr['class'] ) ? implode( ' ', $wrapper_attr['class'] ) : $wrapper_attr['class'] );
}
if ( ! empty( $show_email_icon ) ) {
	$wrapper_class .= ' authvault-form--has-email-icon';
}
if ( ! empty( $show_password_icon ) || ! empty( $show_password_toggle ) ) {
	$wrapper_class .= ' authvault-form--has-password-icon';
}
if ( ! empty( $show_password_toggle ) ) {
	$wrapper_class .= ' authvault-form--has-password-toggle';
}
$login_url = wp_login_url();
$lostpassword_url = wp_lostpassword_url();
$register_url = wp_registration_url();
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
		<input type="hidden" name="authvault_action" value="authvault_login" />
		<?php wp_nonce_field( 'authvault_login', 'authvault_login_nonce' ); ?>
		<?php if ( ! empty( $redirect_after_success ) && wp_http_validate_url( $redirect_after_success ) ) : ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_after_success ); ?>" />
		<?php endif; ?>

		<fieldset class="authvault-fieldset">
			<?php
		$username_label_text       = isset( $username_label ) && '' !== (string) $username_label ? $username_label : __( 'Username or email', 'authvault' );
		$username_placeholder_text = isset( $username_placeholder ) && '' !== (string) $username_placeholder ? $username_placeholder : __( 'Username or email', 'authvault' );
		$password_label_text       = isset( $password_label ) && '' !== (string) $password_label ? $password_label : __( 'Password', 'authvault' );
		$password_placeholder_text = isset( $password_placeholder ) && '' !== (string) $password_placeholder ? $password_placeholder : __( 'Password', 'authvault' );
		?>
		<div class="authvault-field authvault-field--username">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-login-username" class="authvault-field__label"><?php echo esc_html( $username_label_text ); ?></label>
				<?php endif; ?>
				<div class="authvault-field-input-wrap">
					<?php if ( ! empty( $show_email_icon ) ) : ?>
						<span class="authvault-field-icon authvault-field-icon--email" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
						</span>
					<?php endif; ?>
					<input type="text" id="authvault-login-username" name="username" class="authvault-field__input authvault-input" autocomplete="username" required
						<?php echo ( ! empty( $show_placeholders ) ) ? ' placeholder="' . esc_attr( $username_placeholder_text ) . '"' : ''; ?>
					/>
				</div>
			</div>
			<div class="authvault-field authvault-field--password">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-login-password" class="authvault-field__label"><?php echo esc_html( $password_label_text ); ?></label>
				<?php endif; ?>
				<div class="authvault-field-password-wrap authvault-field-input-wrap">
					<?php if ( ! empty( $show_password_icon ) ) : ?>
						<span class="authvault-field-icon authvault-field-icon--password" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
						</span>
					<?php endif; ?>
					<input type="password" id="authvault-login-password" name="password" class="authvault-field__input authvault-input" autocomplete="current-password" required
						<?php echo ( ! empty( $show_placeholders ) ) ? ' placeholder="' . esc_attr( $password_placeholder_text ) . '"' : ''; ?>
					/>
					<?php if ( ! empty( $show_password_toggle ) ) : ?>
						<button type="button" class="authvault-toggle-password" aria-label="<?php echo esc_attr__( 'Toggle password visibility', 'authvault' ); ?>">
							<svg class="authvault-icon-eye" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
							<svg class="authvault-icon-eye-off" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143"/><path d="m2 2 20 20"/></svg>
						</button>
					<?php endif; ?>
				</div>
			</div>
			<?php if ( ! empty( $show_remember_me ) ) : ?>
				<div class="authvault-field authvault-field--remember">
					<label class="authvault-field__label authvault-field__label--checkbox">
						<input type="checkbox" name="remember" value="1" class="authvault-field__input authvault-input--checkbox" autocomplete="off" />
						<?php echo esc_html__( 'Remember me', 'authvault' ); ?>
					</label>
				</div>
			<?php endif; ?>
			<div class="authvault-field authvault-field--submit">
				<button type="submit" class="authvault-button authvault-submit"><?php echo esc_html( $submit_button_text ); ?></button>
			</div>
		</fieldset>

		<?php if ( ! empty( $show_forgot_password_link ) || ! empty( $show_register_link ) ) : ?>
			<nav class="authvault-form__links" aria-label="<?php echo esc_attr__( 'Login form links', 'authvault' ); ?>">
				<?php if ( ! empty( $show_forgot_password_link ) ) : ?>
					<a href="<?php echo esc_url( $lostpassword_url ); ?>" class="authvault-form__link"><?php echo esc_html( $forgot_password_link_text ); ?></a>
				<?php endif; ?>
				<?php if ( ! empty( $show_register_link ) && get_option( 'users_can_register' ) ) : ?>
					<a href="<?php echo esc_url( $register_url ); ?>" class="authvault-form__link"><?php echo esc_html( $register_link_text ); ?></a>
				<?php endif; ?>
			</nav>
		<?php endif; ?>
	</form>
</div>
