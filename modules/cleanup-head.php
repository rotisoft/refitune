<?php
/**
 * Fejléc tisztítás – felesleges wp_head elemek eltávolítása.
 *
 * Csak azokat az elemeket távolítja el, amelyek a beállításokban be vannak kapcsolva.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_settings = get_option( 'wprefi_settings', array() );

if ( ! empty( $wprefi_settings['cleanup_head_generator'] ) ) {
	remove_action( 'wp_head', 'wp_generator' );
}

if ( ! empty( $wprefi_settings['cleanup_head_wc_generator'] ) && class_exists( 'WooCommerce' ) ) {
	add_filter( 'woocommerce_generator_tag', '__return_false' );
}

if ( ! empty( $wprefi_settings['cleanup_head_rsd'] ) ) {
	remove_action( 'wp_head', 'rsd_link' );
}

if ( ! empty( $wprefi_settings['cleanup_head_wlwmanifest'] ) ) {
	remove_action( 'wp_head', 'wlwmanifest_link' );
}

if ( ! empty( $wprefi_settings['cleanup_head_shortlink'] ) ) {
	remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
}

if ( ! empty( $wprefi_settings['cleanup_head_adjacent_posts'] ) ) {
	remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head', 10 );
}
