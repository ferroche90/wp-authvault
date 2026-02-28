/**
 * Admin scripts for WP AuthVault.
 *
 * Tab navigation, conditional field visibility, and page-assignment status dots.
 *
 * @package AuthVault
 */

(function () {
	'use strict';

	/* =================================================================
	   Tab navigation
	   ================================================================= */

	var tabs  = document.querySelectorAll('.authvault-tab-link');
	var panels = document.querySelectorAll('.authvault-tab-panel');

	function activateTab(tabId) {
		tabs.forEach(function (t) {
			t.classList.toggle('authvault-tab-active', t.getAttribute('data-tab') === tabId);
		});
		panels.forEach(function (p) {
			p.classList.toggle('authvault-tab-panel-active', p.getAttribute('data-tab') === tabId);
		});
	}

	function getTabFromHash() {
		var hash = window.location.hash.replace('#', '');
		if (!hash) return '';
		for (var i = 0; i < tabs.length; i++) {
			if (tabs[i].getAttribute('data-tab') === hash) return hash;
		}
		return '';
	}

	tabs.forEach(function (link) {
		link.addEventListener('click', function (e) {
			e.preventDefault();
			var tabId = this.getAttribute('data-tab');
			activateTab(tabId);
			history.replaceState(null, '', '#' + tabId);
		});
	});

	var initial = getTabFromHash();
	activateTab(initial || (tabs.length ? tabs[0].getAttribute('data-tab') : 'general'));

	window.addEventListener('hashchange', function () {
		var t = getTabFromHash();
		if (t) activateTab(t);
	});

	/* =================================================================
	   Preserve tab hash across Settings API save redirect
	   ================================================================= */

	var form = document.getElementById('authvault-settings-form');
	if (form) {
		form.addEventListener('submit', function () {
			var activeTab = document.querySelector('.authvault-tab-link.authvault-tab-active');
			if (activeTab) {
				var hash = activeTab.getAttribute('data-tab');
				var action = form.getAttribute('action') || '';
				action = action.replace(/#.*$/, '') + '#' + hash;
				form.setAttribute('action', action);
			}
		});
	}

	/* =================================================================
	   Conditional visibility helpers
	   ================================================================= */

	function toggleDependents(checkboxId, dependentClass) {
		var cb = document.getElementById(checkboxId);
		if (!cb) return;
		function update() {
			var show = cb.checked;
			document.querySelectorAll('.' + dependentClass).forEach(function (el) {
				el.style.display = show ? '' : 'none';
			});
		}
		cb.addEventListener('change', update);
		update();
	}

	function toggleSelectDependents(selectId, triggerValue, dependentClass) {
		var sel = document.getElementById(selectId);
		if (!sel) return;
		function update() {
			var show = sel.value === triggerValue;
			document.querySelectorAll('.' + dependentClass).forEach(function (el) {
				el.style.display = show ? '' : 'none';
			});
		}
		sel.addEventListener('change', update);
		update();
	}

	// Brute force: lockout settings depend on enable_lockout
	toggleDependents('authvault_enable_lockout', 'authvault-lockout-dependent');

	// Lockout notification email depends on lockout notification checkbox
	toggleDependents('authvault_lockout_admin_email_notification', 'authvault-lockout-notify-dependent');

	// Login log retention depends on enable_login_log
	toggleDependents('authvault_enable_login_log', 'authvault-log-dependent');

	// reCAPTCHA keys depend on recaptcha_enabled
	toggleDependents('authvault_recaptcha_enabled', 'authvault-recaptcha-dependent');

	// Email override fields depend on override checkbox
	toggleDependents('authvault_override_lost_password_email', 'authvault-email-override-dependent');

	// wp-login redirect page depends on behavior = "page"
	toggleSelectDependents('authvault_wp_login_access_behavior', 'page', 'authvault-wplogin-page-dependent');

	// Logged-in redirect page depends on behavior = "page"
	toggleSelectDependents('authvault_logged_in_redirect_behavior', 'page', 'authvault-loggedin-page-dependent');

	// When "Allow weak passwords" is unchecked, minimum length must be at least 10
	(function () {
		var allowWeak = document.getElementById('authvault_allow_weak_passwords');
		var minLengthInput = document.getElementById('authvault_min_password_length');
		var weakNotice = document.getElementById('authvault-password-policy-weak-notice');
		if (!allowWeak || !minLengthInput) return;
		function syncMinLengthConstraint() {
			var allowWeakChecked = allowWeak.checked;
			var minVal = parseInt(minLengthInput.value, 10) || 0;
			if (allowWeakChecked) {
				minLengthInput.min = '1';
				minLengthInput.setAttribute('min', '1');
				if (weakNotice) weakNotice.style.display = 'none';
			} else {
				minLengthInput.min = '10';
				minLengthInput.setAttribute('min', '10');
				if (minVal < 10) {
					minLengthInput.value = '10';
				}
				if (weakNotice) weakNotice.style.display = 'block';
			}
		}
		allowWeak.addEventListener('change', syncMinLengthConstraint);
		syncMinLengthConstraint();
	})();

	/* =================================================================
	   Page assignment status dots — update on dropdown change
	   ================================================================= */

	document.querySelectorAll('.authvault-field-with-status select').forEach(function (sel) {
		sel.addEventListener('change', function () {
			var dot = this.parentNode.querySelector('.authvault-status-dot');
			if (!dot) return;
			if (parseInt(this.value, 10) > 0) {
				dot.className = 'authvault-status-dot authvault-status-ok';
			} else {
				dot.className = 'authvault-status-dot authvault-status-missing';
			}
		});
	});

})();
