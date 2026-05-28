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
			'rest_disable_users'    => __( 'Restrict Users endpoint (block external requests) - /wp-json/wp/v2/users', 'refitune' ),
			'rest_restrict_index'   => __( 'Restrict REST index (block external requests) - /wp-json/', 'refitune' ),
			'rest_disable_media'    => __( 'Restrict Media endpoint (block external requests) - /wp-json/wp/v2/media', 'refitune' ),
			'rest_disable_comments' => __( 'Restrict Comments endpoint (block external requests) - /wp-json/wp/v2/comments', 'refitune' ),
			'rest_disable_search'   => __( 'Restrict Search endpoint (block external requests) - /wp-json/wp/v2/search', 'refitune' ),
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
	'hide_admin_bar'  => array(
		'label'       => __( 'Hide Admin Bar', 'refitune' ),
		'description' => __( 'Hides the admin bar for logged-in users with selected roles.', 'refitune' ),
		'type'        => 'role_select',
		'option_key'  => 'hide_admin_bar_roles',
		'enable_key'  => 'hide_admin_bar_enabled',
		'category'    => 'visual',
	),
	'block_visibility' => array(
		'label'       => __( 'Block Visibility (Mobile)', 'refitune' ),
		'description' => __( 'Adds a visibility option to every Gutenberg block to control whether it appears on mobile, desktop, or both.', 'refitune' ),
		'category'    => 'visual',
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
add_filter( 'plugin_action_links_refitune/refitune.php', 'refitune_plugin_action_links' );

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
 * Sanitize a site-relative path for storage (must resolve to this site only).
 *
 * @param mixed $path Raw path from settings input.
 * @return string Sanitized relative path with leading slash, or empty string if invalid.
 */
function refitune_sanitize_relative_site_path( $path ): string {
	if ( ! is_string( $path ) ) {
		return '';
	}

	$path = trim( wp_unslash( $path ) );
	if ( '' === $path ) {
		return '';
	}

	$path = sanitize_text_field( $path );

	// Disallow external URLs, protocol-relative URLs, whitespace, and path traversal.
	if ( preg_match( '#\s|[\\\\]|(^|[^/])(https?:)?//#i', $path ) || false !== strpos( $path, '..' ) ) {
		return '';
	}

	if ( '/' !== $path[0] ) {
		$path = '/' . $path;
	}

	$full_url = esc_url_raw( home_url( $path ) );
	if ( '' === $full_url || ! wp_http_validate_url( $full_url ) ) {
		return '';
	}

	$home_parts = wp_parse_url( home_url() );
	$url_parts  = wp_parse_url( $full_url );

	if ( empty( $home_parts['host'] ) || empty( $url_parts['host'] ) ) {
		return '';
	}

	if ( strtolower( $home_parts['host'] ) !== strtolower( $url_parts['host'] ) ) {
		return '';
	}

	return $path;
}

/**
 * Sanitize a redirect URL that must belong to this WordPress site.
 *
 * @param mixed $path Raw relative path from settings input.
 * @return string Internal redirect URL from esc_url_raw(), or empty string if invalid.
 */
function refitune_sanitize_internal_redirect_url( $path ): string {
	$relative = refitune_sanitize_relative_site_path( $path );
	if ( '' === $relative ) {
		return '';
	}

	return esc_url_raw( home_url( $relative ) );
}

/**
 * Beállítások szanitizálása mentés előtt.
 *
 * Boolean típusú és role_select típusú értékeket is kezel.
 *
 * @param mixed $input A beküldött nyers adatok.
 * @return array Szanitizált beállítások.
 */
function refitune_sanitize_settings( $input ): array {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$sanitized = array();
	$features  = refitune_get_features();
	$all_roles = array_keys( wp_roles()->get_names() );

	foreach ( $features as $key => $feature ) {
		$type = isset( $feature['type'] ) ? $feature['type'] : '';

		if ( 'login_customizer' === $type ) {
			// Enable checkbox.
			$sanitized['login_customizer_enabled'] = ! empty( $input['login_customizer_enabled'] );

			// Logo forrás (site_icon vagy custom).
			$sanitized['login_logo_source'] = isset( $input['login_logo_source'] ) && 'custom' === $input['login_logo_source']
				? 'custom'
				: 'site_icon';

			// Logo custom URL (site-relative path).
			$sanitized['login_logo_custom_url'] = refitune_sanitize_relative_site_path(
				$input['login_logo_custom_url'] ?? ''
			);

			// Logo szélesség és magasság (pixel).
			$sanitized['login_logo_width']  = isset( $input['login_logo_width'] ) && is_numeric( $input['login_logo_width'] ) && (int) $input['login_logo_width'] > 0
				? (int) $input['login_logo_width']
				: 84;
			$sanitized['login_logo_height'] = isset( $input['login_logo_height'] ) && is_numeric( $input['login_logo_height'] ) && (int) $input['login_logo_height'] > 0
				? (int) $input['login_logo_height']
				: 84;

		// Háttérszín (hex).
		$sanitized['login_bg_color'] = isset( $input['login_bg_color'] ) ? sanitize_hex_color( $input['login_bg_color'] ) : '';

		// Primary szín (hex).
		$sanitized['login_primary_color'] = isset( $input['login_primary_color'] ) ? sanitize_hex_color( $input['login_primary_color'] ) : '';

		// Nyelvválasztó elrejtése.
		$sanitized['login_hide_language_switcher'] = ! empty( $input['login_hide_language_switcher'] );
	} elseif ( 'role_redirects' === $type ) {
			$login_redirects  = array();
			$logout_redirects = array();

			// Login redirects (site-relative path → validated internal URL).
			if ( isset( $input['role_redirects_login'] ) && is_array( $input['role_redirects_login'] ) ) {
				foreach ( $input['role_redirects_login'] as $role => $relative_path ) {
					$role = sanitize_key( $role );
					if ( ! in_array( $role, $all_roles, true ) ) {
						continue;
					}

					$redirect_url = refitune_sanitize_internal_redirect_url( $relative_path );
					if ( '' !== $redirect_url ) {
						$login_redirects[ $role ] = $redirect_url;
					}
				}
			}

			// Logout redirects (site-relative path → validated internal URL).
			if ( isset( $input['role_redirects_logout'] ) && is_array( $input['role_redirects_logout'] ) ) {
				foreach ( $input['role_redirects_logout'] as $role => $relative_path ) {
					$role = sanitize_key( $role );
					if ( ! in_array( $role, $all_roles, true ) ) {
						continue;
					}

					$redirect_url = refitune_sanitize_internal_redirect_url( $relative_path );
					if ( '' !== $redirect_url ) {
						$logout_redirects[ $role ] = $redirect_url;
					}
				}
			}

		$sanitized['role_redirects_login']  = $login_redirects;
		$sanitized['role_redirects_logout'] = $logout_redirects;

		// Enable checkbox.
		$sanitized['role_redirects_enabled'] = ! empty( $input['role_redirects_enabled'] );
	} elseif ( 'email_smtp' === $type ) {
		// Email mode: 'default', 'disable_all', 'smtp'.
		$email_mode = isset( $input['email_mode'] ) ? $input['email_mode'] : 'default';
		if ( ! in_array( $email_mode, array( 'default', 'disable_all', 'smtp' ), true ) ) {
			$email_mode = 'default';
		}
		$sanitized['email_mode'] = $email_mode;

		$sanitized['email_smtp_host']       = isset( $input['email_smtp_host'] ) ? sanitize_text_field( $input['email_smtp_host'] ) : '';
		$sanitized['email_smtp_port']       = isset( $input['email_smtp_port'] ) && is_numeric( $input['email_smtp_port'] )
			? (int) $input['email_smtp_port']
			: 587;
		$sanitized['email_smtp_username']   = isset( $input['email_smtp_username'] ) ? sanitize_text_field( $input['email_smtp_username'] ) : '';

		// SMTP jelszó: titkosítás Sodium-mal.
		$old_settings    = get_option( 'refitune_settings', array() );
		$old_password    = isset( $old_settings['email_smtp_password'] ) ? $old_settings['email_smtp_password'] : '';
		$new_password    = isset( $input['email_smtp_password'] ) ? $input['email_smtp_password'] : '';
		$password_to_save = '';

		if ( '' !== $new_password ) {
			// Ha a jelszó megváltozott (nem egyezik a régi értékkel), titkosítjuk.
			if ( $new_password !== $old_password ) {
				$password_to_save = refitune_encrypt( $new_password );
			} else {
				// Ha nem változott, megtartjuk a régi (már titkosított) értéket.
				$password_to_save = $old_password;
			}
		}
		$sanitized['email_smtp_password'] = $password_to_save;

	$sanitized['email_smtp_encryption'] = isset( $input['email_smtp_encryption'] ) && in_array( $input['email_smtp_encryption'], array( 'none', 'ssl', 'tls' ), true )
		? $input['email_smtp_encryption']
		: 'tls';
	$sanitized['email_smtp_from_email']        = isset( $input['email_smtp_from_email'] ) ? sanitize_email( $input['email_smtp_from_email'] ) : '';
	$sanitized['email_smtp_from_name']         = isset( $input['email_smtp_from_name'] ) ? sanitize_text_field( $input['email_smtp_from_name'] ) : '';
	$sanitized['email_smtp_disable_ssl_verify'] = ! empty( $input['email_smtp_disable_ssl_verify'] );
	} elseif ( 'comments_control' === $type ) {
			$sanitized['disable_comments']              = ! empty( $input['disable_comments'] );
			$sanitized['disable_comments_keep_reviews'] = ! empty( $input['disable_comments_keep_reviews'] );
		} elseif ( 'number_input' === $type ) {
			$option_key = $feature['option_key'];
			$raw        = isset( $input[ $option_key ] ) ? trim( (string) $input[ $option_key ] ) : '';
			if ( '' !== $raw && is_numeric( $raw ) && (int) $raw >= 0 ) {
				$sanitized[ $option_key ] = (int) $raw;
			} else {
				$sanitized[ $option_key ] = '';
			}
		} elseif ( 'email_controls' === $type ) {
			$bool_keys = array(
				'email_disable_all',
				'email_disable_update',
				'email_disable_new_user',
				'email_disable_password_reset',
				'email_disable_comments',
				'email_disable_privacy',
				'email_disable_critical',
			);
			foreach ( $bool_keys as $bk ) {
				$sanitized[ $bk ] = ! empty( $input[ $bk ] );
			}
			$sanitized['email_update_address']   = isset( $input['email_update_address'] )
				? sanitize_email( $input['email_update_address'] )
				: '';
			$sanitized['email_critical_address'] = isset( $input['email_critical_address'] )
				? sanitize_email( $input['email_critical_address'] )
				: '';
		} elseif ( 'role_select' === $type ) {
			$option_key = $feature['option_key'];
			$submitted  = isset( $input[ $option_key ] ) && is_array( $input[ $option_key ] )
				? $input[ $option_key ]
				: array();

			$sanitized_roles = array();
			foreach ( $submitted as $role ) {
				$role = sanitize_key( $role );
				if ( in_array( $role, $all_roles, true ) ) {
					$sanitized_roles[] = $role;
				}
			}

			if ( ! empty( $feature['required_roles'] ) ) {
				foreach ( $feature['required_roles'] as $required ) {
					if ( ! in_array( $required, $sanitized_roles, true ) ) {
						$sanitized_roles[] = $required;
					}
				}
			}

	$sanitized[ $option_key ] = $sanitized_roles;

	if ( isset( $feature['enable_key'] ) ) {
		$sanitized[ $feature['enable_key'] ] = ! empty( $input[ $feature['enable_key'] ] );
	}
} elseif ( 'maintenance_mode' === $type ) {
	// Enable checkbox
	$sanitized[ $feature['enable_key'] ] = ! empty( $input[ $feature['enable_key'] ] );

	// Szerepkörök sanitálása (ugyanaz mint role_select)
	$option_key = $feature['option_key'];
	$submitted  = isset( $input[ $option_key ] ) && is_array( $input[ $option_key ] )
		? $input[ $option_key ]
		: array();

	$sanitized_roles = array();
	foreach ( $submitted as $role ) {
		$role = sanitize_key( $role );
		if ( in_array( $role, $all_roles, true ) ) {
			$sanitized_roles[] = $role;
		}
	}

	// Required roles hozzáadása
	if ( ! empty( $feature['required_roles'] ) ) {
		foreach ( $feature['required_roles'] as $required ) {
			if ( ! in_array( $required, $sanitized_roles, true ) ) {
				$sanitized_roles[] = $required;
			}
		}
	}

	$sanitized[ $option_key ] = $sanitized_roles;

	// Üzenet sanitálása
	$message_key = $feature['message_key'];
	$sanitized[ $message_key ] = isset( $input[ $message_key ] )
		? sanitize_textarea_field( $input[ $message_key ] )
		: '';
	} elseif ( 'login_limit' === $type ) {
		// Enable checkbox.
		$sanitized['login_limit_enabled'] = ! empty( $input['login_limit_enabled'] );

		// Block "admin" username instantly checkbox.
		$sanitized['login_limit_block_admin_username'] = ! empty( $input['login_limit_block_admin_username'] );

		// Max attempts.
		$max_attempts = isset( $input['login_limit_max_attempts'] ) ? trim( (string) $input['login_limit_max_attempts'] ) : '5';
		$sanitized['login_limit_max_attempts'] = ( '' !== $max_attempts && is_numeric( $max_attempts ) && (int) $max_attempts > 0 )
			? (int) $max_attempts
			: 5;

		// Lockout duration.
		$lockout_duration = isset( $input['login_limit_lockout_duration'] ) ? trim( (string) $input['login_limit_lockout_duration'] ) : '15';
		$sanitized['login_limit_lockout_duration'] = ( '' !== $lockout_duration && is_numeric( $lockout_duration ) && (int) $lockout_duration > 0 )
			? (int) $lockout_duration
			: 15;

		// Whitelist IPs - sortörésenként egy IP.
		$whitelist = isset( $input['login_limit_whitelist_ips'] ) ? $input['login_limit_whitelist_ips'] : '';
		$ips       = array_filter( array_map( 'trim', explode( "\n", $whitelist ) ) );
		$valid_ips = array();
		foreach ( $ips as $ip ) {
			if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				$valid_ips[] = $ip;
			}
		}
		$sanitized['login_limit_whitelist_ips'] = implode( "\n", $valid_ips );

		// Global rate limiting: enable checkbox.
		$sanitized['login_limit_global_enabled'] = ! empty( $input['login_limit_global_enabled'] );

		// Global rate limiting: attempts.
		$global_attempts = isset( $input['login_limit_global_attempts'] ) ? trim( (string) $input['login_limit_global_attempts'] ) : '50';
		$sanitized['login_limit_global_attempts'] = ( '' !== $global_attempts && is_numeric( $global_attempts ) && (int) $global_attempts > 0 )
			? (int) $global_attempts
			: 50;

		// Global rate limiting: time window.
		$global_time = isset( $input['login_limit_global_time_window'] ) ? trim( (string) $input['login_limit_global_time_window'] ) : '5';
		$sanitized['login_limit_global_time_window'] = ( '' !== $global_time && is_numeric( $global_time ) && (int) $global_time > 0 )
			? (int) $global_time
			: 5;
	} elseif ( 'heartbeat_control' === $type ) {
		// Main checkbox
		$sanitized['heartbeat_control'] = ! empty( $input['heartbeat_control'] );

		// Admin Heartbeat
		$admin_value = isset( $input['heartbeat_admin'] ) ? $input['heartbeat_admin'] : '';
		$sanitized['heartbeat_admin'] = in_array( $admin_value, array( '', '15', '30', '60', '120', 'disable' ), true ) ? $admin_value : '';

		// Frontend Heartbeat
		$frontend_value = isset( $input['heartbeat_frontend'] ) ? $input['heartbeat_frontend'] : '';
		$sanitized['heartbeat_frontend'] = in_array( $frontend_value, array( '', '15', '30', '60', '120', 'disable' ), true ) ? $frontend_value : '';

		// Post Editor Heartbeat
		$editor_value = isset( $input['heartbeat_editor'] ) ? $input['heartbeat_editor'] : '';
		$sanitized['heartbeat_editor'] = in_array( $editor_value, array( '', '15', '30', '60', '120', 'disable' ), true ) ? $editor_value : '';
	} elseif ( isset( $feature['sub_options'] ) ) {
			foreach ( array_keys( $feature['sub_options'] ) as $sub_key ) {
				$sanitized[ $sub_key ] = ! empty( $input[ $sub_key ] );
			}
		} else {
			$sanitized[ $key ] = ! empty( $input[ $key ] );
		}
	}

	$sanitized['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

	$old_settings = get_option( 'refitune_settings', array() );

	unset( $sanitized['file_restrictions'] );

	// Disable Comments: automatikus opció frissítés a WordPress Site Health számára.
	$old_disable_comments = ! empty( $old_settings['disable_comments'] );
	$new_disable_comments = ! empty( $sanitized['disable_comments'] );

	// Ha változott a beállítás, frissítjük a WordPress core opciókat.
	if ( $old_disable_comments !== $new_disable_comments ) {
		if ( $new_disable_comments ) {
			// Disable Comments aktiválva -> WordPress opciókat 'closed'-ra állítjuk.
			update_option( 'default_comment_status', 'closed' );
			update_option( 'default_ping_status', 'closed' );
		} else {
			// Disable Comments kikapcsolva -> WordPress opciókat visszaállítjuk 'open'-re.
			update_option( 'default_comment_status', 'open' );
			update_option( 'default_ping_status', 'open' );
		}
	}

	return $sanitized;
}

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
			$plugin_data = get_plugin_data( REFITUNE_PATH . 'refitune.php' );

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
