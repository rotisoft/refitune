<?php
/**
 * XML-RPC teljes letiltása.
 *
 * - Letiltja az XML-RPC API-t (404-es válasz minden kérésre).
 * - Automatikusan eltávolítja az RSD (Really Simple Discovery) linket is,
 *   mivel az az XML-RPC felfedezését szolgálja.
 * - Security-through-obscurity: 404 válasz azt sugallja, hogy az xmlrpc.php
 *   nem is létezik, így az attackerek számára rejtve marad a blokkolás.
 *
 * Fontos: Az XML-RPC egy általános távoli API interfész, amit egyes pluginek
 * (pl. Jetpack) és alkalmazások használnak. Ha Jetpack szinkronizációt vagy
 * mobil alkalmazást használsz, ne aktiváld ezt a funkciót.
 *
 * @package RefinerPress
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Minden XML-RPC kérés 403-as választ kap.
add_filter( 'xmlrpc_enabled', '__return_false' );

// RSD link automatikus eltávolítása, mivel az XML-RPC discovery-t szolgálja.
remove_action( 'wp_head', 'rsd_link' );

// Ha valami mégis eljutna az xmlrpc_call hook-ig, leállítjuk 404-es válasszal.
add_action(
	'xmlrpc_call',
	static function (): void {
		wp_die(
			'404 Not Found',
			'404 Not Found',
			array( 'response' => 404 )
		);
	}
);
