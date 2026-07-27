<?php
/**
 * Plugin Name: RefiTune - Site refiner toolkit
 * Plugin URI: https://rotistudio.com/plugins/refitune-site-refiner-toolkit-for-wordpress
 * Description: Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements. RefiTune is an all-in-one toolkit.
 * Version: 1.3.1
 * Requires at least: 6.2
 * Requires PHP: 7.4
 * Author: RotiStudio - Tamas Rottenbacher
 * Author URI: https://rotistudio.com
 * License: GPLv2 or later
 * Text Domain: refitune
 * Domain Path: /languages
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'REFITUNE_VERSION', '1.3.1' );
define( 'REFITUNE_PATH', plugin_dir_path( __FILE__ ) );
define( 'REFITUNE_URL', plugin_dir_url( __FILE__ ) );

// Register and load translation files.
add_action( 'init', 'refitune_register_textdomain', 1 );
add_action( 'init', 'refitune_load_bundled_textdomain', 20 );

/**
 * Register the plugin text domain path.
 *
 * @return void
 */
function refitune_register_textdomain(): void {
	// phpcs:ignore PluginCheck.CodeAnalysis.DiscouragedFunctions.load_plugin_textdomainFound -- Bundled MO must register before override load on init:20.
	load_plugin_textdomain( 'refitune', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

/**
 * Load bundled translations, overriding copies in wp-content/languages/plugins/.
 *
 * WordPress prefers language files installed under wp-content/languages/plugins/,
 * which may be older than the plugin-shipped .mo during development.
 *
 * @return void
 */
function refitune_load_bundled_textdomain(): void {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Core locale filter for textdomain loading.
	$refitune_locale = apply_filters( 'plugin_locale', determine_locale(), 'refitune' );
	$refitune_mofile = REFITUNE_PATH . 'languages/refitune-' . $refitune_locale . '.mo';

	if ( ! is_readable( $refitune_mofile ) ) {
		return;
	}

	unload_textdomain( 'refitune' );
	load_textdomain( 'refitune', $refitune_mofile );
}

// Load encryption helpers.
require_once REFITUNE_PATH . 'includes/encryption.php';

/**
 * Whether a RefiTune feature is available on the current WordPress version.
 *
 * @param array $refitune_feature Feature definition from refitune_get_features().
 * @return bool
 */
function refitune_is_feature_available( array $refitune_feature ): bool {
	if ( ! empty( $refitune_feature['max_wp_version'] ) ) {
		if ( version_compare( get_bloginfo( 'version' ), (string) $refitune_feature['max_wp_version'], '>=' ) ) {
			return false;
		}
	}

	if ( ! empty( $refitune_feature['requires_webp_support'] ) ) {
		require_once REFITUNE_PATH . 'includes/webp-converter.php';

		if ( ! refitune_webp_server_supports() ) {
			return false;
		}
	}

	return true;
}

/**
 * Return the plugin settings array.
 *
 * Thin wrapper around get_option() so every module reads settings the same
 * way. WordPress caches autoloaded options in memory, so repeated calls are
 * cheap and always reflect mid-request updates.
 *
 * @return array
 */
function refitune_get_settings(): array {
	return (array) get_option( 'refitune_settings', array() );
}

/**
 * Whether a file extension is allowed by the multisite network upload policy.
 *
 * On single-site installs this always returns true. On multisite it respects
 * the network `upload_filetypes` setting.
 *
 * @param string $refitune_ext File extension without a leading dot.
 * @return bool
 */
function refitune_network_allows_upload_extension( string $refitune_ext ): bool {
	if ( ! is_multisite() ) {
		return true;
	}

	$refitune_ext      = strtolower( ltrim( $refitune_ext, '.' ) );
	$refitune_allowed  = explode( ' ', strtolower( (string) get_site_option( 'upload_filetypes', 'jpg jpeg png gif' ) ) );
	$refitune_allowed  = array_filter( array_map( 'trim', $refitune_allowed ) );

	return in_array( $refitune_ext, $refitune_allowed, true );
}

/**
 * Restore default cron schedules on plugin deactivation.
 *
 * When Automatic Updates Control changed the update check interval, the
 * custom cron recurrences disappear with the plugin, so the update check
 * events must be restored to the WordPress default schedule.
 *
 * @return void
 */
function refitune_deactivate(): void {
	$refitune_settings = refitune_get_settings();

	if ( ! empty( $refitune_settings['auto_updates_control'] ) ) {
		require_once REFITUNE_PATH . 'modules/auto-updates.php';
		refitune_restore_default_update_check_schedules();
	}

	wp_clear_scheduled_hook( 'refitune_trash_delete_continue' );
}
register_deactivation_hook( __FILE__, 'refitune_deactivate' );

$refitune_settings = refitune_get_settings();

// --- Header cleanup ---
$refitune_cleanup_head_keys = array(
	'cleanup_head_generator',
	'cleanup_head_wc_generator',
	'cleanup_head_rsd',
	'cleanup_head_wlwmanifest',
	'cleanup_head_shortlink',
	'cleanup_head_adjacent_posts',
);
foreach ( $refitune_cleanup_head_keys as $refitune_ck ) {
	if ( ! empty( $refitune_settings[ $refitune_ck ] ) ) {
		require_once REFITUNE_PATH . 'modules/cleanup-head.php';
		break;
	}
}

// --- Feed link removal ---
$refitune_disable_feeds_keys = array( 'disable_feeds_posts', 'disable_feeds_comments', 'disable_feeds_extra' );
foreach ( $refitune_disable_feeds_keys as $refitune_dk ) {
	if ( ! empty( $refitune_settings[ $refitune_dk ] ) ) {
		require_once REFITUNE_PATH . 'modules/disable-feeds.php';
		break;
	}
}

// --- Disable emoji ---
if ( ! empty( $refitune_settings['disable_emoji'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-emoji.php';
}

// --- Disable jQuery Migrate ---
if ( ! empty( $refitune_settings['disable_jquery_migrate'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-jquery-migrate.php';
}

// --- Disable oEmbed ---
if ( ! empty( $refitune_settings['disable_oembed'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-oembed.php';
}

// --- Remove CSS/JS ver query strings ---
if ( ! empty( $refitune_settings['remove_asset_versions'] ) ) {
	require_once REFITUNE_PATH . 'modules/remove-asset-versions.php';
}

// --- Disable XML-RPC ---
if ( ! empty( $refitune_settings['disable_xmlrpc'] ) ) {
	// Block direct access to xmlrpc.php with a 404 response so the file
	// appears not to exist to attackers.
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		http_response_code( 404 );
		header( 'Content-Type: text/html; charset=utf-8' );
		exit( '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>' );
	}
	require_once REFITUNE_PATH . 'modules/disable-xmlrpc.php';
}

// --- Disable comments ---
if ( ! empty( $refitune_settings['disable_comments'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-comments.php';
}

// --- Disable trackbacks ---
if ( ! empty( $refitune_settings['disable_trackbacks'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-trackbacks.php';
}

// --- Disable file editor ---
if ( ! empty( $refitune_settings['disable_file_edit'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-file-edit.php';
}

// --- Login tweaks ---
if ( ! empty( $refitune_settings['login_tweaks'] ) ) {
	require_once REFITUNE_PATH . 'modules/login-tweaks.php';
}

// --- Hide admin bar ---
if ( ! empty( $refitune_settings['hide_admin_bar_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/hide-admin-bar.php';
}

// --- SVG and AVIF upload ---
$refitune_svg_enabled  = ! empty( $refitune_settings['svg_upload_enabled'] );
$refitune_avif_enabled = ! empty( $refitune_settings['avif_upload_enabled'] );
$refitune_svg_roles    = isset( $refitune_settings['svg_upload_roles'] )  ? (array) $refitune_settings['svg_upload_roles']  : array();
$refitune_avif_roles   = isset( $refitune_settings['avif_upload_roles'] ) ? (array) $refitune_settings['avif_upload_roles'] : array();

if ( ( $refitune_svg_enabled && ! empty( $refitune_svg_roles ) ) || ( $refitune_avif_enabled && ! empty( $refitune_avif_roles ) ) ) {
	require_once REFITUNE_PATH . 'modules/svg-avif-upload.php';
}

// --- Block visibility ---
if ( ! empty( $refitune_settings['block_visibility'] ) && version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
	require_once REFITUNE_PATH . 'modules/block-visibility.php';
}

// --- External links in new window ---
if ( ! empty( $refitune_settings['external_links'] ) ) {
	require_once REFITUNE_PATH . 'modules/external-links.php';
}

// --- Enable page excerpt ---
if ( ! empty( $refitune_settings['page_excerpt'] ) ) {
	require_once REFITUNE_PATH . 'modules/page-excerpt.php';
}

// --- Post revisions limit ---
if ( isset( $refitune_settings['post_revisions_limit'] ) && '' !== $refitune_settings['post_revisions_limit'] ) {
	require_once REFITUNE_PATH . 'modules/post-revisions.php';
}

// --- Auto-save interval ---
if ( isset( $refitune_settings['autosave_interval'] ) && '' !== $refitune_settings['autosave_interval'] ) {
	require_once REFITUNE_PATH . 'modules/autosave-interval.php';
}

// --- Trash Auto-Delete ---
if ( isset( $refitune_settings['trash_auto_delete_days'] ) && '' !== $refitune_settings['trash_auto_delete_days'] ) {
	require_once REFITUNE_PATH . 'modules/trash-auto-delete.php';
}

// --- Heartbeat Control ---
if ( ! empty( $refitune_settings['heartbeat_control'] ) ) {
	require_once REFITUNE_PATH . 'modules/heartbeat-control.php';
}

// --- Email SMTP / disable all ---
$refitune_email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
if ( 'disable_all' === $refitune_email_mode || 'smtp' === $refitune_email_mode ) {
	require_once REFITUNE_PATH . 'modules/email-smtp.php';
}

// --- Email notifications ---
$refitune_email_control_keys = array(
	'email_disable_update',
	'email_disable_new_user',
	'email_disable_password_reset',
	'email_disable_comments',
	'email_disable_privacy',
	'email_disable_critical',
);
foreach ( $refitune_email_control_keys as $refitune_eck ) {
	if ( ! empty( $refitune_settings[ $refitune_eck ] ) ) {
		require_once REFITUNE_PATH . 'modules/email-controls.php';
		break;
	}
}

// --- Login page customization ---
if ( ! empty( $refitune_settings['login_customizer_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/login-customizer.php';
}

// --- Role redirects ---
if ( ! empty( $refitune_settings['role_redirects_enabled'] ) ) {
	$refitune_login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
	$refitune_logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();
	if ( ! empty( $refitune_login_redirects ) || ! empty( $refitune_logout_redirects ) ) {
		require_once REFITUNE_PATH . 'modules/role-redirects.php';
	}
}

// --- Maintenance Mode ---
if ( ! empty( $refitune_settings['maintenance_mode_enabled'] ) ) {
	$refitune_maintenance_roles = isset( $refitune_settings['maintenance_mode_roles'] )
		? (array) $refitune_settings['maintenance_mode_roles']
		: array();
	if ( ! empty( $refitune_maintenance_roles ) ) {
		require_once REFITUNE_PATH . 'modules/maintenance-mode.php';
	}
}

// --- Dynamic Year Shortcodes ---
if ( ! empty( $refitune_settings['dynamic_year'] ) ) {
	require_once REFITUNE_PATH . 'modules/dynamic-year.php';
}

// --- Restrict admin access ---
if ( ! empty( $refitune_settings['admin_access_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/admin-access.php';
}

// --- REST API restrictions ---
$refitune_rest_api_keys   = array( 'rest_disable_users', 'rest_restrict_index', 'rest_disable_media', 'rest_disable_comments', 'rest_disable_search' );
$refitune_rest_api_active = false;
foreach ( $refitune_rest_api_keys as $refitune_rest_key ) {
	if ( ! empty( $refitune_settings[ $refitune_rest_key ] ) ) {
		$refitune_rest_api_active = true;
		break;
	}
}
if ( $refitune_rest_api_active ) {
	require_once REFITUNE_PATH . 'modules/rest-api-restrictions.php';
}

// --- Login limit ---
if ( ! empty( $refitune_settings['login_limit_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/login-limit.php';
}

// --- Verified upload ---
if ( ! empty( $refitune_settings['upload_security'] ) ) {
	require_once REFITUNE_PATH . 'modules/upload-security.php';
}

// --- Clean upload filenames ---
if ( ! empty( $refitune_settings['upload_filename_sanitize'] ) ) {
	require_once REFITUNE_PATH . 'modules/upload-filename-sanitize.php';
}

// --- WebP upload conversion ---
if ( ! empty( $refitune_settings['upload_webp_convert'] ) ) {
	require_once REFITUNE_PATH . 'includes/webp-converter.php';

	if ( refitune_webp_server_supports() ) {
		require_once REFITUNE_PATH . 'modules/upload-webp-convert.php';
	}
}

// --- Automatic updates control ---
if ( ! empty( $refitune_settings['auto_updates_control'] ) ) {
	require_once REFITUNE_PATH . 'modules/auto-updates.php';
	refitune_auto_updates_module_init();
}

// Translatable plugin description on the plugins list screen.
add_filter(
	'all_plugins',
	function ( $plugins ) {
		$refitune_plugin_file = plugin_basename( __FILE__ );
		if ( isset( $plugins[ $refitune_plugin_file ] ) ) {
			$plugins[ $refitune_plugin_file ]['Description'] = __( 'Collects useful refinements and fine-tuning options (performance, security, usability).', 'refitune' );
		}
		return $plugins;
	}
);

if ( is_admin() ) {
	require_once REFITUNE_PATH . 'admin/admin-core.php';
}

/**
 * Show an admin warning when encryption is unavailable but SMTP needs it.
 *
 * @return void
 */
function refitune_encryption_admin_notice() {
	if ( ! current_user_can( 'manage_options' ) || refitune_encryption_available() ) {
		return;
	}

	$refitune_settings = refitune_get_settings();

	if ( 'smtp' !== ( $refitune_settings['email_mode'] ?? 'default' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>RefiTune:</strong> %s</p></div>',
		esc_html__( 'The PHP Sodium extension is not available, so SMTP credentials cannot be decrypted securely. SMTP email sending is disabled until Sodium is enabled on the server.', 'refitune' )
	);
}
add_action( 'admin_notices', 'refitune_encryption_admin_notice', 10 );
