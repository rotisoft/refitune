<?php
/**
 * Bejelentkezési tweaks – általánosított hibaüzenet biztonsági okokból.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Általános hibaüzenet visszaadása a bejelentkezési oldalon.
 *
 * Megakadályozza, hogy a támadók kiderítséék, a felhasználónév
 * vagy a jelszó volt-e helytelen.
 *
 * NE írja felül a Login Limit lockout üzeneteket!
 *
 * @param string $errors Hibaüzenet(ek).
 * @return string
 */
function refitune_login_error_message( $errors ) {
	// Ha a hibaüzenet tartalmazza a lockout szöveget, ne írjuk felül!
	if ( strpos( $errors, 'login_locked' ) !== false || 
	     strpos( $errors, 'failed login attempts' ) !== false ||
	     strpos( $errors, 'temporarily locked' ) !== false ) {
		return $errors;
	}
	
	return __( 'Incorrect username or password.', 'refitune' );
}
add_filter( 'login_errors', 'refitune_login_error_message' );
