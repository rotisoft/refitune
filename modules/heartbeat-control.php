<?php
/**
 * Heartbeat API Control
 *
 * Control WordPress Heartbeat API frequency or disable it entirely
 * in three independent contexts: Admin, Frontend, and Post Editor.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_heartbeat_settings = get_option( 'wprefi_settings', array() );

// ============================================================================
// 1. ADMIN HEARTBEAT (Dashboard and other admin pages, NOT post editor)
// ============================================================================
$admin_value = isset( $wprefi_heartbeat_settings['heartbeat_admin'] ) ? $wprefi_heartbeat_settings['heartbeat_admin'] : '';

if ( 'disable' === $admin_value ) {
	add_action(
		'admin_enqueue_scripts',
		static function ( $hook ) {
			global $pagenow;
			// Csak akkor deregisztráljuk ha NEM post editor.
			if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_deregister_script( 'heartbeat' );
			}
		}
	);
} elseif ( '' !== $admin_value && is_numeric( $admin_value ) ) {
	add_filter(
		'heartbeat_settings',
		static function ( $settings ) use ( $admin_value ) {
			global $pagenow;
			// Csak admin oldalakra alkalmazzuk, post editorra nem.
			if ( is_admin() && ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$settings['interval'] = (int) $admin_value;
			}
			return $settings;
		}
	);
}

// ============================================================================
// 2. FRONTEND HEARTBEAT
// ============================================================================
$frontend_value = isset( $wprefi_heartbeat_settings['heartbeat_frontend'] ) ? $wprefi_heartbeat_settings['heartbeat_frontend'] : '';

if ( 'disable' === $frontend_value ) {
	add_action(
		'init',
		static function (): void {
			if ( ! is_admin() ) {
				wp_deregister_script( 'heartbeat' );
			}
		},
		1
	);
} elseif ( '' !== $frontend_value && is_numeric( $frontend_value ) ) {
	add_filter(
		'heartbeat_settings',
		static function ( $settings ) use ( $frontend_value ) {
			if ( ! is_admin() ) {
				$settings['interval'] = (int) $frontend_value;
			}
			return $settings;
		}
	);
}

// ============================================================================
// 3. POST EDITOR HEARTBEAT (Gutenberg + Classic Editor)
// ============================================================================
$editor_value = isset( $wprefi_heartbeat_settings['heartbeat_editor'] ) ? $wprefi_heartbeat_settings['heartbeat_editor'] : '';

if ( 'disable' === $editor_value ) {
	add_action(
		'admin_enqueue_scripts',
		static function ( $hook ) {
			global $pagenow;
			// Csak post editor oldalakon deregisztráljuk.
			if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_deregister_script( 'heartbeat' );
			}
		}
	);
} elseif ( '' !== $editor_value && is_numeric( $editor_value ) ) {
	add_filter(
		'heartbeat_settings',
		static function ( $settings ) use ( $editor_value ) {
			global $pagenow;
			// Csak post editor oldalakon alkalmazzuk.
			if ( is_admin() && in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$settings['interval'] = (int) $editor_value;
			}
			return $settings;
		}
	);
}
