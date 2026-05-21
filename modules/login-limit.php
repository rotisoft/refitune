<?php
/**
 * Login limit - Sikertelen bejelentkezési kísérletek korlátozása.
 *
 * Brute-force támadások ellen véd IP cím és felhasználónév alapú limitálással.
 * Mindkét szempont külön számolódik - ha bármelyik eléri a limitet, kitiltás történik.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_settings = get_option( 'wprefi_settings', array() );

// Beállítások.
$max_attempts = isset( $wprefi_settings['login_limit_max_attempts'] ) && $wprefi_settings['login_limit_max_attempts'] > 0
	? (int) $wprefi_settings['login_limit_max_attempts']
	: 5;
$lockout_duration = isset( $wprefi_settings['login_limit_lockout_duration'] ) && $wprefi_settings['login_limit_lockout_duration'] > 0
	? (int) $wprefi_settings['login_limit_lockout_duration']
	: 15;
$whitelist_ips = isset( $wprefi_settings['login_limit_whitelist_ips'] )
	? array_filter( array_map( 'trim', explode( "\n", $wprefi_settings['login_limit_whitelist_ips'] ) ) )
	: array();
$block_admin_username = ! empty( $wprefi_settings['login_limit_block_admin_username'] );
$global_enabled = ! empty( $wprefi_settings['login_limit_global_enabled'] );
$global_attempts = isset( $wprefi_settings['login_limit_global_attempts'] ) && $wprefi_settings['login_limit_global_attempts'] > 0
	? (int) $wprefi_settings['login_limit_global_attempts']
	: 50;
$global_time_window = isset( $wprefi_settings['login_limit_global_time_window'] ) && $wprefi_settings['login_limit_global_time_window'] > 0
	? (int) $wprefi_settings['login_limit_global_time_window']
	: 5;

/**
 * Ellenőrzi, hogy az IP whitelist-en van-e.
 *
 * @param string $ip        Az ellenőrizendő IP cím.
 * @param array  $whitelist A whitelist IP címek tömbje.
 * @return bool True, ha whitelist-en van.
 */
function wprefi_is_whitelisted_ip( string $ip, array $whitelist ): bool {
	return in_array( $ip, $whitelist, true );
}

// Korai ellenőrzés: Globális és IP lockout ellenőrzés a login form feldolgozása előtt.
add_action(
	'login_form_login',
	function () use ( $max_attempts, $lockout_duration, $whitelist_ips, $global_enabled, $global_attempts, $global_time_window ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		
		// Whitelist ellenőrzés.
		if ( $ip && wprefi_is_whitelisted_ip( $ip, $whitelist_ips ) ) {
			return;
		}
		
		// 1. GLOBÁLIS LOCKOUT ELLENŐRZÉS (DDoS védelem) - csak ha be van kapcsolva.
		if ( $global_enabled ) {
			$global_lockout = get_transient( 'wprefi_global_lockout' );
			if ( false !== $global_lockout ) {
				wp_die(
					esc_html__( 'Too many failed login attempts from multiple sources. All logins are temporarily blocked. Please try again later.', 'refinerpress' ),
					__( 'Login Blocked', 'refinerpress' ),
					array( 'response' => 403, 'back_link' => true )
				);
			}
		}
		
		// 2. IP LOCKOUT ELLENŐRZÉS.
		$ip_hash = md5( $ip );
		$lockout_ip = get_transient( 'wprefi_lockout_ip_' . $ip_hash );
		if ( false !== $lockout_ip ) {
			wp_die(
				esc_html__( 'Too many failed login attempts. Please try again later.', 'refinerpress' ),
				__( 'Login Blocked', 'refinerpress' ),
				array( 'response' => 403, 'back_link' => true )
			);
		}
	}
);

