<?php
/**
 * Disable jQuery Migrate on the front end.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove the jQuery Migrate dependency from the jquery script.
 *
 * @param WP_Scripts $scripts The WordPress script manager object.
 * @return void
 */
function refitune_dequeue_jquery_migrate( $scripts ) {
	if ( ! is_admin() && isset( $scripts->registered['jquery'] ) ) {
		$refitune_script = $scripts->registered['jquery'];
		if ( $refitune_script->deps ) {
			$refitune_script->deps = array_diff( $refitune_script->deps, array( 'jquery-migrate' ) );
		}
	}
}
add_action( 'wp_default_scripts', 'refitune_dequeue_jquery_migrate', 10 );
