<?php
/**
 * Emodzsi letiltás – emoji szkriptek és stílusok eltávolítása.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Emoji-hoz kapcsolódó akciók és filterek eltávolítása.
 *
 * @return void
 */
function wprefi_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'wprefi_disable_emoji_tinymce' );
	add_filter( 'wp_resource_hints', 'wprefi_disable_emoji_dns_prefetch', 10, 2 );
}
add_action( 'init', 'wprefi_disable_emoji' );

/**
 * Emoji plugin eltávolítása a TinyMCE szerkesztőből.
 *
 * @param array $plugins Betöltött TinyMCE pluginok listája.
 * @return array
 */
function wprefi_disable_emoji_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}
	return array();
}

/**
 * Emoji CDN DNS prefetch eltávolítása a resource hints közül.
 *
 * @param array  $urls          Resource hint URL-ek.
 * @param string $relation_type A hint típusa (pl. dns-prefetch).
 * @return array
 */
function wprefi_disable_emoji_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$emoji_url = 'https://s.w.org/images/core/emoji/';
		foreach ( $urls as $key => $url ) {
			if ( false !== strpos( $url, $emoji_url ) ) {
				unset( $urls[ $key ] );
			}
		}
	}
	return $urls;
}
