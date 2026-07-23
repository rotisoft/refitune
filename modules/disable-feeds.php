<?php
/**
 * Remove feed links from the HTML source.
 *
 * Only removes the <link> elements from wp_head; the feed URLs
 * themselves remain accessible.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = refitune_get_settings();

if ( ! empty( $refitune_settings['disable_feeds_posts'] ) ) {
	add_filter( 'feed_links_show_posts_feed', '__return_false', 10 );
}

if ( ! empty( $refitune_settings['disable_feeds_comments'] ) ) {
	add_filter( 'feed_links_show_comments_feed', '__return_false', 10 );
}

if ( ! empty( $refitune_settings['disable_feeds_extra'] ) ) {
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}
