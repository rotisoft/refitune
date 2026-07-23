<?php
/**
 * Disable trackbacks and pingbacks completely.
 *
 * - Closes pings by default on new posts.
 * - Closes pings on every existing post at runtime.
 * - Removes the pingback methods from XML-RPC.
 * - Removes the X-Pingback HTTP header.
 * - Rejects direct trackback requests (403).
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Close pings by default on new posts.
add_filter( 'default_ping_status', '__return_false', 10 );

// Close pings on every post (existing ones too) at runtime.
add_filter( 'pings_open', '__return_false', 99 );

// Remove the pingback methods from the XML-RPC method list.
add_filter(
	'xmlrpc_methods',
	static function ( array $methods ): array {
		unset(
			$methods['pingback.ping'],
			$methods['pingback.extensions.getPingbacks']
		);
		return $methods;
	},
	10
);

// Remove the X-Pingback HTTP header.
add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	},
	10
);

// Remove the pingback URL from bloginfo_url (e.g. the wp_head pingback link).
add_filter(
	'bloginfo_url',
	static function ( string $output, string $show ): string {
		if ( 'pingback_url' === $show ) {
			return '';
		}
		return $output;
	},
	10,
	2
);

// Reject direct HTTP trackback requests (403).
add_action(
	'wp',
	static function (): void {
		if ( is_trackback() ) {
			wp_die(
				esc_html__( 'Trackbacks are disabled on this site.', 'refitune' ),
				'',
				array( 'response' => 403 )
			);
		}
	},
	10
);
