<?php
/**
 * Encryption helpers using the Sodium library.
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
 * Derive the encryption key from WordPress constants.
 *
 * The key is derived from the combination of the WordPress AUTH_KEY,
 * SECURE_AUTH_KEY, and NONCE_KEY constants, so every WordPress install
 * gets a unique key. sodium_crypto_secretbox() requires a 32-byte key.
 *
 * @return string 32-byte binary key.
 */
function refitune_get_encryption_key(): string {
	$refitune_key_material = AUTH_KEY . SECURE_AUTH_KEY . NONCE_KEY;
	return hash( 'sha256', $refitune_key_material, true );
}

/**
 * Encrypt a string with Sodium.
 *
 * @param string $refitune_plaintext Text to encrypt.
 * @return string Base64-encoded encrypted text (nonce + ciphertext).
 */
function refitune_encrypt( string $refitune_plaintext ): string {
	if ( '' === $refitune_plaintext ) {
		return '';
	}

	if ( ! refitune_encryption_available() ) {
		return '';
	}

	$refitune_key   = refitune_get_encryption_key();
	$refitune_nonce = random_bytes( SODIUM_CRYPTO_SECRETBOX_NONCEBYTES );

	$refitune_ciphertext = sodium_crypto_secretbox( $refitune_plaintext, $refitune_nonce, $refitune_key );

	// Combine nonce + ciphertext and base64 encode.
	return base64_encode( $refitune_nonce . $refitune_ciphertext );
}

/**
 * Decrypt an encrypted string.
 *
 * @param string $refitune_encrypted Base64-encoded encrypted text.
 * @return string Original text, or empty string on failure.
 */
function refitune_decrypt( string $refitune_encrypted ): string {
	if ( '' === $refitune_encrypted ) {
		return '';
	}

	if ( ! refitune_encryption_available() ) {
		return '';
	}

	$refitune_decoded = base64_decode( $refitune_encrypted, true );
	if ( false === $refitune_decoded ) {
		return '';
	}

	$refitune_key        = refitune_get_encryption_key();
	$refitune_nonce_size = SODIUM_CRYPTO_SECRETBOX_NONCEBYTES;

	if ( strlen( $refitune_decoded ) < $refitune_nonce_size ) {
		return '';
	}

	$refitune_nonce      = substr( $refitune_decoded, 0, $refitune_nonce_size );
	$refitune_ciphertext = substr( $refitune_decoded, $refitune_nonce_size );

	$refitune_plaintext = sodium_crypto_secretbox_open( $refitune_ciphertext, $refitune_nonce, $refitune_key );

	if ( false === $refitune_plaintext ) {
		// Decryption failed (wrong key or corrupted data).
		return '';
	}

	return $refitune_plaintext;
}
