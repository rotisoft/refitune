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
 * @param string $svg Raw SVG file content.
 * @return string|false Sanitized SVG markup, or false when it cannot be made safe.
 */
function refitune_sanitize_svg_markup( string $svg ) {
	if ( '' === trim( $svg ) || ! refitune_svg_sanitizer_available() ) {
		return false;
	}

	// Strip UTF-8 BOM.
	$svg = preg_replace( '/^\xEF\xBB\xBF/', '', $svg );

	// Reject binary/compressed payloads (e.g. gzipped svgz served as svg).
	if ( false !== strpos( $svg, "\x00" ) || 0 === strpos( $svg, "\x1f\x8b" ) ) {
		return false;
	}

	// Reject DOCTYPE/ENTITY declarations outright (XXE / billion laughs).
	if ( preg_match( '/<!(?:DOCTYPE|ENTITY)/i', $svg ) ) {
		return false;
	}

	$libxml_previous = libxml_use_internal_errors( true );

	if ( function_exists( 'libxml_disable_entity_loader' ) && PHP_VERSION_ID < 80000 ) {
		// phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Needed for XXE protection on PHP < 8.
		$entity_loader_previous = libxml_disable_entity_loader( true );
	}

	$dom = new DOMDocument();
	$dom->preserveWhiteSpace = false;
	$dom->strictErrorChecking = false;

	$load_options = 0;
	if ( defined( 'LIBXML_NONET' ) ) {
		$load_options |= LIBXML_NONET;
	}
	if ( defined( 'LIBXML_NOENT' ) ) {
		// Do NOT expand entities; we already rejected ENTITY above.
		$load_options |= 0;
	}

	$loaded = $dom->loadXML( $svg, $load_options );

	if ( isset( $entity_loader_previous ) ) {
		// phpcs:ignore Generic.PHP.DeprecatedFunctions.Deprecated -- Restore previous state on PHP < 8.
		libxml_disable_entity_loader( $entity_loader_previous );
	}
	libxml_clear_errors();
	libxml_use_internal_errors( $libxml_previous );

	if ( ! $loaded || ! $dom->documentElement ) {
		return false;
	}

	if ( 'svg' !== strtolower( $dom->documentElement->localName ) ) {
		return false;
	}

	$allowed_elements   = refitune_svg_allowed_elements();
	$blocked_attributes = refitune_svg_blocked_attributes();

	refitune_svg_clean_node( $dom->documentElement, $allowed_elements, $blocked_attributes );

	$output = $dom->saveXML( $dom->documentElement );

	if ( false === $output || '' === trim( (string) $output ) ) {
		return false;
	}

	return $output;
}

/**
 * Recursively strip disallowed elements and attributes from a node.
 *
 * @param DOMNode $node               Current node.
 * @param array   $allowed_elements   Allowed lowercase local element names.
 * @param array   $blocked_attributes Explicitly blocked attribute names.
 * @return void
 */
function refitune_svg_clean_node( DOMNode $node, array $allowed_elements, array $blocked_attributes ): void {
	// Process children first (collect into a static array because the live
	// NodeList mutates as nodes are removed).
	$children = array();
	foreach ( $node->childNodes as $child ) {
		$children[] = $child;
	}

	foreach ( $children as $child ) {
		if ( XML_ELEMENT_NODE === $child->nodeType ) {
			$local = strtolower( $child->localName );

			// Remove foreign namespaces (e.g. inkscape, sodipodi) and disallowed tags.
			$namespace      = $child->namespaceURI;
			$is_svg_ns      = ( null === $namespace || 'http://www.w3.org/2000/svg' === $namespace );
			$is_xlink_image = ( 'image' === $local );

			if ( ! $is_svg_ns || ! in_array( $local, $allowed_elements, true ) ) {
				$node->removeChild( $child );
				continue;
			}

			refitune_svg_clean_attributes( $child, $blocked_attributes );

			if ( $is_xlink_image || $child->hasChildNodes() ) {
				refitune_svg_clean_node( $child, $allowed_elements, $blocked_attributes );
			}
		} elseif ( XML_PI_NODE === $child->nodeType || XML_COMMENT_NODE === $child->nodeType ) {
			// Drop processing instructions and comments.
			$node->removeChild( $child );
		}
	}
}

/**
 * Remove dangerous attributes from an element.
 *
 * @param DOMElement $element            Element node.
 * @param array      $blocked_attributes Explicitly blocked attribute names.
 * @return void
 */
function refitune_svg_clean_attributes( DOMElement $element, array $blocked_attributes ): void {
	$to_remove = array();

	foreach ( iterator_to_array( $element->attributes ) as $attribute ) {
		$name  = strtolower( $attribute->nodeName );
		$value = (string) $attribute->nodeValue;

		// Event handlers (onload, onclick, ...).
		if ( 0 === strpos( $name, 'on' ) ) {
			$to_remove[] = $attribute;
			continue;
		}

		if ( in_array( $name, $blocked_attributes, true ) ) {
			$to_remove[] = $attribute;
			continue;
		}

		// href / xlink:href: allow only safe schemes and anchors.
		if ( 'href' === $name || 'xlink:href' === $name ) {
			if ( ! refitune_svg_is_safe_href( $value ) ) {
				$to_remove[] = $attribute;
				continue;
			}
		}

		// Reject any value containing a script-like scheme or expression.
		$normalized = preg_replace( '/\s+/', '', strtolower( html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ) ) );
		if ( false !== strpos( $normalized, 'javascript:' )
			|| false !== strpos( $normalized, 'vbscript:' )
			|| false !== strpos( $normalized, 'data:text/html' )
			|| false !== strpos( $normalized, '@import' )
			|| preg_match( '/expression\(|url\(\s*["\']?javascript:/', $normalized )
		) {
			$to_remove[] = $attribute;
		}
	}

	foreach ( $to_remove as $attribute ) {
		$element->removeAttributeNode( $attribute );
	}
}

/**
 * Whether an href value is a safe internal reference or image data URI.
 *
 * @param string $value Attribute value.
 * @return bool
 */
function refitune_svg_is_safe_href( string $value ): bool {
	$value = trim( html_entity_decode( $value, ENT_QUOTES, 'UTF-8' ) );

	if ( '' === $value ) {
		return false;
	}

	// In-document fragment reference (e.g. #gradient).
	if ( 0 === strpos( $value, '#' ) ) {
		return true;
	}

	// Allow safe raster image data URIs only.
	if ( preg_match( '#^data:image/(png|jpe?g|gif|webp);base64,#i', $value ) ) {
		return true;
	}

	// Allow same-scheme http(s) references.
	if ( preg_match( '#^https?://#i', $value ) ) {
		return true;
	}

	// Relative path without a scheme.
	if ( ! preg_match( '#^[a-z][a-z0-9+.-]*:#i', $value ) ) {
		return true;
	}

	return false;
}
