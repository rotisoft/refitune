<?php
/**
 * Admin menu registration, settings handling, asset loading, and page rendering.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! is_admin() ) {
	return;
}

/**
 * Helper returning the definition of every available feature.
 *
 * Types:
 *  - (none) : simple boolean toggle
 *  - sub_options : list of sub-settings (each boolean)
 *  - role_select : checkbox list of WordPress roles (array value)
 *    - option_key     : option key inside refitune_settings (array)
 *    - required_roles : roles that are always checked/locked
 *    - enable_key     : optional master boolean toggle for the feature
 *
 * @return array
 */
function refitune_get_features() {
	// Header cleanup sub_options dynamic structure.
	$refitune_cleanup_head_sub_options = array(
		'cleanup_head_rsd'            => __( 'Remove RSD (Really Simple Discovery) link', 'refitune' ),
		'cleanup_head_wlwmanifest'    => __( 'Remove Windows Live Writer manifest link', 'refitune' ),
		'cleanup_head_shortlink'      => __( 'Remove Shortlink', 'refitune' ),
		'cleanup_head_adjacent_posts' => __( 'Remove Previous and Next post rel links', 'refitune' ),
	);

	// Generator tag removal sub_options (WooCommerce option only when active).
	$refitune_hide_generator_sub_options = array(
		'cleanup_head_generator' => __( 'Hide WordPress version (Remove Generator tag)', 'refitune' ),
	);

	if ( class_exists( 'WooCommerce' ) ) {
		$refitune_hide_generator_sub_options['cleanup_head_wc_generator'] = __( 'Hide WooCommerce version (Remove Generator tag)', 'refitune' );
	}

	return array(
		'cleanup_head'    => array(
			'label'       => __( 'Header Cleanup', 'refitune' ),
			'description' => __( 'Removes unnecessary wp_head elements from the source.', 'refitune' ),
			'sub_options' => $refitune_cleanup_head_sub_options,
			'category'    => 'performance',
		),
	'disable_feeds'   => array(
		'label'       => __( 'Feed Management', 'refitune' ),
		'description' => __( 'Removes default WordPress RSS/Atom feeds from HTML source.', 'refitune' ),
		'sub_options' => array(
			'disable_feeds_posts'    => __( 'Disable main posts feed (domain.com/feed/)', 'refitune' ),
			'disable_feeds_comments' => __( 'Disable comment feeds', 'refitune' ),
			'disable_feeds_extra'    => __( 'Remove additional feeds (categories, authors, etc.)', 'refitune' ),
		),
		'category'    => 'performance',
	),
		'disable_emoji'   => array(
			'label'       => __( 'Disable Emoji', 'refitune' ),
			'description' => __( 'Disables WordPress built-in emoji processing scripts and stylesheet, reducing page load times.', 'refitune' ),
			'category'    => 'performance',
		),
	'disable_jquery_migrate' => array(
		'label'       => __( 'Disable jQuery Migrate', 'refitune' ),
		'description' => __( 'Removes the jquery-migrate script from frontend pages.', 'refitune' ),
		'category'    => 'performance',
	),
	'disable_oembed'  => array(
		'label'       => __( 'Disable oEmbed', 'refitune' ),
		'description' => __( 'Disables automatic embedding of external content (YouTube, Vimeo, Twitter, etc.) from pasted URLs.', 'refitune' ),
		'category'    => 'performance',
	),
	'remove_asset_versions' => array(
		'label'       => __( 'Remove Asset Version Query Strings', 'refitune' ),
		'description' => __( 'Removes ?ver= from frontend CSS and JS URLs. Can break cache busting after theme or plugin updates unless your CDN or host purges by path.', 'refitune' ),
		'category'    => 'performance',
	),
	'post_revisions'  => array(
		'label'       => __( 'Post Revisions Limit', 'refitune' ),
		'description' => __( 'How many post revisions WordPress should store per post (Recommended: 5-10)', 'refitune' ),
		'type'        => 'number_input',
		'option_key'  => 'post_revisions_limit',
		'min'         => 0,
		'category'    => 'performance',
	),
	'autosave_interval' => array(
		'label'       => __( 'Auto-save Interval', 'refitune' ),
		'description' => __( 'Here you can specify how many seconds to save the post. Recommended: 120 or 300 (2 minutes or 5 minutes)', 'refitune' ),
		'type'        => 'number_input',
		'option_key'  => 'autosave_interval',
		'min'         => 10,
		'category'    => 'performance',
	),
	'trash_auto_delete' => array(
		'label'       => __( 'Trash Auto-Delete', 'refitune' ),
		'description' => __( 'Number of days before items in trash are permanently deleted. Recommended: 7-30 days. Default: 30 days', 'refitune' ),
		'type'        => 'number_input',
		'option_key'  => 'trash_auto_delete_days',
		'min'         => 1,
		'category'    => 'performance',
	),
	'heartbeat_control' => array(
		'label'       => __( 'Heartbeat API Control', 'refitune' ),
		'description' => __( 'Control WordPress Heartbeat API frequency or disable it in admin, frontend, and post editor contexts independently.', 'refitune' ),
		'type'        => 'heartbeat_control',
		'category'    => 'performance',
	),
	'upload_webp_convert' => array(
		'label'                 => __( 'Convert Uploads to WebP', 'refitune' ),
		'description'           => __( 'Automatically converts JPEG and PNG uploads to WebP, optionally resizes large images, and removes the original file.', 'refitune' ),
		'type'                  => 'upload_webp_convert',
		'enable_key'            => 'upload_webp_convert',
		'category'              => 'performance',
		'requires_webp_support' => true,
		'unavailable_notice'    => __( 'Requires PHP GD or Imagick with WebP support on the server.', 'refitune' ),
	),
	'disable_xmlrpc'  => array(
		'label'       => __( 'Disable XML-RPC', 'refitune' ),
		'description' => __( 'Completely disables the XML-RPC remote API interface (404 Not Found response).', 'refitune' ),
		'category'    => 'security',
	),
	'disable_trackbacks' => array(
		'label'       => __( 'Disable Trackback/Pingback', 'refitune' ),
		'description' => __( 'Disables trackback and pingback mechanism (inter-post notifications): closes pings on all posts, removes pingback methods.', 'refitune' ),
		'category'    => 'security',
	),
	'disable_file_edit' => array(
		'label'       => __( 'Disable File Editor', 'refitune' ),
		'description' => __( 'Disables the built-in plugin and theme editor in admin area (DISALLOW_FILE_EDIT).', 'refitune' ),
		'category'    => 'security',
	),
	'hide_generator_tags' => array(
		'label'       => __( 'Hide Generator Tags', 'refitune' ),
		'description' => __( 'Removes generator meta tags that reveal WordPress or WooCommerce version numbers in the HTML source.', 'refitune' ),
		'sub_options' => $refitune_hide_generator_sub_options,
		'category'    => 'security',
	),
	'auto_updates_control' => array(
		'label'       => __( 'Automatic Updates Control', 'refitune' ),
		'description' => __( 'Control which updates run automatically and how often WordPress checks for updates.', 'refitune' ),
		'type'        => 'auto_updates_control',
		'category'    => 'security',
	),
	'login_tweaks'    => array(
		'label'       => __( 'Login Error Messages', 'refitune' ),
		'description' => __( 'Generalizes login error messages so it doesn\'t reveal whether username or password was incorrect.', 'refitune' ),
		'category'    => 'security',
	),
	'admin_access'    => array(
		'label'         => __( 'Restrict Admin Access', 'refitune' ),
		'description'   => __( 'Determines which user roles can access the wp-admin area.', 'refitune' ),
		'type'          => 'role_select',
		'option_key'    => 'admin_access_roles',
		'required_roles' => array( 'administrator' ),
		'enable_key'    => 'admin_access_enabled',
		'category'      => 'security',
	),
	'rest_api_restrictions' => array(
		'label'       => __( 'REST API Restrictions', 'refitune' ),
		'description' => __( 'Restrict selected WordPress REST API endpoints to users with the manage_options capability (administrators). Editors and other roles are denied.', 'refitune' ),
		'sub_options' => array(
			'rest_disable_users'    => __( 'Restrict Users endpoint (administrator access required) - /wp-json/wp/v2/users', 'refitune' ),
			'rest_restrict_index'   => __( 'Restrict REST index (administrator access required) - /wp-json/', 'refitune' ),
			'rest_disable_media'    => __( 'Restrict Media endpoint (administrator access required) - /wp-json/wp/v2/media', 'refitune' ),
			'rest_disable_comments' => __( 'Restrict Comments endpoint (administrator access required) - /wp-json/wp/v2/comments', 'refitune' ),
			'rest_disable_search'   => __( 'Restrict Search endpoint (administrator access required) - /wp-json/wp/v2/search', 'refitune' ),
		),
		'category'    => 'security',
	),
	'login_limit'          => array(
		'label'       => __( 'Login Limit', 'refitune' ),
		'description' => __( 'Limits failed login attempts based on IP address and username/email.', 'refitune' ),
		'type'        => 'login_limit',
		'enable_key'  => 'login_limit_enabled',
		'category'    => 'security',
	),
	'upload_security'      => array(
		'label'       => __( 'Verified Upload', 'refitune' ),
		'description' => __( 'Blocks disguised uploads: double extensions, MIME mismatches, and script markers in media files.', 'refitune' ),
		'category'    => 'security',
	),
	'hide_admin_bar'  => array(
		'label'       => __( 'Hide Admin Bar', 'refitune' ),
		'description' => __( 'Hides the admin bar for logged-in users with selected roles.', 'refitune' ),
		'type'        => 'role_select',
		'option_key'  => 'hide_admin_bar_roles',
		'enable_key'  => 'hide_admin_bar_enabled',
		'category'    => 'visual',
	),
	'block_visibility' => array(
		'label'               => __( 'Block Visibility (Mobile)', 'refitune' ),
		'description'         => __( 'Per-block mobile or desktop visibility in the editor. User-Agent based; full-page cache must vary on User-Agent, or use CSS media queries for layout-only hiding.', 'refitune' ),
		'category'            => 'visual',
		'max_wp_version'      => '7.0',
		'unavailable_notice'  => __( 'A dedicated core feature has been available for this since WordPress 7.0.', 'refitune' ),
	),
	'login_customizer' => array(
		'label'       => __( 'Login Page Customization', 'refitune' ),
		'description' => __( 'Customize WordPress login page (wp-login.php) logo, background color and primary color.', 'refitune' ),
		'type'        => 'login_customizer',
		'enable_key'  => 'login_customizer_enabled',
		'category'    => 'visual',
	),
		'email_controls'  => array(
			'label'       => __( 'Email Notifications', 'refitune' ),
			'description' => __( 'Disable WordPress system emails or redirect them to a custom address.', 'refitune' ),
			'type'        => 'email_controls',
			'category'    => 'email',
		),
		'email_smtp'      => array(
			'label'       => __( 'Email sending', 'refitune' ),
			'description' => __( 'Configure SMTP server or completely disable all emails.', 'refitune' ),
			'type'        => 'email_smtp',
			'category'    => 'email',
		),
	'disable_comments' => array(
		'label'       => __( 'Disable Comments', 'refitune' ),
		'description' => __( 'Completely disables comments and comment submission options.', 'refitune' ),
		'type'        => 'comments_control',
		'category'    => 'misc',
	),
	'external_links'  => array(
		'label'       => __( 'External Links in New Window', 'refitune' ),
		'description' => __( 'Automatically adds target="_blank" and rel="noopener noreferrer" to all external links.', 'refitune' ),
		'category'    => 'misc',
	),
		'page_excerpt'    => array(
			'label'       => __( 'Enable Page Excerpt', 'refitune' ),
			'description' => __( 'Enables the excerpt field for pages in both Gutenberg and Classic editor.', 'refitune' ),
			'category'    => 'misc',
		),
	'upload_filename_sanitize' => array(
		'label'       => __( 'Clean Upload Filenames', 'refitune' ),
		'description' => __( 'Sanitizes image and document filenames on upload: removes accents, lowercases, and replaces invalid characters with hyphens.', 'refitune' ),
		'category'    => 'misc',
	),
	'svg_upload'      => array(
		'label'       => __( 'SVG Upload', 'refitune' ),
		'description' => __( 'Allows SVG file uploads with security filtering. Select which roles can upload SVG.', 'refitune' ),
		'type'        => 'role_select',
		'option_key'  => 'svg_upload_roles',
		'enable_key'  => 'svg_upload_enabled',
		'category'    => 'misc',
	),
	'avif_upload'     => array(
		'label'       => __( 'AVIF Upload', 'refitune' ),
		'description' => __( 'Allows AVIF uploads for selected roles. Full support requires WordPress 6.5+; on 6.2-6.4 only MIME upload is enabled.', 'refitune' ),
		'type'        => 'role_select',
		'option_key'  => 'avif_upload_roles',
		'enable_key'  => 'avif_upload_enabled',
		'category'    => 'misc',
	),
	'role_redirects'  => array(
		'label'       => __( 'Role Redirects', 'refitune' ),
		'description' => __( 'Set custom login and logout redirect URLs per user role.', 'refitune' ),
		'type'        => 'role_redirects',
		'enable_key'  => 'role_redirects_enabled',
		'category'    => 'misc',
	),
	'maintenance_mode' => array(
		'label'          => __( 'Maintenance Mode', 'refitune' ),
		'description'    => __( 'Temporarily block visitors from accessing the site. Select which roles can still view the site.', 'refitune' ),
		'type'           => 'maintenance_mode',
		'option_key'     => 'maintenance_mode_roles',
		'required_roles' => array( 'administrator' ),
		'enable_key'     => 'maintenance_mode_enabled',
		'message_key'    => 'maintenance_mode_message',
		'category'       => 'misc',
	),
	'dynamic_year'     => array(
		'label'       => __( 'Dynamic Year Shortcodes', 'refitune' ),
		'description' => __( 'Provides shortcodes to display current year or calculate duration. Use [refi-year] or [refi-year from="2006"]', 'refitune' ),
		'category'    => 'misc',
	),
	);
}

