=== RefiTune - Site refiner toolkit ===
Contributors: rtomo, rotistudio
Tags: performance, security, tweaks, optimization, toolkit
Requires at least: 5.9
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.2.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://rotistudio.com/contact/

Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements. RefiTune is all in one toolkit.

== Description ==

Hungarian: [Magyar nyelvű bővítmény leírás](https://rotistudio.hu/bovitmenyek/refitune-eszkoztar-wordpress-finomhangolashoz/)

**RefiTune - Site refiner toolkit** is your Swiss Army knife for WordPress optimization. Whether you're cleaning up unnecessary code, securing your login page, or customizing the admin experience, this plugin puts the power in your hands.

Each feature can be enabled or disabled individually with just one click — no coding required. A clean, organized dashboard shows you exactly what's active and what's not.

**What's Inside? (33 Modules)**

**Performance:**
* **Header Cleanup** – Strip out unnecessary wp_head bloat for faster loading.
* **Feed Management** – Take control of your RSS/Atom feeds.
* **Disable Emoji** – Remove WordPress emoji scripts.
* **Disable jQuery Migrate** – Drop legacy jquery-migrate for leaner pages.
* **Disable oEmbed** – Stop automatic embedding of YouTube, Vimeo, Twitter, and other external URLs.
* **Remove Asset Version Query Strings** – Strip ?ver= from frontend CSS and JS URLs.
* **Post Revisions Limit** – Keep your database tidy by limiting stored revisions.
* **Auto-save Interval** – Control how often WordPress auto-saves your work.
* **Trash Auto-Delete** – Set how long items stay in trash before permanent deletion.
* **Heartbeat API Control** – Tune or disable Heartbeat in admin, frontend, and post editor.

**Security:**
* **Disable XML-RPC** – Lock down the XML-RPC interface completely.
* **Disable Trackback/Pingback** – Stop spam by disabling trackbacks and pingbacks.
* **Disable File Editor** – Hide the built-in code editor for extra security.
* **Automatic Updates Control** – Global tri-state control for automatic plugin, theme, translation, and core (minor, major, development) updates; reschedule update checks (WordPress default twice daily, daily, or every 3/7/14 days). Respects `AUTOMATIC_UPDATER_DISABLED` and `WP_AUTO_UPDATE_CORE` in wp-config.php when defined.
* **Login Error Messages** – Make login errors generic to prevent username fishing.
* **Restrict Admin Access** – Choose which roles can access wp-admin.
* **REST API Restrictions** – Smart restrictions for sensitive REST API endpoints.
* **Login Limit** – Block brute-force attacks by limiting failed login attempts.
* **Verified Upload** – Block disguised uploads such as double extensions, MIME mismatches, and embedded script markers.

**Visual:**
* **Hide Admin Bar** – Selectively hide the admin bar for specific roles.
* **Block Visibility (Mobile)** – Show/hide Gutenberg blocks on mobile or desktop.
* **Login Page Customization** – Brand your wp-login.php with custom logo and colors.

**Email:**
* **Email Notifications** – Fine-tune or disable WordPress system emails.
* **Email sending** – Configure external SMTP or disable all emails completely.

**Miscellaneous:**
* **Disable Comments** – Turn off comments site-wide (with WooCommerce review support).
* **External Links in New Window** – Auto-open external links in new tabs (with proper rel attributes).
* **Enable Page Excerpt** – Enable excerpt fields for pages (not just posts).
* **Clean Upload Filenames** – Sanitize image and document filenames on upload (accents, spaces, special characters).
* **SVG Upload** – Allow SVG uploads with built-in security filtering.
* **AVIF Upload** – Support modern AVIF image format uploads.
* **Role Redirects** – Send users to custom pages after login or logout based on their role.
* **Maintenance Mode** – Temporarily block visitors while you work on the site.
* **Dynamic Year Shortcodes** – Display the current year via `[refi-year]` or duration via `[refi-year from="2006"]` shortcodes.

Do you have other plugins? Yes, check my plugins website: [rotistudio.com](https://rotistudio.com/)
Where can we learn more about your work? Check my personal website there: [rottenbacher.hu](https://rottenbacher.hu/)
Plugin GitHub repository: [github.com/rotisoft/refitune](https://github.com/rotisoft/refitune)

== Installation ==

**Getting Started is Easy:**

1. Upload the plugin to `/wp-content/plugins/refitune` (or install directly from WordPress).
2. Activate it through the 'Plugins' menu.
3. Head to **Tools > RefiTune - Site refiner toolkit** and start enabling features.
4. That's it! Each feature has helpful descriptions and a detailed Help page.

== Translations ==

RefiTune - Site refiner toolkit speaks your language! Currently available in:
* 🇬🇧 English (default — source strings in code and `refitune.pot`)
* 🇭🇺 Hungarian (Magyar) — `refitune-hu_HU.po`

The plugin is fully translation-ready. Want to contribute a translation? Language files go in `/wp-content/plugins/refitune/languages/`. Compile `.po` to `.mo` (e.g. with Poedit, Loco Translate, or `wp i18n make-mo`) for WordPress to load translations.

Text Domain: `refitune`

== Frequently Asked Questions ==

= Will this plugin slow down my site? =

Nope! RefiTune is designed to *speed up* your site by removing bloat. Most features actively improve performance.

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

= Can wp-config.php override Automatic Updates Control? =

Yes, for background updates. If `AUTOMATIC_UPDATER_DISABLED` is set to `true`, WordPress disables all automatic background updates site-wide; RefiTune cannot enable them until that constant is removed or set to `false`. If `WP_AUTO_UPDATE_CORE` is defined, it overrides RefiTune core minor/major/development settings. Update *check* frequency (how often WordPress looks for new versions) is controlled by RefiTune cron scheduling and is not overridden by those constants. An admin notice appears when conflicting constants are detected.

= What does "Enable all" mean for plugins and themes? =

It forces automatic updates for every plugin or theme and overrides per-item toggles on the Updates screen. Use with care on production sites. "Disable all" blocks automatic updates for that type. "WordPress default" leaves native behavior unchanged.

== Screenshots ==

1. Dashboard - Overview of all features with quick status indicators
2. Settings - Configure each feature individually with detailed options
3. Help - Detailed documentation for each feature

== Changelog ==

= 1.2.1 =
* Fix: Verified Upload no longer blocks legitimate JPEG and PNG uploads (e.g. JPEG saved with a .png extension); script-marker scanning is skipped for verified binary raster images to avoid false positives

= 1.2.0 =
* New: Automatic Updates Control
* New: Remove Asset Version Query Strings
* New: Verified Upload
* New: Clean Upload Filenames
* New: Disable oEmbed
* Fix: Plugin Check compatibility
* Fix: Media Library and SVG sanitization function conflict

= 1.1.0 =
* Security: Safer redirect validation, SVG sanitization, REST API restrictions, and SMTP credential handling.
* Refactor: Modular settings sanitization.

= 1.0.0 =
* Initial release — everything is shiny and new!
* WordPress 7.0 compatibility check
* PHP 8.5 compatibility check

== Upgrade Notice ==

= 1.2.1 =
* Fixed Verified Upload rejecting safe image uploads

= 1.2.0 =
* Adds few new functions
* Fixed Media Library conflict

= 1.1.0 =
* Recommended security update: hardened redirects, SVG sanitization, REST API restrictions, and SMTP credential handling.

= 1.0.0 =
* Initial release — everything is shiny and new!
* WordPress 7.0 compatibility check
* PHP 8.5 compatibility check
