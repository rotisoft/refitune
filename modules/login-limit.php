<?php
/**
 * Login limit - Rate limit failed login attempts by IP and IP+username pair.
 *
 * Global per-user lockouts are intentionally avoided: failed attempts from one
 * client must not lock out successful logins from another IP.
 *
 * Counters increment atomically (object-cache incr or SQL UPDATE). Lockout
 * checks run on authenticate so wp-login.php and wp_signon() paths (including
 * WooCommerce) are covered.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_settings = refitune_get_settings();

$refitune_max_attempts         = isset( $refitune_settings['login_limit_max_attempts'] ) && $refitune_settings['login_limit_max_attempts'] > 0
	? (int) $refitune_settings['login_limit_max_attempts']
	: 5;
$refitune_lockout_duration     = isset( $refitune_settings['login_limit_lockout_duration'] ) && $refitune_settings['login_limit_lockout_duration'] > 0
	? (int) $refitune_settings['login_limit_lockout_duration']
	: 15;
$refitune_whitelist_raw = isset( $refitune_settings['login_limit_whitelist_ips'] )
	? (string) $refitune_settings['login_limit_whitelist_ips']
	: '';
$refitune_whitelist_raw = str_replace( array( "\r\n", "\r" ), "\n", $refitune_whitelist_raw );
$refitune_whitelist_ips = array_values(
	array_unique(
		array_filter( array_map( 'trim', explode( "\n", $refitune_whitelist_raw ) ) )
	)
);
$refitune_block_admin_username = ! empty( $refitune_settings['login_limit_block_admin_username'] );

/**
 * Check whether an IP is on the whitelist.
 *
 * @param string $refitune_ip        IP address.
 * @param array  $refitune_whitelist Whitelisted IPs.
 * @return bool
 */
function refitune_is_whitelisted_ip( string $refitune_ip, array $refitune_whitelist ): bool {
	return in_array( $refitune_ip, $refitune_whitelist, true );
}

/**
 * Fixed-length transient key suffix for an IP address.
 *
 * @param string $refitune_ip Client IP address.
 * @return string
 */
function refitune_login_limit_ip_hash( string $refitune_ip ): string {
	$refitune_ip = trim( $refitune_ip );

	if ( '' === $refitune_ip ) {
		return '';
	}

	return md5( $refitune_ip );
}

/**
 * Fixed-length transient key suffix for a login identifier.
 *
 * @param string $refitune_username Login input.
 * @return string
 */
function refitune_login_limit_user_hash( string $refitune_username ): string {
	$refitune_username = sanitize_user( wp_unslash( $refitune_username ), true );
	$refitune_username = strtolower( substr( $refitune_username, 0, 60 ) );

	if ( '' === $refitune_username ) {
		return '';
	}

	return md5( $refitune_username );
}

/**
 * Transient key for an IP + username pair lockout / counter.
 *
 * @param string $refitune_ip_hash   Hashed client IP.
 * @param string $refitune_user_hash Hashed login identifier.
 * @param string $refitune_kind      Either "attempts" or "lockout".
 * @return string Empty when either hash is missing.
 */
function refitune_login_limit_pair_key( string $refitune_ip_hash, string $refitune_user_hash, string $refitune_kind ): string {
	if ( '' === $refitune_ip_hash || '' === $refitune_user_hash ) {
		return '';
	}

	if ( 'lockout' === $refitune_kind ) {
		return 'refitune_lockout_pair_' . $refitune_ip_hash . '_' . $refitune_user_hash;
	}

	return 'refitune_login_attempts_pair_' . $refitune_ip_hash . '_' . $refitune_user_hash;
}

/**
 * Read a login limit counter.
 *
 * @param string $refitune_key Transient key.
 * @return int
 */
function refitune_login_limit_get_counter( string $refitune_key ): int {
	$refitune_cached = wp_cache_get( $refitune_key, 'refitune_login_limit' );

	if ( false !== $refitune_cached ) {
		return (int) $refitune_cached;
	}

	$refitune_value = get_transient( $refitune_key );

	return false === $refitune_value ? 0 : (int) $refitune_value;
}

/**
 * Store a login limit counter.
 *
 * @param string $refitune_key        Transient key.
 * @param int    $refitune_count      Counter value.
 * @param int    $refitune_expiration Expiration in seconds.
 * @return void
 */
function refitune_login_limit_set_counter( string $refitune_key, int $refitune_count, int $refitune_expiration ): void {
	wp_cache_set( $refitune_key, $refitune_count, 'refitune_login_limit', $refitune_expiration );
	set_transient( $refitune_key, $refitune_count, $refitune_expiration );
}

