<?php
/**
 * Shared allowlists and option labels for Resource Preload.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allowed preload "as" attribute values (MDN / HTML link type preload).
 *
 * @return string[]
 */
function refitune_resource_preload_allowed_as(): array {
	return array( 'style', 'script', 'font', 'image', 'fetch', 'track', 'audio', 'video' );
}

/**
 * Allowed MIME type attribute values for preload.
 *
 * @return string[]
 */
function refitune_resource_preload_allowed_types(): array {
	return array(
		'text/css',
		'text/javascript',
		'font/woff2',
		'font/woff',
		'font/ttf',
		'font/otf',
		'image/jpeg',
		'image/png',
		'image/webp',
		'image/avif',
		'image/svg+xml',
		'image/gif',
		'application/json',
		'text/vtt',
		'video/mp4',
		'audio/mpeg',
	);
}

/**
 * Allowed crossorigin attribute values.
 *
 * @return string[]
 */
function refitune_resource_preload_allowed_crossorigin(): array {
	return array( 'anonymous', 'use-credentials' );
}

/**
 * Allowed fetchpriority attribute values.
 *
 * @return string[]
 */
function refitune_resource_preload_allowed_fetchpriority(): array {
	return array( 'auto', 'high', 'low' );
}

/**
 * Allowed location keys.
 *
 * @return string[]
 */
function refitune_resource_preload_allowed_locations(): array {
	return array( 'everywhere', 'front_page', 'home', 'post_id' );
}

/**
 * Location option labels for the settings UI.
 *
 * @return array<string, string>
 */
function refitune_resource_preload_location_labels(): array {
	return array(
		'everywhere' => __( 'Everywhere', 'refitune' ),
		'front_page' => __( 'Front page', 'refitune' ),
		'home'       => __( 'Blog page', 'refitune' ),
		'post_id'    => __( 'By post ID', 'refitune' ),
	);
}

/**
 * Whether a preload URL is a single internal absolute URL.
 *
 * Must start with home_url() or site_url(). Rejects whitespace/comma-separated lists.
 *
 * @param string $refitune_raw Raw URL input.
 * @return string Sanitized URL on success, empty string on failure.
 */
function refitune_resource_preload_sanitize_internal_url( string $refitune_raw ): string {
	$refitune_raw = trim( $refitune_raw );

	if ( '' === $refitune_raw ) {
		return '';
	}

	// One URL only - reject lists separated by whitespace, commas, or semicolons.
	if ( 1 === preg_match( '/[\s,;]/', $refitune_raw ) ) {
		return '';
	}

	$refitune_url = esc_url_raw( $refitune_raw );

	if ( '' === $refitune_url ) {
		return '';
	}

	$refitune_home = untrailingslashit( home_url( '/' ) );
	$refitune_site = untrailingslashit( site_url( '/' ) );

	if (
		0 !== strpos( $refitune_url, $refitune_home ) &&
		0 !== strpos( $refitune_url, $refitune_site )
	) {
		return '';
	}

	return $refitune_url;
}

/**
 * Flatten typography.fontFamilies from global settings (origin groups or flat list).
 *
 * @param mixed $refitune_font_families Raw fontFamilies value.
 * @return array<int, array> Font family definition objects.
 */
function refitune_flatten_font_family_definitions( $refitune_font_families ): array {
	if ( ! is_array( $refitune_font_families ) ) {
		return array();
	}

	$refitune_out = array();

	foreach ( $refitune_font_families as $refitune_value ) {
		if ( ! is_array( $refitune_value ) ) {
			continue;
		}

		// Single definition object.
		if ( isset( $refitune_value['fontFamily'] ) || isset( $refitune_value['slug'] ) || isset( $refitune_value['fontFace'] ) ) {
			$refitune_out[] = $refitune_value;
			continue;
		}

		// Origin group (theme / custom / default).
		foreach ( $refitune_value as $refitune_definition ) {
			if ( ! is_array( $refitune_definition ) ) {
				continue;
			}
			if ( isset( $refitune_definition['fontFamily'] ) || isset( $refitune_definition['slug'] ) || isset( $refitune_definition['fontFace'] ) ) {
				$refitune_out[] = $refitune_definition;
			}
		}
	}

	return $refitune_out;
}

/**
 * MIME type for a font file URL from its extension.
 *
 * @param string $refitune_url Font file URL.
 * @return string font/woff2, font/woff, or empty string.
 */
