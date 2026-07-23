<?php
/**
 * Login tweaks - generic error message for security reasons.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Return a generic error message on the login page.
 *
 * Prevents attackers from learning whether the username or
 * the password was incorrect.
 *
 * Must NOT override the Login Limit lockout messages.
 *
 * @param string $errors Error message(s).
 * @return string
 */
function refitune_login_error_message( $errors ) {
	// Keep lockout messages intact so users understand why login is blocked.
	if ( strpos( $errors, 'failed login attempts' ) !== false ||
	     strpos( $errors, 'temporarily locked' ) !== false ) {
		return $errors;
	}

	return __( 'Incorrect username or password.', 'refitune' );
}
add_filter( 'login_errors', 'refitune_login_error_message', 10 );
