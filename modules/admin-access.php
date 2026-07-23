<?php
/**
 * Restrict wp-admin access by role.
 *
 * Logged-in users whose role is not allowed are redirected to the home page
 * when they try to access the wp-admin area.
 * Users with the manage_options capability always have access (lockout safety).
 * AJAX is intentionally not blocked so front-end admin-ajax callbacks keep working.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current user may access wp-admin under Restrict Admin Access.
 *
 * @param array $refitune_allowed_roles Role slugs allowed in settings.
 * @return bool
 */
function refitune_user_may_access_admin( array $refitune_allowed_roles ): bool {
	// Capability hard allow: never lock out site operators, even if role slugs diverge.
	if ( current_user_can( 'manage_options' ) ) {
		return true;
	}

	$refitune_user       = wp_get_current_user();
	$refitune_user_roles = (array) $refitune_user->roles;

	foreach ( $refitune_user_roles as $refitune_role ) {
		if ( in_array( $refitune_role, $refitune_allowed_roles, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Check admin access and redirect unauthorized users.
 *
 * @return void
 */
function refitune_restrict_admin_access(): void {
	/*
	 * Intentionally skip admin-ajax.php: many front-end features (cart, forms,
	 * Heartbeat from the public theme) post here while the user is logged in.
	 * Blocking AJAX would break those flows for roles that must not see wp-admin UI.
	 * Privileged AJAX handlers must still enforce their own capability checks.
	 */
	if ( wp_doing_ajax() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$refitune_settings      = refitune_get_settings();
	$refitune_allowed_roles = isset( $refitune_settings['admin_access_roles'] ) ? (array) $refitune_settings['admin_access_roles'] : array();

	if ( empty( $refitune_allowed_roles ) ) {
		return;
	}

	if ( refitune_user_may_access_admin( $refitune_allowed_roles ) ) {
		return;
	}

	wp_safe_redirect( site_url() );
	exit;
}
add_action( 'admin_init', 'refitune_restrict_admin_access', 10 );
