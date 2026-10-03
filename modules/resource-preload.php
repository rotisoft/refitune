<?php
/**
 * Resource Preload - output link rel="preload" tags in wp_head.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once REFITUNE_PATH . 'includes/resource-preload-options.php';

/**
 * Whether the current request matches a preload row location.
 *
 * @param array $refitune_item Preload row.
 * @return bool
 */
function refitune_resource_preload_location_matches( array $refitune_item ): bool {
	$refitune_location = isset( $refitune_item['location'] ) ? (string) $refitune_item['location'] : 'everywhere';

	if ( 'everywhere' === $refitune_location ) {
		return true;
	}

	if ( 'front_page' === $refitune_location ) {
		return is_front_page();
	}

	if ( 'home' === $refitune_location ) {
		return is_home();
	}

	if ( 'post_id' === $refitune_location ) {
		$refitune_post_id = isset( $refitune_item['post_id'] ) ? absint( $refitune_item['post_id'] ) : 0;
		if ( $refitune_post_id < 1 ) {
			return false;
		}

		return is_singular() && (int) get_queried_object_id() === $refitune_post_id;
	}

	return false;
}

/**
 * Print configured preload link tags early in the document head.
 *
 * @return void
 */
function refitune_resource_preload_print_links(): void {
	if ( is_admin() ) {
		return;
	}

	$refitune_settings = refitune_get_settings();
	$refitune_items    = isset( $refitune_settings['resource_preload_items'] ) && is_array( $refitune_settings['resource_preload_items'] )
		? $refitune_settings['resource_preload_items']
		: array();

	if ( empty( $refitune_items ) ) {
		return;
	}

	$refitune_allowed_as            = refitune_resource_preload_allowed_as();
	$refitune_allowed_types         = refitune_resource_preload_allowed_types();
	$refitune_allowed_crossorigin   = refitune_resource_preload_allowed_crossorigin();
	$refitune_allowed_fetchpriority = refitune_resource_preload_allowed_fetchpriority();
	$refitune_output                = '';

	foreach ( $refitune_items as $refitune_item ) {
		if ( ! is_array( $refitune_item ) ) {
			continue;
		}

		if ( ! refitune_resource_preload_location_matches( $refitune_item ) ) {
			continue;
		}

		$refitune_url = isset( $refitune_item['url'] )
			? refitune_resource_preload_sanitize_internal_url( (string) $refitune_item['url'] )
			: '';
		$refitune_as  = isset( $refitune_item['as'] ) ? sanitize_key( (string) $refitune_item['as'] ) : '';

		if ( '' === $refitune_url || ! in_array( $refitune_as, $refitune_allowed_as, true ) ) {
			continue;
		}

		$refitune_attrs = array(
			'rel'  => 'preload',
			'href' => $refitune_url,
			'as'   => $refitune_as,
		);

		$refitune_type = isset( $refitune_item['type'] ) ? (string) $refitune_item['type'] : '';
		if ( '' !== $refitune_type && in_array( $refitune_type, $refitune_allowed_types, true ) ) {
			$refitune_attrs['type'] = $refitune_type;
		}

		$refitune_crossorigin = isset( $refitune_item['crossorigin'] ) ? sanitize_key( (string) $refitune_item['crossorigin'] ) : '';
		if ( '' !== $refitune_crossorigin && in_array( $refitune_crossorigin, $refitune_allowed_crossorigin, true ) ) {
			$refitune_attrs['crossorigin'] = $refitune_crossorigin;
		}

		$refitune_fetchpriority = isset( $refitune_item['fetchpriority'] ) ? sanitize_key( (string) $refitune_item['fetchpriority'] ) : '';
		if ( '' !== $refitune_fetchpriority && in_array( $refitune_fetchpriority, $refitune_allowed_fetchpriority, true ) ) {
			$refitune_attrs['fetchpriority'] = $refitune_fetchpriority;
		}

		$refitune_html = '<link';
		foreach ( $refitune_attrs as $refitune_attr_name => $refitune_attr_value ) {
			$refitune_escaped_value = ( 'href' === $refitune_attr_name )
				? esc_url( $refitune_attr_value )
				: esc_attr( $refitune_attr_value );

			$refitune_html .= sprintf(
				' %1$s="%2$s"',
				esc_attr( $refitune_attr_name ),
				$refitune_escaped_value
			);
		}
		$refitune_html .= " />\n";

		$refitune_output .= $refitune_html;
	}

	if ( '' === $refitune_output ) {
		return;
	}

	echo "<!-- RefiTune-Preload -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML comment marker.
	echo $refitune_output; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Attributes escaped above.
	echo "<!-- /RefiTune-Preload -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Static HTML comment marker.
}
add_action( 'wp_head', 'refitune_resource_preload_print_links', 2 );
