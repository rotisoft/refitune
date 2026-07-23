# RefiTune - Site Refiner Toolkit

Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements.

RefiTune is an all-in-one toolkit that helps optimize, secure, and refine WordPress websites without touching code. Every feature can be enabled or disabled independently through a clean and intuitive admin interface.

## Why RefiTune?

WordPress sites often accumulate unnecessary features, legacy functionality, and third-party plugins over time. This can lead to slower loading times, increased maintenance overhead, and a larger attack surface.

RefiTune was built to solve these problems through a single, lightweight toolkit that gives site owners and developers precise control over how WordPress behaves.

### One Plugin Instead of Many

Instead of installing separate plugins for:

- XML-RPC disabling
- Login protection
- Maintenance mode
- SMTP configuration
- Admin bar control
- SVG uploads
- WebP conversion
- Comment disabling
- Redirect management
- Performance tweaks

RefiTune brings everything together in one centralized dashboard.

### Modular by Design

Every feature is completely optional.

Enable only the modules you need and leave the rest inactive. No unnecessary code runs when a feature is disabled.

### Performance First

Many modules are designed specifically to reduce WordPress overhead by:

- Removing unnecessary frontend and backend assets
- Limiting database growth
- Optimizing background processes
- Reducing external requests

### Security Without Complexity

RefiTune includes practical security improvements that can be enabled with a single click, helping protect sites against common attack vectors without requiring advanced server knowledge.

### Built for Real-World WordPress Sites

Whether you're managing:

- A personal blog
- A business website
- A WooCommerce store
- A client portfolio
- Multiple WordPress installations

RefiTune helps keep your sites lean, secure, and easier to maintain.

### Developer Friendly

RefiTune follows the WordPress philosophy of flexibility and transparency. Features are organized into independent modules, making it easy to understand exactly what is active on a site.

No hidden optimizations. No mysterious settings. Just clear controls for the features you choose to use.

## Features (35 modules)

### Performance

- Header Cleanup - Strip unnecessary `wp_head` output for leaner pages.
- Feed Management - Control RSS/Atom feed link tags in the document head.
- Disable Emoji - Remove WordPress emoji scripts and styles.
- Disable jQuery Migrate - Drop legacy jquery-migrate when your stack does not need it.
- Disable oEmbed - Stop automatic embeds from pasted YouTube, Vimeo, Twitter/X, and similar URLs.
- Remove Asset Version Query Strings - Strip `?ver=` from front-end CSS/JS (can break cache busting; prefer CDN purge or hashed filenames).
- Post Revisions Limit - Cap stored revisions per post.
- Auto-save Interval - Change how often the editor auto-saves.
- Trash Auto-Delete - Set trash retention; expired items are removed in batches so large queues stay memory-safe.
- Convert Uploads to WebP - Convert JPEG/PNG to WebP on upload, optional max size resize, then remove the original (GD or Imagick with WebP support).
- Heartbeat API Control - Tune or disable Heartbeat in admin, front end, and the post editor.

### Security

- Hide Generator Tags - Remove WordPress (and WooCommerce, when active) version meta tags.
- Disable XML-RPC - Respond to XML-RPC with 404 and remove RSD discovery.
- Disable Trackback/Pingback - Close pings and strip pingback methods/headers.
- Disable File Editor - Set `DISALLOW_FILE_EDIT` so theme/plugin editors stay off.
- Automatic Updates Control - Tri-state plugin, theme, translation, and core updates; reschedule update checks. Respects `AUTOMATIC_UPDATER_DISABLED` and `WP_AUTO_UPDATE_CORE` when defined.
- Login Error Messages - Generic login errors to reduce username enumeration.
- Restrict Admin Access - Choose which roles may open the wp-admin UI. Users with `manage_options` always keep access. Front-end `admin-ajax.php` is intentionally not blocked.
- REST API Restrictions - Limit selected core REST routes to users with `manage_options`.
- Login Limit - Rate-limit failed logins by IP and IP+username pair (`REMOTE_ADDR` only). Optional IP whitelist (one address per line). Covers `wp-login.php` and other `wp_signon()` paths (including WooCommerce).
- Verified Upload - Block disguised uploads: double extensions, MIME/magic mismatches, and script markers.

### Visual

- Hide Admin Bar - Hide the admin bar for selected roles.
- Block Visibility (Mobile) - Show/hide blocks by device via `wp_is_mobile()`; sends `Vary: User-Agent` (full-page caches must honour it). Native support exists in WordPress 7.0+.
- Login Page Customization - Brand wp-login.php with logo and colours.

