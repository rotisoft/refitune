<?php
/**
 * REST API korlátozások.
 *
 * - Users endpoint korlátozása (/wp-json/wp/v2/users)
 * - REST index korlátozása (/wp-json/)
 * - Média endpoint korlátozása (/wp-json/wp/v2/media)
 * - Kommentek endpoint korlátozása (/wp-json/wp/v2/comments)
 * - Keresés endpoint korlátozása (/wp-json/wp/v2/search)
 *
 * Logika:
 * - Bejelentkezett felhasználók: mindig hozzáférhetnek
 * - Nem bejelentkezett felhasználók: csak belső (saját domainről induló) kérések
 * - WooCommerce aktív: vendég vásárlók session cookie-val hozzáférhetnek
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ellenőrzi, hogy a kérés belső (megbízható) forrásból származik-e.
 *
 * @return bool True, ha belső kérés (biztonságos), false ha külső.
 */
function refitune_is_internal_request(): bool {
	// 1. Bejelentkezve mindig engedélyezett.
	if ( is_user_logged_in() ) {
		return true;
	}

	// 2. WooCommerce session cookie ellenőrzés vendég vásárlókhoz.
	if ( class_exists( 'WooCommerce' ) ) {
		$session_cookie = 'wp_woocommerce_session_' . COOKIEHASH;
		if ( isset( $_COOKIE[ $session_cookie ] ) || isset( $_COOKIE['woocommerce_cart_hash'] ) ) {
			return true; // Aktív WooCommerce session = belső használat.
		}
	}

	// 3. Referer és Origin header ellenőrzés.
	$site_url  = home_url();
	$site_host = wp_parse_url( $site_url, PHP_URL_HOST );

	$referer = wp_get_referer();
	$origin  = isset( $_SERVER['HTTP_ORIGIN'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_ORIGIN'] ) ) : '';

	// Referer alapú ellenőrzés.
	if ( $referer ) {
		$referer_host = wp_parse_url( $referer, PHP_URL_HOST );
		if ( $referer_host === $site_host ) {
			return true;
		}
	}

	// Origin alapú ellenőrzés (AJAX, Fetch API).
	if ( $origin ) {
		$origin_host = wp_parse_url( $origin, PHP_URL_HOST );
		if ( $origin_host === $site_host ) {
			return true;
		}
	}

	return false; // Külső kérés.
}

$refitune_settings = get_option( 'refitune_settings', array() );

// ---------------------------------------------------------------------------
// 1. Users endpoint korlátozása
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_users'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( strpos( $route, '/wp/v2/users' ) === 0 ) {
				if ( ! refitune_is_internal_request() ) {
					return new WP_Error(
						'rest_access_denied',
						__( 'A users endpoint csak belső kérésekhez érhető el.', 'refitune' ),
						array( 'status' => 401 )
					);
				}
			}
			return $result;
		},
		10,
		3
	);
}

// ---------------------------------------------------------------------------
// 2. REST index korlátozása
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_restrict_index'] ) ) {
	add_filter(
		'rest_index',
		static function ( $response ) {
			if ( ! refitune_is_internal_request() ) {
				return new WP_Error(
					'rest_access_denied',
					__( 'A REST API index csak belső kérésekhez érhető el.', 'refitune' ),
					array( 'status' => 401 )
				);
			}
			return $response;
		}
	);
}

// ---------------------------------------------------------------------------
// 3. Média endpoint korlátozása
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_media'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( strpos( $route, '/wp/v2/media' ) === 0 ) {
				if ( ! refitune_is_internal_request() ) {
					return new WP_Error(
						'rest_access_denied',
						__( 'A media endpoint csak belső kérésekhez érhető el.', 'refitune' ),
						array( 'status' => 401 )
					);
				}
			}
			return $result;
		},
		10,
		3
	);
}

// ---------------------------------------------------------------------------
// 4. Kommentek endpoint korlátozása
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_comments'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( strpos( $route, '/wp/v2/comments' ) === 0 ) {
				if ( ! refitune_is_internal_request() ) {
					return new WP_Error(
						'rest_access_denied',
						__( 'A comments endpoint csak belső kérésekhez érhető el.', 'refitune' ),
						array( 'status' => 401 )
					);
				}
			}
			return $result;
		},
		10,
		3
	);
}

// ---------------------------------------------------------------------------
// 5. Keresés endpoint korlátozása
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_search'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( strpos( $route, '/wp/v2/search' ) === 0 ) {
				if ( ! refitune_is_internal_request() ) {
					return new WP_Error(
						'rest_access_denied',
						__( 'A search endpoint csak belső kérésekhez érhető el.', 'refitune' ),
						array( 'status' => 401 )
					);
				}
			}
			return $result;
		},
		10,
		3
	);
}
