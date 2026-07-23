<?php
/**
 * Heartbeat API Control
 *
 * Control WordPress Heartbeat API frequency or disable it entirely
 * in three independent contexts: Admin, Frontend, and Post Editor.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_heartbeat_settings = refitune_get_settings();

// ============================================================================
// 1. ADMIN HEARTBEAT (Dashboard and other admin pages, NOT post editor)
// ============================================================================
$refitune_admin_value = isset( $refitune_heartbeat_settings['heartbeat_admin'] ) ? $refitune_heartbeat_settings['heartbeat_admin'] : '';

if ( 'disable' === $refitune_admin_value ) {
	add_action(
		'admin_enqueue_scripts',
		static function ( $hook ) {
			global $pagenow;
			// Only deregister when NOT on a post editor screen.
			if ( ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_deregister_script( 'heartbeat' );
			}
		},
		100
	);
} elseif ( '' !== $refitune_admin_value && is_numeric( $refitune_admin_value ) ) {
	add_filter(
		'heartbeat_settings',
		static function ( $settings ) use ( $refitune_admin_value ) {
			global $pagenow;
			// Apply to admin pages only, not the post editor.
			if ( is_admin() && ! in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$settings['interval'] = (int) $refitune_admin_value;
			}
			return $settings;
		},
		10
	);
}

// ============================================================================
// 2. FRONTEND HEARTBEAT
// ============================================================================
$refitune_frontend_value = isset( $refitune_heartbeat_settings['heartbeat_frontend'] ) ? $refitune_heartbeat_settings['heartbeat_frontend'] : '';

if ( 'disable' === $refitune_frontend_value ) {
	// The heartbeat script is registered on wp_default_scripts and may be
	// enqueued by themes/plugins on wp_enqueue_scripts, so deregister late
	// on the same hook instead of init (where deregistration is a no-op).
	add_action(
		'wp_enqueue_scripts',
		static function (): void {
			wp_dequeue_script( 'heartbeat' );
			wp_deregister_script( 'heartbeat' );
		},
		100
	);
} elseif ( '' !== $refitune_frontend_value && is_numeric( $refitune_frontend_value ) ) {
	add_filter(
		'heartbeat_settings',
		static function ( $settings ) use ( $refitune_frontend_value ) {
			if ( ! is_admin() ) {
				$settings['interval'] = (int) $refitune_frontend_value;
			}
			return $settings;
		},
		10
	);
}

// ============================================================================
// 3. POST EDITOR HEARTBEAT (Gutenberg + Classic Editor)
// ============================================================================
$refitune_editor_value = isset( $refitune_heartbeat_settings['heartbeat_editor'] ) ? $refitune_heartbeat_settings['heartbeat_editor'] : '';

if ( 'disable' === $refitune_editor_value ) {
	add_action(
		'admin_enqueue_scripts',
		static function ( $hook ) {
			global $pagenow;
			// Only deregister on post editor screens.
			if ( in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				wp_deregister_script( 'heartbeat' );
			}
		},
		100
	);
} elseif ( '' !== $refitune_editor_value && is_numeric( $refitune_editor_value ) ) {
	add_filter(
		'heartbeat_settings',
		static function ( $settings ) use ( $refitune_editor_value ) {
			global $pagenow;
			// Apply on post editor screens only.
			if ( is_admin() && in_array( $pagenow, array( 'post.php', 'post-new.php' ), true ) ) {
				$settings['interval'] = (int) $refitune_editor_value;
			}
			return $settings;
		},
		10
	);
}
