<?php
/**
 * Dynamic Year Shortcodes
 *
 * Provides two shortcodes:
 * 1. [refi-year] - Displays the current year
 * 2. [refi-year from="2006"] - Displays the difference between current year and "from" year
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the [refi-year] shortcode.
 *
 * Usage:
 * - [refi-year] => 2026 (current year)
 * - [refi-year from="2006"] => 20 (2026 - 2006 = 20)
 *
 * @param array $atts Shortcode attributes.
 * @return string The year or duration.
 */
function refitune_year_shortcode( $atts ): string {
	$refitune_atts = shortcode_atts(
		array(
			'from' => '',
		),
		$atts,
		'refi-year'
	);

	$refitune_current_year = (int) gmdate( 'Y' );

	// If 'from' attribute is provided, calculate duration.
	if ( ! empty( $refitune_atts['from'] ) && is_numeric( $refitune_atts['from'] ) ) {
		$refitune_from_year = (int) $refitune_atts['from'];
		$refitune_duration  = $refitune_current_year - $refitune_from_year;

		// Only return positive durations.
		return $refitune_duration > 0 ? (string) $refitune_duration : '0';
	}

	// Otherwise, return the current year.
	return (string) $refitune_current_year;
}
add_shortcode( 'refi-year', 'refitune_year_shortcode' );
