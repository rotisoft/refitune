<?php
/**
 * Titkosítási segédfüggvények Sodium könyvtárral.
 *
 * @package RefinerPress_Toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
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
function wprefi_get_encryption_key(): string {
	$key_material = AUTH_KEY . SECURE_AUTH_KEY . NONCE_KEY;
	return hash( 'sha256', $key_material, true );
}

/**
 * Szöveg titkosítása Sodium segítségével.
 *
 * @param string $plaintext A titkosítandó szöveg.
 * @return string Base64-kódolt titkosított szöveg (nonce + ciphertext).
 */
function wprefi_encrypt( string $plaintext ): string {
	if ( '' === $plaintext ) {
		return '';
	}

	if ( ! function_exists( 'sodium_crypto_secretbox' ) ) {
		// Fallback: ha Sodium nem elérhető (bár PHP 7.2+-ban beépített).
		return $plaintext;
	}

	$key   = wprefi_get_encryption_key();
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
function wprefi_decrypt( string $encrypted ): string {
	if ( '' === $encrypted ) {
		return '';
	}

	if ( ! function_exists( 'sodium_crypto_secretbox_open' ) ) {
		// Fallback: ha Sodium nem elérhető, visszaadjuk az eredeti értéket
		// (ez történhet régi plain text jelszavaknál a migrálás után).
		return $encrypted;
	}

	$decoded = base64_decode( $encrypted, true );
	if ( false === $decoded ) {
		return '';
	}

	$key        = wprefi_get_encryption_key();
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
