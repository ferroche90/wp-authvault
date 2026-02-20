=== WP AuthVault ===

Contributors: (your username)
Tags: authentication, login, register, password reset, elementor, security
Requires at least: 6.4
Tested up to: 6.4
Requires PHP: 8.0
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Replace WordPress default login, register, and password reset with custom pages you can style in Elementor. Includes login lockout, optional reCAPTCHA v3, and URL hardening.

== Description ==

WP AuthVault replaces the default WordPress login, registration, and password reset flows with your own pages. You assign WordPress pages to each flow and style them with Elementor or your theme. The plugin adds security options such as login attempt lockout, optional reCAPTCHA v3, and the ability to hide or redirect direct access to wp-login.php.

= Features =

* **Custom auth pages** — Use any page as your Login, Register, Password Reset, and Password Reset Confirm screens.
* **Elementor widgets** — Drop-in AuthVault Login, Register, and Password Reset widgets for Elementor so you can build and style forms without shortcodes.
* **Shortcodes** — Use `[authvault_login]`, `[authvault_register]`, and `[authvault_reset_password]` in any page or template.
* **URL security** — Optional custom login slug and behavior when someone visits wp-login.php directly (404, redirect to home, or redirect to a custom page).
* **Login lockout** — Limit failed login attempts per IP and lock out for a configurable duration.
* **Optional reCAPTCHA v3** — Add reCAPTCHA v3 to login, register, and reset forms.
* **Optional login logging** — Log successful and failed attempts (hashed IP only) to a custom table for auditing.

= Requirements =

* PHP 8.0 or higher
* WordPress 6.4 or higher
* Composer (for installation)

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/wp-authvault/`, or install the plugin through the WordPress Plugins screen.
2. In the plugin directory, run `composer install` (required for autoloading and dependencies).
3. Activate the plugin via the Plugins screen in WordPress.
4. Go to **Settings → AuthVault** to assign your Login, Register, and Password Reset pages. On first activation, the plugin can create default pages with the AuthVault shortcodes; you can replace them with your own pages and assign them here.
5. Optionally enable URL hiding, lockout, reCAPTCHA, or login logging and configure redirects and roles.

== Frequently Asked Questions ==

= How do I change the login URL? =

In **Settings → AuthVault**, under **URL Security**, enable "Enable login URL hiding" and set **Custom Login URL slug** to the path you want (e.g. `sign-in`). After saving, your login URL will be `https://yoursite.com/sign-in/` (or your chosen slug). Direct visits to `wp-login.php` are then handled according to **When wp-login.php is accessed directly** (404, redirect to homepage, or redirect to a custom page).

= How do I style the forms with Elementor? =

1. Create or edit a page with Elementor.
2. Add the **AuthVault Login**, **AuthVault Register**, or **AuthVault Password Reset** widget from the widget panel.
3. Use the widget’s options and Elementor’s styling controls to customize labels, placeholders, buttons, and layout.
4. Assign that page in **Settings → AuthVault** as the Login, Register, or Password Reset page so the correct URLs are used site-wide.

= How do I override templates from my theme? =

Copy the template file you want to override from `wp-content/plugins/wp-authvault/templates/` into your theme directory under a folder named `authvault`. For example, to override the login form template, create `your-theme/authvault/login.php`. The plugin will load the theme file first if it exists. Do not remove the required security-related fields (nonces, etc.) when overriding.

== Changelog ==

= 1.0.0 =
* Initial release.
* Custom Login, Register, and Password Reset pages with shortcodes and Elementor widgets.
* Settings: page assignments, custom login slug, wp-login.php behavior, registration and default role, lockout and reCAPTCHA, email overrides.
* Login lockout and optional login attempt logging.
* URL hiding and safe redirect handling.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
