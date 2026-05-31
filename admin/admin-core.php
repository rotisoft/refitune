<?php
/**
 * Admin menü regisztráció, beállítások kezelése, asset betöltés és oldal renderelés.
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
 * Az összes elérhető funkció definícióját visszaadó segédfüggvény.
 *
 * Típusok:
 *  - (nincs) : egyszerű boolean kapcsoló
 *  - sub_options : albeállítások listája (egyenként boolean)
 *  - role_select : WordPress szerepkörök checkbox listája (tömb érték)
 *    - option_key     : az option kulcsa a refitune_settings-ben (array)
 *    - required_roles : mindig bejelölt/zárolt szerepkörök
 *    - enable_key     : opcionális master boolean kapcsoló a feature be/kikapcsolásához
 *
 * @return array
 */
function refitune_get_features() {
	// Header cleanup sub_options dynamic structure.
	$cleanup_head_sub_options = array(
		'cleanup_head_generator'      => __( 'Hide WordPress version (Remove Generator tag)', 'refitune' ),
	);

	// If WooCommerce is active, add WC generator option.
	if ( class_exists( 'WooCommerce' ) ) {
		$cleanup_head_sub_options['cleanup_head_wc_generator'] = __( 'Hide WooCommerce version (Remove Generator tag)', 'refitune' );
	}

	$cleanup_head_sub_options['cleanup_head_rsd']            = __( 'Remove RSD (Really Simple Discovery) link', 'refitune' );
	$cleanup_head_sub_options['cleanup_head_wlwmanifest']    = __( 'Remove Windows Live Writer manifest link', 'refitune' );
	$cleanup_head_sub_options['cleanup_head_shortlink']      = __( 'Remove Shortlink', 'refitune' );
	$cleanup_head_sub_options['cleanup_head_adjacent_posts'] = __( 'Remove Previous and Next post rel links', 'refitune' );

	return array(
		'cleanup_head'    => array(
			'label'       => __( 'Header Cleanup', 'refitune' ),
			'description' => __( 'Removes unnecessary wp_head elements from the source.', 'refitune' ),
			'sub_options' => $cleanup_head_sub_options,
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
		'description' => __( 'Removes the ?ver= query parameter from CSS and JavaScript URLs on frontend pages.', 'refitune' ),
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
		'description' => __( 'Intelligent restriction of certain WordPress REST API endpoints.', 'refitune' ),
		'sub_options' => array(
			'rest_disable_users'    => __( 'Restrict Users endpoint (authentication required) - /wp-json/wp/v2/users', 'refitune' ),
			'rest_restrict_index'   => __( 'Restrict REST index (authentication required) - /wp-json/', 'refitune' ),
			'rest_disable_media'    => __( 'Restrict Media endpoint (authentication required) - /wp-json/wp/v2/media', 'refitune' ),
			'rest_disable_comments' => __( 'Restrict Comments endpoint (authentication required) - /wp-json/wp/v2/comments', 'refitune' ),
			'rest_disable_search'   => __( 'Restrict Search endpoint (authentication required) - /wp-json/wp/v2/search', 'refitune' ),
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
		'description'         => __( 'Adds a visibility option to every Gutenberg block to control whether it appears on mobile, desktop, or both.', 'refitune' ),
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
		'description' => __( 'Allows AVIF image file uploads. Select which roles can upload AVIF.', 'refitune' ),
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
 * Admin menüpontok regisztrálása a Tools (Eszközök) menü alatt.
 *
 * A Settings és Help oldalakat közvetlenül a regisztráció után
 * remove_submenu_page()-gel eltávolítjuk a menüből, hogy csak a
 * főoldal linkje jelenjen meg a Tools alatt. Az oldalak URL-en
 * továbbra is elérhetők maradnak.
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
		__( 'RefiTune – Settings', 'refitune' ),
		__( 'RefiTune Settings', 'refitune' ),
		'manage_options',
		'refitune-settings',
		'refitune_render_settings_page'
	);

	add_submenu_page(
		'tools.php',
		__( 'RefiTune – Help', 'refitune' ),
		__( 'RefiTune Help', 'refitune' ),
		'manage_options',
		'refitune-help',
		'refitune_render_help_page'
	);

	// Csak a főoldal látszik a menüben; a Settings és Help elérhetők URL-en.
	remove_submenu_page( 'tools.php', 'refitune-settings' );
	remove_submenu_page( 'tools.php', 'refitune-help' );
}
add_action( 'admin_menu', 'refitune_register_admin_menu', 10 );

/**
 * Előre beállítja a $GLOBALS['title'] változót a rejtett aloldalakhoz.
 *
 * A remove_submenu_page() törli a bejegyzést a $submenu tömbből, ezért
 * a get_admin_page_title() nem találja meg az oldal nevét, és null-t ad
 * vissza. PHP 8.1+-on ez strip_tags(null) deprecation figyelmeztetést okoz
 * az admin-header.php-ban. A current_screen action a get_admin_page_title()
 * hívása előtt fut, és ha $title már nem üres, az a függvény azonnal
 * visszatér a meglévő értékkel (nem írja felül).
 *
 * @return void
 */
function refitune_set_hidden_page_title(): void {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

	if ( 'refitune-settings' === $page ) {
		$GLOBALS['title'] = __( 'RefiTune – Settings', 'refitune' );
	} elseif ( 'refitune-help' === $page ) {
		$GLOBALS['title'] = __( 'RefiTune – Help', 'refitune' );
	}
}
add_action( 'current_screen', 'refitune_set_hidden_page_title' );

/**
 * "Settings" link hozzáadása a plugin listában a plugin sorához.
 *
 * @param array $links Meglévő plugin action linkek.
 * @return array Kiegészített linkek.
 */
function refitune_plugin_action_links( array $links ): array {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=refitune-settings' ) ),
		esc_html__( 'Settings', 'refitune' )
	);
	
	$help_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'tools.php?page=refitune-help' ) ),
		esc_html__( 'Help', 'refitune' )
	);
	
	// Settings és Help linkek hozzáadása az elejére (fordított sorrendben, mert unshift).
	array_unshift( $links, $help_link );
	array_unshift( $links, $settings_link );
	
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( REFITUNE_PATH . 'refitune.php' ), 'refitune_plugin_action_links' );