/**
 * Atomically increment a login limit counter.
 *
 * Uses object-cache incr when available; otherwise a SQL UPDATE on the
 * transient option row so parallel failed logins cannot lose increments.
 *
 * @param string $refitune_key        Transient key.
 * @param int    $refitune_expiration Expiration in seconds.
 * @return int New counter value after increment.
 */
function refitune_login_limit_increment_counter( string $refitune_key, int $refitune_expiration ): int {
	global $wpdb;

	if ( wp_using_ext_object_cache() ) {
		$refitune_count = wp_cache_incr( $refitune_key, 1, 'refitune_login_limit' );

		if ( false === $refitune_count ) {
			wp_cache_add( $refitune_key, 0, 'refitune_login_limit', $refitune_expiration );
			$refitune_count = wp_cache_incr( $refitune_key, 1, 'refitune_login_limit' );
		}

		$refitune_count = max( 1, (int) $refitune_count );
		set_transient( $refitune_key, $refitune_count, $refitune_expiration );

		return $refitune_count;
	}

	$refitune_option  = '_transient_' . $refitune_key;
	$refitune_timeout = '_transient_timeout_' . $refitune_key;
	$refitune_now     = time();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Atomic counter update; no core API for this.
	$refitune_updated = $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->options} AS o
			INNER JOIN {$wpdb->options} AS t ON t.option_name = %s
			SET o.option_value = CAST(o.option_value AS UNSIGNED) + 1
			WHERE o.option_name = %s
			AND CAST(t.option_value AS UNSIGNED) > %d",
			$refitune_timeout,
			$refitune_option,
			$refitune_now
		)
	);

	if ( $refitune_updated > 0 ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Read value after atomic increment.
		$refitune_count = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT option_value FROM {$wpdb->options} WHERE option_name = %s LIMIT 1",
				$refitune_option
			)
		);
		$refitune_count = max( 1, $refitune_count );
		wp_cache_set( $refitune_key, $refitune_count, 'refitune_login_limit', $refitune_expiration );

		return $refitune_count;
	}

	set_transient( $refitune_key, 1, $refitune_expiration );
	wp_cache_set( $refitune_key, 1, 'refitune_login_limit', $refitune_expiration );

	return 1;
}

/**
 * Canonical login identifier for rate-limit keys.
 *
 * Maps an email login to the account's user_login when the user exists so
 * email and username failures share the same pair counter.
 *
 * @param string $refitune_username Submitted login identifier.
 * @return string
 */
function refitune_login_limit_canonical_username( string $refitune_username ): string {
	$refitune_username = wp_unslash( $refitune_username );

	if ( '' === $refitune_username ) {
		return '';
	}

	$refitune_user = get_user_by( 'login', $refitune_username );

	if ( ! $refitune_user && is_email( $refitune_username ) ) {
		$refitune_user = get_user_by( 'email', $refitune_username );
	}

	return $refitune_user instanceof WP_User ? $refitune_user->user_login : $refitune_username;
}

/**
 * Delete a login limit counter.
 *
 * @param string $refitune_key Transient key.
 * @return void
 */
function refitune_login_limit_delete_counter( string $refitune_key ): void {
	wp_cache_delete( $refitune_key, 'refitune_login_limit' );
	delete_transient( $refitune_key );
}

/**
 * Return the client IP address.
 *
 * @return string
 */
function refitune_login_limit_get_client_ip(): string {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

/**
 * Whether the current client is locked out for the given login identifier.
 *
 * Checks IP lockout and IP+username pair lockout. Never blocks by username alone.
 *
 * @param string $refitune_username Login identifier (may be empty for IP-only check).
 * @param array  $refitune_whitelist Whitelisted IPs.
 * @return bool
 */
function refitune_login_limit_is_locked( string $refitune_username, array $refitune_whitelist ): bool {
	$refitune_ip = refitune_login_limit_get_client_ip();

	if ( $refitune_ip && refitune_is_whitelisted_ip( $refitune_ip, $refitune_whitelist ) ) {
		return false;
	}

	$refitune_ip_hash = refitune_login_limit_ip_hash( $refitune_ip );

	if ( '' !== $refitune_ip_hash && false !== get_transient( 'refitune_lockout_ip_' . $refitune_ip_hash ) ) {
		return true;
	}

	if ( '' === $refitune_username ) {
		return false;
	}

	$refitune_canonical = refitune_login_limit_canonical_username( $refitune_username );
	$refitune_user_hash = refitune_login_limit_user_hash( $refitune_canonical );
	$refitune_pair_key  = refitune_login_limit_pair_key( $refitune_ip_hash, $refitune_user_hash, 'lockout' );

	return '' !== $refitune_pair_key && false !== get_transient( $refitune_pair_key );
}

// Early IP / pair lockout check before the login form is processed (wp-login.php).
add_action(
	'login_form_login',
	static function () use ( $refitune_whitelist_ips ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WordPress core verifies the login form nonce separately.
		$refitune_login_input = isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ), true ) : '';

		if ( refitune_login_limit_is_locked( $refitune_login_input, $refitune_whitelist_ips ) ) {
			wp_die(
				esc_html__( 'Too many failed login attempts. Please try again later.', 'refitune' ),
				esc_html__( 'Login Blocked', 'refitune' ),
				array( 'response' => 403, 'back_link' => true )
			);
		}
	}
);

