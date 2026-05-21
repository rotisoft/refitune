<?php
/**
 * SVG és AVIF fájlok feltöltésének engedélyezése szerepkör alapján.
 *
 * SVG esetén biztonsági ellenőrzést is végez a feltöltés előtt.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ellenőrzi, hogy az aktuális felhasználó a megadott szerepkörök egyikébe tartozik-e.
 *
 * @param array $roles Engedélyezett szerepkörök.
 * @return bool
 */
function wprefi_user_has_upload_role( array $roles ): bool {
	if ( empty( $roles ) || ! is_user_logged_in() ) {
		return false;
	}
	$user = wp_get_current_user();
	return (bool) array_intersect( (array) $user->roles, $roles );
}

/**
 * SVG és AVIF MIME típusok hozzáadása az engedélyezett listához.
 *
 * @param array $mimes Engedélyezett MIME típusok.
 * @return array
 */
function wprefi_svg_avif_enable_mimes( array $mimes ): array {
	$settings   = get_option( 'wprefi_settings', array() );
	$svg_roles  = isset( $settings['svg_upload_roles'] )  ? (array) $settings['svg_upload_roles']  : array();
	$avif_roles = isset( $settings['avif_upload_roles'] ) ? (array) $settings['avif_upload_roles'] : array();

	if ( ! empty( $svg_roles ) && wprefi_user_has_upload_role( $svg_roles ) ) {
		$mimes['svg']  = 'image/svg+xml';
		$mimes['svgz'] = 'image/svg+xml';
	}

	if ( ! empty( $avif_roles ) && wprefi_user_has_upload_role( $avif_roles ) ) {
		$mimes['avif'] = 'image/avif';
	}

	return $mimes;
}
add_filter( 'upload_mimes', 'wprefi_svg_avif_enable_mimes' );

/**
 * MIME type ellenőrzés javítása SVG és AVIF fájlokhoz.
 *
 * @param array       $data     Fájl adatok.
 * @param string      $file     Fájl elérési út.
 * @param string      $filename Fájlnév.
 * @param array|null  $mimes    Engedélyezett MIME típusok (lehet null).
 * @return array
 */
function wprefi_svg_avif_fix_mime_type( array $data, string $file, string $filename, ?array $mimes ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
	$ext = isset( $data['ext'] ) ? $data['ext'] : '';

	if ( strlen( $ext ) < 1 ) {
		$exploded = explode( '.', $filename );
		$ext      = strtolower( end( $exploded ) );
	}

	if ( 'svg' === $ext || 'svgz' === $ext ) {
		$data['type']            = 'image/svg+xml';
		$data['ext']             = $ext;
		$data['proper_filename'] = $filename;
	}

	if ( 'avif' === $ext ) {
		$data['type']            = 'image/avif';
		$data['ext']             = 'avif';
		$data['proper_filename'] = $filename;
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'wprefi_svg_avif_fix_mime_type', 10, 4 );

/**
 * WordPress beépített real MIME ellenőrzés kikapcsolása SVG és AVIF fájloknál.
 *
 * @param array        $data      Fájl adatok.
 * @param string       $file      Fájl elérési út.
 * @param string       $filename  Fájlnév.
 * @param array|null   $mimes     Engedélyezett MIME típusok (lehet null).
 * @param string|null  $real_mime Valódi MIME típus (lehet null).
 * @return array
 */
function wprefi_svg_avif_disable_real_mime_check( array $data, string $file, string $filename, ?array $mimes, ?string $real_mime ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
	if ( empty( $data['ext'] ) ) {
		return $data;
	}

	$settings = get_option( 'wprefi_settings', array() );

	if ( 'svg' === $data['ext'] || 'svgz' === $data['ext'] ) {
		$svg_roles = isset( $settings['svg_upload_roles'] ) ? (array) $settings['svg_upload_roles'] : array();
		if ( ! empty( $svg_roles ) && wprefi_user_has_upload_role( $svg_roles ) ) {
			$data['type']            = 'image/svg+xml';
			$data['proper_filename'] = $filename;
		}
	}

	if ( 'avif' === $data['ext'] ) {
		$avif_roles = isset( $settings['avif_upload_roles'] ) ? (array) $settings['avif_upload_roles'] : array();
		if ( ! empty( $avif_roles ) && wprefi_user_has_upload_role( $avif_roles ) ) {
			$data['type']            = 'image/avif';
			$data['proper_filename'] = $filename;
		}
	}

	return $data;
}
add_filter( 'wp_check_filetype_and_ext', 'wprefi_svg_avif_disable_real_mime_check', 99, 5 );

/**
 * SVG biztonsági ellenőrzés feltöltés előtt.
 *
 * @param array $file Feltöltött fájl adatai.
 * @return array Fájl adatok, esetleg hibaüzenettel.
 */
function wprefi_svg_security_check( array $file ): array {
	$settings  = get_option( 'wprefi_settings', array() );
	$svg_roles = isset( $settings['svg_upload_roles'] ) ? (array) $settings['svg_upload_roles'] : array();

	if ( empty( $svg_roles ) || ! wprefi_user_has_upload_role( $svg_roles ) ) {
		return $file;
	}

	if ( ! isset( $file['type'] ) || 'image/svg+xml' !== $file['type'] ) {
		return $file;
	}

	if ( ! isset( $file['tmp_name'] ) || ! file_exists( $file['tmp_name'] ) ) {
		return $file;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Helyi temp fájl olvasása.
	$file_content = file_get_contents( $file['tmp_name'] );

	$dangerous_patterns = array(
		'/<script[^>]*>/i',
		'/<iframe[^>]*>/i',
		'/<embed[^>]*>/i',
		'/<object[^>]*>/i',
		'/\s(onclick|onload|onmouseover|onmouseout|onerror|onkeypress|onkeydown|onkeyup)\s*=/i',
		'/href\s*=\s*["\']?\s*javascript:/i',
		'/src\s*=\s*["\']?\s*javascript:/i',
	);

	foreach ( $dangerous_patterns as $pattern ) {
		if ( preg_match( $pattern, $file_content ) ) {
			$file['error'] = __( 'Biztonsági okokból ez az SVG fájl nem tölthető fel. Potenciálisan veszélyes kódot tartalmaz.', 'refinerpress' );
			return $file;
		}
	}

	return $file;
}
add_filter( 'wp_handle_upload_prefilter', 'wprefi_svg_security_check' );

/**
 * SVG előnézet javítása a médiatárban (JS válasz).
 *
 * @param array $response Attachment válasz adatok.
 * @return array
 */
function wprefi_svg_fix_display( array $response ): array {
	if ( isset( $response['mime'] ) && 'image/svg+xml' === $response['mime'] && empty( $response['sizes'] ) ) {
		$response['sizes'] = array(
			'full' => array(
				'url' => $response['url'],
			),
		);
	}
	return $response;
}
add_filter( 'wp_prepare_attachment_for_js', 'wprefi_svg_fix_display' );

/**
 * SVG thumbnail megjelenítés javítása a médiatárban.
 *
 * @param array   $response   Attachment válasz adatok.
 * @param WP_Post $attachment Attachment objektum.
 * @param array   $meta       Attachment meta adatok.
 * @return array
 */
function wprefi_svg_media_thumbnails( array $response, WP_Post $attachment, array $meta ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Required by filter signature.
	if ( 'image/svg+xml' === $response['mime'] && empty( $response['sizes'] ) ) {
		$svg_path = get_attached_file( $attachment->ID );
		if ( file_exists( $svg_path ) ) {
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
add_filter( 'wp_prepare_attachment_for_js', 'wprefi_svg_media_thumbnails', 10, 3 );
