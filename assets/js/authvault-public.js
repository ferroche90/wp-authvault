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

	/* -----------------------------------------------------------------
	   Password strength meter (reset-confirm form)
	   Uses WP's bundled wp.passwordStrength.meter (zxcvbn).
	   ----------------------------------------------------------------- */

	var STRENGTH_CLASSES = ['very-weak', 'weak', 'medium', 'strong'];
	var STRENGTH_WIDTHS  = ['25%', '50%', '75%', '100%'];

	function getStrengthLabels() {
		if (window.authvaultStrength && window.authvaultStrength.labels) {
			return window.authvaultStrength.labels;
		}
		return ['Very weak', 'Weak', 'Medium', 'Strong'];
	}

	function updateStrengthMeter(score) {
		var fill  = document.getElementById('authvault-strength-fill');
		var label = document.getElementById('authvault-strength-label');
		var warn  = document.getElementById('authvault-weak-message');
		if (!fill || !label) {
			return;
		}

		var idx = Math.max(0, Math.min(score - 1, 3));
		if (score < 1) {
			idx = 0;
		}

		fill.className  = 'authvault-strength__fill authvault-strength__fill--' + STRENGTH_CLASSES[idx];
		fill.style.width = STRENGTH_WIDTHS[idx];
		label.textContent = getStrengthLabels()[idx];
		label.className   = 'authvault-strength__label authvault-strength__label--' + STRENGTH_CLASSES[idx];

		if (warn) {
			if (score < 3) {
				warn.classList.add('authvault-weak-message--visible');
			} else {
				warn.classList.remove('authvault-weak-message--visible');
			}
		}
	}

	function syncPass2(val) {
		var pass2 = document.getElementById('authvault-reset-pass2');
		if (pass2) {
			pass2.value = val;
		}
	}

	function initStrengthMeter() {
		var passInput = document.getElementById('authvault-reset-pass1');
		if (!passInput) {
			return;
		}

		var hasMeter = typeof wp !== 'undefined' && wp.passwordStrength && wp.passwordStrength.meter;

		function evaluate() {
			var val = passInput.value;
			syncPass2(val);

			if (!hasMeter || val.length === 0) {
				var fill  = document.getElementById('authvault-strength-fill');
				var label = document.getElementById('authvault-strength-label');
				if (fill) {
					fill.style.width = '0';
					fill.className = 'authvault-strength__fill';
				}
				if (label) {
					label.textContent = '';
				}
				var warn = document.getElementById('authvault-weak-message');
				if (warn) {
					warn.classList.remove('authvault-weak-message--visible');
				}
				return;
			}

			var score = wp.passwordStrength.meter(val, [], val);
			updateStrengthMeter(score);
		}

		passInput.addEventListener('input', evaluate);
		passInput.addEventListener('change', evaluate);

		if (passInput.value.length > 0) {
			evaluate();
		}
	}

	/* -----------------------------------------------------------------
	   Generate password button
	   ----------------------------------------------------------------- */

	function generateStrongPassword(length) {
		length = length || 24;
		var charset = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()-_=+[]{}|;:,.<>?';
		var password = '';
		if (window.crypto && window.crypto.getRandomValues) {
			var values = new Uint32Array(length);
			window.crypto.getRandomValues(values);
			for (var i = 0; i < length; i++) {
				password += charset[values[i] % charset.length];
			}
		} else {
			for (var j = 0; j < length; j++) {
				password += charset[Math.floor(Math.random() * charset.length)];
			}
		}
		return password;
	}

	function initGenerateButton() {
		var btn = document.getElementById('authvault-generate');
		var passInput = document.getElementById('authvault-reset-pass1');
		if (!btn || !passInput) {
			return;
		}

		btn.addEventListener('click', function (e) {
			e.preventDefault();
			var newPass = generateStrongPassword(24);
			passInput.value = newPass;
			passInput.type = 'text';

			var toggle = passInput.closest('.authvault-field-password-wrap, .authvault-field-input-wrap');
			if (toggle) {
				var toggleBtn = toggle.querySelector('.authvault-toggle-password');
				if (toggleBtn) {
					toggleBtn.classList.add('is-active');
				}
			}

			syncPass2(newPass);
			passInput.dispatchEvent(new Event('input', { bubbles: true }));
			passInput.focus();
		});
	}

	/* -----------------------------------------------------------------
	   Initialize on DOMContentLoaded
	   ----------------------------------------------------------------- */

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			initStrengthMeter();
			initGenerateButton();
		});
	} else {
		initStrengthMeter();
		initGenerateButton();
	}
})();
