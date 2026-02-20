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
 * @var array  $messages
 * @var array  $wrapper_attributes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wrapper_attr = isset( $wrapper_attributes ) && is_array( $wrapper_attributes ) ? $wrapper_attributes : array();
$wrapper_class = 'authvault-form authvault-form--login';
if ( ! empty( $wrapper_attr['class'] ) ) {
	$wrapper_class .= ' ' . ( is_array( $wrapper_attr['class'] ) ? implode( ' ', $wrapper_attr['class'] ) : $wrapper_attr['class'] );
}
$login_url = wp_login_url();
$lostpassword_url = wp_lostpassword_url();
$register_url = wp_registration_url();
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
		<input type="hidden" name="authvault_action" value="authvault_login" />
		<?php wp_nonce_field( 'authvault_login', 'authvault_login_nonce' ); ?>
		<?php if ( ! empty( $redirect_after_success ) && wp_http_validate_url( $redirect_after_success ) ) : ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_url( $redirect_after_success ); ?>" />
		<?php endif; ?>

		<fieldset class="authvault-fieldset">
			<div class="authvault-field authvault-field--username">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-login-username" class="authvault-field__label"><?php echo esc_html__( 'Username or email', 'authvault' ); ?></label>
				<?php endif; ?>
				<input type="text" id="authvault-login-username" name="username" class="authvault-field__input authvault-input" autocomplete="username" required
					<?php echo ( ! empty( $show_placeholders ) ) ? ' placeholder="' . esc_attr__( 'Username or email', 'authvault' ) . '"' : ''; ?>
				/>
			</div>
			<div class="authvault-field authvault-field--password">
				<?php if ( ! empty( $show_labels ) ) : ?>
					<label for="authvault-login-password" class="authvault-field__label"><?php echo esc_html__( 'Password', 'authvault' ); ?></label>
				<?php endif; ?>
				<input type="password" id="authvault-login-password" name="password" class="authvault-field__input authvault-input" autocomplete="current-password" required
					<?php echo ( ! empty( $show_placeholders ) ) ? ' placeholder="' . esc_attr__( 'Password', 'authvault' ) . '"' : ''; ?>
				/>
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
