<?php
/**
 * External linkek automatikusan új ablakban nyílnak meg.
 *
 * Minden belső domainen kívülre mutató <a href="..."> taghez hozzáadja a
 * target="_blank" és rel="noopener noreferrer" attribútumokat.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A tartalom összes külső linkjéhez hozzáadja a target és rel attribútumokat.
 *
 * @param string $content HTML tartalom.
 * @return string Módosított HTML tartalom.
 */
function refitune_external_links_new_tab( string $content ): string {
	if ( empty( $content ) ) {
		return $content;
	}

	$site_host = (string) wp_parse_url( home_url(), PHP_URL_HOST );

	return preg_replace_callback(
		'/<a(\s[^>]*)>/i',
		static function ( array $matches ) use ( $site_host ): string {
			$attrs = $matches[1];

			if ( ! preg_match( '/\bhref\s*=\s*(["\'])([^"\']*)\1/i', $attrs, $href_match ) ) {
				return $matches[0];
			}

			$href   = $href_match[2];
			$parsed = wp_parse_url( $href );

			if ( ! isset( $parsed['host'] ) ) {
				return $matches[0];
			}

			if ( false !== strpos( $parsed['host'], $site_host ) ) {
				return $matches[0];
			}

			if ( ! preg_match( '/\btarget\s*=/i', $attrs ) ) {
				$attrs .= ' target="_blank"';
			}

			if ( ! preg_match( '/\brel\s*=/i', $attrs ) ) {
				$attrs .= ' rel="noopener noreferrer"';
			} else {
				$attrs = preg_replace_callback(
					'/\brel\s*=\s*(["\'])([^"\']*)\1/i',
					static function ( array $rel_match ): string {
						$val = $rel_match[2];
						if ( false === strpos( $val, 'noopener' ) ) {
							$val .= ' noopener';
						}
						if ( false === strpos( $val, 'noreferrer' ) ) {
							$val .= ' noreferrer';
						}
						return 'rel="' . trim( $val ) . '"';
					},
					$attrs
				);
			}

			return '<a' . $attrs . '>';
		},
		$content
	);
}
add_filter( 'the_content', 'refitune_external_links_new_tab' );
add_filter( 'widget_text', 'refitune_external_links_new_tab' );
