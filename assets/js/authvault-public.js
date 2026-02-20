/**
 * Public-facing scripts for WP AuthVault.
 *
 * @package AuthVault
 */

(function () {
	'use strict';

	/**
	 * Password visibility toggle: delegation so it works for static and dynamically loaded forms.
	 */
	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest && e.target.closest('.authvault-toggle-password');
		if (!btn) {
			return;
		}
		e.preventDefault();

		var wrap = btn.closest('.authvault-field-password-wrap, .authvault-field-input-wrap');
		var input = wrap ? wrap.querySelector('input[type="password"], input[type="text"]') : null;

		if (!input || (input.type !== 'password' && input.type !== 'text')) {
			return;
		}

		var isPassword = input.type === 'password';
		input.type = isPassword ? 'text' : 'password';
		if (isPassword) {
			btn.classList.add('is-active');
		} else {
			btn.classList.remove('is-active');
		}
		input.focus();
	});
})();
