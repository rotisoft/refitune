<?php
/**
 * Open external links in a new tab.
 *
 * Adds target="_blank" and rel="noopener noreferrer" to external links only.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Check whether a link host belongs to this site (exact or subdomain).
 *
 * @param string $link_host Host from the link URL.
 * @param string $site_host Site host from home_url().
 * @return bool
 */
function refitune_is_same_site_host( string $refitune_link_host, string $refitune_site_host ): bool {
	$refitune_link_host = strtolower( $refitune_link_host );
	$refitune_site_host = strtolower( $refitune_site_host );

	if ( '' === $refitune_link_host || '' === $refitune_site_host ) {
		return false;
	}

	if ( $refitune_link_host === $refitune_site_host ) {
		return true;
	}

	$refitune_suffix = '.' . $refitune_site_host;

	return strlen( $refitune_link_host ) > strlen( $refitune_suffix ) && substr( $refitune_link_host, -strlen( $refitune_suffix ) ) === $refitune_suffix;
}

/**
 * Add target and rel attributes to external links in HTML content.
 *
 * @param string $content HTML content.
 * @return string
 */
function refitune_external_links_new_tab( string $content ): string {
	if ( empty( $content ) || false === strpos( $content, '<a' ) ) {
		return $content;
	}

	// WP_HTML_Tag_Processor is available since WordPress 6.2 (plugin minimum).
	if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $content;
	}

	$refitune_site_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

	if ( '' === $refitune_site_host ) {
		return $content;
	}

	$refitune_processor = new WP_HTML_Tag_Processor( $content );

	while ( $refitune_processor->next_tag( array( 'tag_name' => 'A' ) ) ) {
		$refitune_href = $refitune_processor->get_attribute( 'href' );

		if ( ! is_string( $refitune_href ) || '' === $refitune_href ) {
			continue;
		}

		$refitune_parsed = wp_parse_url( $refitune_href );

		if ( empty( $refitune_parsed['host'] ) ) {
			continue;
		}

		if ( refitune_is_same_site_host( $refitune_parsed['host'], $refitune_site_host ) ) {
			continue;
		}

		if ( null === $refitune_processor->get_attribute( 'target' ) ) {
			$refitune_processor->set_attribute( 'target', '_blank' );
		}

		$refitune_rel = $refitune_processor->get_attribute( 'rel' );
		$refitune_rel = is_string( $refitune_rel ) ? preg_split( '/\s+/', trim( $refitune_rel ) ) : array();
		$refitune_rel = is_array( $refitune_rel ) ? $refitune_rel : array();

		foreach ( array( 'noopener', 'noreferrer' ) as $refitune_token ) {
			if ( ! in_array( $refitune_token, $refitune_rel, true ) ) {
				$refitune_rel[] = $refitune_token;
			}
		}

		$refitune_processor->set_attribute( 'rel', implode( ' ', $refitune_rel ) );
	}

	return $refitune_processor->get_updated_html();
}
add_filter( 'the_content', 'refitune_external_links_new_tab', 10 );
add_filter( 'widget_text', 'refitune_external_links_new_tab', 10 );