/**
 * Register admin menu items under the Tools menu.
 *
 * The Settings and Help pages are removed from the menu right after
 * registration with remove_submenu_page() so only the main page link
 * appears under Tools. The pages remain accessible via URL.
 *
 * @return void
 */
function refitune_register_admin_menu(): void {
	add_submenu_page(
		'tools.php',
		__( 'RefiTune - Site refiner toolkit', 'refitune' ),
		__( 'RefiTune Toolkit', 'refitune' ),
		'manage_options',
		'refitune-refinements',
		'refitune_render_dashboard_page'
	);

	add_submenu_page(
		'tools.php',
		__( 'RefiTune - Settings', 'refitune' ),
		__( 'RefiTune Settings', 'refitune' ),
		'manage_options',
		'refitune-settings',
		'refitune_render_settings_page'
	);

	add_submenu_page(
		'tools.php',
		__( 'RefiTune - Help', 'refitune' ),
		__( 'RefiTune Help', 'refitune' ),
		'manage_options',
		'refitune-help',
		'refitune_render_help_page'
	);

	// Only the main page shows in the menu; Settings and Help remain reachable via URL.
	remove_submenu_page( 'tools.php', 'refitune-settings' );
	remove_submenu_page( 'tools.php', 'refitune-help' );
}
add_action( 'admin_menu', 'refitune_register_admin_menu', 10 );

