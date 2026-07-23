<?php
/**
 * Limit the number of post revisions.
 *
 * The wp_revisions_to_keep filter also overrides the WP_POST_REVISIONS
 * constant, so the revision count can be set without editing wp-config.php.
 * 0 = disable revisions, positive integer = keep at most that many revisions.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_revisions_settings = refitune_get_settings();
$refitune_revisions_limit    = isset( $refitune_revisions_settings['post_revisions_limit'] )
	? (int) $refitune_revisions_settings['post_revisions_limit']
	: 0;

add_filter(
	'wp_revisions_to_keep',
	static function () use ( $refitune_revisions_limit ): int {
		return $refitune_revisions_limit;
	},
	10
);
