<?php
/**
 * Kivonat (excerpt) mező engedélyezése az oldalakhoz (page post type).
 *
 * @package RefiTune
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
