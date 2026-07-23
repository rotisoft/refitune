<?php
/**
 * Allowlist-based SVG sanitizer.
 *
 * Parses SVG markup with DOMDocument and removes any element, attribute, or
 * reference that is not explicitly allowed. External entity loading is disabled
 * to prevent XXE attacks.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the SVG sanitizer can run in this environment.
 *
 * @return bool
 */
function refitune_svg_sanitizer_available(): bool {
	return class_exists( 'DOMDocument' ) && function_exists( 'libxml_use_internal_errors' );
}

/**
 * Allowed SVG element names (lowercase, local name without namespace prefix).
 *
 * @return array
 */
function refitune_svg_allowed_elements(): array {
	return array(
		'svg', 'g', 'defs', 'symbol', 'use', 'switch', 'title', 'desc', 'metadata',
		'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
		'text', 'tspan', 'textpath', 'tref',
		'lineargradient', 'radialgradient', 'stop', 'pattern',
		'clippath', 'mask', 'marker', 'view',
		'filter', 'fegaussianblur', 'feoffset', 'feblend', 'fecolormatrix',
		'fecomponenttransfer', 'fefunca', 'fefuncb', 'fefuncg', 'fefuncr',
		'fecomposite', 'feconvolvematrix', 'fediffuselighting', 'fedisplacementmap',
		'fedistantlight', 'feflood', 'femerge', 'femergenode', 'femorphology',
		'fepointlight', 'fespecularlighting', 'fespotlight', 'fetile', 'feturbulence',
		'image',
	);
}

/**
 * Attribute names that must never be allowed (event handlers handled separately).
 *
 * @return array
 */
function refitune_svg_blocked_attributes(): array {
	return array(
		'xlink:actuate', 'xlink:arcrole', 'xlink:role', 'xlink:show', 'xlink:title',
		'contentscripttype', 'contentstyletype',
	);
}

/**
 * Sanitize raw SVG markup.
 *
 * @param string $refitune_svg Raw SVG file content.
 * @return string|false Sanitized SVG markup, or false when it cannot be made safe.
 */
function refitune_sanitize_svg_markup( string $refitune_svg ) {
	if ( '' === trim( $refitune_svg ) || ! refitune_svg_sanitizer_available() ) {
		return false;
	}

	$refitune_max_bytes = (int) apply_filters( 'refitune_svg_max_bytes', 512 * 1024 );

	if ( $refitune_max_bytes > 0 && strlen( $refitune_svg ) > $refitune_max_bytes ) {
		return false;
	}

	// Strip UTF-8 BOM.
	$refitune_svg = preg_replace( '/^\xEF\xBB\xBF/', '', $refitune_svg );

	// Reject binary/compressed payloads (e.g. gzipped svgz served as svg).
	if ( false !== strpos( $refitune_svg, "\x00" ) || 0 === strpos( $refitune_svg, "\x1f\x8b" ) ) {
		return false;
	}

	// Reject DOCTYPE/ENTITY declarations outright (XXE / billion laughs).
	if ( preg_match( '/<!(?:DOCTYPE|ENTITY)/i', $refitune_svg ) ) {
		return false;
	}

	$refitune_libxml_previous = libxml_use_internal_errors( true );

	if ( function_exists( 'libxml_disable_entity_loader' ) && PHP_VERSION_ID < 80000 ) {
		// phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Needed for XXE protection on PHP < 8.
		$refitune_entity_loader_previous = libxml_disable_entity_loader( true );
	}

	$refitune_dom = new DOMDocument();
	$refitune_dom->preserveWhiteSpace = false;
	$refitune_dom->strictErrorChecking = false;

	$refitune_load_options = 0;
	if ( defined( 'LIBXML_NONET' ) ) {
		$refitune_load_options |= LIBXML_NONET;
	}
	if ( defined( 'LIBXML_NOENT' ) ) {
		// Do NOT expand entities; we already rejected ENTITY above.
		$refitune_load_options |= 0;
	}

	$refitune_loaded = $refitune_dom->loadXML( $refitune_svg, $refitune_load_options );

	if ( isset( $refitune_entity_loader_previous ) ) {
		// phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Restore previous state on PHP < 8.
		libxml_disable_entity_loader( $refitune_entity_loader_previous );
	}
	libxml_clear_errors();
	libxml_use_internal_errors( $refitune_libxml_previous );

	if ( ! $refitune_loaded || ! $refitune_dom->documentElement ) {
		return false;
	}

	if ( 'svg' !== strtolower( $refitune_dom->documentElement->localName ) ) {
		return false;
	}

	$refitune_allowed_elements   = refitune_svg_allowed_elements();
	$refitune_blocked_attributes = refitune_svg_blocked_attributes();
	$refitune_limits             = array(
		'nodes'   => 0,
		'max_nodes' => (int) apply_filters( 'refitune_svg_max_nodes', 2000 ),
		'max_depth' => (int) apply_filters( 'refitune_svg_max_depth', 32 ),
	);

	if ( ! refitune_svg_clean_node( $refitune_dom->documentElement, $refitune_allowed_elements, $refitune_blocked_attributes, $refitune_limits, 0 ) ) {
		return false;
	}

	$refitune_output = $refitune_dom->saveXML( $refitune_dom->documentElement );

	if ( false === $refitune_output || '' === trim( (string) $refitune_output ) ) {
		return false;
	}

	return $refitune_output;
}

