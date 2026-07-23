<?php
/**
 * Disable XML-RPC completely.
 *
 * - Disables the XML-RPC API (404 response for every request).
 * - Also removes the RSD (Really Simple Discovery) link automatically,
 *   since it exists to advertise XML-RPC.
 * - Security through obscurity: the 404 response suggests that xmlrpc.php
 *   does not exist, hiding the fact that it is blocked.
 *
 * Note: XML-RPC is a generic remote API used by some plugins (e.g. Jetpack)
 * and applications. Do not enable this feature when using Jetpack sync or
 * the WordPress mobile app.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Disable the XML-RPC API entirely.
add_filter( 'xmlrpc_enabled', '__return_false', 10 );

// Remove the RSD link automatically since it serves XML-RPC discovery.
remove_action( 'wp_head', 'rsd_link' );

// If anything still reaches the xmlrpc_call hook, stop it with a 404 response.
add_action(
	'xmlrpc_call',
	static function (): void {
		wp_die(
			'404 Not Found',
			'404 Not Found',
			array( 'response' => 404 )
		);
	},
	10
);
