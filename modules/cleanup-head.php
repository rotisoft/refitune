<?php
/**
 * Header cleanup - remove unnecessary wp_head elements.
 *
 * Only removes the elements that are enabled in the settings.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = refitune_get_settings();

if ( ! empty( $refitune_settings['cleanup_head_generator'] ) ) {
	remove_action( 'wp_head', 'wp_generator' );
}

// Defer WooCommerce detection until plugins_loaded so load order does not matter.
if ( ! empty( $refitune_settings['cleanup_head_wc_generator'] ) ) {
	add_action(
		'plugins_loaded',
		static function (): void {
			if ( class_exists( 'WooCommerce' ) ) {
				add_filter( 'woocommerce_generator_tag', '__return_false', 10 );
			}
		},
		20
	);
}

if ( ! empty( $refitune_settings['cleanup_head_rsd'] ) ) {
	remove_action( 'wp_head', 'rsd_link' );
}

if ( ! empty( $refitune_settings['cleanup_head_wlwmanifest'] ) ) {
	remove_action( 'wp_head', 'wlwmanifest_link' );
}

if ( ! empty( $refitune_settings['cleanup_head_shortlink'] ) ) {
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
}

if ( ! empty( $refitune_settings['cleanup_head_adjacent_posts'] ) ) {
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
