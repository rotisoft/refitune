<?php
/**
 * Plugin Name: RefinerPress Toolkit
 * Plugin URI: https://rotistudio.com/plugins/refinerpress-toolkit-wordpress-tweaks
 * Description: Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements — all in one toolkit.
 * Version: 1.0.0
 * Requires at least: 5.9
 * Requires PHP: 7.4
 * Author: RotiStudio - Tamas Rottenbacher
 * Author URI: https://rotistudio.hu
 * License: GPLv2 or later
 * Text Domain: refinerpress
 * Domain Path: /languages
 *
 * @package RefinerPress_Toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'WPREFI_VERSION', '1.0.0' );
define( 'WPREFI_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPREFI_URL', plugin_dir_url( __FILE__ ) );

// Load translation files.
add_action( 'init', function() {
	load_plugin_textdomain( 'refinerpress', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 1 );

// Titkosítási segédfüggvények betöltése.
require_once WPREFI_PATH . 'includes/encryption.php';

$wprefi_settings = get_option( 'wprefi_settings', array() );

// --- Fejléc tisztítás ---
$cleanup_head_keys = array(
	'cleanup_head_generator',
	'cleanup_head_wc_generator',
	'cleanup_head_rsd',
	'cleanup_head_wlwmanifest',
	'cleanup_head_shortlink',
	'cleanup_head_adjacent_posts',
);
foreach ( $cleanup_head_keys as $ck ) {
	if ( ! empty( $wprefi_settings[ $ck ] ) ) {
		require_once WPREFI_PATH . 'modules/cleanup-head.php';
		break;
	}
}

// --- Feed linkek eltávolítása ---
$disable_feeds_keys = array( 'disable_feeds_posts', 'disable_feeds_comments', 'disable_feeds_extra' );
foreach ( $disable_feeds_keys as $dk ) {
	if ( ! empty( $wprefi_settings[ $dk ] ) ) {
		require_once WPREFI_PATH . 'modules/disable-feeds.php';
		break;
	}
}

// --- Emodzsi letiltás ---
if ( ! empty( $wprefi_settings['disable_emoji'] ) ) {
	require_once WPREFI_PATH . 'modules/disable-emoji.php';
}

// --- jQuery Migrate letiltás ---
if ( ! empty( $wprefi_settings['disable_jquery_migrate'] ) ) {
	require_once WPREFI_PATH . 'modules/disable-jquery-migrate.php';
}

// --- XML-RPC letiltás ---
if ( ! empty( $wprefi_settings['disable_xmlrpc'] ) ) {
	// Blokkoljuk az xmlrpc.php fájl közvetlen elérését 404-es válasszal.
	// Security through obscurity: az attackerek azt hiszik, a fájl nem létezik.
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		http_response_code( 404 );
		header( 'Content-Type: text/html; charset=utf-8' );
		exit( '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>' );
	}
	require_once WPREFI_PATH . 'modules/disable-xmlrpc.php';
}

// --- Hozzászólások letiltása ---
if ( ! empty( $wprefi_settings['disable_comments'] ) ) {
	require_once WPREFI_PATH . 'modules/disable-comments.php';
}

// --- Trackback letiltás ---
if ( ! empty( $wprefi_settings['disable_trackbacks'] ) ) {
	require_once WPREFI_PATH . 'modules/disable-trackbacks.php';
}

// --- Fájlszerkesztő letiltása ---
if ( ! empty( $wprefi_settings['disable_file_edit'] ) ) {
	require_once WPREFI_PATH . 'modules/disable-file-edit.php';
}

// --- Bejelentkezési tweaks ---
if ( ! empty( $wprefi_settings['login_tweaks'] ) ) {
	require_once WPREFI_PATH . 'modules/login-tweaks.php';
}

// --- Admin sáv elrejtése ---
// --- Hide Admin Bar ---
if ( ! empty( $wprefi_settings['hide_admin_bar_enabled'] ) ) {
	require_once WPREFI_PATH . 'modules/hide-admin-bar.php';
}

// --- SVG és AVIF feltöltés ---
$svg_enabled  = ! empty( $wprefi_settings['svg_upload_enabled'] );
$avif_enabled = ! empty( $wprefi_settings['avif_upload_enabled'] );
$svg_roles    = isset( $wprefi_settings['svg_upload_roles'] )  ? (array) $wprefi_settings['svg_upload_roles']  : array();
$avif_roles   = isset( $wprefi_settings['avif_upload_roles'] ) ? (array) $wprefi_settings['avif_upload_roles'] : array();

if ( ( $svg_enabled && ! empty( $svg_roles ) ) || ( $avif_enabled && ! empty( $avif_roles ) ) ) {
	require_once WPREFI_PATH . 'modules/svg-avif-upload.php';
}

// --- Blokk láthatóság ---
if ( ! empty( $wprefi_settings['block_visibility'] ) ) {
	require_once WPREFI_PATH . 'modules/block-visibility.php';
}

// --- External linkek új ablakban ---
if ( ! empty( $wprefi_settings['external_links'] ) ) {
	require_once WPREFI_PATH . 'modules/external-links.php';
}

// --- Oldal kivonat engedélyezése ---
if ( ! empty( $wprefi_settings['page_excerpt'] ) ) {
	require_once WPREFI_PATH . 'modules/page-excerpt.php';
}

// --- Post revíziók száma ---
if ( isset( $wprefi_settings['post_revisions_limit'] ) && '' !== $wprefi_settings['post_revisions_limit'] ) {
	require_once WPREFI_PATH . 'modules/post-revisions.php';
}

// --- Auto-save interval ---
if ( isset( $wprefi_settings['autosave_interval'] ) && '' !== $wprefi_settings['autosave_interval'] ) {
	require_once WPREFI_PATH . 'modules/autosave-interval.php';
}

// --- Trash Auto-Delete ---
if ( isset( $wprefi_settings['trash_auto_delete_days'] ) && '' !== $wprefi_settings['trash_auto_delete_days'] ) {
	require_once WPREFI_PATH . 'modules/trash-auto-delete.php';
}

// --- Heartbeat Control ---
if ( ! empty( $wprefi_settings['heartbeat_control'] ) ) {
	require_once WPREFI_PATH . 'modules/heartbeat-control.php';
}

// --- Email SMTP / Teljes letiltás ---
$email_mode = isset( $wprefi_settings['email_mode'] ) ? $wprefi_settings['email_mode'] : 'default';
if ( 'disable_all' === $email_mode || 'smtp' === $email_mode ) {
	require_once WPREFI_PATH . 'modules/email-smtp.php';
}

// --- Email értesítések ---
$email_control_keys = array(
	'email_disable_update',
	'email_disable_new_user',
	'email_disable_password_reset',
	'email_disable_comments',
	'email_disable_privacy',
	'email_disable_critical',
);
foreach ( $email_control_keys as $eck ) {
	if ( ! empty( $wprefi_settings[ $eck ] ) ) {
		require_once WPREFI_PATH . 'modules/email-controls.php';
		break;
	}
}

// --- Login oldal testreszabása ---
if ( ! empty( $wprefi_settings['login_customizer_enabled'] ) ) {
	require_once WPREFI_PATH . 'modules/login-customizer.php';
}

// --- Szerepkör átirányítások ---
if ( ! empty( $wprefi_settings['role_redirects_enabled'] ) ) {
	$login_redirects  = isset( $wprefi_settings['role_redirects_login'] ) && is_array( $wprefi_settings['role_redirects_login'] ) ? $wprefi_settings['role_redirects_login'] : array();
	$logout_redirects = isset( $wprefi_settings['role_redirects_logout'] ) && is_array( $wprefi_settings['role_redirects_logout'] ) ? $wprefi_settings['role_redirects_logout'] : array();
	if ( ! empty( $login_redirects ) || ! empty( $logout_redirects ) ) {
		require_once WPREFI_PATH . 'modules/role-redirects.php';
	}
}

// --- Maintenance Mode ---
if ( ! empty( $wprefi_settings['maintenance_mode_enabled'] ) ) {
	$maintenance_roles = isset( $wprefi_settings['maintenance_mode_roles'] ) 
		? (array) $wprefi_settings['maintenance_mode_roles'] 
		: array();
	if ( ! empty( $maintenance_roles ) ) {
		require_once WPREFI_PATH . 'modules/maintenance-mode.php';
	}
}

// --- Dynamic Year Shortcodes ---
if ( ! empty( $wprefi_settings['dynamic_year'] ) ) {
	require_once WPREFI_PATH . 'modules/dynamic-year.php';
}

// --- Admin felület hozzáférés korlátozása ---
if ( ! empty( $wprefi_settings['admin_access_enabled'] ) ) {
	require_once WPREFI_PATH . 'modules/admin-access.php';
}

// --- REST API korlátozások ---
$rest_api_keys = array( 'rest_disable_users', 'rest_restrict_index', 'rest_disable_media', 'rest_disable_comments', 'rest_disable_search' );
$rest_api_active = false;
foreach ( $rest_api_keys as $rest_key ) {
	if ( ! empty( $wprefi_settings[ $rest_key ] ) ) {
		$rest_api_active = true;
		break;
	}
}
if ( $rest_api_active ) {
	require_once WPREFI_PATH . 'modules/rest-api-restrictions.php';
}

/**
 * Remove legacy File Restrictions marker blocks from .htaccess (feature removed).
 */
