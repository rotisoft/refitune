<?php
/**
 * Upload filename sanitization helpers.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * File extensions eligible for upload filename sanitization.
 *
 * @return array
 */
function refitune_upload_filename_allowed_extensions(): array {
	return array(
		// Images.
		'jpg',
		'jpeg',
		'png',
		'gif',
		'webp',
		'avif',
		'svg',
		'ico',
		'bmp',
		// Documents.
		'pdf',
		'doc',
		'docx',
		'xls',
		'xlsx',
		'ppt',
		'pptx',
		'odt',
		'ods',
		'odp',
		'rtf',
		'txt',
		'csv',
	);
}

/**
 * Whether a file extension is eligible for filename sanitization.
 *
 * @param string $extension Lowercase extension without dot.
 * @return bool
 */
function refitune_upload_filename_is_allowed_extension( string $extension ): bool {
	$extension = strtolower( trim( $extension ) );

	if ( '' === $extension ) {
		return false;
	}

	return in_array( $extension, refitune_upload_filename_allowed_extensions(), true );
}

/**
 * Sanitize an upload filename for images and documents.
 *
 * Example: "árvíz tűrő +33.jpg" becomes "arviz-turo-33.jpg".
 *
 * @param string $filename Original upload filename.
 * @return string Sanitized filename, or the original when not eligible.
 */
function refitune_sanitize_upload_filename( string $filename ): string {
	$filename = wp_basename( $filename );

	if ( '' === $filename ) {
		return $filename;
	}

	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( ! refitune_upload_filename_is_allowed_extension( $extension ) ) {
		return $filename;
	}

	$basename = pathinfo( $filename, PATHINFO_FILENAME );

	if ( function_exists( 'remove_accents' ) ) {
		$basename = remove_accents( $basename );
	}

	$basename = strtolower( $basename );
	$basename = preg_replace( '/[^a-z0-9]+/', '-', $basename );
	$basename = preg_replace( '/-+/', '-', (string) $basename );
	$basename = trim( (string) $basename, '-' );

	if ( '' === $basename ) {
		$basename = 'file';
	}

	return $basename . '.' . $extension;
}
