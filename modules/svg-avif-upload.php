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
function refitune_user_has_upload_role( array $roles ): bool {
	if ( empty( $roles ) || ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	return (bool) array_intersect( (array) $user->roles, $roles );
}

/**
 * Whether the current user may upload the given extension.
 *
 * @param string $ext File extension (svg or avif).
 * @return bool
 */
function refitune_user_can_upload_extension( string $ext ): bool {
	$settings = get_option( 'refitune_settings', array() );

	if ( 'svg' === $ext ) {
		$roles = isset( $settings['svg_upload_roles'] ) ? (array) $settings['svg_upload_roles'] : array();
		return ! empty( $roles ) && refitune_user_has_upload_role( $roles );
	}

	if ( 'avif' === $ext ) {
		$roles = isset( $settings['avif_upload_roles'] ) ? (array) $settings['avif_upload_roles'] : array();
		return ! empty( $roles ) && refitune_user_has_upload_role( $roles );
	}

	return false;
}

/**
 * Sanitize an SVG file in place using the allowlist sanitizer.
 *
 * @param string $path Path to the uploaded temp file.
 * @return bool True when the file is safe (and was rewritten with clean markup).
 */
function refitune_svg_sanitize_file( string $path ): bool {
	if ( ! is_readable( $path ) || ! is_writable( $path ) ) {
		return false;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local temp upload file.
	$content = file_get_contents( $path );

	if ( false === $content || '' === trim( (string) $content ) ) {
		return false;
	}

	$clean = refitune_sanitize_svg_markup( $content );

	if ( false === $clean ) {
		return false;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_put_contents_file_put_contents -- Local temp upload file.
	$written = file_put_contents( $path, $clean );

	return false !== $written;
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
add_filter( 'upload_mimes', 'refitune_svg_avif_enable_mimes' );

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

	$ext = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );

	if ( ! in_array( $ext, array( 'svg', 'avif' ), true ) ) {
		return $data;
	}

	if ( ! refitune_user_can_upload_extension( $ext ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	$allowed_mimes = null !== $mimes ? $mimes : get_allowed_mime_types();
	$filetype      = wp_check_filetype( $filename, $allowed_mimes );

	if ( empty( $filetype['type'] ) || empty( $filetype['ext'] ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	if ( 'svg' === $ext && ! refitune_svg_sanitize_file( $file ) ) {
		return array(
			'ext'             => false,
			'type'            => false,
			'proper_filename' => false,
		);
	}

	$data['ext']             = $filetype['ext'];
	$data['type']            = $filetype['type'];
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

	$ext = strtolower( pathinfo( $file['name'], PATHINFO_EXTENSION ) );

	if ( 'svg' !== $ext ) {
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
add_filter( 'wp_handle_upload_prefilter', 'refitune_svg_security_check' );

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
add_filter( 'wp_prepare_attachment_for_js', 'refitune_svg_fix_display' );

/**
 * Fix SVG thumbnail display in the media library.
 *
 * @param array   $response   Attachment response data.
 * @param WP_Post $attachment Attachment object.
 * @param array   $meta       Attachment meta data.
 * @return array
 */
function refitune_svg_media_thumbnails( array $response, WP_Post $attachment, array $meta ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
	if ( 'image/svg+xml' === $response['mime'] && empty( $response['sizes'] ) ) {
		$svg_path = get_attached_file( $attachment->ID );
		if ( $svg_path && file_exists( $svg_path ) ) {
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
