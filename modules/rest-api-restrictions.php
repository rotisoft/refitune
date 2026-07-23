<?php
/**
 * REST API restrictions.
 *
 * Restricted endpoints require an administrator-level session.
 * Anonymous and non-admin REST access to sensitive routes is denied.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current REST request is allowed to access restricted endpoints.
 *
 * @return bool True when the user has administrator capabilities.
 */
function refitune_rest_restricted_endpoint_allowed(): bool {
	return current_user_can( 'manage_options' );
}

/**
 * HTTP status for a denied restricted endpoint request.
 *
 * @return int 401 for anonymous requests, 403 for authenticated non-admins.
 */
function refitune_rest_restricted_denied_status(): int {
	return is_user_logged_in() ? 403 : 401;
}

$refitune_settings = refitune_get_settings();

// ---------------------------------------------------------------------------
// 1. Users endpoint restriction
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_settings['rest_disable_users'] ) ) {
	add_filter(
		'rest_pre_dispatch',
		static function ( $result, $server, $request ) {
			$refitune_route = $request->get_route();
			if ( 0 === strpos( $refitune_route, '/wp/v2/users' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The users endpoint requires administrator access.', 'refitune' ),
					array( 'status' => refitune_rest_restricted_denied_status() )
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
					__( 'The REST API index requires administrator access.', 'refitune' ),
					array( 'status' => refitune_rest_restricted_denied_status() )
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
			$refitune_route = $request->get_route();
			if ( 0 === strpos( $refitune_route, '/wp/v2/media' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The media endpoint requires administrator access.', 'refitune' ),
					array( 'status' => refitune_rest_restricted_denied_status() )
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
			$refitune_route = $request->get_route();
			if ( 0 === strpos( $refitune_route, '/wp/v2/comments' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The comments endpoint requires administrator access.', 'refitune' ),
					array( 'status' => refitune_rest_restricted_denied_status() )
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
			$refitune_route = $request->get_route();
			if ( 0 === strpos( $refitune_route, '/wp/v2/search' ) && ! refitune_rest_restricted_endpoint_allowed() ) {
				return new WP_Error(
					'rest_forbidden',
					__( 'The search endpoint requires administrator access.', 'refitune' ),
					array( 'status' => refitune_rest_restricted_denied_status() )
				);
			}
			return $result;
		},
		10,
		3
	);
}
