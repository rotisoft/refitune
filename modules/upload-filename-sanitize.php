<?php
/**
 * Clean upload filenames for images and documents.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once REFITUNE_PATH . 'includes/upload-filename-sanitizer.php';

/**
 * Sanitize upload filenames before WordPress saves the file.
 *
 * Runs after Verified Upload (priority 5) and SVG checks (priority 10).
 *
 * @param array $file Upload file data from $_FILES.
 * @return array
 */
function refitune_upload_filename_sanitize_prefilter( array $file ): array {
	if ( empty( $file['name'] ) || ! empty( $file['error'] ) ) {
		return $file;
	}

	$file['name'] = refitune_sanitize_upload_filename( $file['name'] );

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'refitune_upload_filename_sanitize_prefilter', 15 );
