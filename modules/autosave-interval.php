<?php
/**
 * Module: Auto-save Interval
 *
 * Adjusts editor autosave timing via targeted hooks (does not redefine AUTOSAVE_INTERVAL).
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get configured autosave interval in seconds.
 *
 * @return int Seconds, or 0 when not configured.
 */
function refitune_get_autosave_interval(): int {
	$settings = get_option( 'refitune_settings', array() );

	if ( ! isset( $settings['autosave_interval'] ) || '' === $settings['autosave_interval'] ) {
		return 0;
	}

	$seconds = (int) $settings['autosave_interval'];

	return max( 10, $seconds );
}

/**
 * Block editor autosave interval (seconds).
 *
 * @param array                   $editor_settings Editor settings.
 * @param WP_Block_Editor_Context $editor_context  Editor context.
 * @return array
 */
function refitune_filter_block_editor_autosave_interval( $editor_settings, $editor_context ) {
	$interval = refitune_get_autosave_interval();

	if ( $interval > 0 ) {
		$editor_settings['autosaveInterval'] = $interval;
	}

	return $editor_settings;
}
add_filter( 'block_editor_settings_all', 'refitune_filter_block_editor_autosave_interval', 10, 2 );

/**
 * Classic editor autosave script interval.
 *
 * Runs after core wp_just_in_time_script_localization() on admin_print_scripts.
 *
 * @return void
 */
function refitune_localize_classic_autosave_interval(): void {
	$interval = refitune_get_autosave_interval();

	if ( $interval <= 0 || ! wp_script_is( 'autosave', 'registered' ) ) {
		return;
	}

	wp_localize_script(
		'autosave',
		'autosaveL10n',
		array(
			'autosaveInterval' => $interval,
			'blog_id'          => get_current_blog_id(),
		)
	);
}
add_action( 'admin_print_scripts', 'refitune_localize_classic_autosave_interval', 100 );
