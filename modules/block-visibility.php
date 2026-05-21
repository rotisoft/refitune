<?php
/**
 * Blokk láthatóság vezérlése eszköztípus szerint.
 *
 * Minden Gutenberg blokkhoz hozzáad egy „Láthatóság" opciót, amellyel
 * beállítható, hogy a blokk csak mobilon, csak asztali gépen, vagy mindenhol
 * jelenjen meg. A kizárt blokk teljesen kimarad a frontend HTML-ből.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A blokkszerkesztőhöz szükséges JS betöltése.
 *
 * @return void
 */
function wprefi_block_visibility_editor_assets(): void {
	$js_file = WPREFI_PATH . 'admin/js/block-visibility.js';

	wp_enqueue_script(
		'wprefi-block-visibility',
		WPREFI_URL . 'admin/js/block-visibility.js',
		array( 'wp-hooks', 'wp-compose', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
		file_exists( $js_file ) ? filemtime( $js_file ) : WPREFI_VERSION,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'wprefi_block_visibility_editor_assets' );

/**
 * Frontend renderelés szűrése: kizárja a blokkot, ha az aktuális eszköz
 * nem felel meg a beállított láthatósági feltételnek.
 *
 * @param string $block_content A blokk HTML kimenete.
 * @param array  $block         A blokk adatai (név, attribútumok).
 * @return string Módosított (vagy üres) HTML kimenet.
 */
function wprefi_filter_block_visibility( string $block_content, array $block ): string {
	$visibility = $block['attrs']['wprefiVisibility'] ?? '';

	if ( '' === $visibility ) {
		return $block_content;
	}

	$is_mobile = wp_is_mobile();

	if ( 'mobile' === $visibility && ! $is_mobile ) {
		return '';
	}

	if ( 'desktop' === $visibility && $is_mobile ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block', 'wprefi_filter_block_visibility', 10, 2 );