/**
 * Keep the RefiTune submenu item highlighted on the hidden subpages.
 *
 * Settings and Help are removed from the $submenu array, so WordPress cannot
 * match them to a menu entry and the RefiTune item loses its "current"
 * (bold) state. Point the highlight at the main RefiTune entry instead.
 *
 * @param string|null $submenu_file The submenu file to highlight.
 * @return string|null
 */
function refitune_admin_menu_highlight( $submenu_file ) {
	$refitune_page = refitune_get_current_page_slug();

	if ( in_array( $refitune_page, array( 'refitune-settings', 'refitune-help' ), true ) ) {
		return 'refitune-refinements';
	}

	return $submenu_file;
}
add_filter( 'submenu_file', 'refitune_admin_menu_highlight', 10 );

/**
 * Keep the Tools menu open on the hidden RefiTune subpages.
 *
 * @param string $parent_file The parent file.
 * @return string
 */
function refitune_admin_menu_parent_file( $parent_file ) {
	$refitune_page = refitune_get_current_page_slug();

	if ( in_array( $refitune_page, array( 'refitune-settings', 'refitune-help' ), true ) ) {
		return 'tools.php';
	}

	return $parent_file;
}
add_filter( 'parent_file', 'refitune_admin_menu_parent_file', 10 );

