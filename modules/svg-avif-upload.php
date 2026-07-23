<?php
/**
 * SVG and AVIF upload support by user role.
 *
 * SVG uploads require content inspection before acceptance.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once REFITUNE_PATH . 'includes/svg-sanitizer.php';

/**
 * Check whether the current user belongs to one of the allowed roles.
 *
 * @param array $roles Allowed roles.
 * @return bool
 */
function refitune_user_has_upload_role( array $refitune_roles ): bool {
	if ( empty( $refitune_roles ) || ! is_user_logged_in() ) {
		return false;
	}
	$refitune_user = wp_get_current_user();
	return (bool) array_intersect( (array) $refitune_user->roles, $refitune_roles );
}

/**
 * Whether the current user may upload the given extension.
 *
 * @param string $refitune_ext File extension (svg or avif).
 * @return bool
 */
function refitune_user_can_upload_extension( string $refitune_ext ): bool {
	if ( ! refitune_network_allows_upload_extension( $refitune_ext ) ) {
		return false;
	}

	$refitune_settings = refitune_get_settings();

	if ( 'svg' === $refitune_ext ) {
		$refitune_roles = isset( $refitune_settings['svg_upload_roles'] ) ? (array) $refitune_settings['svg_upload_roles'] : array();
		return ! empty( $refitune_roles ) && refitune_user_has_upload_role( $refitune_roles );
	}

	if ( 'avif' === $refitune_ext ) {
		$refitune_roles = isset( $refitune_settings['avif_upload_roles'] ) ? (array) $refitune_settings['avif_upload_roles'] : array();
		return ! empty( $refitune_roles ) && refitune_user_has_upload_role( $refitune_roles );
	}

	return false;
}

/**
 * Sanitize an SVG file in place using the allowlist sanitizer.
 *
 * Parses each temp path at most once per request. Rejects oversized files
 * before DOM parsing.
 *
 * @param string $refitune_path Path to the uploaded temp file.
 * @return bool True when the file is safe (and was rewritten with clean markup).
 */