/**
 * Plugin beállítások regisztrálása a Settings API-val.
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
	$settings = get_option( 'refitune_settings', array() );

	if ( empty( $settings['block_visibility'] ) ) {
		return;
	}

	if ( version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
		return;
	}

	$settings['block_visibility'] = false;
	update_option( 'refitune_settings', $settings );
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
 * Admin CSS és JS betöltése kizárólag a plugin oldalain.
 *
 * @param string $hook_suffix Az aktuális admin oldal hook suffix-e.
 * @return void
 */
function refitune_enqueue_admin_assets( $hook_suffix ) {
	$plugin_pages = array(
		'tools_page_refitune-refinements',
		'tools_page_refitune-settings',
		'tools_page_refitune-help',
	);

	if ( ! in_array( $hook_suffix, $plugin_pages, true ) ) {
		return;
	}

	// Color Picker (WordPress core).
	if ( 'tools_page_refitune-settings' === $hook_suffix ) {
		wp_enqueue_style( 'wp-color-picker' );
	}

	$css_file = REFITUNE_PATH . 'admin/css/admin-style.css';

	wp_enqueue_style(
		'refitune-admin-style',
		REFITUNE_URL . 'admin/css/admin-style.css',
		array( 'wp-color-picker' ),
		file_exists( $css_file ) ? filemtime( $css_file ) : REFITUNE_VERSION
	);

	$js_file = REFITUNE_PATH . 'admin/js/admin-script.js';

	wp_enqueue_script(
		'refitune-admin-script',
		REFITUNE_URL . 'admin/js/admin-script.js',
		array( 'wp-color-picker' ),
		file_exists( $js_file ) ? filemtime( $js_file ) : REFITUNE_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'refitune_enqueue_admin_assets', 10 );

/**
 * Admin fejléc navigáció linkjeinek definiálása.
 *
 * @return array Slug => label párok.
 */
function refitune_get_admin_nav_links() {
	return array(
		'refitune-refinements' => __( 'Modules', 'refitune' ),
		'refitune-settings'    => __( 'Settings', 'refitune' ),
		'refitune-help'        => __( 'Help', 'refitune' ),
	);
}

/**
 * Aktuális admin oldal slug-jának meghatározása.
 *
 * @return string Az aktuális oldal slug-ja.
 */
function refitune_get_current_page_slug() {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Oldal azonosítás, nincs állapotváltozás.
	return isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
}

/**
 * Cached plugin header data for admin footer.
 *
 * @return array
 */
function refitune_get_plugin_header_data(): array {
	static $plugin_data = null;

	if ( null === $plugin_data ) {
		$plugin_data = get_plugin_data( REFITUNE_PATH . 'refitune.php', false, false );
	}

	return $plugin_data;
}

/**
 * Admin oldal wrapper renderelése egységes fejléccel.
 *
 * @param string $page_file A betöltendő oldal fájl neve (pl. 'page-dashboard.php').
 * @return void
 */
function refitune_render_admin_wrapper( $page_file ) {
	$nav_links    = refitune_get_admin_nav_links();
	$current_slug = refitune_get_current_page_slug();
	?>
	<h1 style="display: none !important;"><?php esc_html_e( 'RefiTune - Site refiner toolkit', 'refitune' ); ?></h1>
	<div class="wrap refitune-admin-wrap">
		<h2 class="refitune-hidden-title"><?php echo esc_html( get_admin_page_title() ); ?></h2>

		<div class="refitune-admin-header">
			<h1 class="refitune-admin-title"><?php esc_html_e( 'RefiTune - Site refiner toolkit', 'refitune' ); ?></h1>

			<nav class="refitune-admin-nav">
				<?php
				foreach ( $nav_links as $slug => $label ) {
					$url          = admin_url( 'tools.php?page=' . $slug );
					$active_class = ( $current_slug === $slug ) ? ' refitune-admin-nav-active' : '';

					printf(
						'<a href="%s" class="refitune-admin-nav-link%s">%s</a>',
						esc_url( $url ),
						esc_attr( $active_class ),
						esc_html( $label )
					);
				}
				?>
			</nav>
		</div>

		<div class="refitune-admin-content">
			<?php
			$file_path = REFITUNE_PATH . 'admin/' . $page_file;

			if ( file_exists( $file_path ) ) {
				require $file_path;
			}
			?>
		</div>

		<div class="refitune-admin-footer">
			<?php
			$plugin_data = refitune_get_plugin_header_data();

			printf(
				'%s - %s - <a href="%s" target="_blank" rel="noopener">%s</a>',
				esc_html( $plugin_data['Name'] ),
				esc_html( $plugin_data['Version'] ),
				esc_url( $plugin_data['PluginURI'] ),
				esc_html( $plugin_data['PluginURI'] )
			);
			?>
		</div>
	</div>
	<?php
}

/**
 * Dashboard (fő) oldal renderelése.
 *
 * @return void
 */
function refitune_render_dashboard_page() {
	refitune_render_admin_wrapper( 'page-dashboard.php' );
}

/**
 * Settings oldal renderelése.
 *
 * @return void
 */
function refitune_render_settings_page() {
	refitune_render_admin_wrapper( 'page-settings.php' );
}

/**
 * Help oldal renderelése.
 *
 * @return void
 */
function refitune_render_help_page() {
	refitune_render_admin_wrapper( 'page-help.php' );
}