/**
 * Pre-set the $GLOBALS['title'] variable for the hidden subpages.
 *
 * remove_submenu_page() deletes the entry from the $submenu array, so
 * get_admin_page_title() cannot find the page name and returns null.
 * On PHP 8.1+ this causes a strip_tags(null) deprecation warning in
 * admin-header.php. The current_screen action runs before
 * get_admin_page_title() is called, and when $title is already non-empty
 * the function returns the existing value immediately (does not override).
 *
 * @return void
 */
function refitune_set_hidden_page_title(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$refitune_page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'refitune-settings' === $refitune_page ) {
		$GLOBALS['title'] = __( 'RefiTune - Settings', 'refitune' );
	} elseif ( 'refitune-help' === $refitune_page ) {
		$GLOBALS['title'] = __( 'RefiTune - Help', 'refitune' );
	}
}
add_action( 'current_screen', 'refitune_set_hidden_page_title', 10 );

/**
 * Add a "Settings" link to the plugin row on the plugins list screen.
 *
 * @param array $links Existing plugin action links.
 * @return array Extended links.
 */
function refitune_plugin_action_links( array $links ): array {
	$refitune_settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=refitune-settings' ) ),
		esc_html__( 'Settings', 'refitune' )
	);
	
	$refitune_help_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=refitune-help' ) ),
		esc_html__( 'Help', 'refitune' )
	);
	
	// Add Settings and Help links to the front (reverse order because of unshift).
	array_unshift( $links, $refitune_help_link );
	array_unshift( $links, $refitune_settings_link );
	
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( REFITUNE_PATH . 'refitune.php' ), 'refitune_plugin_action_links' );