// Felhasználónév-alapú lockout ellenőrzés az authentikáció során.
add_filter(
	'wp_authenticate_user',
	function ( $user, $password ) use ( $max_attempts, $lockout_duration, $whitelist_ips ) {
		// Ha már van hiba, ne írjuk felül.
		if ( is_wp_error( $user ) ) {
			return $user;
		}
		
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		
		// Whitelist ellenőrzés.
		if ( $ip && wprefi_is_whitelisted_ip( $ip, $whitelist_ips ) ) {
			return $user;
		}
		
		$username_clean = $user->user_login;
		
		// Kitiltás ellenőrzés felhasználónév alapján.
		$lockout_user = get_transient( 'wprefi_lockout_user_' . $username_clean );
		if ( false !== $lockout_user ) {
			return new WP_Error(
				'login_locked',
				__( 'This user account is temporarily locked. Please try again later.', 'refinerpress' )
			);
		}
		
		return $user;
	},
	10,
	2
);

add_action(
	'wp_login_failed',
	function ( $username ) use ( $max_attempts, $lockout_duration, $whitelist_ips, $block_admin_username, $global_enabled, $global_attempts, $global_time_window ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		// Whitelist ellenőrzés.
		if ( $ip && wprefi_is_whitelisted_ip( $ip, $whitelist_ips ) ) {
			return;
		}

		$ip_hash        = md5( $ip );
		$username_clean = sanitize_user( $username );

		// 0. AZONNALI "ADMIN" USERNAME BLOKKOLÁS (ha be van kapcsolva).
		if ( $block_admin_username && 'admin' === strtolower( $username_clean ) ) {
			// Azonnal kitiltjuk az IP-t 1 órára, már az első próbálkozásnál.
			set_transient( 'wprefi_lockout_ip_' . $ip_hash, time() + HOUR_IN_SECONDS, HOUR_IN_SECONDS );
			// Nem számolunk tovább, azonnal visszatérünk.
			return;
		}

		// 1. GLOBÁLIS SZÁMLÁLÁS (minden forrásból) - csak ha be van kapcsolva.
		if ( $global_enabled ) {
			$global_key      = 'wprefi_global_attempts';
			$global_current  = get_transient( $global_key );
			$global_current  = ( false === $global_current ) ? 1 : ( (int) $global_current + 1 );
			$time_window_sec = $global_time_window * MINUTE_IN_SECONDS;
			set_transient( $global_key, $global_current, $time_window_sec );

			// Globális lockout aktiválása, ha túlléptük a limitet.
			if ( $global_current >= $global_attempts ) {
				set_transient( 'wprefi_global_lockout', time() + $time_window_sec, $time_window_sec );
			}
		}

		// 2. IP KÍSÉRLETEK SZÁMLÁLÁSA.
		$ip_attempts_key = 'wprefi_login_attempts_ip_' . $ip_hash;
		$ip_attempts     = get_transient( $ip_attempts_key );
		$ip_attempts     = ( false === $ip_attempts ) ? 1 : ( (int) $ip_attempts + 1 );
		set_transient( $ip_attempts_key, $ip_attempts, HOUR_IN_SECONDS );

		// 3. FELHASZNÁLÓNÉV KÍSÉRLETEK SZÁMLÁLÁSA.
		$user_attempts_key = 'wprefi_login_attempts_user_' . $username_clean;
		$user_attempts     = get_transient( $user_attempts_key );
		$user_attempts     = ( false === $user_attempts ) ? 1 : ( (int) $user_attempts + 1 );
		set_transient( $user_attempts_key, $user_attempts, HOUR_IN_SECONDS );

		// 4. IP KITILTÁS.
		if ( $ip_attempts >= $max_attempts ) {
			$lockout_until = time() + ( $lockout_duration * MINUTE_IN_SECONDS );
			set_transient( 'wprefi_lockout_ip_' . $ip_hash, $lockout_until, $lockout_duration * MINUTE_IN_SECONDS );
		}

		// 5. FELHASZNÁLÓNÉV KITILTÁS.
		if ( $user_attempts >= $max_attempts ) {
			$lockout_until = time() + ( $lockout_duration * MINUTE_IN_SECONDS );
			set_transient( 'wprefi_lockout_user_' . $username_clean, $lockout_until, $lockout_duration * MINUTE_IN_SECONDS );
		}
	}
);

add_action(
	'wp_login',
	function ( $user_login, $user ) {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';

		$ip_hash        = md5( $ip );
		$username_clean = sanitize_user( $user_login );

		delete_transient( 'wprefi_login_attempts_ip_' . $ip_hash );
		delete_transient( 'wprefi_login_attempts_user_' . $username_clean );
	},
	10,
	2
);
