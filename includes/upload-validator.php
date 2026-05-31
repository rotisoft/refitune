<?php
/**
 * Upload file validation helpers for disguised or dangerous uploads.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Extensions that must never appear in an uploaded filename.
 *
 * @return array
 */
function refitune_upload_dangerous_extensions(): array {
	return array(
		'php',
		'php3',
		'php4',
		'php5',
		'php7',
		'php8',
		'phtml',
		'phar',
		'pht',
		'phps',
		'cgi',
		'pl',
		'py',
		'asp',
		'aspx',
		'jsp',
		'sh',
		'exe',
		'dll',
		'htaccess',
		'suspected',
	);
}

/**
 * Image file extensions checked against MIME and magic bytes.
 *
 * @return array
 */
function refitune_upload_image_extensions(): array {
	return array(
		'jpg',
		'jpeg',
		'png',
		'gif',
		'webp',
		'avif',
		'svg',
		'ico',
		'bmp',
	);
}

/**
 * Maximum bytes scanned for script signatures in Standard mode.
 */
define( 'REFITUNE_UPLOAD_SCAN_MAX_BYTES', 262144 );

/**
 * Validate an upload filename for dangerous or double extensions.
 *
 * @param string $filename Original upload filename.
 * @return true|WP_Error
 */
function refitune_upload_validate_filename( string $filename ) {
	if ( '' === trim( $filename ) ) {
		return refitune_upload_reject(
			'empty_filename',
			__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
			__( 'Upload rejected: empty filename.', 'refitune' )
		);
	}

	if ( preg_match( '/[\x00-\x1f\x7f]/', $filename ) ) {
		return refitune_upload_reject(
			'invalid_filename_chars',
			__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
			__( 'Upload rejected: invalid control characters in filename.', 'refitune' )
		);
	}

	$basename = wp_basename( $filename );
	$parts    = explode( '.', strtolower( $basename ) );

	if ( count( $parts ) < 2 ) {
		return true;
	}

	$dangerous = refitune_upload_dangerous_extensions();

	foreach ( $parts as $index => $part ) {
		if ( 0 === $index ) {
			continue;
		}

		if ( in_array( $part, $dangerous, true ) ) {
			return refitune_upload_reject(
				'dangerous_extension',
				__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
				__( 'Upload rejected: dangerous file extension in filename.', 'refitune' )
			);
		}
	}

	// Catch disguised patterns such as "shell.php.jpg" embedded in the name.
	if ( preg_match( '/\.(' . implode( '|', array_map( 'preg_quote', $dangerous ) ) . ')(\.|$)/i', $basename ) ) {
		return refitune_upload_reject(
			'double_extension',
			__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
			__( 'Upload rejected: double or disguised extension detected.', 'refitune' )
		);
	}

	return true;
}

/**
 * Read the beginning of a file for magic-byte inspection.
 *
 * @param string $file_path Absolute path to temp file.
 * @param int    $length    Bytes to read.
 * @return string
 */
