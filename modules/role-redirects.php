<?php
/**
 * Szerepkör-alapú átirányítások bejelentkezés és kijelentkezés után.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_redirect_settings = get_option( 'refitune_settings', array() );

// ---------------------------------------------------------------------------
// Helper függvények
// ---------------------------------------------------------------------------

/**
 * Validate a redirect URL against this site's host.
 *
 * @param string $url Redirect URL.
 * @return string Safe internal URL.
 */
function refitune_validate_internal_redirect_url( string $url ): string {
	$url = wp_validate_redirect( $url, home_url( '/' ) );

	$home_host   = wp_parse_url( home_url(), PHP_URL_HOST );
	$target_host = wp_parse_url( $url, PHP_URL_HOST );

	if ( empty( $target_host ) || (string) $target_host === (string) $home_host ) {
		return $url;
	}

	return home_url( '/' );
}

/**
 * Login redirect URL lekérése szerepkör alapján.
 *
 * @param WP_User $user A bejelentkezett felhasználó.
 * @param array   $login_redirects Login redirect beállítások.
 * @return string|null A redirect URL vagy null ha nincs beállítva.
 */
function refitune_get_login_redirect_url( $user, $login_redirects ) {
	if ( ! isset( $user->ID ) || ! $user instanceof WP_User ) {
		return null;
	}

	foreach ( (array) $user->roles as $role ) {
		if ( isset( $login_redirects[ $role ] ) && '' !== trim( $login_redirects[ $role ] ) ) {
			return refitune_validate_internal_redirect_url( $login_redirects[ $role ] );
		}
	}

	return null;
}

/**
 * Logout redirect URL lekérése szerepkör alapján.
 *
 * @param WP_User $user A kijelentkezés előtti felhasználó.
 * @param array   $logout_redirects Logout redirect beállítások.
 * @return string|null A redirect URL vagy null ha nincs beállítva.
 */
function refitune_get_logout_redirect_url( $user, $logout_redirects ) {
	if ( ! isset( $user->ID ) || ! $user instanceof WP_User ) {
		return null;
	}

	foreach ( (array) $user->roles as $role ) {
		if ( isset( $logout_redirects[ $role ] ) && '' !== trim( $logout_redirects[ $role ] ) ) {
			return refitune_validate_internal_redirect_url( $logout_redirects[ $role ] );
		}
	}

	return null;
}

// ---------------------------------------------------------------------------
// Login redirectek
// ---------------------------------------------------------------------------

// WordPress login redirect.
add_filter(
	'login_redirect',
	static function ( string $redirect_to, string $requested_redirect_to, $user ) use ( $refitune_redirect_settings ) {
		$login_redirects = isset( $refitune_redirect_settings['role_redirects_login'] ) && is_array( $refitune_redirect_settings['role_redirects_login'] )
			? $refitune_redirect_settings['role_redirects_login']
			: array();

		$custom_redirect = refitune_get_login_redirect_url( $user, $login_redirects );
		
		return $custom_redirect ?? $redirect_to;
	},
	999,
	3
);

// WooCommerce login/logout redirectek (csak ha WooCommerce aktív).
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
				$login_redirects = isset( $refitune_redirect_settings['role_redirects_login'] ) && is_array( $refitune_redirect_settings['role_redirects_login'] )
					? $refitune_redirect_settings['role_redirects_login']
					: array();

				$custom_redirect = refitune_get_login_redirect_url( $user, $login_redirects );
				
				return $custom_redirect ?? $redirect;
			},
			999,
			2
		);

		// WooCommerce logout redirect.
		// Ha van redirect_to paraméter az URL-ben (amit mi adtunk hozzá), használjuk azt.
		add_filter(
			'woocommerce_logout_default_redirect_url',
			static function ( string $redirect_to ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Validated against site host below.
				if ( ! empty( $_GET['redirect_to'] ) ) {
					return refitune_validate_internal_redirect_url( wp_unslash( $_GET['redirect_to'] ) );
				}

				return $redirect_to;
			},
			999
		);
	}
);

// ---------------------------------------------------------------------------
// Logout redirectek
// ---------------------------------------------------------------------------

/**
 * WordPress logout redirect.
 * Ha van redirect_to paraméter az URL-ben (amit mi adtunk hozzá), használjuk azt.
 */
add_filter(
	'logout_redirect',
	static function ( string $redirect_to, string $requested_redirect_to, $user ) use ( $refitune_redirect_settings ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Validated against site host below.
		if ( ! empty( $_GET['redirect_to'] ) ) {
			return refitune_validate_internal_redirect_url( wp_unslash( $_GET['redirect_to'] ) );
		}

		$logout_redirects = isset( $refitune_redirect_settings['role_redirects_logout'] ) && is_array( $refitune_redirect_settings['role_redirects_logout'] )
			? $refitune_redirect_settings['role_redirects_logout']
			: array();

		$custom_redirect = refitune_get_logout_redirect_url( $user, $logout_redirects );
		
		return $custom_redirect ?? $redirect_to;
	},
	999,
	3
);

/**
 * WooCommerce My Account logout endpoint elfogása.
 * 
 * Ez a hook korán fut (még mielőtt a WooCommerce feldolgozná a logoutot),
 * így a user még be van jelentkezve és meg tudjuk határozni a role-based redirect-et.
 * Átirányítjuk a usert a WooCommerce logout URL-re, hozzáadva a redirect_to paramétert.
 */
add_action(
	'template_redirect',
	function () use ( $refitune_redirect_settings ) {
		// Csak ha WooCommerce aktív ÉS a user be van jelentkezve.
		if ( ! class_exists( 'WooCommerce' ) || ! is_user_logged_in() ) {
			return;
		}

		// Ellenőrizzük, hogy a WooCommerce My Account logout endpoint-on vagyunk-e.
		global $wp;
		if ( ! isset( $wp->query_vars['customer-logout'] ) ) {
			return;
		}

		// Ha már van redirect_to paraméter az URL-ben, ne foglalkozzunk vele.
		if ( isset( $_GET['redirect_to'] ) ) {
			return;
		}

		$user = wp_get_current_user();
		if ( ! $user || ! $user->ID ) {
			return;
		}

		$logout_redirects = isset( $refitune_redirect_settings['role_redirects_logout'] ) && is_array( $refitune_redirect_settings['role_redirects_logout'] )
			? $refitune_redirect_settings['role_redirects_logout']
			: array();

		$custom_redirect = refitune_get_logout_redirect_url( $user, $logout_redirects );
		
		// Ha van custom redirect, átirányítjuk a usert a logout URL-re, hozzáadva a redirect_to paramétert.
		if ( $custom_redirect ) {
			$logout_url = wc_get_account_endpoint_url( 'customer-logout' );
			$logout_url = wp_nonce_url( $logout_url, 'customer-logout' );
			$logout_url = add_query_arg( 'redirect_to', rawurlencode( $custom_redirect ), $logout_url );
			
			wp_safe_redirect( $logout_url );
			exit;
		}
	},
	1
);
