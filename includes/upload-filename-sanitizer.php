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
 * @param string $refitune_extension Lowercase extension without dot.
 * @return bool
 */
function refitune_upload_filename_is_allowed_extension( string $refitune_extension ): bool {
	$refitune_extension = strtolower( trim( $refitune_extension ) );

	if ( '' === $refitune_extension ) {
		return false;
	}

	return in_array( $refitune_extension, refitune_upload_filename_allowed_extensions(), true );
}

/**
 * Sanitize an upload filename for images and documents.
 *
 * Example: "árvíz tűrő +33.jpg" becomes "arviz-turo-33.jpg".
 *
 * @param string $refitune_filename Original upload filename.
 * @return string Sanitized filename, or the original when not eligible.
 */
function refitune_sanitize_upload_filename( string $refitune_filename ): string {
	$refitune_filename = wp_basename( $refitune_filename );

	if ( '' === $refitune_filename ) {
		return $refitune_filename;
	}

	$refitune_extension = strtolower( pathinfo( $refitune_filename, PATHINFO_EXTENSION ) );

	if ( ! refitune_upload_filename_is_allowed_extension( $refitune_extension ) ) {
		return $refitune_filename;
	}

	$refitune_basename = pathinfo( $refitune_filename, PATHINFO_FILENAME );

	if ( function_exists( 'remove_accents' ) ) {
		$refitune_basename = remove_accents( $refitune_basename );
	}

	$refitune_basename = strtolower( $refitune_basename );
	$refitune_basename = preg_replace( '/[^a-z0-9]+/', '-', $refitune_basename );
	$refitune_basename = preg_replace( '/-+/', '-', (string) $refitune_basename );
	$refitune_basename = trim( (string) $refitune_basename, '-' );

	if ( '' === $refitune_basename ) {
		$refitune_basename = 'file';
	}

	return $refitune_basename . '.' . $refitune_extension;
}
