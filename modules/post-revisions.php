<?php
/**
 * Post revíziók számának korlátozása.
 *
 * A wp_revisions_to_keep filter felülírja a WP_POST_REVISIONS konstanst is,
 * így wp-config.php módosítása nélkül állítható a revíziók száma.
 * 0 = revíziók letiltása, pozitív egész = maximum ennyi revízió marad meg.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_revisions_limit = (int) get_option( 'wprefi_settings', array() )['post_revisions_limit'];

add_filter(
	'wp_revisions_to_keep',
	static function () use ( $wprefi_revisions_limit ): int {
		return $wprefi_revisions_limit;
	}
);
