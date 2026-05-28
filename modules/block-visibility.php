<?php
/**
 * Blokk láthatóság vezérlése eszköztípus szerint.
 *
 * Minden Gutenberg blokkhoz hozzáad egy „Láthatóság" opciót, amellyel
 * beállítható, hogy a blokk csak mobilon, csak asztali gépen, vagy mindenhol
 * jelenjen meg. A kizárt blokk teljesen kimarad a frontend HTML-ből.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A blokkszerkesztőhöz szükséges JS betöltése.
 *
 * @return void
 */
function refitune_block_visibility_editor_assets(): void {
	$js_file = REFITUNE_PATH . 'admin/js/block-visibility.js';

	wp_enqueue_script(
		'refitune-block-visibility',
		REFITUNE_URL . 'admin/js/block-visibility.js',
		array( 'wp-hooks', 'wp-compose', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
		file_exists( $js_file ) ? filemtime( $js_file ) : REFITUNE_VERSION,
		true
	);

	wp_set_script_translations(
		'refitune-block-visibility',
		'refitune',
		REFITUNE_PATH . 'languages'
	);
}
add_action( 'enqueue_block_editor_assets', 'refitune_block_visibility_editor_assets' );

/**
 * Frontend renderelés szűrése: kizárja a blokkot, ha az aktuális eszköz
 * nem felel meg a beállított láthatósági feltételnek.
 *
 * @param string $block_content A blokk HTML kimenete.
 * @param array  $block         A blokk adatai (név, attribútumok).
 * @return string Módosított (vagy üres) HTML kimenet.
 */
function refitune_filter_block_visibility( string $block_content, array $block ): string {
	$visibility = $block['attrs']['refituneVisibility'] ?? $block['attrs']['wprefiVisibility'] ?? '';

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
add_filter( 'render_block', 'refitune_filter_block_visibility', 10, 2 );
