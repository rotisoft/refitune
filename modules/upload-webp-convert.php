<?php
/**
 * Convert JPEG and PNG uploads to WebP after WordPress saves the file.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once REFITUNE_PATH . 'includes/webp-converter.php';

/**
 * Convert an uploaded image to WebP when the feature is enabled.
 *
 * @param array  $upload  Associative array with file, url, and type keys.
 * @param string $context Upload context passed by WordPress.
 * @return array
 */
function refitune_upload_webp_convert( array $upload, string $context = 'upload' ): array {
	unset( $context );

	if ( empty( $upload['file'] ) || ! refitune_webp_server_supports() ) {
		return $upload;
	}

	$refitune_settings = refitune_get_settings();

	if ( empty( $refitune_settings['upload_webp_convert'] ) ) {
		return $upload;
	}

	$refitune_mime = isset( $upload['type'] ) ? (string) $upload['type'] : '';

	if ( ! refitune_webp_is_convertible_mime( $refitune_mime ) ) {
		return $upload;
	}

	// Resolve upload paths before converting so a failed URL mapping cannot
	// leave the caller with a deleted source file.
	$refitune_upload_dir = wp_upload_dir();

	if ( ! empty( $refitune_upload_dir['error'] ) ) {
		return $upload;
	}

	// Multisite: do not produce WebP when the network disallows that extension.
	if ( ! refitune_network_allows_upload_extension( 'webp' ) ) {
		return $upload;
	}

	$refitune_max_width  = isset( $refitune_settings['upload_webp_max_width'] ) ? (int) $refitune_settings['upload_webp_max_width'] : 0;
	$refitune_max_height = isset( $refitune_settings['upload_webp_max_height'] ) ? (int) $refitune_settings['upload_webp_max_height'] : 0;

	$refitune_source_path = wp_normalize_path( (string) $upload['file'] );
	$refitune_webp_path   = refitune_convert_file_to_webp( $refitune_source_path, $refitune_max_width, $refitune_max_height );

	if ( false === $refitune_webp_path ) {
		return $upload;
	}

	$refitune_webp_url = refitune_webp_path_to_url( $refitune_webp_path, $refitune_upload_dir );

	if ( '' === $refitune_webp_url ) {
		wp_delete_file( $refitune_webp_path );
		return $upload;
	}

	// Commit: only remove the original after the WebP file and URL are ready.
	if ( file_exists( $refitune_source_path ) && $refitune_source_path !== $refitune_webp_path ) {
		wp_delete_file( $refitune_source_path );
	}

	$upload['file'] = $refitune_webp_path;
	$upload['type'] = 'image/webp';
	$upload['url']  = $refitune_webp_url;

	return $upload;
}
add_filter( 'wp_handle_upload', 'refitune_upload_webp_convert', 20 );