function refitune_svg_sanitize_file( string $refitune_path ): bool {
	static $refitune_sanitized_paths = array();

	if ( ! is_readable( $refitune_path ) ) {
		return false;
	}

	$refitune_realpath = realpath( $refitune_path );
	$refitune_cache_key = false !== $refitune_realpath ? $refitune_realpath : $refitune_path;

	if ( isset( $refitune_sanitized_paths[ $refitune_cache_key ] ) ) {
		return $refitune_sanitized_paths[ $refitune_cache_key ];
	}

	$refitune_max_bytes = (int) apply_filters( 'refitune_svg_max_bytes', 512 * 1024 );

	if ( $refitune_max_bytes > 0 ) {
		$refitune_size = filesize( $refitune_path );

		if ( false === $refitune_size || $refitune_size > $refitune_max_bytes ) {
			$refitune_sanitized_paths[ $refitune_cache_key ] = false;
			return false;
		}
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp upload file.
	$refitune_content = file_get_contents( $refitune_path );

	if ( false === $refitune_content || '' === trim( (string) $refitune_content ) ) {
		$refitune_sanitized_paths[ $refitune_cache_key ] = false;
		return false;
	}

	$refitune_clean = refitune_sanitize_svg_markup( $refitune_content );

	if ( false === $refitune_clean ) {
		$refitune_sanitized_paths[ $refitune_cache_key ] = false;
		return false;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents -- Local temp upload file.
	$refitune_written = file_put_contents( $refitune_path, $refitune_clean );
	$refitune_ok      = false !== $refitune_written;

	$refitune_sanitized_paths[ $refitune_cache_key ] = $refitune_ok;

	return $refitune_ok;
}

/**
 * Add SVG and AVIF MIME types for authorized roles only.
 *
 * @param array $mimes Allowed MIME types.
 * @return array
 */
function refitune_svg_avif_enable_mimes( array $mimes ): array {
	if ( refitune_user_can_upload_extension( 'svg' ) ) {
		$mimes['svg'] = 'image/svg+xml';
	}

	if ( refitune_user_can_upload_extension( 'avif' ) ) {
		$mimes['avif'] = 'image/avif';
	}

	return $mimes;
}
// Priority 9: run before multisite check_upload_mimes() so the network policy can filter.
add_filter( 'upload_mimes', 'refitune_svg_avif_enable_mimes', 9 );

/**
 * Allow SVG/AVIF only when the user is authorized and the file passes checks.
 *
 * Does not override a core rejection (type/ext already false).
 *
 * @param array      $data     File data.
 * @param string     $file     File path.
 * @param string     $filename File name.
 * @param array|null $mimes    Allowed MIME types.
 * @return array
 */
function refitune_svg_avif_validate_filetype( array $data, string $file, string $filename, ?array $mimes ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
	if ( false === $data['type'] && false === $data['ext'] ) {
		return $data;
	}

	$refitune_ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( ! in_array( $refitune_ext, array( 'svg', 'avif' ), true ) ) {
		return $data;
	}

	if ( ! refitune_user_can_upload_extension( $refitune_ext ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	$refitune_allowed_mimes = null !== $mimes ? $mimes : get_allowed_mime_types();
	$refitune_filetype      = wp_check_filetype( $filename, $refitune_allowed_mimes );

	if ( empty( $refitune_filetype['type'] ) || empty( $refitune_filetype['ext'] ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	if ( 'svg' === $refitune_ext && ! refitune_svg_sanitize_file( $file ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	$data['ext']             = $refitune_filetype['ext'];
	$data['type']            = $refitune_filetype['type'];
	$data['proper_filename'] = $filename;

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'refitune_svg_avif_validate_filetype', 10, 4 );

/**
 * Final SVG security check before the file is moved into uploads.
 *
 * @param array $file Uploaded file data.
 * @return array
 */
function refitune_svg_security_check( array $file ): array {
	if ( empty( $file['tmp_name'] ) || ! is_uploaded_file( $file['tmp_name'] ) ) {
		return $file;
	}

	$refitune_ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

	if ( 'svg' !== $refitune_ext ) {
		return $file;
	}

	if ( ! refitune_user_can_upload_extension( 'svg' ) ) {
		$file['error'] = __( 'You are not allowed to upload SVG files.', 'refitune' );
		return $file;
	}

	if ( ! refitune_svg_sanitize_file( $file['tmp_name'] ) ) {
		$file['error'] = __( 'This SVG file cannot be uploaded for security reasons. It may contain dangerous code.', 'refitune' );
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'refitune_svg_security_check', 10 );

/**
 * Fix SVG preview in the media library (JS response).
 *
 * @param array $response Attachment response data.
 * @return array
 */
function refitune_svg_fix_display( array $response ): array {
	if ( isset( $response['mime'] ) && 'image/svg+xml' === $response['mime'] && empty( $response['sizes'] ) ) {
		$response['sizes'] = array(
			'full' => array(
				'url' => $response['url'],
			),
		);
	}
	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'refitune_svg_fix_display', 10 );

/**
 * Fix SVG thumbnail display in the media library.
 *
 * @param array   $response   Attachment response data.
 * @param WP_Post $attachment Attachment object.
 * @param array|false $meta       Attachment meta data. WordPress may pass false when meta is missing.
 * @return array
 */
function refitune_svg_media_thumbnails( array $response, WP_Post $attachment, $meta ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
	if ( 'image/svg+xml' === $response['mime'] && empty( $response['sizes'] ) ) {
		$refitune_svg_path = get_attached_file( $attachment->ID );
		if ( $refitune_svg_path && file_exists( $refitune_svg_path ) ) {
			$response['sizes'] = array(
				'full' => array(
					'url'         => $response['url'],
					'width'       => 100,
					'height'      => 100,
					'orientation' => 'landscape',
				),
			);
		}
	}
	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'refitune_svg_media_thumbnails', 10, 3 );
