<?php
/**
 * Admin felület hozzáférés korlátozása szerepkör szerint.
 *
 * A nem engedélyezett szerepkörű bejelentkezett felhasználókat a kezdőlapra
 * irányítja, ha megpróbálják elérni a wp-admin felületet.
 * Az administrator szerepkör mindig hozzáfér.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin hozzáférés ellenőrzése és átirányítás.
 *
 * @return void
 */
function refitune_restrict_admin_access(): void {
	if ( wp_doing_ajax() ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		return;
	}

	$settings      = get_option( 'refitune_settings', array() );
	$allowed_roles = isset( $settings['admin_access_roles'] ) ? (array) $settings['admin_access_roles'] : array();

	if ( empty( $allowed_roles ) ) {
		return;
	}

	$user       = wp_get_current_user();
	$user_roles = (array) $user->roles;

	foreach ( $user_roles as $role ) {
		if ( in_array( $role, $allowed_roles, true ) ) {
			return;
		}
	}

	wp_safe_redirect( site_url() );
	exit;
}
add_action( 'admin_init', 'refitune_restrict_admin_access' );
