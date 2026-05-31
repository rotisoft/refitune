<?php
/**
 * Plugin Name: RefiTune - Site refiner toolkit
 * Plugin URI: https://rotistudio.com/plugins/refitune-site-refiner-toolkit-for-wordpress
 * Description: Take control of WordPress with smart performance tweaks, security enhancements, and usability improvements. RefiTune is all in one toolkit.
 * Version: 1.2.0
 * Requires at least: 5.9
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

define( 'REFITUNE_VERSION', '1.2.0' );
define( 'REFITUNE_PATH', plugin_dir_path( __FILE__ ) );
define( 'REFITUNE_URL', plugin_dir_url( __FILE__ ) );

// Load translation files.
add_action( 'init', function() {
	load_plugin_textdomain( 'refitune', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}, 1 );

// Load encryption helpers.
require_once REFITUNE_PATH . 'includes/encryption.php';

/**
 * Whether a RefiTune feature is available on the current WordPress version.
 *
 * @param array $feature Feature definition from refitune_get_features().
 * @return bool
 */
function refitune_is_feature_available( array $feature ): bool {
	if ( empty( $feature['max_wp_version'] ) ) {
		return true;
	}

	return version_compare( get_bloginfo( 'version' ), (string) $feature['max_wp_version'], '<' );
}

$refitune_settings = get_option( 'refitune_settings', array() );

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
	if ( ! empty( $refitune_settings[ $ck ] ) ) {
		require_once REFITUNE_PATH . 'modules/cleanup-head.php';
		break;
	}
}

// --- Feed linkek eltávolítása ---
$disable_feeds_keys = array( 'disable_feeds_posts', 'disable_feeds_comments', 'disable_feeds_extra' );
foreach ( $disable_feeds_keys as $dk ) {
	if ( ! empty( $refitune_settings[ $dk ] ) ) {
		require_once REFITUNE_PATH . 'modules/disable-feeds.php';
		break;
	}
}

