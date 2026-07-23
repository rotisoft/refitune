<?php
/**
 * WebP upload conversion helpers.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the server can write WebP images via WordPress image editors.
 *
 * @return bool
 */
function refitune_webp_server_supports(): bool {
	return (bool) wp_image_editor_supports(
		array(
			'mime_type' => 'image/webp',
		)
	);
}

/**
 * MIME types eligible for upload-time WebP conversion.
 *
 * @param string $refitune_mime_type Detected MIME type.
 * @return bool
 */
function refitune_webp_is_convertible_mime( string $refitune_mime_type ): bool {
	return in_array( strtolower( $refitune_mime_type ), array( 'image/jpeg', 'image/jpg', 'image/png' ), true );
}

/**
 * Calculate target dimensions without upscaling.
 *
 * @param int $refitune_width      Current image width.
 * @param int $refitune_height     Current image height.
 * @param int $refitune_max_width  Maximum width (0 = no limit).
 * @param int $refitune_max_height Maximum height (0 = no limit).
 * @return array{width:int,height:int} Target dimensions.
 */
function refitune_webp_calculate_resize_dimensions( int $refitune_width, int $refitune_height, int $refitune_max_width, int $refitune_max_height ): array {
	if ( $refitune_width <= 0 || $refitune_height <= 0 ) {
		return array(
			'width'  => max( 1, $refitune_width ),
			'height' => max( 1, $refitune_height ),
		);
	}

	if ( $refitune_max_width <= 0 && $refitune_max_height <= 0 ) {
		return array(
			'width'  => $refitune_width,
			'height' => $refitune_height,
		);
	}

	list( $refitune_new_width, $refitune_new_height ) = wp_constrain_dimensions( $refitune_width, $refitune_height, $refitune_max_width, $refitune_max_height );

	return array(
		'width'  => max( 1, (int) $refitune_new_width ),
		'height' => max( 1, (int) $refitune_new_height ),
	);
}

/**
 * Build a unique absolute .webp destination path for a source file.
 *
 * Uses wp_unique_filename() so an existing photo.webp is never overwritten
 * when converting photo.jpg / photo.png.
 *
 * @param string $refitune_file_path Absolute path to the source file.
 * @return string|false Absolute destination path, or false on failure.
 */
function refitune_webp_unique_destination( string $refitune_file_path ) {
	$refitune_dir = dirname( $refitune_file_path );

	if ( '' === $refitune_dir || ! is_dir( $refitune_dir ) ) {
		return false;
	}

	$refitune_basename = pathinfo( $refitune_file_path, PATHINFO_FILENAME ) . '.webp';
	$refitune_unique   = wp_unique_filename( $refitune_dir, $refitune_basename );

	if ( '' === $refitune_unique ) {
		return false;
	}

	return trailingslashit( $refitune_dir ) . $refitune_unique;
}

/**
 * Convert a filesystem path under the uploads basedir into a public URL.
 *
 * @param string $refitune_file_path Absolute file path.
 * @param array  $refitune_upload_dir Result of wp_upload_dir().
 * @return string Empty string when the path is outside the uploads directory.
 */
function refitune_webp_path_to_url( string $refitune_file_path, array $refitune_upload_dir ): string {
	if ( empty( $refitune_upload_dir['basedir'] ) || empty( $refitune_upload_dir['baseurl'] ) ) {
		return '';
	}

	$refitune_basedir = wp_normalize_path( (string) $refitune_upload_dir['basedir'] );
	$refitune_path    = wp_normalize_path( $refitune_file_path );
	$refitune_prefix  = trailingslashit( $refitune_basedir );

	if ( 0 !== strpos( $refitune_path, $refitune_prefix ) && $refitune_path !== $refitune_basedir ) {
		return '';
	}

	$refitune_relative = ltrim( substr( $refitune_path, strlen( $refitune_basedir ) ), '/' );

	return trailingslashit( (string) $refitune_upload_dir['baseurl'] ) . str_replace( '\\', '/', $refitune_relative );
}

/**
 * Convert a JPEG or PNG file to WebP, optionally resizing first.
 *
 * Does not delete the source file. The caller must delete the source only after
 * the WebP path and public URL are validated.
 *
 * @param string $refitune_file_path  Absolute path to the uploaded source file.
 * @param int    $refitune_max_width  Maximum width in pixels (0 = no limit).
 * @param int    $refitune_max_height Maximum height in pixels (0 = no limit).
 * @return string|false Absolute path to the WebP file on success, false on failure.
 */
function refitune_convert_file_to_webp( string $refitune_file_path, int $refitune_max_width = 0, int $refitune_max_height = 0 ) {
	if ( ! refitune_webp_server_supports() || ! is_readable( $refitune_file_path ) ) {
		return false;
	}

	$refitune_filetype = wp_check_filetype( $refitune_file_path );
	$refitune_mime     = isset( $refitune_filetype['type'] ) ? (string) $refitune_filetype['type'] : '';

	if ( ! refitune_webp_is_convertible_mime( $refitune_mime ) ) {
		return false;
	}

	$refitune_destination = refitune_webp_unique_destination( $refitune_file_path );

	if ( false === $refitune_destination ) {
		return false;
	}

	// Refuse to overwrite an existing file even if unique-name generation fails.
	if ( file_exists( $refitune_destination ) ) {
		return false;
	}

	$refitune_editor = wp_get_image_editor( $refitune_file_path );

	if ( is_wp_error( $refitune_editor ) ) {
		return false;
	}

	// Match WordPress attachment flow: normalize EXIF orientation before resize/save.
	$refitune_rotated = $refitune_editor->maybe_exif_rotate();

	if ( is_wp_error( $refitune_rotated ) ) {
		return false;
	}

	$refitune_size = $refitune_editor->get_size();

	if ( is_wp_error( $refitune_size ) || empty( $refitune_size['width'] ) || empty( $refitune_size['height'] ) ) {
		return false;
	}

	$refitune_target = refitune_webp_calculate_resize_dimensions(
		(int) $refitune_size['width'],
		(int) $refitune_size['height'],
		$refitune_max_width,
		$refitune_max_height
	);

	if ( $refitune_target['width'] < (int) $refitune_size['width'] || $refitune_target['height'] < (int) $refitune_size['height'] ) {
		$refitune_resized = $refitune_editor->resize( $refitune_target['width'], $refitune_target['height'], false );

		if ( is_wp_error( $refitune_resized ) ) {
			return false;
		}
	}

	$refitune_quality = (int) apply_filters( 'refitune_upload_webp_quality', 82 );
	$refitune_quality = max( 1, min( 100, $refitune_quality ) );
	$refitune_editor->set_quality( $refitune_quality );

	$refitune_saved = $refitune_editor->save( $refitune_destination, 'image/webp' );

	if ( is_wp_error( $refitune_saved ) || empty( $refitune_saved['path'] ) || ! is_readable( $refitune_saved['path'] ) ) {
		return false;
	}

	$refitune_webp_path = wp_normalize_path( (string) $refitune_saved['path'] );
	$refitune_source    = wp_normalize_path( $refitune_file_path );

	// Never treat the source path as a successful WebP result.
	if ( $refitune_source === $refitune_webp_path ) {
		return false;
	}

	if ( empty( $refitune_saved['mime-type'] ) || 'image/webp' !== $refitune_saved['mime-type'] ) {
		wp_delete_file( $refitune_webp_path );
		return false;
	}

	return $refitune_webp_path;
}
