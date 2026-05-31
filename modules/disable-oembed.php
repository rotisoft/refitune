<?php
/**
 * oEmbed letiltása – automatikus külső tartalom beágyazás kikapcsolása.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Empty sanctioned oEmbed providers (primary mechanism).
add_filter( 'oembed_providers', '__return_empty_array', PHP_INT_MAX );

// Disable oEmbed discovery (WP 4.4+).
add_filter( 'embed_oembed_discover', '__return_false', PHP_INT_MAX );

/**
 * Remove oEmbed discovery links and host JS from the head.
 *
 * @return void
 */
function refitune_disable_oembed_remove_head_assets(): void {
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'wp_oembed_add_host_js' );
}
add_action( 'init', 'refitune_disable_oembed_remove_head_assets', 10 );