// --- Emodzsi letiltás ---
if ( ! empty( $refitune_settings['disable_emoji'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-emoji.php';
}

// --- jQuery Migrate letiltás ---
if ( ! empty( $refitune_settings['disable_jquery_migrate'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-jquery-migrate.php';
}

// --- oEmbed letiltás ---
if ( ! empty( $refitune_settings['disable_oembed'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-oembed.php';
}

// --- CSS/JS ver query string eltávolítás ---
if ( ! empty( $refitune_settings['remove_asset_versions'] ) ) {
	require_once REFITUNE_PATH . 'modules/remove-asset-versions.php';
}

// --- XML-RPC letiltás ---
if ( ! empty( $refitune_settings['disable_xmlrpc'] ) ) {
	// Blokkoljuk az xmlrpc.php fájl közvetlen elérését 404-es válasszal.
	// Security through obscurity: az attackerek azt hiszik, a fájl nem létezik.
	if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
		http_response_code( 404 );
		header( 'Content-Type: text/html; charset=utf-8' );
		exit( '<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The requested URL was not found on this server.</p></body></html>' );
	}
	require_once REFITUNE_PATH . 'modules/disable-xmlrpc.php';
}

// --- Hozzászólások letiltása ---
if ( ! empty( $refitune_settings['disable_comments'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-comments.php';
}

// --- Trackback letiltás ---
if ( ! empty( $refitune_settings['disable_trackbacks'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-trackbacks.php';
}

// --- Fájlszerkesztő letiltása ---
if ( ! empty( $refitune_settings['disable_file_edit'] ) ) {
	require_once REFITUNE_PATH . 'modules/disable-file-edit.php';
}

// --- Bejelentkezési tweaks ---
if ( ! empty( $refitune_settings['login_tweaks'] ) ) {
	require_once REFITUNE_PATH . 'modules/login-tweaks.php';
}

// --- Admin sáv elrejtése ---
// --- Hide Admin Bar ---
if ( ! empty( $refitune_settings['hide_admin_bar_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/hide-admin-bar.php';
}

// --- SVG és AVIF feltöltés ---
$svg_enabled  = ! empty( $refitune_settings['svg_upload_enabled'] );
$avif_enabled = ! empty( $refitune_settings['avif_upload_enabled'] );
$svg_roles    = isset( $refitune_settings['svg_upload_roles'] )  ? (array) $refitune_settings['svg_upload_roles']  : array();
$avif_roles   = isset( $refitune_settings['avif_upload_roles'] ) ? (array) $refitune_settings['avif_upload_roles'] : array();

if ( ( $svg_enabled && ! empty( $svg_roles ) ) || ( $avif_enabled && ! empty( $avif_roles ) ) ) {
	require_once REFITUNE_PATH . 'modules/svg-avif-upload.php';
}

// --- Blokk láthatóság ---
if ( ! empty( $refitune_settings['block_visibility'] ) && version_compare( get_bloginfo( 'version' ), '7.0', '<' ) ) {
	require_once REFITUNE_PATH . 'modules/block-visibility.php';
}

// --- External linkek új ablakban ---
if ( ! empty( $refitune_settings['external_links'] ) ) {
	require_once REFITUNE_PATH . 'modules/external-links.php';
}

// --- Oldal kivonat engedélyezése ---
if ( ! empty( $refitune_settings['page_excerpt'] ) ) {
	require_once REFITUNE_PATH . 'modules/page-excerpt.php';
}

// --- Post revíziók száma ---
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

// --- Email SMTP / Teljes letiltás ---
$email_mode = isset( $refitune_settings['email_mode'] ) ? $refitune_settings['email_mode'] : 'default';
if ( 'disable_all' === $email_mode || 'smtp' === $email_mode ) {
	require_once REFITUNE_PATH . 'modules/email-smtp.php';
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
	if ( ! empty( $refitune_settings[ $eck ] ) ) {
		require_once REFITUNE_PATH . 'modules/email-controls.php';
		break;
	}
}

// --- Login oldal testreszabása ---
if ( ! empty( $refitune_settings['login_customizer_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/login-customizer.php';
}

// --- Szerepkör átirányítások ---
if ( ! empty( $refitune_settings['role_redirects_enabled'] ) ) {
	$login_redirects  = isset( $refitune_settings['role_redirects_login'] ) && is_array( $refitune_settings['role_redirects_login'] ) ? $refitune_settings['role_redirects_login'] : array();
	$logout_redirects = isset( $refitune_settings['role_redirects_logout'] ) && is_array( $refitune_settings['role_redirects_logout'] ) ? $refitune_settings['role_redirects_logout'] : array();
	if ( ! empty( $login_redirects ) || ! empty( $logout_redirects ) ) {
		require_once REFITUNE_PATH . 'modules/role-redirects.php';
	}
}

// --- Maintenance Mode ---
if ( ! empty( $refitune_settings['maintenance_mode_enabled'] ) ) {
	$maintenance_roles = isset( $refitune_settings['maintenance_mode_roles'] ) 
		? (array) $refitune_settings['maintenance_mode_roles'] 
		: array();
	if ( ! empty( $maintenance_roles ) ) {
		require_once REFITUNE_PATH . 'modules/maintenance-mode.php';
	}
}

// --- Dynamic Year Shortcodes ---
if ( ! empty( $refitune_settings['dynamic_year'] ) ) {
	require_once REFITUNE_PATH . 'modules/dynamic-year.php';
}

// --- Admin felület hozzáférés korlátozása ---
if ( ! empty( $refitune_settings['admin_access_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/admin-access.php';
}

// --- REST API korlátozások ---
$rest_api_keys = array( 'rest_disable_users', 'rest_restrict_index', 'rest_disable_media', 'rest_disable_comments', 'rest_disable_search' );
$rest_api_active = false;
foreach ( $rest_api_keys as $rest_key ) {
	if ( ! empty( $refitune_settings[ $rest_key ] ) ) {
		$rest_api_active = true;
		break;
	}
}
if ( $rest_api_active ) {
	require_once REFITUNE_PATH . 'modules/rest-api-restrictions.php';
}

// --- Bejelentkezési limit ---
if ( ! empty( $refitune_settings['login_limit_enabled'] ) ) {
	require_once REFITUNE_PATH . 'modules/login-limit.php';
}

// --- Ellenőrzött feltöltés ---
if ( ! empty( $refitune_settings['upload_security'] ) ) {
	require_once REFITUNE_PATH . 'modules/upload-security.php';
}

// --- Fájlnév tisztítás feltöltéskor ---
if ( ! empty( $refitune_settings['upload_filename_sanitize'] ) ) {
	require_once REFITUNE_PATH . 'modules/upload-filename-sanitize.php';
}

// --- Automatic updates control ---
if ( ! empty( $refitune_settings['auto_updates_control'] ) ) {
	require_once REFITUNE_PATH . 'modules/auto-updates.php';
	refitune_auto_updates_module_init();
}

// Fordítható plugin leírás a plugin listában.
add_filter(
	'all_plugins',
	function ( $plugins ) {
		$plugin_file = plugin_basename( __FILE__ );
		if ( isset( $plugins[ $plugin_file ] ) ) {
			$plugins[ $plugin_file ]['Description'] = __( 'Collects useful refinements and fine-tuning options (performance, security, usability).', 'refitune' );
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

	$settings = get_option( 'refitune_settings', array() );

	if ( 'smtp' !== ( $settings['email_mode'] ?? 'default' ) ) {
		return;
	}

	printf(
		'<div class="notice notice-error"><p><strong>RefiTune:</strong> %s</p></div>',
		esc_html__( 'The PHP Sodium extension is not available, so SMTP credentials cannot be decrypted securely. SMTP email sending is disabled until Sodium is enabled on the server.', 'refitune' )
	);
}
add_action( 'admin_notices', 'refitune_encryption_admin_notice', 10 );
