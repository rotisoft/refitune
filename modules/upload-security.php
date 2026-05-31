<?php
/**
 * Upload security - block disguised or dangerous file uploads.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once REFITUNE_PATH . 'includes/upload-validator.php';

/**
 * Validate an upload before WordPress moves it into uploads.
 *
 * @param array $file Upload file data from $_FILES.
 * @return array
 */
function refitune_upload_security_prefilter( array $file ): array {
	if ( empty( $file['tmp_name'] ) || empty( $file['name'] ) ) {
		return $file;
	}

	if ( ! empty( $file['error'] ) ) {
		return $file;
	}

	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return $file;
	}

	$result = refitune_upload_validate_file( $file['tmp_name'], $file['name'] );

	if ( is_wp_error( $result ) ) {
		$file['error'] = $result->get_error_message();
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'refitune_upload_security_prefilter', 5 );
