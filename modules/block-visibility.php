<?php
/**
 * Block visibility control by device type.
 *
 * Adds a "Visibility" option to every Gutenberg block that controls whether
 * the block renders on mobile only, desktop only, or everywhere. Excluded
 * blocks are omitted from the front-end HTML entirely.
 *
 * When this module is active, responses send Vary: User-Agent so full-page
 * caches that honour Vary can store separate mobile/desktop variants.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue the JS needed for the block editor.
 *
 * @return void
 */
function refitune_block_visibility_editor_assets(): void {
	$refitune_js_file = REFITUNE_PATH . 'admin/js/block-visibility.js';

	wp_enqueue_script(
		'refitune-block-visibility',
		REFITUNE_URL . 'admin/js/block-visibility.js',
		array( 'wp-hooks', 'wp-compose', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n' ),
		file_exists( $refitune_js_file ) ? filemtime( $refitune_js_file ) : REFITUNE_VERSION,
		true
	);

	wp_set_script_translations(
		'refitune-block-visibility',
		'refitune',
		REFITUNE_PATH . 'languages'
	);
}
add_action( 'enqueue_block_editor_assets', 'refitune_block_visibility_editor_assets', 10 );

/**
 * Ask caches to vary on User-Agent while this module can omit blocks by device.
 *
 * @param array $refitune_headers Associative array of headers to be sent.
 * @return array
 */
function refitune_block_visibility_vary_headers( array $refitune_headers ): array {
	if ( is_admin() ) {
		return $refitune_headers;
	}

	$refitune_vary = isset( $refitune_headers['Vary'] ) ? (string) $refitune_headers['Vary'] : '';

	if ( false === stripos( $refitune_vary, 'User-Agent' ) ) {
		$refitune_headers['Vary'] = '' === $refitune_vary
			? 'User-Agent'
			: $refitune_vary . ', User-Agent';
	}

	return $refitune_headers;
}
add_filter( 'wp_headers', 'refitune_block_visibility_vary_headers', 10 );

/**
 * Filter front-end rendering: exclude the block when the current device
 * does not match the configured visibility condition.
 *
 * @param string $block_content Block HTML output.
 * @param array  $block         Block data (name, attributes).
 * @return string Modified (or empty) HTML output.
 */
function refitune_filter_block_visibility( string $block_content, array $block ): string {
	$refitune_visibility = $block['attrs']['refituneVisibility'] ?? $block['attrs']['wprefiVisibility'] ?? '';

	if ( '' === $refitune_visibility ) {
		return $block_content;
	}

	$refitune_is_mobile = wp_is_mobile();

	if ( 'mobile' === $refitune_visibility && ! $refitune_is_mobile ) {
		return '';
	}

	if ( 'desktop' === $refitune_visibility && $refitune_is_mobile ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block', 'refitune_filter_block_visibility', 10, 2 );
