<?php
/**
 * Remove ?ver= query strings from frontend CSS and JS asset URLs.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Strip the ver query argument from enqueued stylesheet and script URLs.
 *
 * @param string $src Asset URL.
 * @return string Filtered asset URL.
 */
function refitune_remove_asset_version_query( $src ) {
	if ( is_admin() ) {
		return $src;
	}

	// Skip external hosts; only strip ver on same-site assets.
	$refitune_src_host  = wp_parse_url( $src, PHP_URL_HOST );
	$refitune_home_host = wp_parse_url( home_url(), PHP_URL_HOST );
	if ( $refitune_src_host && $refitune_home_host && $refitune_src_host !== $refitune_home_host ) {
		return $src;
	}

	if ( false !== strpos( $src, 'ver=' ) ) {
		return remove_query_arg( 'ver', $src );
	}

	return $src;
}
add_filter( 'style_loader_src', 'refitune_remove_asset_version_query', 9999 );
add_filter( 'script_loader_src', 'refitune_remove_asset_version_query', 9999 );
