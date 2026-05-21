<?php
/**
 * jQuery Migrate letiltás a frontend oldalon.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * jQuery Migrate függőség eltávolítása a jquery szkriptről.
 *
 * @param WP_Scripts $scripts A WordPress szkript kezelő objektuma.
 * @return void
 */
function wprefi_dequeue_jquery_migrate( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$script = $scripts->registered['jquery'];
		if ( $script->deps ) {
			$script->deps = array_diff( $script->deps, array( 'jquery-migrate' ) );
		}
	}
}
add_action( 'wp_default_scripts', 'wprefi_dequeue_jquery_migrate' );
