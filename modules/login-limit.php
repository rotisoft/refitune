<?php
/**
 * Login limit - Rate limit failed login attempts by IP and username.
 *
 * TODO: Extend full protection (including IP lockout on login) to WooCommerce
 * My Account login, not only wp-login.php.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = get_option( 'refitune_settings', array() );

$max_attempts         = isset( $refitune_settings['login_limit_max_attempts'] ) && $refitune_settings['login_limit_max_attempts'] > 0
	? (int) $refitune_settings['login_limit_max_attempts']
	: 5;
$lockout_duration     = isset( $refitune_settings['login_limit_lockout_duration'] ) && $refitune_settings['login_limit_lockout_duration'] > 0
	? (int) $refitune_settings['login_limit_lockout_duration']
	: 15;
$whitelist_ips        = isset( $refitune_settings['login_limit_whitelist_ips'] )
	? array_filter( array_map( 'trim', explode( "\n", $refitune_settings['login_limit_whitelist_ips'] ) ) )
	: array();
$block_admin_username = ! empty( $refitune_settings['login_limit_block_admin_username'] );

/**
 * Check whether an IP is on the whitelist.
 *
 * @param string $ip        IP address.
 * @param array  $whitelist Whitelisted IPs.
 * @return bool
 */
function refitune_is_whitelisted_ip( string $ip, array $whitelist ): bool {
	return in_array( $ip, $whitelist, true );
}

/**
 * Fixed-length transient key suffix for an IP address.
 *
 * @param string $ip Client IP address.
 * @return string
 */
function refitune_login_limit_ip_hash( string $ip ): string {
	$ip = trim( $ip );

	if ( '' === $ip ) {
		return '';
	}

	return md5( $ip );
}

/**
 * Fixed-length transient key suffix for a login identifier.
 *
 * @param string $username Login input.
 * @return string
 */
function refitune_login_limit_user_hash( string $username ): string {
	$username = sanitize_user( wp_unslash( $username ), true );
	$username = strtolower( substr( $username, 0, 60 ) );

	if ( '' === $username ) {
		return '';
	}

	return md5( $username );
}

/**
 * Read a login limit counter.
 *
 * @param string $key Transient key.
 * @return int
 */
function refitune_login_limit_get_counter( string $key ): int {
	$cached = wp_cache_get( $key, 'refitune_login_limit' );

	if ( false !== $cached ) {
		return (int) $cached;
	}

	$value = get_transient( $key );

	return false === $value ? 0 : (int) $value;
}

/**
 * Store a login limit counter.
 *
 * @param string $key        Transient key.
 * @param int    $count      Counter value.
 * @param int    $expiration Expiration in seconds.
 * @return void
 */
function refitune_login_limit_set_counter( string $key, int $count, int $expiration ): void {
	wp_cache_set( $key, $count, 'refitune_login_limit', $expiration );
	set_transient( $key, $count, $expiration );
}

/**
 * Delete a login limit counter.
 *
 * @param string $key Transient key.
 * @return void
 */
function refitune_login_limit_delete_counter( string $key ): void {
	wp_cache_delete( $key, 'refitune_login_limit' );
	delete_transient( $key );
}

/**
 * Return the client IP address.
 *
 * @return string
 */