function refitune_resource_preload_type_from_font_url( string $refitune_url ): string {
	$refitune_path = (string) wp_parse_url( $refitune_url, PHP_URL_PATH );
	$refitune_ext  = strtolower( pathinfo( $refitune_path, PATHINFO_EXTENSION ) );

	if ( 'woff2' === $refitune_ext ) {
		return 'font/woff2';
	}

	if ( 'woff' === $refitune_ext ) {
		return 'font/woff';
	}

	return '';
}

/**
 * Prefer woff2, then woff, from a list of candidate font URLs.
 *
 * @param string[] $refitune_urls Candidate absolute URLs.
 * @return string Chosen URL, or empty string.
 */
function refitune_resource_preload_pick_font_url( array $refitune_urls ): string {
	$refitune_woff2 = '';
	$refitune_woff  = '';

	foreach ( $refitune_urls as $refitune_url ) {
		if ( ! is_string( $refitune_url ) || '' === $refitune_url ) {
			continue;
		}

		$refitune_sanitized = refitune_resource_preload_sanitize_internal_url( $refitune_url );
		if ( '' === $refitune_sanitized ) {
			continue;
		}

		$refitune_type = refitune_resource_preload_type_from_font_url( $refitune_sanitized );

		if ( 'font/woff2' === $refitune_type && '' === $refitune_woff2 ) {
			$refitune_woff2 = $refitune_sanitized;
		} elseif ( 'font/woff' === $refitune_type && '' === $refitune_woff ) {
			$refitune_woff = $refitune_sanitized;
		}
	}

	return '' !== $refitune_woff2 ? $refitune_woff2 : $refitune_woff;
}

/**
 * Build one preload item array for a font file URL.
 *
 * @param string $refitune_url Absolute font file URL.
 * @return array<string, mixed>
 */
function refitune_resource_preload_font_item_from_url( string $refitune_url ): array {
	return array(
		'url'           => $refitune_url,
		'location'      => 'everywhere',
		'post_id'       => 0,
		'as'            => 'font',
		'type'          => refitune_resource_preload_type_from_font_url( $refitune_url ),
		'crossorigin'   => 'anonymous',
		'fetchpriority' => 'auto',
	);
}

/**
 * Collect Font Library candidate URLs from a fontFace src list.
 *
 * @param mixed  $refitune_src      src value (string or list).
 * @param string $refitune_baseurl Font Library base URL.
 * @return string[] Absolute URLs under the Font Library directory.
 */
function refitune_resource_preload_font_library_srcs( $refitune_src, string $refitune_baseurl ): array {
	$refitune_srcs       = is_array( $refitune_src ) ? $refitune_src : array( $refitune_src );
	$refitune_candidates = array();

	foreach ( $refitune_srcs as $refitune_one ) {
		if ( ! is_string( $refitune_one ) || '' === $refitune_one ) {
			continue;
		}

		// Theme-bundled file:./ sources are not Font Library uploads.
		if ( 0 === strpos( $refitune_one, 'file:' ) ) {
			continue;
		}

		$refitune_url = refitune_resource_preload_sanitize_internal_url( $refitune_one );
		if ( '' === $refitune_url ) {
			continue;
		}

		if ( 0 !== strpos( $refitune_url, $refitune_baseurl ) ) {
			continue;
		}

		$refitune_candidates[] = $refitune_url;
	}

	return $refitune_candidates;
}

/**
 * Active Font Library font faces as Resource Preload row data.
 *
 * Reads fonts activated in Global Styles (and matching Font Library CPT faces)
 * that resolve to files under wp_get_font_dir().
 *
 * @return array<int, array<string, mixed>>
 */