function refitune_upload_read_file_header( string $file_path, int $length = 512 ): string {
	if ( ! is_readable( $file_path ) ) {
		return '';
	}

	$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local temp upload file.

	if ( false === $handle ) {
		return '';
	}

	$header = fread( $handle, $length ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Local temp upload file.
	fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Local temp upload file.

	return false === $header ? '' : $header;
}

/**
 * Detect a coarse file type from magic bytes.
 *
 * @param string $header File header bytes.
 * @return string One of: jpeg, png, gif, webp, pdf, svg, avif, zip, text, binary.
 */
function refitune_upload_detect_magic_type( string $header ): string {
	if ( '' === $header ) {
		return 'binary';
	}

	if ( 0 === strpos( $header, "\xFF\xD8\xFF" ) ) {
		return 'jpeg';
	}

	if ( 0 === strpos( $header, "\x89PNG\r\n\x1a\n" ) ) {
		return 'png';
	}

	if ( 0 === strncmp( $header, 'GIF87a', 6 ) || 0 === strncmp( $header, 'GIF89a', 6 ) ) {
		return 'gif';
	}

	if ( 0 === strncmp( $header, '%PDF', 4 ) ) {
		return 'pdf';
	}

	if ( 0 === strncmp( $header, 'RIFF', 4 ) && false !== strpos( substr( $header, 0, 16 ), 'WEBP' ) ) {
		return 'webp';
	}

	if ( false !== strpos( substr( $header, 0, 4096 ), 'ftypavif' ) || false !== strpos( substr( $header, 0, 64 ), 'ftypavis' ) ) {
		return 'avif';
	}

	$trimmed = ltrim( $header );

	if ( 0 === strncmp( $trimmed, '<svg', 4 ) || 0 === strncmp( $trimmed, '<?xml', 5 ) ) {
		return 'svg';
	}

	if ( 0 === strncmp( $header, 'PK', 2 ) ) {
		return 'zip';
	}

	$sample = substr( $header, 0, 512 );

	if ( false !== strpbrk( $sample, "\0" ) ) {
		return 'binary';
	}

	if ( preg_match( '/[\x09\x0a\x0d\x20-\x7e]{8,}/', $sample ) ) {
		return 'text';
	}

	return 'binary';
}

/**
 * Map a file extension to an expected magic type.
 *
 * @param string $extension Lowercase extension without dot.
 * @return string Expected magic type or empty string when not mapped.
 */
function refitune_upload_expected_magic_for_extension( string $extension ): string {
	$map = array(
		'jpg'  => 'jpeg',
		'jpeg' => 'jpeg',
		'png'  => 'png',
		'gif'  => 'gif',
		'webp' => 'webp',
		'avif' => 'avif',
		'svg'  => 'svg',
		'pdf'  => 'pdf',
	);

	return $map[ $extension ] ?? '';
}

/**
 * Detect a file MIME type with PHP Fileinfo when available.
 *
 * Compatible with PHP 7.4 through 8.5+: finfo_close() is only called below PHP 8.5,
 * where manual cleanup is still expected.
 *
 * @param string $file_path Absolute path to temp file.
 * @return string Detected MIME type, or empty string when unavailable.
 */
function refitune_upload_detect_mime_type( string $file_path ): string {
	if ( ! function_exists( 'finfo_open' ) || ! function_exists( 'finfo_file' ) ) {
		return '';
	}

	$finfo = finfo_open( FILEINFO_MIME_TYPE ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.finfo_open_optionsFound -- Standard MIME detection.

	if ( false === $finfo ) {
		return '';
	}

	$detected = (string) finfo_file( $finfo, $file_path ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.finfo_file_optionsFound -- Standard MIME detection.

	if ( PHP_VERSION_ID < 80500 ) {
		finfo_close( $finfo ); // phpcs:ignore PHPCompatibility.FunctionUse.NewFunctionParameters.finfo_close_optionsFound -- Required on PHP 7.4-8.4.
	}

	return $detected;
}

/**
 * Whether a finfo MIME type looks like an image.
 *
 * @param string $mime Detected MIME type.
 * @return bool
 */
function refitune_upload_mime_is_image( string $mime ): bool {
	return 0 === strpos( $mime, 'image/' );
}

/**
 * Validate MIME type and magic bytes against the declared extension.
 *
 * @param string $file_path Absolute path to temp file.
 * @param string $filename  Original filename.
 * @return true|WP_Error
 */
function refitune_upload_validate_mime_and_magic( string $file_path, string $filename ) {
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( '' === $extension ) {
		return true;
	}

	$header      = refitune_upload_read_file_header( $file_path, 512 );
	$magic_type  = refitune_upload_detect_magic_type( $header );
	$expected    = refitune_upload_expected_magic_for_extension( $extension );
	$detected    = refitune_upload_detect_mime_type( $file_path );
	$image_types = refitune_upload_image_extensions();

	if ( in_array( $extension, $image_types, true ) ) {
		if ( '' !== $detected && ! refitune_upload_mime_is_image( $detected ) && 'image/svg+xml' !== $detected ) {
			return refitune_upload_reject(
				'mime_mismatch',
				__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
				__( 'Upload rejected: file content does not match the image extension.', 'refitune' )
			);
		}

		if ( '' !== $expected && '' !== $magic_type && $expected !== $magic_type ) {
			// Allow svg extension with xml header and avif variants detected as binary/zip-like containers.
			$allowed_mismatch = ( 'svg' === $expected && 'svg' === $magic_type )
				|| ( 'avif' === $expected && in_array( $magic_type, array( 'avif', 'binary' ), true ) );

			if ( ! $allowed_mismatch ) {
				return refitune_upload_reject(
					'magic_mismatch',
					__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
					__( 'Upload rejected: file signature does not match the image extension.', 'refitune' )
				);
			}
		}

		if ( 'text' === $magic_type ) {
			return refitune_upload_reject(
				'text_disguised_as_image',
				__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
				__( 'Upload rejected: text content disguised as an image file.', 'refitune' )
			);
		}
	}

	if ( 'pdf' === $extension && 'pdf' !== $magic_type ) {
		return refitune_upload_reject(
			'pdf_magic_mismatch',
			__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
			__( 'Upload rejected: file signature does not match the PDF extension.', 'refitune' )
		);
	}

	if ( '' !== $detected ) {
		$executable_mimes = array(
			'application/x-httpd-php',
			'application/x-php',
			'application/x-phps',
			'text/x-php',
			'text/html',
			'application/x-sh',
			'application/x-msdownload',
		);

		foreach ( $executable_mimes as $executable_mime ) {
			if ( 0 === strpos( $detected, $executable_mime ) ) {
				return refitune_upload_reject(
					'executable_mime',
					__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
					__( 'Upload rejected: executable MIME type detected.', 'refitune' )
				);
			}
		}
	}

	return true;
}

/**
 * Dangerous content signatures to scan for in uploads.
 *
 * @return array
 */
function refitune_upload_script_signatures(): array {
	return array(
		'<?php',
		'<?=',
		'<?',
		'<script',
		'javascript:',
		'eval(',
		'base64_decode(',
		'shell_exec',
		'system(',
		'passthru(',
	);
}

/**
 * Scan upload content for embedded script markers.
 *
 * @param string $file_path Absolute path to temp file.
 * @param string $filename  Original filename.
 * @return true|WP_Error
 */
function refitune_upload_scan_for_scripts( string $file_path, string $filename ) {
	$extension = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	// SVG script handling is delegated to the SVG sanitizer module.
	if ( 'svg' === $extension ) {
		return true;
	}

	if ( ! is_readable( $file_path ) ) {
		return refitune_upload_reject(
			'unreadable_file',
			__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
			__( 'Upload rejected: uploaded file is not readable.', 'refitune' )
		);
	}

	$file_size = filesize( $file_path );

	if ( false === $file_size ) {
		return true;
	}

	$chunks = array();

	if ( $file_size <= REFITUNE_UPLOAD_SCAN_MAX_BYTES ) {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp upload file.
		$content = file_get_contents( $file_path );
		if ( false !== $content && '' !== $content ) {
			$chunks[] = $content;
		}
	} else {
		$chunks[] = refitune_upload_read_file_header( $file_path, (int) ( REFITUNE_UPLOAD_SCAN_MAX_BYTES / 2 ) );

		$handle = fopen( $file_path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- Local temp upload file.
		if ( false !== $handle ) {
			$tail_length = (int) ( REFITUNE_UPLOAD_SCAN_MAX_BYTES / 2 );
			if ( fseek( $handle, -1 * $tail_length, SEEK_END ) === 0 ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fseek -- Local temp upload file.
				$tail = fread( $handle, $tail_length ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread -- Local temp upload file.
				if ( false !== $tail && '' !== $tail ) {
					$chunks[] = $tail;
				}
			}
			fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- Local temp upload file.
		}
	}

	foreach ( $chunks as $chunk ) {
		$normalized = strtolower( $chunk );

		foreach ( refitune_upload_script_signatures() as $signature ) {
			if ( false !== strpos( $normalized, strtolower( $signature ) ) ) {
				return refitune_upload_reject(
					'script_marker',
					__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
					__( 'Upload rejected: dangerous script marker found in file content.', 'refitune' )
				);
			}
		}
	}

	return true;
}

/**
 * Run all Standard upload security checks.
 *
 * @param string $file_path Absolute path to temp upload file.
 * @param string $filename  Original upload filename.
 * @return true|WP_Error
 */
function refitune_upload_validate_file( string $file_path, string $filename ) {
	if ( ! is_uploaded_file( $file_path ) && ! is_readable( $file_path ) ) {
		return refitune_upload_reject(
			'invalid_path',
			__( 'This file cannot be uploaded for security reasons.', 'refitune' ),
			__( 'Upload rejected: invalid upload path.', 'refitune' )
		);
	}

	$filename_result = refitune_upload_validate_filename( $filename );
	if ( is_wp_error( $filename_result ) ) {
		return $filename_result;
	}

	$mime_result = refitune_upload_validate_mime_and_magic( $file_path, $filename );
	if ( is_wp_error( $mime_result ) ) {
		return $mime_result;
	}

	$script_result = refitune_upload_scan_for_scripts( $file_path, $filename );
	if ( is_wp_error( $script_result ) ) {
		return $script_result;
	}

	return true;
}

/**
 * Build a user-facing upload rejection error.
 *
 * @param string $code           Error code.
 * @param string $public_message Message shown to the user.
 * @param string $debug_message  Detailed message for logs when WP_DEBUG is enabled.
 * @return WP_Error
 */
function refitune_upload_reject( string $code, string $public_message, string $debug_message ): WP_Error {
	if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only rejection detail.
		error_log( 'RefiTune Verified Upload: ' . $debug_message );
	}

	return new WP_Error( $code, $public_message );
}
