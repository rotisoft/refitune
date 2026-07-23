<?php
/**
 * Hide the admin bar by role.
 *
 * Hides the WordPress admin bar for logged-in users with the
 * selected roles.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Hide the admin bar when the user's role is on the hidden list.
 *
 * @return void
 */
function refitune_hide_admin_bar_for_roles(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$refitune_settings   = refitune_get_settings();
	$refitune_hide_roles = isset( $refitune_settings['hide_admin_bar_roles'] ) ? (array) $refitune_settings['hide_admin_bar_roles'] : array();

	if ( empty( $refitune_hide_roles ) ) {
		return;
	}

	$refitune_user = wp_get_current_user();

	foreach ( (array) $refitune_user->roles as $refitune_role ) {
		if ( in_array( $refitune_role, $refitune_hide_roles, true ) ) {
			show_admin_bar( false );
			return;
		}
	}
}
add_action( 'after_setup_theme', 'refitune_hide_admin_bar_for_roles', 10 );
