=== RefiTune - Site refiner toolkit ===
Contributors: rtomo, rotistudio
Tags: performance, security, tweaks, optimization, toolkit
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Donate link: https://rotistudio.com/contact/

Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements. RefiTune is an all-in-one toolkit.

== Description ==

Hungarian: [Magyar nyelvű bővítmény leírás](https://rotistudio.hu/bovitmenyek/refitune-eszkoztar-wordpress-finomhangolashoz/)

**RefiTune - Site refiner toolkit** is a modular Swiss Army knife for WordPress. Clean up unnecessary front-end code, harden login and uploads, tune Heartbeat and updates, or brand wp-login - each module is opt-in.

Enable or disable features individually. A clean dashboard shows what is active. In-plugin Help pages document behaviour, trade-offs, and requirements.

**What's Inside? (35 Modules)**

**Performance:**
* **Header Cleanup** - Strip unnecessary wp_head output for leaner pages.
* **Feed Management** - Control RSS/Atom feed link tags in the document head.
* **Disable Emoji** - Remove WordPress emoji scripts and styles.
* **Disable jQuery Migrate** - Drop legacy jquery-migrate when your stack does not need it.
* **Disable oEmbed** - Stop automatic embeds from pasted YouTube, Vimeo, Twitter/X, and similar URLs.
* **Remove Asset Version Query Strings** - Strip `?ver=` from front-end CSS/JS (can break cache busting; prefer CDN purge or hashed filenames).
* **Post Revisions Limit** - Cap stored revisions per post.
* **Auto-save Interval** - Change how often the editor auto-saves.
* **Trash Auto-Delete** - Set trash retention; expired items are removed in batches so large queues stay memory-safe.
* **Convert Uploads to WebP** - Convert JPEG/PNG to WebP on upload, optional max size resize, then remove the original (GD or Imagick with WebP; uses unique filenames and refuses unsafe overwrites).
* **Heartbeat API Control** - Tune or disable Heartbeat in admin, front end, and the post editor.

**Security:**
* **Hide Generator Tags** - Remove WordPress (and WooCommerce, when active) version meta tags.
* **Disable XML-RPC** - Respond to XML-RPC with 404 and remove RSD discovery.
* **Disable Trackback/Pingback** - Close pings and strip pingback methods/headers.
* **Disable File Editor** - Set `DISALLOW_FILE_EDIT` so theme/plugin editors stay off.
* **Automatic Updates Control** - Tri-state plugin, theme, translation, and core updates; reschedule update checks. Respects `AUTOMATIC_UPDATER_DISABLED` and `WP_AUTO_UPDATE_CORE` when defined.
* **Login Error Messages** - Generic login errors to reduce username enumeration.
* **Restrict Admin Access** - Choose which roles may open wp-admin UI. Users with `manage_options` always keep access. Front-end `admin-ajax.php` is intentionally not blocked.
* **REST API Restrictions** - Limit selected core REST routes to users with `manage_options`.
* **Login Limit** - Rate-limit failed logins by IP and IP+username pair (`REMOTE_ADDR` only). Optional IP whitelist (one address per line). Covers `wp-login.php` and other `wp_signon()` paths (including WooCommerce).
* **Verified Upload** - Block disguised uploads: double extensions, MIME/magic mismatches, and script markers.

**Visual:**
* **Hide Admin Bar** - Hide the admin bar for selected roles.
* **Block Visibility (Mobile)** - Show/hide blocks by device via `wp_is_mobile()`; sends `Vary: User-Agent` (full-page caches must honour it).
* **Login Page Customization** - Brand wp-login.php with logo and colours.

**Email:**
* **Email Notifications** - Disable or redirect selected WordPress system emails.
* **Email sending** - SMTP with encrypted password storage, or disable all site emails. In production, disabling TLS/certificate verification is blocked.

**Miscellaneous:**
* **Disable Comments** - Site-wide comments off (optional WooCommerce review keep).
* **External Links in New Window** - Open external links in a new tab with safe `rel` attributes.
* **Enable Page Excerpt** - Excerpt support for pages.
* **Clean Upload Filenames** - Sanitize upload filenames (accents, spaces, special characters).
* **SVG Upload** - Role-gated SVG uploads with allowlist-based sanitization (XXE-safe parse).
* **AVIF Upload** - Role-gated AVIF uploads (full core AVIF support needs WordPress 6.5+).
* **Role Redirects** - Per-role login and logout redirect URLs.
* **Maintenance Mode** - 503 maintenance page for guests; chosen roles keep access. Admin, AJAX, and cron stay available so staff can work.
* **Dynamic Year Shortcodes** - `[refi-year]` and `[refi-year from="2006"]`.

More plugins: [rotistudio.com](https://rotistudio.com/)
Author site: [rottenbacher.hu](https://rottenbacher.hu/)
GitHub: [github.com/rotisoft/refitune](https://github.com/rotisoft/refitune)

== Installation ==

1. Upload to `/wp-content/plugins/refitune` (or install from WordPress.org).
2. Activate under Plugins.
3. Open **Tools > RefiTune - Site refiner toolkit** and enable the modules you need.
4. Use the Help tab for per-feature documentation.

== Translations ==

* English (default - source strings in code and `refitune.pot`)
* Hungarian (Magyar) - `refitune-hu_HU.po` (compile to `.mo` for WordPress to load)

Contribute translations under `/wp-content/plugins/refitune/languages/`. Text Domain: `refitune`.

== Frequently Asked Questions ==

= Will this plugin slow down my site? =

No. Most modules remove unused front-end work or add checks only on login/upload. Features are opt-in.

= Is it safe to disable jQuery Migrate? =

Only if your theme and plugins work with current jQuery. Test first; leave it off when unsure.

= What happens if I disable XML-RPC? =

The WordPress mobile app, Jetpack sync, and other remote clients that need XML-RPC may stop working.

= How does Login Limit identify clients? =

It uses `REMOTE_ADDR` only (not spoofable `X-Forwarded-For`). Failed attempts are counted per IP and per IP+username pair. Add static office or trusted egress IPs to the whitelist (one per line). Behind a CDN or shared NAT, many visitors share one address - whitelist only IPs you control.

= Does Login Limit work with email logins and WooCommerce? =

Yes. Email logins are canonicalized to usernames where possible, and lockouts are checked on `authenticate` so `wp_signon()` paths (including WooCommerce) are covered.

= Can I hide wp-admin from certain roles? =

Yes. Restrict Admin Access limits the wp-admin UI by role. Anyone with `manage_options` always retains access. AJAX is not blocked so front-end `admin-ajax.php` callbacks keep working; each handler must still check capabilities.

= What's Maintenance Mode good for? =

Short downtime windows. Guests get a 503 page; selected roles still browse. Admin, AJAX, and cron remain available so developers can test plugins that need those endpoints.

= Can wp-config.php override Automatic Updates Control? =

Yes. `AUTOMATIC_UPDATER_DISABLED` and `WP_AUTO_UPDATE_CORE` override RefiTune background update settings when defined. Update *check* frequency still follows RefiTune cron scheduling.

= What does "Enable all" mean for plugins and themes? =

It forces automatic updates for every plugin or theme and overrides per-item toggles on the Updates screen. "Disable all" blocks them; "WordPress default" leaves native behaviour unchanged.

= Is SMTP test mode safe on production? =

No. While `WP_ENVIRONMENT_TYPE` is `production`, RefiTune will not run without encryption/certificate verification, and the test-mode checkbox cannot be enabled. Set the environment type correctly on live sites.

== Screenshots ==

1. Dashboard - Overview of all features with quick status indicators
2. Settings - Configure each feature individually with detailed options
3. Help - Detailed documentation for each feature

== Changelog ==

= 1.3.1 =
* Minor language file fixes.

= 1.3.0 =
* New: Convert Uploads to WebP (JPEG/PNG), optional resize, original removal when conversion succeeds
* Security: WebP conversion uses unique filenames and refuses unsafe overwrites; source deleted only after a valid WebP
* Security: Login Limit uses IP and IP+username pair lockouts (no global per-user lockout); atomic counters; `REMOTE_ADDR` only; IP whitelist (one per line)
* Security: Restrict Admin Access always allows `manage_options`; documents intentional AJAX skip for front-end callbacks
* Security: SVG sanitizer limits (size/nodes/depth), safer href/style rules; SMTP fail-closed in production for test mode
* Security: Multisite network upload MIME settings respected for SVG, AVIF, and WebP conversion
* Fix: WooCommerce-aware load order for comment and head cleanup modules
* Fix: Trash auto-delete runs in batches and clears schedule on deactivate
* Fix: Block Visibility sends `Vary: User-Agent`; asset `?ver=` removal warnings clarified
* Fix: Uninstall cleanup per site on multisite; removes leftover login-limit transients when data deletion is enabled
* Docs: Help and settings copy for Login Limit, Admin Access, WebP, AVIF, REST labels; Hungarian `.po` updated
* Docs: Replaced typographic en/em dashes with ASCII hyphens in admin UI strings, Help text, and language files (`.pot` / `.po`)

= 1.2.2 =
* Security: stricter REST restrictions, SVG sanitizer hardening, admin capability checks
* Fix: Maintenance Mode REST 503; update-check cron restore; Heartbeat/login-limit/email/upload fixes
* Performance: update-check reschedule limited to admin/cron; auto-updates notice scoped
* Code quality: shared settings helper, explicit hook priorities; minimum WordPress 6.2

= 1.2.1 =
* Fix: Verified Upload no longer blocks legitimate JPEG/PNG uploads

= 1.2.0 =
* New: Automatic Updates Control, Remove Asset Version Query Strings, Verified Upload, Clean Upload Filenames, Disable oEmbed
* Fix: Plugin Check compatibility; Media Library / SVG sanitization conflict

= 1.1.0 =
* Security: Safer redirects, SVG sanitization, REST restrictions, SMTP credentials
* Refactor: Modular settings sanitization

= 1.0.0 =
* Initial release
* WordPress 7.0 and PHP 8.5 compatibility check

== Upgrade Notice ==

= 1.3.1 =
Hungarian translation fixes for Help and settings module descriptions; shorter English module summaries on the settings screen.

= 1.3.0 =
WebP upload conversion plus login, upload, SVG, SMTP, and admin-access hardening. Review Login Limit whitelist and SMTP environment type after update.

= 1.2.2 =
Recommended security and stability update.

= 1.2.1 =
Fixed Verified Upload rejecting safe image uploads.

= 1.2.0 =
Adds new modules; fixed Media Library conflict.

= 1.1.0 =
Recommended security update.

= 1.0.0 =
Initial release.
