<?php
/**
 * Help oldal tartalma – funkciók részletes leírása.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Category definitions.
$help_categories = array(
	'performance' => __( 'Performance', 'refitune' ),
	'security'    => __( 'Security', 'refitune' ),
	'visual'      => __( 'Visual', 'refitune' ),
	'email'       => __( 'Email', 'refitune' ),
	'misc'        => __( 'Miscellaneous', 'refitune' ),
);

$help_items = array(
	// Performance category.
	array(
		'id'       => 'cleanup-head',
		'title'    => __( 'Header Cleanup', 'refitune' ),
		'content'  => __( 'WordPress adds several links and meta tags to the <code>&lt;head&gt;</code> section by default, most of which are unnecessary for most websites. This feature removes: the generator tag showing WordPress version (also recommended for security reasons), the RSD (Really Simple Discovery) link, the Windows Live Writer manifest link, the shortlink, and the adjacent posts rel links.', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'disable-feeds',
		'title'    => __( 'Feed Management', 'refitune' ),
		'content'  => __( 'WordPress adds three types of feed <code>&lt;link&gt;</code> elements to the <code>&lt;head&gt;</code>: the main posts feed (domain.com/feed/), the comments feed, and additional feeds (categories, authors, tags, etc.). These elements can be disabled individually. Important: this only removes the <code>&lt;head&gt;</code> reference – the feed URLs remain directly accessible via browser.', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'disable-emoji',
		'title'    => __( 'Disable Emoji', 'refitune' ),
		'content'  => __( 'WordPress has had built-in emoji support since version 4.2, which loads an external JavaScript file and a CSS file. If your website doesn\'t use emojis in content, it\'s worth disabling these unnecessary resources for faster page loading. This feature also removes the emoji plugin from the editor (TinyMCE).', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'disable-jquery-migrate',
		'title'    => __( 'Disable jQuery Migrate', 'refitune' ),
		'content'  => __( 'jquery-migrate is a compatibility layer that makes older jQuery code run with newer jQuery versions. If all your website\'s themes and plugins are compatible with the current jQuery version, this package is unnecessary. Only enable this if you\'re sure none of your scripts require it.', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'post-revisions',
		'title'    => __( 'Post Revisions Limit', 'refitune' ),
		'content'  => __( 'WordPress stores an unlimited number of revisions for each post by default, which can unnecessarily increase database size. This setting overrides the maximum number of revisions using the <code>wp_revisions_to_keep</code> filter – without needing to modify <code>wp-config.php</code>. Setting <strong>0</strong> completely disables revisions; a positive integer keeps at most that many revisions per post (older ones are automatically deleted on save). An empty field leaves the WordPress default in effect.', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'autosave-interval',
		'title'    => __( 'Auto-save Interval', 'refitune' ),
		'content'  => __( 'WordPress automatically saves your post content while you\'re editing to prevent data loss. By default, this happens every 60 seconds. You can increase this interval to reduce server load and database writes. The value is specified in seconds. Recommended values: <strong>120</strong> (2 minutes) or <strong>300</strong> (5 minutes). Setting a higher value means less frequent auto-saves, which can improve performance on busy sites. Note: This does not affect the manual save when you click the Save or Publish button.', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'trash-auto-delete',
		'title'    => __( 'Trash Auto-Delete', 'refitune' ),
		'content'  => __( 'WordPress keeps deleted posts, pages, and media files in the trash for 30 days by default before permanently deleting them. You can change this period to automatically clean up your database more frequently or keep items longer for recovery purposes. The value is specified in days. Setting a lower value (e.g., 7 days) reduces database size but gives you less time to recover accidentally deleted items.', 'refitune' ),
		'category' => 'performance',
	),
	array(
		'id'       => 'heartbeat-control',
		'title'    => __( 'Heartbeat API Control', 'refitune' ),
		'content'  => __( 'The WordPress Heartbeat API is a built-in feature that uses AJAX to communicate between the browser and the server at regular intervals. It powers several real-time features like autosave, post locking (notifying when someone else is editing the same post), and admin notifications. However, frequent Heartbeat requests can increase server load and consume resources, especially on shared hosting or high-traffic sites. <br><br>This feature allows you to independently control the Heartbeat API in three different contexts:<br><br><strong>1. Admin Heartbeat</strong><br>Controls Heartbeat on the Dashboard and other admin pages (not the post editor). These pages typically don\'t require frequent updates.<br><strong>Recommended:</strong> 60 seconds, medium<br><br><strong>2. Frontend Heartbeat</strong><br>Controls Heartbeat on your public-facing website. The Heartbeat API is rarely needed on the frontend unless you have specific plugins that rely on it.<br><strong>Recommended:</strong> Disable (not needed on public pages)<br><br><strong>3. Post Editor Heartbeat</strong><br>Controls Heartbeat in the post/page editor (Gutenberg and Classic Editor). This is crucial for autosave and post locking features.<br><strong>Recommended:</strong> 30-60 seconds<br><strong>WARNING:</strong> Disabling the Heartbeat in the editor will also disable autosave and post locking!<br><br><strong>Available Options:</strong><ul><li><strong>WordPress default:</strong> No changes, uses the default interval (typically 15-60 seconds).</li><li><strong>15 seconds, dense:</strong> Very frequent updates, highest server load.</li><li><strong>30 seconds, frequent:</strong> Frequent updates, moderate server load.</li><li><strong>60 seconds, medium:</strong> Balanced updates, recommended for most sites.</li><li><strong>120 seconds, rare:</strong> Less frequent updates, lowest server load.</li><li><strong>Disable:</strong> Completely turns off the Heartbeat API in this context.</li></ul><strong>Example Configuration:</strong><ul><li><strong>Admin:</strong> 60 seconds, medium (reduces dashboard requests)</li><li><strong>Frontend:</strong> Disable (not needed on public pages)</li><li><strong>Post Editor:</strong> 30 seconds, frequent (keeps autosave functional)</li></ul><strong>Performance Impact:</strong> Reducing Heartbeat frequency or disabling it on the frontend can significantly reduce server CPU usage, memory consumption, and database queries. This is especially beneficial on shared hosting environments or sites with limited resources.', 'refitune' ),
		'category' => 'performance',
	),

	// Security category.
	array(
		'id'       => 'disable-xmlrpc',
		'title'    => __( 'Disable XML-RPC', 'refitune' ),
		'content'  => __( 'XML-RPC is a remote API protocol that allows external applications (mobile apps, desktop clients) and plugins (e.g., Jetpack) to connect to WordPress. Since it can pose a security risk and be a target for brute-force attacks, it\'s worth disabling if you don\'t use it. When enabled: all XML-RPC requests receive a <strong>404 Not Found</strong> response (making it appear as if the xmlrpc.php file doesn\'t exist), and the RSD (Really Simple Discovery) <code>&lt;link&gt;</code> tag is automatically removed from the HTML header. This security-through-obscurity approach prevents attackers from knowing that XML-RPC is actively blocked. <strong>Don\'t enable XML-RPC disabling if you use Jetpack synchronization, WordPress mobile app, or other plugins that require XML-RPC!</strong>', 'refitune' ),
		'category' => 'security',
	),
	array(
		'id'       => 'disable-trackbacks',
		'title'    => __( 'Disable Trackback/Pingback', 'refitune' ),
		'content'  => __( 'The trackback and pingback mechanism is an automatic notification system between WordPress blogs, activated when you link to another WordPress site in a post. Today it\'s primarily a source of spam and security risk. This feature: closes pings on all existing and new posts (at runtime, without database modification), removes pingback XML-RPC methods (so XML-RPC remains active but pingback features don\'t), removes the <code>X-Pingback</code> HTTP header and pingback URL (<code>&lt;link rel="pingback"&gt;</code>) from the source, and rejects direct HTTP trackback requests with a 403.', 'refitune' ),
		'category' => 'security',
	),
	array(
		'id'       => 'disable-file-edit',
		'title'    => __( 'Disable File Editor', 'refitune' ),
		'content'  => __( 'Disables WordPress\'s built-in plugin and theme editor functionality in the admin area. After activation, the <em>Plugins &gt; Editor</em> and <em>Appearance &gt; Theme Editor</em> menu items disappear from the admin menu and are not accessible via direct URL. This feature works by setting the <code>DISALLOW_FILE_EDIT</code> WordPress constant. This is a recommended security measure as it prevents direct browser-based editing of server files in case of compromised administrator accounts.', 'refitune' ),
		'category' => 'security',
	),
	array(
		'id'       => 'login-tweaks',
		'title'    => __( 'Login Error Messages', 'refitune' ),
		'content'  => __( 'By default, WordPress provides separate error messages for when the username exists but the password is incorrect, and vice versa. This information can help with brute-force and username enumeration attacks. This feature displays a generic, neutral error message in both cases.', 'refitune' ),
		'category' => 'security',
	),
	array(
		'id'       => 'admin-access',
		'title'    => __( 'Restrict Admin Access', 'refitune' ),
		'content'  => __( 'Determines which WordPress user roles can access the wp-admin area. Users without permission will be redirected to the website homepage when attempting to access admin. The <strong>administrator</strong> role always has access and cannot be removed from the list. AJAX requests are not affected by this restriction. Important: make sure the administrator role is checked before activating.', 'refitune' ),
		'category' => 'security',
	),
	array(
		'id'       => 'rest-api-restrictions',
		'title'    => __( 'REST API Restrictions', 'refitune' ),
		'content'  => __( 'Intelligent restriction of certain WordPress REST API endpoints for security reasons. The REST API provides publicly accessible data by default (e.g., usernames, media files), which can pose a security risk in some cases.<br><br><strong>How it works:</strong><ul><li><strong>Logged-in users:</strong> always have access (any role)</li><li><strong>Anonymous requests:</strong> receive a 401 Unauthorized error on restricted routes</li></ul>Access is based on an authenticated WordPress session, not on cookies or HTTP headers that can be spoofed by external clients.<br><br><strong>Restrictable endpoints:</strong><ul><li><strong>Users endpoint:</strong> Restricts the <code>/wp-json/wp/v2/users</code> endpoint, which exposes user data (names, slugs, email addresses). This makes it harder for attackers collecting usernames for brute-force attacks.</li><li><strong>REST index:</strong> The <code>/wp-json/</code> root index requires authentication. This endpoint lists all available REST API routes, which is valuable information for attackers.</li><li><strong>Media endpoint:</strong> Restricts the <code>/wp-json/wp/v2/media</code> endpoint, which allows listing and querying uploaded media files.</li><li><strong>Comments endpoint:</strong> Restricts the <code>/wp-json/wp/v2/comments</code> endpoint.</li><li><strong>Search endpoint:</strong> Restricts the <code>/wp-json/wp/v2/search</code> endpoint, which allows searching within page content.</li></ul><strong>Important:</strong> WooCommerce\'s own REST API endpoints (<code>/wp-json/wc/v3/</code>, <code>/wp-json/wc-store/v1/</code>) are NOT affected.', 'refitune' ),
		'category' => 'security',
	),
	array(
		'id'       => 'login-limit',
		'title'    => __( 'Login Limit', 'refitune' ),
		'content'  => __( 'Limits failed login attempts based on IP address and username, protecting the website against brute-force attacks.<br><br><strong>How it works:</strong><ul><li>Counts every failed login attempt both by <strong>IP address</strong> and by <strong>username</strong></li><li>If either reaches the limit, a timed lockout occurs</li><li>Username lockouts are checked before password verification</li><li>Successful login clears the counter</li></ul><strong>Settings:</strong><ul><li><strong>Block "admin" Username Instantly:</strong> When enabled, immediately blocks the IP address for 1 hour on the first login attempt with username "admin".</li><li><strong>Maximum attempts:</strong> How many failed attempts the system allows (default: 5). Counted separately per IP address and per username.</li><li><strong>Lockout duration:</strong> How long login is blocked after reaching the limit in minutes (default: 15 minutes).</li><li><strong>Whitelist IP addresses:</strong> IP addresses exempt from the limit (one IP per line).</li></ul><strong>Storage:</strong> Attempt counters use the WordPress Transients API with object-cache support when available.<br><br><strong>Note:</strong> This feature only works on the <code>wp-login.php</code> page.', 'refitune' ),
		'category' => 'security',
	),

	// Visual category.
	array(
		'id'       => 'hide-admin-bar',
		'title'    => __( 'Hide Admin Bar', 'refitune' ),
		'content'  => __( 'When logged in, WordPress displays an admin bar at the top of the page. This is useful for administrators, but can be distracting for users with other roles (editor, author, subscriber, etc.). The setting allows you to specify exactly which WordPress roles should have the admin bar hidden. The feature only disables the bar for selected role users; when the list is empty, the feature is not active.', 'refitune' ),
		'category' => 'visual',
	),
	array(
		'id'       => 'block-visibility',
		'title'    => __( 'Block Visibility (Mobile)', 'refitune' ),
		'content'  => __( 'Adds a "Visibility" panel to every Gutenberg block in the block editor (Inspector Controls). You can set whether the block is always visible, appears only on mobile, or only on desktop. The mobile/desktop decision happens server-side using WordPress core\'s <code>wp_is_mobile()</code> function, so the block\'s HTML code is completely omitted from the source on the wrong device – not just hidden with CSS. This ensures better performance and cleaner HTML output.', 'refitune' ),
		'category' => 'visual',
	),
	array(
		'id'       => 'login-customizer',
		'title'    => __( 'Login Page Customization', 'refitune' ),
		'content'  => __( 'Customize the WordPress login page (wp-login.php) logo, background color, primary color, and language switcher visibility. <strong>Logo settings:</strong> Choose whether to display the WordPress Site Icon (Settings → General → Site Icon) on the login page, or a custom image URL (relative path, e.g., <code>/wp-content/uploads/logo.png</code>). Logo width and height can be specified in pixels (default: 84x84 px). <strong>Background color:</strong> The login page background color is customizable (default WordPress color: #f0f0f1). <strong>Primary color:</strong> The "Log In" button background and border color can be modified (default WordPress color: #3858e9). <strong>Language Switcher:</strong> Option to hide the language selector dropdown from the login screen entirely. Customizations are implemented with CSS injected in the <code>login_head</code> action, safely overriding WordPress default values.', 'refitune' ),
		'category' => 'visual',
	),

	// Email category.
	array(
		'id'       => 'email-controls',
		'title'    => __( 'Email Notifications', 'refitune' ),
		'content'  => __( 'Individual WordPress system emails can be disabled or redirected to a custom email address:<ul><li><strong>Update notifications</strong> – emails sent to admin about automatic core/plugin/theme updates; can be redirected to a custom email address instead of disabled.</li><li><strong>New user registration</strong> – only disables the admin notification (the newly registered user\'s welcome email remains).</li><li><strong>Password reset</strong> – the notification sent to admin can be disabled (not the password reset link email).</li><li><strong>Comment notifications</strong> – all comment moderation and author notifications.</li><li><strong>Privacy (GDPR) notifications</strong> – data export, data deletion, consent confirmation emails.</li><li><strong>Critical error email</strong> – error notification sent in WordPress recovery mode; can also be redirected to a custom email address.</li></ul>', 'refitune' ),
		'category' => 'email',
	),
	array(
		'id'       => 'email-smtp',
		'title'    => __( 'Email SMTP / Complete Disable', 'refitune' ),
		'content'  => __( '<strong>Complete email sending disable:</strong> Uses the <code>pre_wp_mail</code> filter to block all <code>wp_mail()</code> calls – no emails are sent from the system. This is useful in development, test, or staging environments. <strong>SMTP settings:</strong> If complete disable is not enabled and SMTP host is specified, it configures the use of an external SMTP server instead of WordPress\'s native mail() function via the <code>phpmailer_init</code> action. Configurable options include: SMTP host, port, encryption (SSL/TLS/none), username and password (SMTP Auth), and sender email address and name (<code>setFrom()</code>). The password is stored encrypted with Sodium in the database, with the encryption key derived from a SHA-256 hash of the WordPress <code>AUTH_KEY</code>, <code>SECURE_AUTH_KEY</code>, and <code>NONCE_KEY</code> constants.', 'refitune' ),
		'category' => 'email',
	),

	// Miscellaneous category.
	array(
		'id'       => 'disable-comments',
		'title'    => __( 'Disable Comments', 'refitune' ),
		'content'  => __( 'Completely disables the comment system: closes comments on all existing posts at runtime (without database modification), removes comment support from all post types, and blocks submission via both <strong>REST API</strong> and traditional <code>wp-comments-post.php</code> (403 response). In the admin area, it removes the Comments menu item, the comment icon from the admin bar, the dashboard widget, and the Discussion/Comments metaboxes from the post editor. If WooCommerce is active, checking the "Keep product reviews" option keeps comments (reviews) working on the <code>product</code> post type.', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'external-links',
		'title'    => __( 'External Links in New Window', 'refitune' ),
		'content'  => __( 'Automatically adds <code>target="_blank"</code> and <code>rel="noopener noreferrer"</code> attributes to all links pointing outside your own domain. Internal links (same domain) in posts and widgets are not modified. The <code>rel="noopener noreferrer"</code> is also important for security, preventing the opened page from accessing the original page.', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'page-excerpt',
		'title'    => __( 'Enable Page Excerpt', 'refitune' ),
		'content'  => __( 'By default, WordPress pages (page post type) don\'t have the excerpt field available in the editor. This feature enables the excerpt field for pages in both Gutenberg and the Classic editor. The excerpt can then be used in templates with the <code>get_the_excerpt()</code> function, as well as in SEO plugins.', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'svg-upload',
		'title'    => __( 'SVG Upload', 'refitune' ),
		'content'  => __( 'Allows SVG image files to be uploaded to the media library for selected WordPress roles. Performs security checks before upload: examines whether the SVG contains potentially dangerous code (script, iframe, JavaScript event handlers, etc.). Only users with designated roles can upload SVG; for all other users, SVG remains prohibited.', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'avif-upload',
		'title'    => __( 'AVIF Upload', 'refitune' ),
		'content'  => __( 'Allows AVIF image files to be uploaded to the media library for selected WordPress roles. AVIF is a modern, highly efficient image format that is not supported by default in WordPress versions prior to WP 6.1. The plugin ensures proper MIME type handling on older WP versions as well.', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'role-redirects',
		'title'    => __( 'Role Redirects', 'refitune' ),
		'content'  => __( 'Set custom redirect URLs per role after login and logout. In the settings, two fields are available for each WordPress role (administrator, editor, author, contributor, subscriber, etc.): <strong>Redirect after login</strong> – using the <code>login_redirect</code> filter, the user with that role is redirected to the specified path after successful login. <strong>Redirect after logout</strong> – using the <code>logout_redirect</code> filter, the user with that role is redirected to the specified path after logout. If a user has multiple roles, the first matching role\'s redirect applies. The website URL is automatically pre-filled, you only need to enter the <strong>relative path</strong> (e.g., <code>/dashboard/</code>, <code>/my-account/</code>, <code>/</code>). This simplifies configuration and ensures only on-site redirects occur. In case of domain change or staging → production migration, redirects automatically adapt to the new domain.<br><br><strong>Tested and working reliably:</strong><ul><li>Standard WordPress login/logout (<code>wp-login.php</code>)</li><li>WooCommerce My Account page shortcode-based login/logout</li><li>Admin toolbar logout</li></ul>', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'maintenance-mode',
		'title'    => __( 'Maintenance Mode', 'refitune' ),
		'content'  => __( 'Temporarily blocks visitors from accessing the site while allowing authorized users to continue working. Uses the <code>init</code> hook (priority 1) to intercept all requests before any template, REST API, feed, or other WordPress functionality loads. <strong>Access Control:</strong> Select which WordPress roles can view the site during maintenance (Administrator is always allowed). Logged-in users with permitted roles can access the site normally, while all other visitors receive a <strong>503 Service Unavailable</strong> HTTP status with a custom message. <strong>Exclusions:</strong> The admin area (<code>is_admin()</code>), AJAX requests (<code>wp_doing_ajax()</code>), and cron jobs (<code>wp_doing_cron()</code>) continue to function normally. <strong>SEO-Friendly:</strong> Returns 503 HTTP status code (not 200) with a <code>Retry-After: 3600</code> header (1 hour), <code>noindex, nofollow</code> meta tags, ensuring search engines understand this is temporary. <strong>Visual Indicators:</strong> When active, a red warning notice appears in the admin area showing which roles have access, and the feature card on the dashboard displays with a red border instead of green to indicate a warning state. <strong>Custom Message:</strong> Optionally specify a custom message for visitors; if left empty, a default maintenance message is shown. The maintenance page displays the site title and message in a clean, centered layout.', 'refitune' ),
		'category' => 'misc',
	),
	array(
		'id'       => 'dynamic-year',
		'title'    => __( 'Dynamic Year Shortcodes', 'refitune' ),
		'content'  => __( 'Provides two shortcodes for displaying dynamic year information anywhere on your site: <br><br><strong>1. Current Year:</strong> <code>[refi-year]</code> displays the current year (e.g., 2026). Perfect for copyright notices that auto-update: <code>&copy; [refi-year] Company Name</code><br><br><strong>2. Duration Since:</strong> <code>[refi-year from="2006"]</code> calculates and displays the years between the specified year and now (e.g., if current year is 2026, displays: 20). Ideal for "Serving clients since 2006 (20 years)" or similar messaging.<br><br><strong>Usage Examples:</strong><ul><li><code>&copy; 2006-[refi-year] Company Name</code> → "© 2006-2026 Company Name"</li><li><code>Proudly serving for [refi-year from="2006"] years</code> → "Proudly serving for 20 years"</li><li><code>Established [refi-year from="2010"]</code> → "Established 16"</li></ul><strong>Note:</strong> Shortcodes work in posts, pages, text widgets, and most theme areas that support shortcode processing.', 'refitune' ),
		'category' => 'misc',
	),
);

// Csoportosítás kategóriánként.
$help_by_category = array();
foreach ( $help_items as $item ) {
	$cat = isset( $item['category'] ) ? $item['category'] : 'misc';
	if ( ! isset( $help_by_category[ $cat ] ) ) {
		$help_by_category[ $cat ] = array();
	}
	$help_by_category[ $cat ][] = $item;
}
?>

<?php foreach ( $help_categories as $cat_key => $cat_label ) : ?>
	<?php if ( ! isset( $help_by_category[ $cat_key ] ) ) {
		continue;
	} ?>

	<h2 id="refitune-help-category-<?php echo esc_attr( $cat_key ); ?>" class="refitune-category-title">
		<?php echo esc_html( $cat_label ); ?>
	</h2>

	<div class="refitune-help-list">
		<?php foreach ( $help_by_category[ $cat_key ] as $item ) : ?>
			<div class="refitune-help-item" id="help-<?php echo esc_attr( $item['id'] ); ?>">
				<h3 class="refitune-help-item-title"><?php echo esc_html( $item['title'] ); ?></h3>
				<div class="refitune-help-item-content"><?php echo wp_kses( $item['content'], array( 'code' => array(), 'strong' => array(), 'em' => array(), 'br' => array(), 'ul' => array(), 'li' => array() ) ); ?></div>
			</div>
		<?php endforeach; ?>
	</div>

<?php endforeach; ?>
