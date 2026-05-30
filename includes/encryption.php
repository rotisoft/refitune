<?php
/**
 * Titkosítási segédfüggvények Sodium könyvtárral.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the Sodium encryption library is available.
 *
 * @return bool
 */
function refitune_encryption_available(): bool {
	return function_exists( 'sodium_crypto_secretbox' )
		&& function_exists( 'sodium_crypto_secretbox_open' )
		&& defined( 'SODIUM_CRYPTO_SECRETBOX_NONCEBYTES' );
}

/**
 * Titkosítási kulcs generálása WordPress konstansokból.
 *
 * A kulcs a WordPress AUTH_KEY, SECURE_AUTH_KEY és NONCE_KEY
 * kombinációjából származik, így minden WordPress telepítés
 * egyedi kulcsot kap. A sodium_crypto_secretbox() 32 byte-os
 * kulcsot igényel.
 *
 * @return string 32 byte-os bináris kulcs.
 */
function refitune_get_encryption_key(): string {
	$key_material = AUTH_KEY . SECURE_AUTH_KEY . NONCE_KEY;
	return hash( 'sha256', $key_material, true );
}

/**
 * Szöveg titkosítása Sodium segítségével.
 *
 * @param string $plaintext A titkosítandó szöveg.
 * @return string Base64-kódolt titkosított szöveg (nonce + ciphertext).
 */
function refitune_encrypt( string $plaintext ): string {
	if ( '' === $plaintext ) {
		return '';
	}

	if ( ! refitune_encryption_available() ) {
		return '';
	}

	$key   = refitune_get_encryption_key();
	$nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

	$ciphertext = sodium_crypto_secretbox( $plaintext, $nonce, $key );

	// Nonce + ciphertext kombinálása és base64 kódolás.
	return base64_encode( $nonce . $ciphertext );
}

/**
 * Titkosított szöveg dekódolása.
 *
 * @param string $encrypted Base64-kódolt titkosított szöveg.
 * @return string Eredeti szöveg, vagy üres string hiba esetén.
 */
function refitune_decrypt( string $encrypted ): string {
	if ( '' === $encrypted ) {
		return '';
	}

	if ( ! refitune_encryption_available() ) {
		return '';
	}

	$decoded = base64_decode( $encrypted, true );
	if ( false === $decoded ) {
		return '';
	}

	$key        = refitune_get_encryption_key();
	$nonce_size = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

	if ( strlen( $decoded ) < $nonce_size ) {
		return '';
	}

	$nonce      = substr( $decoded, 0, $nonce_size );
	$ciphertext = substr( $decoded, $nonce_size );

	$plaintext = sodium_crypto_secretbox_open( $ciphertext, $nonce, $key );

	if ( false === $plaintext ) {
		// Dekódolás sikertelen (rossz kulcs, vagy korrupt adat).
		return '';
	}

	return $plaintext;
}