/**
 * Register the plugin settings with the Settings API.
 *
 * @return void
 */
function refitune_register_settings() {
	register_setting(
		'refitune_settings_group',
		'refitune_settings',
		array(
			'type'              => 'array',
			'sanitize_callback' => 'refitune_sanitize_settings',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'refitune_register_settings', 10 );

/**
 * Disable block visibility when WordPress core provides the feature natively.
 *
 * @return void
 */
function refitune_disable_block_visibility_on_unsupported_wp(): void {
	// Only administrators may trigger this silent settings migration.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$refitune_settings = get_option( 'refitune_settings', array() );

	if ( empty( $refitune_settings['block_visibility'] ) ) {
		return;
	}

	if ( version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
		return;
	}

	$refitune_settings['block_visibility'] = false;
	update_option( 'refitune_settings', $refitune_settings );
}
add_action( 'admin_init', 'refitune_disable_block_visibility_on_unsupported_wp', 20 );

/**
 * Restore default update check cron when automatic updates control is turned off.
 *
 * @param mixed $old_value Previous option value.
 * @param mixed $value     New option value.
 * @return void
 */
function refitune_restore_update_checks_when_auto_updates_disabled( $old_value, $value ): void {
	if ( ! is_array( $old_value ) || ! is_array( $value ) ) {
		return;
	}

	if ( ! empty( $old_value['auto_updates_control'] ) && empty( $value['auto_updates_control'] ) ) {
		require_once REFITUNE_PATH . 'modules/auto-updates.php';
		refitune_restore_default_update_check_schedules();
	}
}
add_action( 'update_option_refitune_settings', 'refitune_restore_update_checks_when_auto_updates_disabled', 5, 2 );

require_once REFITUNE_PATH . 'admin/settings-sanitizer.php';

/**
 * Load admin CSS and JS only on the plugin pages.
 *
 * @param string $hook_suffix Current admin page hook suffix.
 * @return void
 */
function refitune_enqueue_admin_assets( $hook_suffix ) {
	$refitune_plugin_pages = array(
		'tools_page_refitune-refinements',
		'tools_page_refitune-settings',
		'tools_page_refitune-help',
	);

	if ( ! in_array( $hook_suffix, $refitune_plugin_pages, true ) ) {
		return;
	}

	// Color Picker only on the settings page (login customizer fields).
	$refitune_style_deps  = array();
	$refitune_script_deps = array();

	if ( 'tools_page_refitune-settings' === $hook_suffix ) {
		wp_enqueue_style( 'wp-color-picker' );
		$refitune_style_deps[]  = 'wp-color-picker';
		$refitune_script_deps[] = 'wp-color-picker';
	}

	$refitune_css_file = REFITUNE_PATH . 'admin/css/admin-style.css';

	wp_enqueue_style(
		'refitune-admin-style',
		REFITUNE_URL . 'admin/css/admin-style.css',
		$refitune_style_deps,
		file_exists( $refitune_css_file ) ? filemtime( $refitune_css_file ) : REFITUNE_VERSION
	);

	$refitune_js_file = REFITUNE_PATH . 'admin/js/admin-script.js';

	wp_enqueue_script(
		'refitune-admin-script',
		REFITUNE_URL . 'admin/js/admin-script.js',
		$refitune_script_deps,
		file_exists( $refitune_js_file ) ? filemtime( $refitune_js_file ) : REFITUNE_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'refitune_enqueue_admin_assets', 10 );

/**
 * Define the admin header navigation links.
 *
 * @return array Slug => label pairs.
 */
function refitune_get_admin_nav_links() {
	return array(
		'refitune-refinements' => __( 'Modules', 'refitune' ),
		'refitune-settings'    => __( 'Settings', 'refitune' ),
		'refitune-help'        => __( 'Help', 'refitune' ),
	);
}

/**
 * Determine the current admin page slug.
 *
 * @return string The current page slug.
 */
function refitune_get_current_page_slug() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Page identification only, no state change.
	return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
}

/**
 * Cached plugin header data for admin footer.
 *
 * @return array
 */
function refitune_get_plugin_header_data(): array {
	static $refitune_plugin_data = null;

	if ( null === $refitune_plugin_data ) {
		$refitune_plugin_data = get_plugin_data( REFITUNE_PATH . 'refitune.php', false, false );
	}

	return $refitune_plugin_data;
}

/**
 * Render the admin page wrapper with a unified header.
 *
 * @param string $refitune_page_file Page file name to load (e.g. 'page-dashboard.php').
 * @return void
 */
function refitune_render_admin_wrapper( $refitune_page_file ) {
	// Defense in depth: the menu capability already restricts access.
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to access this page.', 'refitune' ) );
	}

	$refitune_nav_links    = refitune_get_admin_nav_links();
	$refitune_current_slug = refitune_get_current_page_slug();
	?>
	<h1 style="display: none !important;"><?php esc_html_e( 'RefiTune - Site refiner toolkit', 'refitune' ); ?></h1>
	<div class="wrap refitune-admin-wrap">
		<h2 class="refitune-hidden-title"><?php echo esc_html( get_admin_page_title() ); ?></h2>

		<div class="refitune-admin-header">
			<h1 class="refitune-admin-title"><?php esc_html_e( 'RefiTune - Site refiner toolkit', 'refitune' ); ?></h1>

			<nav class="refitune-admin-nav">
				<?php
				foreach ( $refitune_nav_links as $refitune_slug => $refitune_label ) {
					$refitune_url          = admin_url( 'tools.php?page=' . $refitune_slug );
					$refitune_active_class = ( $refitune_current_slug === $refitune_slug ) ? ' refitune-admin-nav-active' : '';

					printf(
						'<a href="%s" class="refitune-admin-nav-link%s">%s</a>',
						esc_url( $refitune_url ),
						esc_attr( $refitune_active_class ),
						esc_html( $refitune_label )
					);
				}
				?>
			</nav>
		</div>

		<div class="refitune-admin-content">
			<?php
			$refitune_file_path = REFITUNE_PATH . 'admin/' . $refitune_page_file;

			if ( file_exists( $refitune_file_path ) ) {
				require $refitune_file_path;
			}
			?>
		</div>

		<div class="refitune-admin-footer">
			<?php
			$refitune_plugin_data = refitune_get_plugin_header_data();

			printf(
				'%s - %s - <a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_html( $refitune_plugin_data['Name'] ),
				esc_html( $refitune_plugin_data['Version'] ),
				esc_url( $refitune_plugin_data['PluginURI'] ),
				esc_html( $refitune_plugin_data['PluginURI'] )
			);
			?>
		</div>
	</div>
	<?php
}

/**
 * Render the dashboard (main) page.
 *
 * @return void
 */
function refitune_render_dashboard_page() {
	refitune_render_admin_wrapper( 'page-dashboard.php' );
}

/**
 * Render the settings page.
 *
 * @return void
 */
function refitune_render_settings_page() {
	refitune_render_admin_wrapper( 'page-settings.php' );
}

/**
 * Render the help page.
 *
 * @return void
 */
function refitune_render_help_page() {
	refitune_render_admin_wrapper( 'page-help.php' );
}
