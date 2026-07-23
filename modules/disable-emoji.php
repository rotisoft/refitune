<?php
/**
 * Disable emoji - remove emoji scripts and styles.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remove emoji-related actions and filters.
 *
 * @return void
 */
function refitune_disable_emoji() {
	remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
	remove_action( 'wp_print_styles', 'print_emoji_styles' );
	remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
	remove_action( 'admin_print_styles', 'print_emoji_styles' );
	remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
	remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
	remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
	add_filter( 'tiny_mce_plugins', 'refitune_disable_emoji_tinymce', 10 );
	add_filter( 'wp_resource_hints', 'refitune_disable_emoji_dns_prefetch', 10, 2 );
}
add_action( 'init', 'refitune_disable_emoji', 10 );

/**
 * Remove the emoji plugin from the TinyMCE editor.
 *
 * @param array $plugins List of loaded TinyMCE plugins.
 * @return array
 */
function refitune_disable_emoji_tinymce( $plugins ) {
	if ( is_array( $plugins ) ) {
		return array_diff( $plugins, array( 'wpemoji' ) );
	}
	return array();
}

/**
 * Remove the emoji CDN DNS prefetch from resource hints.
 *
 * @param array  $urls          Resource hint URLs.
 * @param string $relation_type Hint type (e.g. dns-prefetch).
 * @return array
 */
function refitune_disable_emoji_dns_prefetch( $urls, $relation_type ) {
	if ( 'dns-prefetch' === $relation_type ) {
		$refitune_emoji_url = 'https://s.w.org/images/core/emoji/';
		foreach ( $urls as $refitune_key => $refitune_url ) {
			if ( false !== strpos( $refitune_url, $refitune_emoji_url ) ) {
				unset( $urls[ $refitune_key ] );
			}
		}
	}
	return $urls;
}