function refitune_login_limit_get_client_ip(): string {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

// Early IP lockout check before the login form is processed.
add_action(
	'login_form_login',
	static function () use ( $whitelist_ips ) {
		$ip = refitune_login_limit_get_client_ip();

		if ( $ip && refitune_is_whitelisted_ip( $ip, $whitelist_ips ) ) {
			return;
		}

		$ip_hash = refitune_login_limit_ip_hash( $ip );
		if ( '' !== $ip_hash && false !== get_transient( 'refitune_lockout_ip_' . $ip_hash ) ) {
			wp_die(
				esc_html__( 'Too many failed login attempts. Please try again later.', 'refitune' ),
				__( 'Login Blocked', 'refitune' ),
				array( 'response' => 403, 'back_link' => true )
			);
		}

		// Pre-auth username lockout (before password verification).
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Login form has its own nonce.
		if ( ! empty( $_POST['log'] ) ) {
			$user_hash = refitune_login_limit_user_hash( wp_unslash( $_POST['log'] ) );
			if ( '' !== $user_hash && false !== get_transient( 'refitune_lockout_user_' . $user_hash ) ) {
				wp_die(
					esc_html__( 'This user account is temporarily locked. Please try again later.', 'refitune' ),
					__( 'Login Blocked', 'refitune' ),
					array( 'response' => 403, 'back_link' => true )
				);
			}
		}
	}
);

// Pre-auth username lockout during authentication.
add_filter(
	'authenticate',
	static function ( $user, $username ) use ( $whitelist_ips ) {
		if ( is_wp_error( $user ) && 'login_locked' === $user->get_error_code() ) {
			return $user;
		}

		$ip = refitune_login_limit_get_client_ip();
		if ( $ip && refitune_is_whitelisted_ip( $ip, $whitelist_ips ) ) {
			return $user;
		}

		if ( empty( $username ) ) {
			return $user;
		}

		$user_hash = refitune_login_limit_user_hash( $username );
		if ( '' !== $user_hash && false !== get_transient( 'refitune_lockout_user_' . $user_hash ) ) {
			return new WP_Error(
				'login_locked',
				__( 'This user account is temporarily locked. Please try again later.', 'refitune' )
			);
		}

		return $user;
	},
	30,
	2
);

add_action(
	'wp_login_failed',
	static function ( $username ) use ( $max_attempts, $lockout_duration, $whitelist_ips, $block_admin_username ) {
		$ip = refitune_login_limit_get_client_ip();

		if ( $ip && refitune_is_whitelisted_ip( $ip, $whitelist_ips ) ) {
			return;
		}

		$ip_hash        = refitune_login_limit_ip_hash( $ip );
		$username_clean = sanitize_user( wp_unslash( $username ), true );
		$user_hash      = refitune_login_limit_user_hash( $username );

		if ( $block_admin_username && 'admin' === strtolower( $username_clean ) && '' !== $ip_hash ) {
			set_transient( 'refitune_lockout_ip_' . $ip_hash, time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS );
			return;
		}

		if ( '' !== $ip_hash ) {
			$ip_attempts_key = 'refitune_login_attempts_ip_' . $ip_hash;
			$ip_attempts     = refitune_login_limit_get_counter( $ip_attempts_key ) + 1;
			refitune_login_limit_set_counter( $ip_attempts_key, $ip_attempts, HOUR_IN_SECONDS );

			if ( $ip_attempts >= $max_attempts ) {
				$lockout_until = time() + ( $lockout_duration * MINUTE_IN_SECONDS );
				set_transient( 'refitune_lockout_ip_' . $ip_hash, $lockout_until, $lockout_duration * MINUTE_IN_SECONDS );
			}
		}

		if ( '' !== $user_hash ) {
			$user_attempts_key = 'refitune_login_attempts_user_' . $user_hash;
			$user_attempts     = refitune_login_limit_get_counter( $user_attempts_key ) + 1;
			refitune_login_limit_set_counter( $user_attempts_key, $user_attempts, HOUR_IN_SECONDS );

			if ( $user_attempts >= $max_attempts ) {
				$lockout_until = time() + ( $lockout_duration * MINUTE_IN_SECONDS );
				set_transient( 'refitune_lockout_user_' . $user_hash, $lockout_until, $lockout_duration * MINUTE_IN_SECONDS );
			}
		}
	}
);

add_action(
	'wp_login',
	static function ( $user_login ) {
		$ip_hash   = refitune_login_limit_ip_hash( refitune_login_limit_get_client_ip() );
		$user_hash = refitune_login_limit_user_hash( $user_login );

		if ( '' !== $ip_hash ) {
			refitune_login_limit_delete_counter( 'refitune_login_attempts_ip_' . $ip_hash );
		}
		if ( '' !== $user_hash ) {
			refitune_login_limit_delete_counter( 'refitune_login_attempts_user_' . $user_hash );
		}
	},
	10,
	1
);