/**
 * Recursively strip disallowed elements and attributes from a node.
 *
 * @param DOMNode $refitune_node               Current node.
 * @param array   $refitune_allowed_elements   Allowed lowercase local element names.
 * @param array   $refitune_blocked_attributes Explicitly blocked attribute names.
 * @param array   $refitune_limits             Mutable node/depth budget.
 * @param int     $refitune_depth              Current nesting depth.
 * @return bool False when complexity limits are exceeded.
 */
function refitune_svg_clean_node( DOMNode $refitune_node, array $refitune_allowed_elements, array $refitune_blocked_attributes, array &$refitune_limits, int $refitune_depth ): bool {
	++$refitune_limits['nodes'];

	if ( $refitune_limits['max_nodes'] > 0 && $refitune_limits['nodes'] > $refitune_limits['max_nodes'] ) {
		return false;
	}

	if ( $refitune_limits['max_depth'] > 0 && $refitune_depth > $refitune_limits['max_depth'] ) {
		return false;
	}

	// Process children first (collect into a static array because the live
	// NodeList mutates as nodes are removed).
	$refitune_children = array();
	foreach ( $refitune_node->childNodes as $refitune_child ) {
		$refitune_children[] = $refitune_child;
	}

	foreach ( $refitune_children as $refitune_child ) {
		if ( XML_ELEMENT_NODE === $refitune_child->nodeType ) {
			$refitune_local = strtolower( $refitune_child->localName );

			// Remove foreign namespaces (e.g. inkscape, sodipodi) and disallowed tags.
			$refitune_namespace = $refitune_child->namespaceURI;
			$refitune_is_svg_ns = ( null === $refitune_namespace || 'http://www.w3.org/2000/svg' === $refitune_namespace );

			if ( ! $refitune_is_svg_ns || ! in_array( $refitune_local, $refitune_allowed_elements, true ) ) {
				$refitune_node->removeChild( $refitune_child );
				continue;
			}

			refitune_svg_clean_attributes( $refitune_child, $refitune_blocked_attributes );

			if ( $refitune_child->hasChildNodes() ) {
				if ( ! refitune_svg_clean_node( $refitune_child, $refitune_allowed_elements, $refitune_blocked_attributes, $refitune_limits, $refitune_depth + 1 ) ) {
					return false;
				}
			}
		} elseif ( XML_PI_NODE === $refitune_child->nodeType || XML_COMMENT_NODE === $refitune_child->nodeType ) {
			// Drop processing instructions and comments.
			$refitune_node->removeChild( $refitune_child );
		}
	}

	return true;
}

/**
 * Remove dangerous attributes from an element.
 *
 * @param DOMElement $refitune_element            Element node.
 * @param array      $refitune_blocked_attributes Explicitly blocked attribute names.
 * @return void
 */
