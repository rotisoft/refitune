<?php
/**
 * Feed linkek eltávolítása a HTML forrásból.
 *
 * Csak a <link> elemeket távolítja el a wp_head-ből; a feed URL-ek
 * továbbra is elérhetők maradnak.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = get_option( 'refitune_settings', array() );

if ( ! empty( $refitune_settings['disable_feeds_posts'] ) ) {
	add_filter( 'feed_links_show_posts_feed', '__return_false' );
}

if ( ! empty( $refitune_settings['disable_feeds_comments'] ) ) {
	add_filter( 'feed_links_show_comments_feed', '__return_false' );
}

if ( ! empty( $refitune_settings['disable_feeds_extra'] ) ) {
	remove_action( 'wp_head', 'feed_links_extra', 3 );
}
