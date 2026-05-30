<?php
/**
 * REST API restrictions.
 *
 * Restricted endpoints require an authenticated WordPress session.
 * Anonymous REST access to sensitive routes is denied.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current REST request is allowed to access restricted endpoints.
 *
 * @return bool True when the user is logged in.
 */
function refitune_rest_restricted_endpoint_allowed(): bool {
	return is_user_logged_in();
}

$refitune_settings = get_option( 'refitune_settings', array() );

// ---------------------------------------------------------------------------
// 1. Users endpoint restriction
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_users'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( 0 === strpos( $route, '/wp/v2/users' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The users endpoint requires authentication.', 'refitune' ),
					array( 'status' => 401 )
				);
			}
			return $result;
		},
		10,
		3
	);
}

// ---------------------------------------------------------------------------
// 2. REST index restriction
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_restrict_index'] ) ) {
	add_filter(
		'rest_index',
		static function ( $response ) {
			if ( ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The REST API index requires authentication.', 'refitune' ),
					array( 'status' => 401 )
				);
			}
			return $response;
		}
	);
}

// ---------------------------------------------------------------------------
// 3. Media endpoint restriction
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_media'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( 0 === strpos( $route, '/wp/v2/media' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The media endpoint requires authentication.', 'refitune' ),
					array( 'status' => 401 )
				);
			}
			return $result;
		},
		10,
		3
	);
}

// ---------------------------------------------------------------------------
// 4. Comments endpoint restriction
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_comments'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( 0 === strpos( $route, '/wp/v2/comments' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The comments endpoint requires authentication.', 'refitune' ),
					array( 'status' => 401 )
				);
			}
			return $result;
		},
		10,
		3
	);
}

// ---------------------------------------------------------------------------
// 5. Search endpoint restriction
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_search'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$route = $request->get_route();
			if ( 0 === strpos( $route, '/wp/v2/search' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The search endpoint requires authentication.', 'refitune' ),
					array( 'status' => 401 )
				);
			}
			return $result;
		},
		10,
		3
	);
}
