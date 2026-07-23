<?php
/**
 * Role-based redirects after login and logout.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_redirect_settings = refitune_get_settings();

// ---------------------------------------------------------------------------
// Helper functions
// ---------------------------------------------------------------------------

/**
 * Validate a redirect URL against this site's host.
 *
 * @param string $refitune_url Redirect URL.
 * @return string Safe internal URL.
 */
function refitune_validate_internal_redirect_url( string $refitune_url ): string {
	$refitune_url = wp_validate_redirect( $refitune_url, home_url( '/' ) );

	$refitune_home_host   = wp_parse_url( home_url(), PHP_URL_HOST );
	$refitune_target_host = wp_parse_url( $refitune_url, PHP_URL_HOST );

	if ( empty( $refitune_target_host ) || (string) $refitune_target_host === (string) $refitune_home_host ) {
		return $refitune_url;
	}

	return home_url( '/' );
}

/**
 * Get the login redirect URL based on the user's role.
 *
 * @param WP_User $user                       The logged-in user.
 * @param array   $refitune_login_redirects Login redirect settings.
 * @return string|null The redirect URL or null when not configured.
 */
function refitune_get_login_redirect_url( $user, $refitune_login_redirects ) {
	if ( ! isset( $user->ID ) || ! $user instanceof WP_User ) {
		return null;
	}

	foreach ( (array) $user->roles as $refitune_role ) {
		if ( isset( $refitune_login_redirects[ $refitune_role ] ) && '' !== trim( $refitune_login_redirects[ $refitune_role ] ) ) {
			return refitune_validate_internal_redirect_url( $refitune_login_redirects[ $refitune_role ] );
		}
	}

	return null;
}

/**
 * Get the logout redirect URL based on the user's role.
 *
 * @param WP_User $user                        The user before logout.
 * @param array   $refitune_logout_redirects Logout redirect settings.
 * @return string|null The redirect URL or null when not configured.
 */
function refitune_get_logout_redirect_url( $user, $refitune_logout_redirects ) {
	if ( ! isset( $user->ID ) || ! $user instanceof WP_User ) {
		return null;
	}

	foreach ( (array) $user->roles as $refitune_role ) {
		if ( isset( $refitune_logout_redirects[ $refitune_role ] ) && '' !== trim( $refitune_logout_redirects[ $refitune_role ] ) ) {
			return refitune_validate_internal_redirect_url( $refitune_logout_redirects[ $refitune_role ] );
		}
	}

	return null;
}

// ---------------------------------------------------------------------------
// Login redirects
// ---------------------------------------------------------------------------

// WordPress login redirect.
add_filter(
	'login_redirect',
	static function ( string $redirect_to, string $requested_redirect_to, $user ) use ( $refitune_redirect_settings ) {
		$refitune_login_redirects = isset( $refitune_redirect_settings['role_redirects_login'] ) && is_array( $refitune_redirect_settings['role_redirects_login'] )
			? $refitune_redirect_settings['role_redirects_login']
			: array();

		$refitune_custom_redirect = refitune_get_login_redirect_url( $user, $refitune_login_redirects );

		return $refitune_custom_redirect ?? $redirect_to;
	},
	999,
	3
);

// WooCommerce login/logout redirects (only when WooCommerce is active).
add_action(
	'plugins_loaded',
	function () use ( $refitune_redirect_settings ) {
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}

		// WooCommerce login redirect.
		add_filter(
			'woocommerce_login_redirect',
			static function ( string $redirect, $user ) use ( $refitune_redirect_settings ) {
				$refitune_login_redirects = isset( $refitune_redirect_settings['role_redirects_login'] ) && is_array( $refitune_redirect_settings['role_redirects_login'] )
					? $refitune_redirect_settings['role_redirects_login']
					: array();

				$refitune_custom_redirect = refitune_get_login_redirect_url( $user, $refitune_login_redirects );

				return $refitune_custom_redirect ?? $redirect;
			},
			999,
			2
		);

		// WooCommerce logout redirect.
		// When a redirect_to parameter is present in the URL (added by us), use it.
		add_filter(
			'woocommerce_logout_default_redirect_url',
			static function ( string $redirect_to ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public redirect target validated against site host below.
				$refitune_requested_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';

				if ( '' !== $refitune_requested_redirect ) {
					return refitune_validate_internal_redirect_url( $refitune_requested_redirect );
				}

				return $redirect_to;
			},
			999
		);
	},
	10
);

// ---------------------------------------------------------------------------
// Logout redirects
// ---------------------------------------------------------------------------

/**
 * WordPress logout redirect.
 * When a redirect_to parameter is present in the URL (added by us), use it.
 */
add_filter(
	'logout_redirect',
	static function ( string $redirect_to, string $requested_redirect_to, $user ) use ( $refitune_redirect_settings ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public redirect target validated against site host below.
		$refitune_requested_redirect = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';

		if ( '' !== $refitune_requested_redirect ) {
			return refitune_validate_internal_redirect_url( $refitune_requested_redirect );
		}

		$refitune_logout_redirects = isset( $refitune_redirect_settings['role_redirects_logout'] ) && is_array( $refitune_redirect_settings['role_redirects_logout'] )
			? $refitune_redirect_settings['role_redirects_logout']
			: array();

		$refitune_custom_redirect = refitune_get_logout_redirect_url( $user, $refitune_logout_redirects );

		return $refitune_custom_redirect ?? $redirect_to;
	},
	999,
	3
);

/**
 * Intercept the WooCommerce My Account logout endpoint.
 *
 * This hook runs early (before WooCommerce processes the logout), so the
 * user is still logged in and the role-based redirect can be determined.
 * The user is redirected to the WooCommerce logout URL with the
 * redirect_to parameter appended.
 */
add_action(
	'template_redirect',
	function () use ( $refitune_redirect_settings ) {
		// Only when WooCommerce is active AND the user is logged in.
		if ( ! class_exists( 'WooCommerce' ) || ! is_user_logged_in() ) {
			return;
		}

		// Check whether we are on the WooCommerce My Account logout endpoint.
		global $wp;
		if ( ! isset( $wp->query_vars['customer-logout'] ) ) {
			return;
		}

		// When the URL already has a redirect_to parameter, leave it alone.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only check for an existing redirect query arg.
		if ( isset( $_GET['redirect_to'] ) ) {
			return;
		}

		$refitune_user = wp_get_current_user();
		if ( ! $refitune_user || ! $refitune_user->ID ) {
			return;
		}

		$refitune_logout_redirects = isset( $refitune_redirect_settings['role_redirects_logout'] ) && is_array( $refitune_redirect_settings['role_redirects_logout'] )
			? $refitune_redirect_settings['role_redirects_logout']
			: array();

		$refitune_custom_redirect = refitune_get_logout_redirect_url( $refitune_user, $refitune_logout_redirects );

		// When a custom redirect exists, send the user to the logout URL with the redirect_to parameter.
		if ( $refitune_custom_redirect ) {
			$refitune_logout_url = wc_get_account_endpoint_url( 'customer-logout' );
			$refitune_logout_url = wp_nonce_url( $refitune_logout_url, 'customer-logout' );
			$refitune_logout_url = add_query_arg( 'redirect_to', rawurlencode( $refitune_custom_redirect ), $refitune_logout_url );

			wp_safe_redirect( $refitune_logout_url );
			exit;
		}
	},
	1
);