function refitune_get_active_font_library_preload_items(): array {
	if ( ! function_exists( 'wp_get_font_dir' ) || ! function_exists( 'wp_get_global_settings' ) ) {
		return array();
	}

	$refitune_font_dir = wp_get_font_dir();
	$refitune_baseurl  = isset( $refitune_font_dir['baseurl'] ) ? untrailingslashit( (string) $refitune_font_dir['baseurl'] ) : '';

	if ( '' === $refitune_baseurl ) {
		return array();
	}

	$refitune_settings = wp_get_global_settings();
	if ( empty( $refitune_settings['typography']['fontFamilies'] ) ) {
		return array();
	}

	$refitune_definitions = refitune_flatten_font_family_definitions( $refitune_settings['typography']['fontFamilies'] );
	$refitune_items       = array();
	$refitune_seen        = array();
	$refitune_active_slugs = array();

	foreach ( $refitune_definitions as $refitune_definition ) {
		if ( ! empty( $refitune_definition['slug'] ) && is_string( $refitune_definition['slug'] ) ) {
			$refitune_active_slugs[ $refitune_definition['slug'] ] = true;
		}

		if ( empty( $refitune_definition['fontFace'] ) || ! is_array( $refitune_definition['fontFace'] ) ) {
			continue;
		}

		foreach ( $refitune_definition['fontFace'] as $refitune_face ) {
			if ( ! is_array( $refitune_face ) || empty( $refitune_face['src'] ) ) {
				continue;
			}

			$refitune_picked = refitune_resource_preload_pick_font_url(
				refitune_resource_preload_font_library_srcs( $refitune_face['src'], $refitune_baseurl )
			);

			if ( '' === $refitune_picked || isset( $refitune_seen[ $refitune_picked ] ) ) {
				continue;
			}

			$refitune_seen[ $refitune_picked ] = true;
			$refitune_items[]                  = refitune_resource_preload_font_item_from_url( $refitune_picked );
		}
	}

	// Supplement from Font Library CPT faces for active family slugs.
	if ( ! empty( $refitune_active_slugs ) && post_type_exists( 'wp_font_family' ) && post_type_exists( 'wp_font_face' ) ) {
		$refitune_families = get_posts(
			array(
				'post_type'              => 'wp_font_family',
				'post_status'            => 'publish',
				'posts_per_page'         => -1,
				'orderby'                => 'title',
				'order'                  => 'ASC',
				'no_found_rows'          => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $refitune_families as $refitune_family ) {
			if ( ! isset( $refitune_active_slugs[ $refitune_family->post_name ] ) ) {
				continue;
			}

			$refitune_faces = get_posts(
				array(
					'post_type'              => 'wp_font_face',
					'post_status'            => 'publish',
					'post_parent'            => $refitune_family->ID,
					'posts_per_page'         => -1,
					'no_found_rows'          => true,
					'update_post_meta_cache' => false,
					'update_post_term_cache' => false,
				)
			);

			foreach ( $refitune_faces as $refitune_face_post ) {
				$refitune_face_settings = json_decode( $refitune_face_post->post_content, true );
				if ( ! is_array( $refitune_face_settings ) || empty( $refitune_face_settings['src'] ) ) {
					continue;
				}

				$refitune_picked = refitune_resource_preload_pick_font_url(
					refitune_resource_preload_font_library_srcs( $refitune_face_settings['src'], $refitune_baseurl )
				);

				if ( '' === $refitune_picked || isset( $refitune_seen[ $refitune_picked ] ) ) {
					continue;
				}

				$refitune_seen[ $refitune_picked ] = true;
				$refitune_items[]                  = refitune_resource_preload_font_item_from_url( $refitune_picked );
			}
		}
	}

	return $refitune_items;
}

/**
 * Render one Resource Preload settings row.
 *
 * @param int|string $refitune_index Row index (use __INDEX__ placeholder string for template).
 * @param array      $refitune_item  Row values.
 * @return void
 */
function refitune_render_resource_preload_row( $refitune_index, array $refitune_item = array() ): void {
	$refitune_url           = isset( $refitune_item['url'] ) ? (string) $refitune_item['url'] : '';
	$refitune_location      = isset( $refitune_item['location'] ) ? (string) $refitune_item['location'] : 'everywhere';
	$refitune_post_id       = isset( $refitune_item['post_id'] ) ? (int) $refitune_item['post_id'] : 0;
	$refitune_as            = isset( $refitune_item['as'] ) ? (string) $refitune_item['as'] : 'style';
	$refitune_type          = isset( $refitune_item['type'] ) ? (string) $refitune_item['type'] : '';
	$refitune_crossorigin   = isset( $refitune_item['crossorigin'] ) ? (string) $refitune_item['crossorigin'] : '';
	$refitune_fetchpriority = isset( $refitune_item['fetchpriority'] ) ? (string) $refitune_item['fetchpriority'] : '';
	$refitune_name_prefix   = 'refitune_settings[resource_preload_items][' . $refitune_index . ']';
	$refitune_show_post_id  = ( 'post_id' === $refitune_location );
	$refitune_placeholder   = trailingslashit( home_url( '/' ) ) . 'wp-content/uploads/example.woff2';
	?>
	<div class="refitune-preload-row" data-refitune-preload-row>
		<div class="refitune-preload-url-wrap">
			<input
				type="url"
				class="regular-text refitune-preload-url"
				name="<?php echo esc_attr( $refitune_name_prefix ); ?>[url]"
				value="<?php echo esc_attr( $refitune_url ); ?>"
				placeholder="<?php echo esc_attr( $refitune_placeholder ); ?>"
				data-refitune-preload-url
			/>
			<p class="refitune-preload-url-error is-hidden" data-refitune-preload-url-error role="alert"></p>
		</div>

		<select
			class="refitune-preload-location"
			name="<?php echo esc_attr( $refitune_name_prefix ); ?>[location]"
			data-refitune-preload-location
			aria-label="<?php esc_attr_e( 'Preload location', 'refitune' ); ?>"
		>
			<?php foreach ( refitune_resource_preload_location_labels() as $refitune_loc_key => $refitune_loc_label ) : ?>
				<option value="<?php echo esc_attr( $refitune_loc_key ); ?>" <?php selected( $refitune_location, $refitune_loc_key ); ?>>
					<?php echo esc_html( $refitune_loc_label ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<input
			type="number"
			class="small-text refitune-preload-post-id<?php echo $refitune_show_post_id ? '' : ' is-hidden'; ?>"
			name="<?php echo esc_attr( $refitune_name_prefix ); ?>[post_id]"
			value="<?php echo $refitune_post_id > 0 ? esc_attr( (string) $refitune_post_id ) : ''; ?>"
			min="1"
			step="1"
			inputmode="numeric"
			data-refitune-preload-post-id
			placeholder="<?php esc_attr_e( 'Post ID', 'refitune' ); ?>"
			aria-label="<?php esc_attr_e( 'Post ID', 'refitune' ); ?>"
		/>

		<select
			name="<?php echo esc_attr( $refitune_name_prefix ); ?>[as]"
			data-refitune-preload-as
			aria-label="<?php esc_attr_e( 'as', 'refitune' ); ?>"
		>
			<?php foreach ( refitune_resource_preload_allowed_as() as $refitune_as_value ) : ?>
				<option value="<?php echo esc_attr( $refitune_as_value ); ?>" <?php selected( $refitune_as, $refitune_as_value ); ?>>
					<?php echo esc_html( $refitune_as_value ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select
			name="<?php echo esc_attr( $refitune_name_prefix ); ?>[type]"
			data-refitune-preload-type
			aria-label="<?php esc_attr_e( 'type', 'refitune' ); ?>"
		>
			<option value="" <?php selected( $refitune_type, '' ); ?>><?php esc_html_e( 'type (optional)', 'refitune' ); ?></option>
			<?php foreach ( refitune_resource_preload_allowed_types() as $refitune_type_value ) : ?>
				<option value="<?php echo esc_attr( $refitune_type_value ); ?>" <?php selected( $refitune_type, $refitune_type_value ); ?>>
					<?php echo esc_html( $refitune_type_value ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select
			name="<?php echo esc_attr( $refitune_name_prefix ); ?>[crossorigin]"
			data-refitune-preload-crossorigin
			aria-label="<?php esc_attr_e( 'crossorigin', 'refitune' ); ?>"
		>
			<option value="" <?php selected( $refitune_crossorigin, '' ); ?>><?php esc_html_e( 'crossorigin (default)', 'refitune' ); ?></option>
			<?php foreach ( refitune_resource_preload_allowed_crossorigin() as $refitune_co_value ) : ?>
				<option value="<?php echo esc_attr( $refitune_co_value ); ?>" <?php selected( $refitune_crossorigin, $refitune_co_value ); ?>>
					<?php echo esc_html( $refitune_co_value ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<select
			name="<?php echo esc_attr( $refitune_name_prefix ); ?>[fetchpriority]"
			data-refitune-preload-fetchpriority
			aria-label="<?php esc_attr_e( 'fetchpriority', 'refitune' ); ?>"
		>
			<option value="" <?php selected( $refitune_fetchpriority, '' ); ?>><?php esc_html_e( 'fetchpriority (default)', 'refitune' ); ?></option>
			<?php foreach ( refitune_resource_preload_allowed_fetchpriority() as $refitune_fp_value ) : ?>
				<option value="<?php echo esc_attr( $refitune_fp_value ); ?>" <?php selected( $refitune_fetchpriority, $refitune_fp_value ); ?>>
					<?php echo esc_html( $refitune_fp_value ); ?>
				</option>
			<?php endforeach; ?>
		</select>

		<button
			type="button"
			class="button-link-delete refitune-preload-remove"
			data-refitune-preload-remove
			aria-label="<?php esc_attr_e( 'Remove', 'refitune' ); ?>"
			title="<?php esc_attr_e( 'Remove', 'refitune' ); ?>"
		>
			<span class="dashicons dashicons-trash" aria-hidden="true"></span>
		</button>
	</div>
	<?php
}