// Shared pre-auth lockout for wp-login.php, wp_signon(), and WooCommerce login.
add_filter(
	'authenticate',
	static function ( $user, $username ) use ( $refitune_whitelist_ips ) {
		if ( is_wp_error( $user ) && 'login_locked' === $user->get_error_code() ) {
			return $user;
		}

		if ( refitune_login_limit_is_locked( (string) $username, $refitune_whitelist_ips ) ) {
			return new WP_Error(
				'login_locked',
				__( 'Too many failed login attempts. Please try again later.', 'refitune' )
			);
		}

		return $user;
	},
	30,
	2
);

add_action(
	'wp_login_failed',
	static function ( $username ) use ( $refitune_max_attempts, $refitune_lockout_duration, $refitune_whitelist_ips, $refitune_block_admin_username ) {
		$refitune_ip = refitune_login_limit_get_client_ip();

		if ( $refitune_ip && refitune_is_whitelisted_ip( $refitune_ip, $refitune_whitelist_ips ) ) {
			return;
		}

		$refitune_ip_hash         = refitune_login_limit_ip_hash( $refitune_ip );
		$refitune_username_clean  = sanitize_user( wp_unslash( $username ), true );
		$refitune_canonical       = refitune_login_limit_canonical_username( (string) $username );
		$refitune_user_hash       = refitune_login_limit_user_hash( $refitune_canonical );
		$refitune_lockout_seconds = $refitune_lockout_duration * MINUTE_IN_SECONDS;

		if ( $refitune_block_admin_username && 'admin' === strtolower( $refitune_username_clean ) && '' !== $refitune_ip_hash ) {
			// Use the configured lockout duration, consistent with the other lockout paths.
			set_transient( 'refitune_lockout_ip_' . $refitune_ip_hash, time() + $refitune_lockout_seconds, $refitune_lockout_seconds );
			return;
		}

		if ( '' !== $refitune_ip_hash ) {
			$refitune_ip_attempts_key = 'refitune_login_attempts_ip_' . $refitune_ip_hash;
			$refitune_ip_attempts     = refitune_login_limit_increment_counter( $refitune_ip_attempts_key, HOUR_IN_SECONDS );

			if ( $refitune_ip_attempts >= $refitune_max_attempts ) {
				set_transient( 'refitune_lockout_ip_' . $refitune_ip_hash, time() + $refitune_lockout_seconds, $refitune_lockout_seconds );
			}
		}

		// Pair lockout only: failed attempts from IP A must not lock IP B.
		$refitune_pair_attempts_key = refitune_login_limit_pair_key( $refitune_ip_hash, $refitune_user_hash, 'attempts' );
		$refitune_pair_lockout_key  = refitune_login_limit_pair_key( $refitune_ip_hash, $refitune_user_hash, 'lockout' );

		if ( '' !== $refitune_pair_attempts_key && '' !== $refitune_pair_lockout_key ) {
			$refitune_pair_attempts = refitune_login_limit_increment_counter( $refitune_pair_attempts_key, HOUR_IN_SECONDS );

			if ( $refitune_pair_attempts >= $refitune_max_attempts ) {
				set_transient( $refitune_pair_lockout_key, time() + $refitune_lockout_seconds, $refitune_lockout_seconds );
			}
		}
	}
);

add_action(
	'wp_login',
	static function ( $user_login ) {
		$refitune_ip_hash   = refitune_login_limit_ip_hash( refitune_login_limit_get_client_ip() );
		$refitune_user_hash = refitune_login_limit_user_hash( $user_login );

		if ( '' !== $refitune_ip_hash ) {
			refitune_login_limit_delete_counter( 'refitune_login_attempts_ip_' . $refitune_ip_hash );
			delete_transient( 'refitune_lockout_ip_' . $refitune_ip_hash );
		}

		$refitune_pair_attempts_key = refitune_login_limit_pair_key( $refitune_ip_hash, $refitune_user_hash, 'attempts' );
		$refitune_pair_lockout_key  = refitune_login_limit_pair_key( $refitune_ip_hash, $refitune_user_hash, 'lockout' );

		if ( '' !== $refitune_pair_attempts_key ) {
			refitune_login_limit_delete_counter( $refitune_pair_attempts_key );
		}
		if ( '' !== $refitune_pair_lockout_key ) {
			delete_transient( $refitune_pair_lockout_key );
		}
	},
	10,
	1
);