### Email

- Email Notifications - Disable or redirect selected WordPress system emails.
- Email Sending - SMTP with encrypted password storage, or disable all site emails. In production, disabling TLS/certificate verification is blocked.

### Miscellaneous

- Disable Comments - Site-wide comments off (optional WooCommerce review keep).
- External Links in New Window - Open external links in a new tab with safe `rel` attributes.
- Enable Page Excerpt - Excerpt support for pages.
- Clean Upload Filenames - Sanitize upload filenames (accents, spaces, special characters).
- SVG Upload - Role-gated SVG uploads with allowlist-based sanitization.
- AVIF Upload - Role-gated AVIF uploads (full core AVIF support needs WordPress 6.5+).
- Role Redirects - Per-role login and logout redirect URLs.
- Maintenance Mode - 503 maintenance page for guests; chosen roles keep access. Admin, AJAX, and cron stay available so staff can work.
- Dynamic Year Shortcodes - `[refi-year]` and `[refi-year from="2006"]`.

## Screenshots

### Dashboard

Overview of all available modules and their status.

### Settings

Configure each feature individually.

### Help

Detailed documentation and usage instructions.

## Requirements

| Requirement | Version |
|-------------|---------|
| WordPress | 6.2+ |
| Tested up to (WP) | 7.0 |
| PHP | 7.4+ |
| Tested up to (PHP) | 8.5 |

## Installation

### WordPress Admin

1. Go to **Plugins → Add New**.
2. Upload the plugin ZIP file.
3. Activate the plugin.

### Manual Installation

1. Upload the plugin folder to:

```text
/wp-content/plugins/refitune/
```

2. Activate the plugin from the WordPress admin panel.
3. Navigate to:

```text
Tools → RefiTune - Site Refiner Toolkit
```

4. Enable the modules you want to use.

## Translations

Currently available languages:

- English (default)
- Hungarian (Magyar)

The plugin is translation-ready. Language files live in `/languages/`. Compile `.po` to `.mo` for WordPress to load translations.

**Text domain:** `refitune`

## FAQ

### Will RefiTune slow down my website?

No. Most modules are designed to improve performance by removing unnecessary WordPress functionality and reducing resource usage.

### Is it safe to disable jQuery Migrate?

Only if your theme and plugins are compatible with modern jQuery versions. Test thoroughly before enabling this option on production sites.

### What happens if I disable XML-RPC?

Some third-party services, mobile applications, and legacy integrations may stop working.

### How does Login Limit identify clients?

It uses `REMOTE_ADDR` only (not spoofable `X-Forwarded-For`). Failed attempts are counted per IP and per IP+username pair. Add static office or trusted egress IPs to the whitelist (one per line). Email logins and WooCommerce `wp_signon()` paths are covered.

### Can administrators lock themselves out of wp-admin?

No. Users with the `manage_options` capability always retain access. Restrict Admin Access only hides the wp-admin UI for other roles; front-end AJAX is intentionally not blocked.

### Is Maintenance Mode SEO-friendly?

Yes. The plugin returns an HTTP 503 status code while maintenance mode is active. Admin, AJAX, and cron remain available so staff can work.

### Can wp-config.php override Automatic Updates Control?

Yes. `AUTOMATIC_UPDATER_DISABLED` and `WP_AUTO_UPDATE_CORE` override RefiTune background update settings when defined. Update *check* frequency is still controlled by RefiTune cron scheduling.

### What does "Enable all" mean for plugins and themes?

It forces automatic updates for every plugin or theme and overrides per-item toggles on the Updates screen. Use with care on production sites.

### Is SMTP test mode safe on production?

No. While `WP_ENVIRONMENT_TYPE` is `production`, RefiTune will not run without encryption/certificate verification.

## Changelog

See `readme.txt` in the plugin (Stable tag 1.3.0) for full details.

## Links

- Plugin page: [rotistudio.com/plugins/refitune](https://rotistudio.com/plugins/refitune-site-refiner-toolkit-for-wordpress)
- Author plugins: [rotistudio.com](https://rotistudio.com/)
- GitHub: [github.com/rotisoft/refitune](https://github.com/rotisoft/refitune)
- Hungarian documentation: [rotistudio.hu](https://rotistudio.hu/bovitmenyek/refitune-eszkoztar-wordpress-finomhangolashoz/)
- Author website: [rottenbacher.hu](https://rottenbacher.hu/)

## License

Licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).