function wprefi_cleanup_removed_file_restrictions_htaccess(): void {
	if ( ! function_exists( 'get_home_path' ) ) {
		require_once ABSPATH . 'wp-admin/includes/file.php';
	}

	$path = wp_normalize_path( get_home_path() . '.htaccess' );

	if ( ! file_exists( $path ) || ! is_writable( $path ) ) {
		return;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$contents = file_get_contents( $path );

	if ( false === $contents ) {
		return;
	}

	$marker  = preg_quote( 'RefinerPress File Restrictions', '/' );
	$stripped = preg_replace(
		'/[\r\n]*# BEGIN ' . $marker . '.*?# END ' . $marker . '[\r\n]*/s',
		"\n",
		$contents
	);

	if ( ! is_string( $stripped ) || $stripped === $contents ) {
		return;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	file_put_contents( $path, trim( $stripped ) . "\n" );
}

/**
 * One-time migration: drop file_restrictions setting and clean .htaccess.
 */
function wprefi_migrate_remove_file_restrictions_feature(): void {
	if ( get_option( 'wprefi_removed_file_restrictions_v1' ) ) {
		return;
	}

	wprefi_cleanup_removed_file_restrictions_htaccess();

	$settings = get_option( 'wprefi_settings', array() );

	if ( is_array( $settings ) && array_key_exists( 'file_restrictions', $settings ) ) {
		unset( $settings['file_restrictions'] );
		update_option( 'wprefi_settings', $settings );
	}

	update_option( 'wprefi_removed_file_restrictions_v1', 1 );
}
add_action( 'plugins_loaded', 'wprefi_migrate_remove_file_restrictions_feature', 1 );
register_deactivation_hook( __FILE__, 'wprefi_cleanup_removed_file_restrictions_htaccess' );

// --- Bejelentkezési limit ---
if ( ! empty( $wprefi_settings['login_limit_enabled'] ) ) {
	require_once WPREFI_PATH . 'modules/login-limit.php';
}

// Fordítható plugin leírás a plugin listában.
add_filter(
	'all_plugins',
	function ( $plugins ) {
		$plugin_file = plugin_basename( __FILE__ );
		if ( isset( $plugins[ $plugin_file ] ) ) {
			$plugins[ $plugin_file ]['Description'] = __( 'Collects useful refinements and fine-tuning options (performance, security, usability).', 'refinerpress' );
		}
		return $plugins;
	}
);

if ( is_admin() ) {
	require_once WPREFI_PATH . 'admin/admin-core.php';
}
