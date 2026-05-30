# RefiTune – Site Refiner Toolkit

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

## Features

### Performance

- Header Cleanup – Remove unnecessary `wp_head` output.
- Feed Management – Control RSS and Atom feeds.
- Disable Emoji – Remove WordPress emoji scripts and styles.
- Disable jQuery Migrate – Eliminate legacy jQuery compatibility layer.
- Post Revisions Limit – Reduce database bloat.
- Auto-save Interval – Customize WordPress autosave frequency.
- Trash Auto-Delete – Automatically remove old trash items.
- Heartbeat API Control – Configure or disable Heartbeat API.

### Security

- Disable XML-RPC – Completely disable XML-RPC access.
- Disable Trackback/Pingback – Prevent spam and abuse.
- Disable File Editor – Hide built-in theme and plugin editors.
- Login Error Messages – Use generic login errors.
- Restrict Admin Access – Control access to `wp-admin`.
- REST API Restrictions – Protect sensitive REST endpoints.
- Login Limit – Limit failed login attempts and reduce brute-force attacks.

### Visual

- Hide Admin Bar – Hide the toolbar for selected user roles.
- Block Visibility (Mobile) – Show or hide Gutenberg blocks by device type.
- Login Page Customization – Customize logo, colors, and branding.

### Email

- Email Notifications – Disable or fine-tune WordPress system emails.
- Email Sending – Configure SMTP or disable email delivery entirely.

### Miscellaneous

- Disable Comments – Disable comments globally with WooCommerce review support.
- External Links in New Window – Automatically open external links in new tabs.
- Enable Page Excerpt – Add excerpt support to pages.
- SVG Upload – Upload SVG files with security filtering.
- AVIF Upload – Enable AVIF image uploads.
- Role Redirects – Redirect users after login or logout based on role.
- Maintenance Mode – Put the site into maintenance mode.
- Display the current year via `[refi-year]` or duration via `[refi-year from="2006"]` shortcodes.


## Screenshots

### Dashboard

Overview of all available modules and their status.

### Settings

Configure each feature individually.

### Help

Detailed documentation and usage instructions.

## Requirements

| Requirement | Version |
|------------|----------|
| WordPress | 5.9+ |
| Tested up to | 7.0 |
| PHP | 7.4+ |

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
Tools → RefiTune – Site Refiner Toolkit
```

4. Enable the modules you want to use.

## Translations

Currently available languages:

- English
- Hungarian (Magyar)

The plugin is translation-ready.

**Text Domain**

```text
refitune
```

## FAQ

### Will RefiTune slow down my website?

No. Most modules are designed to improve performance by removing unnecessary WordPress functionality and reducing resource usage.

### Is it safe to disable jQuery Migrate?

Only if your theme and plugins are compatible with modern jQuery versions. Test thoroughly before enabling this option on production sites.

### What happens if I disable XML-RPC?

Some third-party services, mobile applications, and legacy integrations may stop working.

### Does Login Limit support email-based logins?

Yes. Failed attempts are tracked for both usernames and email addresses.

### Can administrators lock themselves out of wp-admin?

No. Administrators always retain access.

### Is Maintenance Mode SEO-friendly?

Yes. The plugin returns an HTTP 503 status code while maintenance mode is active.

## Changelog

### 1.1.0

#### Security

- Improved redirect validation.
- Enhanced SVG sanitization.
- Strengthened REST API restrictions.
- Improved SMTP credential handling.

#### Refactoring

- Modular settings sanitization.

### 1.0.0

- Initial public release.
- WordPress 7.0 compatibility verification.
- PHP 8.5 compatibility verification.

## Upgrade Notice

### 1.1.0

Recommended security update including:

- Redirect hardening
- SVG sanitization improvements
- REST API restrictions
- SMTP credential handling improvements

### 1.0.0

Initial release.

## Links

- Website: :contentReference[oaicite:0]{index=0}
- Plugin Repository: :contentReference[oaicite:1]{index=1}
- Hungarian Documentation: :contentReference[oaicite:2]{index=2}
- Author Website: :contentReference[oaicite:3]{index=3}

## License

Licensed under the GNU General Public License v2.0 or later.

See:

:contentReference[oaicite:4]{index=4}
