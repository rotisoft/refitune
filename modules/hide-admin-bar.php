<?php
/**
 * Admin sáv elrejtése szerepkör szerint.
 *
 * A kiválasztott szerepkörű bejelentkezett felhasználók számára
 * elrejti a WordPress admin sávot.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin sáv elrejtése, ha a felhasználó szerepköre a tiltott listán van.
 *
 * @return void
 */
function wprefi_hide_admin_bar_for_roles(): void {
	if ( ! is_user_logged_in() ) {
		return;
	}

	$settings   = get_option( 'wprefi_settings', array() );
	$hide_roles = isset( $settings['hide_admin_bar_roles'] ) ? (array) $settings['hide_admin_bar_roles'] : array();

	if ( empty( $hide_roles ) ) {
		return;
	}

	$user = wp_get_current_user();

	foreach ( (array) $user->roles as $role ) {
		if ( in_array( $role, $hide_roles, true ) ) {
			show_admin_bar( false );
			return;
		}
	}
}
add_action( 'after_setup_theme', 'wprefi_hide_admin_bar_for_roles' );
