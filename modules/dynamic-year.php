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
	$atts = shortcode_atts(
		array(
			'from' => '',
		),
		$atts,
		'refi-year'
	);

	$current_year = (int) gmdate( 'Y' );

	// If 'from' attribute is provided, calculate duration.
	if ( ! empty( $atts['from'] ) && is_numeric( $atts['from'] ) ) {
		$from_year = (int) $atts['from'];
		$duration  = $current_year - $from_year;

		// Only return positive durations.
		return $duration > 0 ? (string) $duration : '0';
	}

	// Otherwise, return the current year.
	return (string) $current_year;
}
add_shortcode( 'refi-year', 'refitune_year_shortcode' );
