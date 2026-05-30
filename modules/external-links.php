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
function refitune_is_same_site_host( string $link_host, string $site_host ): bool {
	$link_host = strtolower( $link_host );
	$site_host = strtolower( $site_host );

	if ( '' === $link_host || '' === $site_host ) {
		return false;
	}

	if ( $link_host === $site_host ) {
		return true;
	}

	$suffix = '.' . $site_host;

	return strlen( $link_host ) > strlen( $suffix ) && substr( $link_host, -strlen( $suffix ) ) === $suffix;
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

	if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $content;
	}

	$site_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

	if ( '' === $site_host ) {
		return $content;
	}

	$processor = new WP_HTML_Tag_Processor( $content );

	while ( $processor->next_tag( array( 'tag_name' => 'A' ) ) ) {
		$href = $processor->get_attribute( 'href' );

		if ( ! is_string( $href ) || '' === $href ) {
			continue;
		}

		$parsed = wp_parse_url( $href );

		if ( empty( $parsed['host'] ) ) {
			continue;
		}

		if ( refitune_is_same_site_host( $parsed['host'], $site_host ) ) {
			continue;
		}

		if ( null === $processor->get_attribute( 'target' ) ) {
			$processor->set_attribute( 'target', '_blank' );
		}

		$rel = $processor->get_attribute( 'rel' );
		$rel = is_string( $rel ) ? preg_split( '/\s+/', trim( $rel ) ) : array();
		$rel = is_array( $rel ) ? $rel : array();

		foreach ( array( 'noopener', 'noreferrer' ) as $token ) {
			if ( ! in_array( $token, $rel, true ) ) {
				$rel[] = $token;
			}
		}

		$processor->set_attribute( 'rel', implode( ' ', $rel ) );
	}

	return $processor->get_updated_html();
}
add_filter( 'the_content', 'refitune_external_links_new_tab' );
add_filter( 'widget_text', 'refitune_external_links_new_tab' );
