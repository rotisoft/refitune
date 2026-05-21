<?php
/**
 * Kivonat (excerpt) mező engedélyezése az oldalakhoz (page post type).
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action(
	'init',
	static function (): void {
		add_post_type_support( 'page', 'excerpt' );
	}
);