function refitune_svg_clean_attributes( DOMElement $refitune_element, array $refitune_blocked_attributes ): void {
	$refitune_to_remove = array();

	foreach ( iterator_to_array( $refitune_element->attributes ) as $refitune_attribute ) {
		$refitune_name  = strtolower( $refitune_attribute->nodeName );
		$refitune_value = (string) $refitune_attribute->nodeValue;

		// Event handlers (onload, onclick, ...).
		if ( 0 === strpos( $refitune_name, 'on' ) ) {
			$refitune_to_remove[] = $refitune_attribute;
			continue;
		}

		if ( in_array( $refitune_name, $refitune_blocked_attributes, true ) ) {
			$refitune_to_remove[] = $refitune_attribute;
			continue;
		}

		// href / xlink:href: allow only fragment refs and small raster data URIs.
		if ( 'href' === $refitune_name || 'xlink:href' === $refitune_name ) {
			if ( ! refitune_svg_is_safe_href( $refitune_value ) ) {
				$refitune_to_remove[] = $refitune_attribute;
				continue;
			}
		}

		// style attributes: drop external/protocol-relative CSS url() references.
		if ( 'style' === $refitune_name && refitune_svg_style_has_external_url( $refitune_value ) ) {
			$refitune_to_remove[] = $refitune_attribute;
			continue;
		}

		// Reject any value containing a script-like scheme or expression.
		$refitune_normalized = preg_replace( '/\s+/', '', strtolower( html_entity_decode( $refitune_value, ENT_QUOTES, 'UTF-8' ) ) );
		if ( false !== strpos( $refitune_normalized, 'javascript:' )
			|| false !== strpos( $refitune_normalized, 'vbscript:' )
			|| false !== strpos( $refitune_normalized, 'data:text/html' )
			|| false !== strpos( $refitune_normalized, '@import' )
			|| preg_match( '/expression\(|url\(\s*["\']?javascript:/', $refitune_normalized )
		) {
			$refitune_to_remove[] = $refitune_attribute;
		}
	}

	foreach ( $refitune_to_remove as $refitune_attribute ) {
		$refitune_element->removeAttributeNode( $refitune_attribute );
	}
}

/**
 * Whether a CSS style value contains an external or unsafe url() reference.
 *
 * @param string $refitune_value Style attribute value.
 * @return bool
 */
function refitune_svg_style_has_external_url( string $refitune_value ): bool {
	if ( ! preg_match_all( '/url\s*\(\s*([\'"]?)([^\'")]+)\1\s*\)/i', $refitune_value, $refitune_matches ) ) {
		return false;
	}

	foreach ( $refitune_matches[2] as $refitune_url ) {
		$refitune_url = trim( html_entity_decode( (string) $refitune_url, ENT_QUOTES, 'UTF-8' ) );

		if ( '' === $refitune_url ) {
			continue;
		}

		// Fragment-only references inside the same document are fine.
		if ( 0 === strpos( $refitune_url, '#' ) ) {
			continue;
		}

		return true;
	}

	return false;
}

/**
 * Whether an href value is a safe internal fragment or small raster data URI.
 *
 * External http(s), protocol-relative, and relative file paths are rejected so
 * sanitized SVGs stay self-contained.
 *
 * @param string $refitune_value Attribute value.
 * @return bool
 */
function refitune_svg_is_safe_href( string $refitune_value ): bool {
	$refitune_value = trim( html_entity_decode( $refitune_value, ENT_QUOTES, 'UTF-8' ) );

	if ( '' === $refitune_value ) {
		return false;
	}

	// Reject script-like schemes hidden behind URL encoding (e.g. %6aavascript:).
	$refitune_decoded = strtolower( preg_replace( '/\s+/', '', rawurldecode( $refitune_value ) ) );
	if ( false !== strpos( $refitune_decoded, 'javascript:' )
		|| false !== strpos( $refitune_decoded, 'vbscript:' )
		|| false !== strpos( $refitune_decoded, 'data:text' )
	) {
		return false;
	}

	// In-document fragment reference (e.g. #gradient).
	if ( 0 === strpos( $refitune_value, '#' ) ) {
		return true;
	}

	// Allow size-limited safe raster image data URIs only (no GIF to reduce attack surface).
	if ( preg_match( '#^data:image/(png|jpe?g|webp);base64,(.+)$#i', $refitune_value, $refitune_matches ) ) {
		$refitune_max_data_uri = (int) apply_filters( 'refitune_svg_max_data_uri_bytes', 100 * 1024 );
		$refitune_payload      = (string) $refitune_matches[2];

		// Base64 expands ~4/3; reject oversized payloads before decode.
		if ( $refitune_max_data_uri > 0 && strlen( $refitune_payload ) > (int) ceil( $refitune_max_data_uri * 4 / 3 ) ) {
			return false;
		}

		$refitune_decoded_bytes = base64_decode( $refitune_payload, true );

		if ( false === $refitune_decoded_bytes ) {
			return false;
		}

		if ( $refitune_max_data_uri > 0 && strlen( $refitune_decoded_bytes ) > $refitune_max_data_uri ) {
			return false;
		}

		return true;
	}

	return false;
}
