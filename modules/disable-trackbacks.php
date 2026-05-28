<?php
/**
 * Trackback és pingback teljes letiltása.
 *
 * - Új bejegyzéseknél alapértelmezetten zárja a pingeket.
 * - Minden meglévő bejegyzésnél lezárja a pingeket futásidőben.
 * - Eltávolítja a pingback metódusokat az XML-RPC-ből.
 * - Eltávolítja az X-Pingback HTTP fejlécet.
 * - Közvetlen trackback kéréseket visszautasítja (403).
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Új bejegyzéseknél alapértelmezetten zárja a pingeket.
add_filter( 'default_ping_status', '__return_false' );

// Minden bejegyzésnél (meglévők is) futásidőben zárja a pingeket.
add_filter( 'pings_open', '__return_false', 99 );

// Pingback metódusok eltávolítása az XML-RPC metóduslistából.
add_filter(
	'xmlrpc_methods',
	static function ( array $methods ): array {
		unset(
			$methods['pingback.ping'],
			$methods['pingback.extensions.getPingbacks']
		);
		return $methods;
	}
);

// X-Pingback HTTP fejléc eltávolítása.
add_filter(
	'wp_headers',
	static function ( array $headers ): array {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

// Pingback URL eltávolítása a bloginfo_url-ből (pl. wp_head pingback link).
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

// Közvetlen HTTP trackback kérések letiltása (403).
add_action(
	'wp',
	static function (): void {
		if ( is_trackback() ) {
			wp_die(
				esc_html__( 'A trackback le van tiltva ezen a webhelyen.', 'refitune' ),
				'',
				array( 'response' => 403 )
			);
		}
	}
);
