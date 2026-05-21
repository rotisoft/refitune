=== RefinerPress Toolkit ===
Contributors: rtomo
Tags: performance, security, tweaks, optimization, admin
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://rotistudio.com/contact/

Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements — all in one toolkit.

== Description ==

Hungarian: [Magyar nyelvű bővítmény leírás](https://rotistudio.hu/bovitmenyek/refinerpress-toolkit-wordpress-bovitmeny)

**RefinerPress Toolkit** is your Swiss Army knife for WordPress optimization. Whether you're cleaning up unnecessary code, securing your login page, or customizing the admin experience, this plugin puts the power in your hands.

Each feature can be enabled or disabled individually with just one click — no coding required. A clean, organized dashboard shows you exactly what's active and what's not.

**What's Inside? (24+ Features)**

**Performance Tweaks:**
* **Header Cleanup** – Strip out unnecessary wp_head bloat for faster loading.
* **Feed Management** – Take control of your RSS/Atom feeds.
* **Emoji Disable** – Remove WordPress emoji scripts (because who needs them?).
* **jQuery Migrate Disable** – Drop legacy jquery-migrate for leaner pages.
* **Post Revisions Limit** – Keep your database tidy by limiting stored revisions.
* **Auto-save Interval** – Control how often WordPress auto-saves your work.
* **Trash Auto-Delete** – Set how long items stay in trash before permanent deletion.

**Security & Access Control:**
* **XML-RPC Disable** – Lock down the XML-RPC interface completely.
* **Trackback/Pingback Disable** – Stop spam by disabling trackbacks and pingbacks.
* **File Editor Disable** – Hide the built-in code editor for extra security.
* **Login Error Messages** – Make login errors generic to prevent username fishing.
* **Admin Access Restrictions** – Choose which roles can access wp-admin.
* **REST API Restrictions** – Smart restrictions for sensitive REST API endpoints.
* **Login Limit** – Block brute-force attacks by limiting failed login attempts.
* **Maintenance Mode** – Temporarily block visitors while you work on the site.

**Visual & UX Improvements:**
* **Hide Admin Bar** – Selectively hide the admin bar for specific roles.
* **Block Visibility** – Show/hide Gutenberg blocks on mobile or desktop.
* **Login Page Customization** – Brand your wp-login.php with custom logo and colors.

**Email & Communication:**
* **Email Notifications** – Fine-tune or disable WordPress system emails.
* **Email SMTP** – Configure external SMTP or disable all emails completely.

**Content & Media:**
* **Comments Disable** – Turn off comments site-wide (with WooCommerce review support).
* **External Links** – Auto-open external links in new tabs (with proper rel attributes).
* **Page Excerpt** – Enable excerpt fields for pages (not just posts).
* **SVG Upload** – Allow SVG uploads with built-in security filtering.
* **AVIF Upload** – Support modern AVIF image format uploads.

**Advanced Features:**
* **Role Redirects** – Send users to custom pages after login or logout based on their role.

== Installation ==

**Getting Started is Easy:**

1. Upload the plugin to `/wp-content/plugins/refinerpress` (or install directly from WordPress).
2. Activate it through the 'Plugins' menu.
3. Head to **Tools > RefinerPress Toolkit** and start enabling features.
4. That's it! Each feature has helpful descriptions and a detailed Help page.

== Translations ==

RefinerPress Toolkit speaks your language! Currently available in:
* 🇬🇧 English (default)
* 🇭🇺 Hungarian (Magyar)

The plugin is fully translation-ready. Want to contribute a translation? Language files go in `/wp-content/plugins/refinerpress/languages/`.

Text Domain: `refinerpress`

== Frequently Asked Questions ==

= Will this plugin slow down my site? =

Nope! RefinerPress is designed to *speed up* your site by removing bloat. Most features actively improve performance.

= Is it safe to disable jQuery Migrate? =

Only if you're sure your theme and plugins work with the latest jQuery. When in doubt, leave it off and test first.

= What happens if I disable XML-RPC? =

Mobile apps and some plugins (like older Jetpack features) might stop working. If you're using the WordPress mobile app or remote publishing tools, keep XML-RPC enabled.

= Does the Login Limit work with email addresses? =

Yep! WordPress lets you log in with either username or email, and our login limiter tracks both.

= Can I hide wp-admin from certain user roles? =

Absolutely. Use "Admin Access Restrictions" to choose which roles can access wp-admin. Administrators always have access (you can't lock yourself out).

= What's Maintenance Mode good for? =

Perfect for when you're making updates and don't want visitors seeing broken pages. You choose which roles can still access the site while everyone else sees a clean maintenance message. SEO-friendly too (returns 503 status).

== Screenshots ==

1. Dashboard - Overview of all features with quick status indicators
2. Settings - Configure each feature individually with detailed options
3. Help - Detailed documentation for each feature

== Changelog ==

= 1.0.0 =
* Initial release — everything is shiny and new!
* WordPress 7.0 compatibility check
* PHP 8.5 compatibility check

== Upgrade Notice ==

= 1.0.0 =
* Initial release — everything is shiny and new!
* WordPress 7.0 compatibility check
* PHP 8.5 compatibility check
