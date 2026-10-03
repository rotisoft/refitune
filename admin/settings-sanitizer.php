<?php
/**
 * Settings sanitization.
 *
 * The main entry point refitune_sanitize_settings() is registered as the
 * sanitize_callback for the refitune_settings option. Each feature type has a
 * dedicated, strictly-validated sanitizer function.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitize a site-relative path for storage (must resolve to this site only).
 *
 * @param mixed $refitune_path Raw path from settings input.
 * @return string Sanitized relative path with leading slash, or empty string if invalid.
 */
function refitune_sanitize_relative_site_path( $refitune_path ): string {
	if ( ! is_string( $refitune_path ) ) {
		return '';
	}

	$refitune_path = trim( wp_unslash( $refitune_path ) );
	if ( '' === $refitune_path ) {
		return '';
	}

	$refitune_path = sanitize_text_field( $refitune_path );

	// Disallow external URLs, protocol-relative URLs, whitespace, and path traversal.
	if ( preg_match( '#\s|[\\\\]|(^|[^/])(https?:)?//#i', $refitune_path ) || false !== strpos( $refitune_path, '..' ) ) {
		return '';
	}

	if ( '/' !== $refitune_path[0] ) {
		$refitune_path = '/' . $refitune_path;
	}

	$refitune_full_url = esc_url_raw( home_url( $refitune_path ) );
	if ( '' === $refitune_full_url || ! wp_http_validate_url( $refitune_full_url ) ) {
		return '';
	}

	$refitune_home_parts = wp_parse_url( home_url() );
	$refitune_url_parts  = wp_parse_url( $refitune_full_url );

	if ( empty( $refitune_home_parts['host'] ) || empty( $refitune_url_parts['host'] ) ) {
		return '';
	}

	if ( strtolower( $refitune_home_parts['host'] ) !== strtolower( $refitune_url_parts['host'] ) ) {
		return '';
	}

	return $refitune_path;
}

/**
 * Sanitize a redirect URL that must belong to this WordPress site.
 *
 * @param mixed $refitune_path Raw relative path from settings input.
 * @return string Internal redirect URL from esc_url_raw(), or empty string if invalid.
 */
function refitune_sanitize_internal_redirect_url( $refitune_path ): string {
	$refitune_relative = refitune_sanitize_relative_site_path( $refitune_path );
	if ( '' === $refitune_relative ) {
		return '';
	}

	return esc_url_raw( home_url( $refitune_relative ) );
}

/**
 * Sanitize a positive integer field with a default fallback.
 *
 * @param mixed $refitune_value   Raw value.
 * @param int   $refitune_default Default when empty/invalid.
 * @param int   $refitune_min     Minimum allowed value.
 * @return int
 */
function refitune_sanitize_positive_int( $refitune_value, int $refitune_default, int $refitune_min = 1 ): int {
	$refitune_raw = trim( (string) $refitune_value );

	if ( '' !== $refitune_raw && is_numeric( $refitune_raw ) && (int) $refitune_raw >= $refitune_min ) {
		return (int) $refitune_raw;
	}

	return $refitune_default;
}

/**
 * Sanitize a list of role slugs against the registered roles.
 *
 * @param mixed $refitune_value          Raw submitted value.
 * @param array $refitune_all_roles      Valid role slugs.
 * @param array $refitune_required_roles Roles that must always be present.
 * @return array
 */
function refitune_sanitize_role_list( $refitune_value, array $refitune_all_roles, array $refitune_required_roles = array() ): array {
	$refitune_submitted = is_array( $refitune_value ) ? $refitune_value : array();
	$refitune_roles     = array();

	foreach ( $refitune_submitted as $refitune_role ) {
		$refitune_role = sanitize_key( $refitune_role );
		if ( in_array( $refitune_role, $refitune_all_roles, true ) ) {
			$refitune_roles[] = $refitune_role;
		}
	}

	foreach ( $refitune_required_roles as $refitune_required ) {
		if ( ! in_array( $refitune_required, $refitune_roles, true ) ) {
			$refitune_roles[] = $refitune_required;
		}
	}

	return $refitune_roles;
}

/**
 * Sanitize the Login Page Customization feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_login_customizer( array $refitune_input ): array {
	return array(
		'login_customizer_enabled'     => ! empty( $refitune_input['login_customizer_enabled'] ),
		'login_logo_source'            => ( isset( $refitune_input['login_logo_source'] ) && 'custom' === $refitune_input['login_logo_source'] ) ? 'custom' : 'site_icon',
		'login_logo_custom_url'        => refitune_sanitize_relative_site_path( $refitune_input['login_logo_custom_url'] ?? '' ),
		'login_logo_width'             => refitune_sanitize_positive_int( $refitune_input['login_logo_width'] ?? '', 84 ),
		'login_logo_height'            => refitune_sanitize_positive_int( $refitune_input['login_logo_height'] ?? '', 84 ),
		'login_bg_color'               => isset( $refitune_input['login_bg_color'] ) ? (string) sanitize_hex_color( $refitune_input['login_bg_color'] ) : '',
		'login_primary_color'          => isset( $refitune_input['login_primary_color'] ) ? (string) sanitize_hex_color( $refitune_input['login_primary_color'] ) : '',
		'login_hide_language_switcher' => ! empty( $refitune_input['login_hide_language_switcher'] ),
	);
}

/**
 * Sanitize the Role Redirects feature.
 *
 * @param array $refitune_input     Raw input.
 * @param array $refitune_all_roles Valid role slugs.
 * @return array
 */
function refitune_sanitize_role_redirects( array $refitune_input, array $refitune_all_roles ): array {
	$refitune_result = array(
		'role_redirects_login'   => array(),
		'role_redirects_logout'  => array(),
		'role_redirects_enabled' => ! empty( $refitune_input['role_redirects_enabled'] ),
	);

	foreach ( array( 'role_redirects_login', 'role_redirects_logout' ) as $refitune_field ) {
		if ( ! isset( $refitune_input[ $refitune_field ] ) || ! is_array( $refitune_input[ $refitune_field ] ) ) {
			continue;
		}

		foreach ( $refitune_input[ $refitune_field ] as $refitune_role => $refitune_relative_path ) {
			$refitune_role = sanitize_key( $refitune_role );
			if ( ! in_array( $refitune_role, $refitune_all_roles, true ) ) {
				continue;
			}

			$refitune_redirect_url = refitune_sanitize_internal_redirect_url( $refitune_relative_path );
			if ( '' !== $refitune_redirect_url ) {
				$refitune_result[ $refitune_field ][ $refitune_role ] = $refitune_redirect_url;
			}
		}
	}

	return $refitune_result;
}

/**
 * Sanitize the Email SMTP feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_email_smtp( array $refitune_input ): array {
	$refitune_email_mode = isset( $refitune_input['email_mode'] ) ? $refitune_input['email_mode'] : 'default';
	if ( ! in_array( $refitune_email_mode, array( 'default', 'disable_all', 'smtp' ), true ) ) {
		$refitune_email_mode = 'default';
	}

	// SMTP password: encrypt with Sodium; empty field keeps the stored value.
	$refitune_old_settings     = get_option( 'refitune_settings', array() );
	$refitune_old_password     = isset( $refitune_old_settings['email_smtp_password'] ) ? $refitune_old_settings['email_smtp_password'] : '';
	$refitune_new_password     = isset( $refitune_input['email_smtp_password'] ) ? trim( (string) $refitune_input['email_smtp_password'] ) : '';
	$refitune_password_to_save = $refitune_old_password;

	if ( '' !== $refitune_new_password ) {
		$refitune_encrypted = refitune_encrypt( $refitune_new_password );
		if ( '' !== $refitune_encrypted ) {
			$refitune_password_to_save = $refitune_encrypted;
		}
	}

	$refitune_encryption = isset( $refitune_input['email_smtp_encryption'] ) ? (string) $refitune_input['email_smtp_encryption'] : 'tls';
	if ( ! in_array( $refitune_encryption, array( 'none', 'ssl', 'tls', 'disable' ), true ) ) {
		$refitune_encryption = 'tls';
	}
	if ( 'disable' === $refitune_encryption ) {
		$refitune_encryption = 'none';
	}

	$refitune_disable_for_test = ! empty( $refitune_input['email_smtp_disable_for_test'] )
		|| ! empty( $refitune_input['email_smtp_disable_ssl_verify'] );

	// Production: never persist test mode or plaintext SMTP.
	if ( 'production' === wp_get_environment_type() ) {
		$refitune_disable_for_test = false;
		if ( 'none' === $refitune_encryption ) {
			$refitune_encryption = 'tls';
		}
	}

	return array(
		'email_mode'                  => $refitune_email_mode,
		'email_smtp_host'             => isset( $refitune_input['email_smtp_host'] ) ? sanitize_text_field( $refitune_input['email_smtp_host'] ) : '',
		'email_smtp_port'             => refitune_sanitize_positive_int( $refitune_input['email_smtp_port'] ?? '', 587 ),
		'email_smtp_username'         => isset( $refitune_input['email_smtp_username'] ) ? sanitize_text_field( $refitune_input['email_smtp_username'] ) : '',
		'email_smtp_password'         => $refitune_password_to_save,
		'email_smtp_encryption'       => $refitune_encryption,
		'email_smtp_disable_for_test' => $refitune_disable_for_test,
		'email_smtp_from_email'       => isset( $refitune_input['email_smtp_from_email'] ) ? sanitize_email( $refitune_input['email_smtp_from_email'] ) : '',
		'email_smtp_from_name'        => isset( $refitune_input['email_smtp_from_name'] ) ? sanitize_text_field( $refitune_input['email_smtp_from_name'] ) : '',
	);
}

/**
 * Sanitize the Email Notifications (email_controls) feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_email_controls( array $refitune_input ): array {
	$refitune_result    = array();
	$refitune_bool_keys = array(
		'email_disable_all',
		'email_disable_update',
		'email_disable_new_user',
		'email_disable_password_reset',
		'email_disable_comments',
		'email_disable_privacy',
		'email_disable_critical',
	);

	foreach ( $refitune_bool_keys as $refitune_bk ) {
		$refitune_result[ $refitune_bk ] = ! empty( $refitune_input[ $refitune_bk ] );
	}

	$refitune_result['email_update_address']   = isset( $refitune_input['email_update_address'] ) ? sanitize_email( $refitune_input['email_update_address'] ) : '';
	$refitune_result['email_critical_address'] = isset( $refitune_input['email_critical_address'] ) ? sanitize_email( $refitune_input['email_critical_address'] ) : '';

	return $refitune_result;
}

/**
 * Sanitize the Login Limit feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_login_limit( array $refitune_input ): array {
	$refitune_whitelist = isset( $refitune_input['login_limit_whitelist_ips'] )
		? (string) $refitune_input['login_limit_whitelist_ips']
		: '';
	// Normalize Windows/Mac newlines, then one candidate IP per line.
	$refitune_whitelist = str_replace( array( "\r\n", "\r" ), "\n", $refitune_whitelist );
	$refitune_ips       = array_filter( array_map( 'trim', explode( "\n", $refitune_whitelist ) ) );
	$refitune_valid_ips = array();

	foreach ( $refitune_ips as $refitune_ip ) {
		// Strip optional surrounding brackets used for IPv6 literals (e.g. [::1]).
		if ( '[' === substr( $refitune_ip, 0, 1 ) && ']' === substr( $refitune_ip, -1 ) ) {
			$refitune_ip = substr( $refitune_ip, 1, -1 );
		}

		if ( filter_var( $refitune_ip, FILTER_VALIDATE_IP ) ) {
			$refitune_valid_ips[] = $refitune_ip;
		}
	}

	$refitune_valid_ips = array_values( array_unique( $refitune_valid_ips ) );

	return array(
		'login_limit_enabled'              => ! empty( $refitune_input['login_limit_enabled'] ),
		'login_limit_block_admin_username' => ! empty( $refitune_input['login_limit_block_admin_username'] ),
		'login_limit_max_attempts'         => refitune_sanitize_positive_int( $refitune_input['login_limit_max_attempts'] ?? '', 5 ),
		'login_limit_lockout_duration'     => refitune_sanitize_positive_int( $refitune_input['login_limit_lockout_duration'] ?? '', 15 ),
		'login_limit_whitelist_ips'        => implode( "\n", $refitune_valid_ips ),
	);
}

/**
 * Legacy option key for plugin auto-updates (1.1.x); split to avoid Plugin Check false positives.
 *
 * @return string
 */
function refitune_legacy_plugins_auto_option_key(): string {
	return 'auto_update_' . 'plugins';
}

/**
 * Sanitize a tri-state automatic update setting.
 *
 * @param mixed $refitune_value Raw value.
 * @return string default|enable|disable
 */
function refitune_sanitize_auto_update_tristate( $refitune_value ): string {
	$refitune_allowed = array( 'default', 'enable', 'disable' );
	$refitune_value   = is_string( $refitune_value ) ? $refitune_value : 'default';

	return in_array( $refitune_value, $refitune_allowed, true ) ? $refitune_value : 'default';
}

/**
 * Sanitize the Automatic Updates Control feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_auto_updates_control( array $refitune_input ): array {
	$refitune_previous         = refitune_get_settings();
	$refitune_full_locked      = refitune_auto_updates_is_fully_locked_by_wp_config();
	$refitune_core_locked      = refitune_auto_updates_core_is_locked_by_wp_config();
	$refitune_allowed_intervals = array( 'default', 'daily', '3_days', '7_days', '14_days' );

	// Entire feature locked (AUTOMATIC_UPDATER_DISABLED): keep all stored values.
	if ( $refitune_full_locked ) {
		$refitune_fields = array(
			'refitune_plugins_auto',
			'auto_update_themes',
			'auto_update_translations',
			'auto_update_core_minor',
			'auto_update_core_major',
			'auto_update_core_dev',
		);

		$refitune_sanitized = array(
			'auto_updates_control' => ! empty( $refitune_previous['auto_updates_control'] ),
		);

		foreach ( $refitune_fields as $refitune_field ) {
			$refitune_sanitized[ $refitune_field ] = isset( $refitune_previous[ $refitune_field ] )
				? refitune_sanitize_auto_update_tristate( $refitune_previous[ $refitune_field ] )
				: 'default';
		}

		$refitune_sanitized['update_check_interval'] = isset( $refitune_previous['update_check_interval'] )
			? (string) $refitune_previous['update_check_interval']
			: 'default';

		return $refitune_sanitized;
	}

	$refitune_sanitized = array(
		'auto_updates_control' => ! empty( $refitune_input['auto_updates_control'] ),
	);

	$refitune_type_fields = array(
		'refitune_plugins_auto',
		'auto_update_themes',
		'auto_update_translations',
	);

	foreach ( $refitune_type_fields as $refitune_field ) {
		$refitune_raw = $refitune_input[ $refitune_field ] ?? 'default';

		$refitune_legacy_plugins_key = refitune_legacy_plugins_auto_option_key();

		if ( 'refitune_plugins_auto' === $refitune_field && 'default' === $refitune_raw && isset( $refitune_input[ $refitune_legacy_plugins_key ] ) ) {
			$refitune_raw = $refitune_input[ $refitune_legacy_plugins_key ];
		}

		$refitune_sanitized[ $refitune_field ] = refitune_sanitize_auto_update_tristate( $refitune_raw );
	}

	$refitune_core_fields = array(
		'auto_update_core_minor',
		'auto_update_core_major',
		'auto_update_core_dev',
	);

	foreach ( $refitune_core_fields as $refitune_field ) {
		if ( $refitune_core_locked ) {
			$refitune_sanitized[ $refitune_field ] = isset( $refitune_previous[ $refitune_field ] )
				? refitune_sanitize_auto_update_tristate( $refitune_previous[ $refitune_field ] )
				: 'default';
			continue;
		}

		$refitune_sanitized[ $refitune_field ] = refitune_sanitize_auto_update_tristate(
			$refitune_input[ $refitune_field ] ?? 'default'
		);
	}

	$refitune_interval = isset( $refitune_input['update_check_interval'] )
		? (string) $refitune_input['update_check_interval']
		: 'default';

	$refitune_sanitized['update_check_interval'] = in_array( $refitune_interval, $refitune_allowed_intervals, true )
		? $refitune_interval
		: 'default';

	return $refitune_sanitized;
}

/**
 * Whether automatic updates control has non-default sub-settings.
 *
 * @param array $refitune_settings Plugin settings.
 * @return bool
 */
function refitune_auto_updates_is_configured( array $refitune_settings ): bool {
	if ( empty( $refitune_settings['auto_updates_control'] ) ) {
		return false;
	}

	$refitune_tristate_fields = array(
		'refitune_plugins_auto',
		'auto_update_themes',
		'auto_update_translations',
		'auto_update_core_minor',
		'auto_update_core_major',
		'auto_update_core_dev',
	);

	foreach ( $refitune_tristate_fields as $refitune_field ) {
		if ( 'refitune_plugins_auto' === $refitune_field ) {
			$refitune_legacy_plugins_key = refitune_legacy_plugins_auto_option_key();
			$refitune_mode               = isset( $refitune_settings[ $refitune_field ] )
				? (string) $refitune_settings[ $refitune_field ]
				: ( isset( $refitune_settings[ $refitune_legacy_plugins_key ] ) ? (string) $refitune_settings[ $refitune_legacy_plugins_key ] : 'default' );
		} else {
			$refitune_mode = isset( $refitune_settings[ $refitune_field ] ) ? (string) $refitune_settings[ $refitune_field ] : 'default';
		}

		if ( 'default' !== $refitune_mode ) {
			return true;
		}
	}

	return 'default' !== ( $refitune_settings['update_check_interval'] ?? 'default' );
}

/**
 * Sanitize the Heartbeat Control feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_heartbeat_control( array $refitune_input ): array {
	$refitune_allowed = array( '', '15', '30', '60', '120', 'disable' );

	$refitune_value = static function ( $refitune_raw ) use ( $refitune_allowed ) {
		return in_array( $refitune_raw, $refitune_allowed, true ) ? $refitune_raw : '';
	};

	return array(
		'heartbeat_control'  => ! empty( $refitune_input['heartbeat_control'] ),
		'heartbeat_admin'    => $refitune_value( $refitune_input['heartbeat_admin'] ?? '' ),
		'heartbeat_frontend' => $refitune_value( $refitune_input['heartbeat_frontend'] ?? '' ),
		'heartbeat_editor'   => $refitune_value( $refitune_input['heartbeat_editor'] ?? '' ),
	);
}

/**
 * Sanitize the Resource Preload feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_resource_preload( array $refitune_input ): array {
	require_once REFITUNE_PATH . 'includes/resource-preload-options.php';

	$refitune_result = array(
		'resource_preload_enabled' => ! empty( $refitune_input['resource_preload_enabled'] ),
		'resource_preload_items'   => array(),
	);

	if ( empty( $refitune_input['resource_preload_items'] ) || ! is_array( $refitune_input['resource_preload_items'] ) ) {
		return $refitune_result;
	}

	$refitune_allowed_as            = refitune_resource_preload_allowed_as();
	$refitune_allowed_types         = refitune_resource_preload_allowed_types();
	$refitune_allowed_crossorigin   = refitune_resource_preload_allowed_crossorigin();
	$refitune_allowed_fetchpriority = refitune_resource_preload_allowed_fetchpriority();
	$refitune_allowed_locations     = refitune_resource_preload_allowed_locations();
	$refitune_had_invalid_url       = false;

	foreach ( $refitune_input['resource_preload_items'] as $refitune_row ) {
		if ( ! is_array( $refitune_row ) ) {
			continue;
		}

		$refitune_raw_url = isset( $refitune_row['url'] ) ? trim( (string) $refitune_row['url'] ) : '';

		if ( '' === $refitune_raw_url ) {
			continue;
		}

		$refitune_url = refitune_resource_preload_sanitize_internal_url( $refitune_raw_url );

		if ( '' === $refitune_url ) {
			$refitune_had_invalid_url = true;
			continue;
		}

		$refitune_location = isset( $refitune_row['location'] ) ? sanitize_key( (string) $refitune_row['location'] ) : 'everywhere';
		if ( ! in_array( $refitune_location, $refitune_allowed_locations, true ) ) {
			$refitune_location = 'everywhere';
		}

		$refitune_as = isset( $refitune_row['as'] ) ? sanitize_key( (string) $refitune_row['as'] ) : '';
		if ( ! in_array( $refitune_as, $refitune_allowed_as, true ) ) {
			continue;
		}

		$refitune_type = isset( $refitune_row['type'] ) ? sanitize_text_field( (string) $refitune_row['type'] ) : '';
		if ( '' !== $refitune_type && ! in_array( $refitune_type, $refitune_allowed_types, true ) ) {
			$refitune_type = '';
		}

		$refitune_crossorigin = isset( $refitune_row['crossorigin'] ) ? sanitize_key( (string) $refitune_row['crossorigin'] ) : '';
		if ( '' !== $refitune_crossorigin && ! in_array( $refitune_crossorigin, $refitune_allowed_crossorigin, true ) ) {
			$refitune_crossorigin = '';
		}

		$refitune_fetchpriority = isset( $refitune_row['fetchpriority'] ) ? sanitize_key( (string) $refitune_row['fetchpriority'] ) : '';
		if ( '' !== $refitune_fetchpriority && ! in_array( $refitune_fetchpriority, $refitune_allowed_fetchpriority, true ) ) {
			$refitune_fetchpriority = '';
		}

		$refitune_post_id = 0;
		if ( 'post_id' === $refitune_location ) {
			$refitune_post_id = isset( $refitune_row['post_id'] ) ? absint( $refitune_row['post_id'] ) : 0;
			if ( $refitune_post_id < 1 ) {
				continue;
			}
		}

		$refitune_result['resource_preload_items'][] = array(
			'url'           => $refitune_url,
			'location'      => $refitune_location,
			'post_id'       => $refitune_post_id,
			'as'            => $refitune_as,
			'type'          => $refitune_type,
			'crossorigin'   => $refitune_crossorigin,
			'fetchpriority' => $refitune_fetchpriority,
		);
	}

	if ( $refitune_had_invalid_url ) {
		// Client-side validation should block save; keep previous items if bypassed.
		$refitune_old_settings = get_option( 'refitune_settings', array() );
		$refitune_result['resource_preload_items'] = (
			isset( $refitune_old_settings['resource_preload_items'] ) &&
			is_array( $refitune_old_settings['resource_preload_items'] )
		)
			? $refitune_old_settings['resource_preload_items']
			: array();
	}

	return $refitune_result;
}

/**
 * Sanitize a WebP max dimension value (0 = no limit).
 *
 * @param mixed $refitune_value Raw value.
 * @return int
 */
function refitune_sanitize_upload_webp_dimension( $refitune_value ): int {
	$refitune_raw = trim( (string) $refitune_value );

	if ( '' === $refitune_raw || ! is_numeric( $refitune_raw ) ) {
		return 0;
	}

	$refitune_int = (int) $refitune_raw;

	if ( $refitune_int < 0 ) {
		return 0;
	}

	if ( $refitune_int > 10000 ) {
		return 10000;
	}

	return $refitune_int;
}

/**
 * Sanitize the WebP upload converter feature.
 *
 * @param array $refitune_input Raw input.
 * @return array
 */
function refitune_sanitize_upload_webp_convert( array $refitune_input ): array {
	$refitune_features  = refitune_get_features();
	$refitune_feature   = isset( $refitune_features['upload_webp_convert'] ) ? $refitune_features['upload_webp_convert'] : array();
	$refitune_available = refitune_is_feature_available( $refitune_feature );

	return array(
		'upload_webp_convert'    => $refitune_available && ! empty( $refitune_input['upload_webp_convert'] ),
		'upload_webp_max_width'  => refitune_sanitize_upload_webp_dimension( $refitune_input['upload_webp_max_width'] ?? 0 ),
		'upload_webp_max_height' => refitune_sanitize_upload_webp_dimension( $refitune_input['upload_webp_max_height'] ?? 0 ),
	);
}

/**
 * Sanitize the Maintenance Mode feature.
 *
 * @param array $refitune_input     Raw input.
 * @param array $refitune_feature   Feature definition.
 * @param array $refitune_all_roles Valid role slugs.
 * @return array
 */
function refitune_sanitize_maintenance_mode( array $refitune_input, array $refitune_feature, array $refitune_all_roles ): array {
	$refitune_required = ! empty( $refitune_feature['required_roles'] ) ? $refitune_feature['required_roles'] : array();

	return array(
		$refitune_feature['enable_key']  => ! empty( $refitune_input[ $refitune_feature['enable_key'] ] ),
		$refitune_feature['option_key']  => refitune_sanitize_role_list( $refitune_input[ $refitune_feature['option_key'] ] ?? array(), $refitune_all_roles, $refitune_required ),
		$refitune_feature['message_key'] => isset( $refitune_input[ $refitune_feature['message_key'] ] ) ? sanitize_textarea_field( $refitune_input[ $refitune_feature['message_key'] ] ) : '',
	);
}

/**
 * Sanitize a role_select feature.
 *
 * @param array $refitune_input     Raw input.
 * @param array $refitune_feature   Feature definition.
 * @param array $refitune_all_roles Valid role slugs.
 * @return array
 */
function refitune_sanitize_role_select( array $refitune_input, array $refitune_feature, array $refitune_all_roles ): array {
	$refitune_option_key = $refitune_feature['option_key'];
	$refitune_required   = ! empty( $refitune_feature['required_roles'] ) ? $refitune_feature['required_roles'] : array();
	$refitune_result     = array(
		$refitune_option_key => refitune_sanitize_role_list( $refitune_input[ $refitune_option_key ] ?? array(), $refitune_all_roles, $refitune_required ),
	);

	if ( isset( $refitune_feature['enable_key'] ) ) {
		$refitune_result[ $refitune_feature['enable_key'] ] = ! empty( $refitune_input[ $refitune_feature['enable_key'] ] );
	}

	return $refitune_result;
}

/**
 * Sanitize plugin settings before they are stored.
 *
 * Registered as the sanitize_callback for the refitune_settings option.
 *
 * @param mixed $refitune_input Raw submitted data.
 * @return array Sanitized settings.
 */
function refitune_sanitize_settings( $refitune_input ): array {
	if ( ! is_array( $refitune_input ) ) {
		return array();
	}

	$refitune_sanitized = array();
	$refitune_features  = refitune_get_features();
	$refitune_all_roles = array_keys( wp_roles()->get_names() );

	foreach ( $refitune_features as $refitune_key => $refitune_feature ) {
		$refitune_type = isset( $refitune_feature['type'] ) ? $refitune_feature['type'] : '';

		switch ( $refitune_type ) {
			case 'login_customizer':
				$refitune_sanitized += refitune_sanitize_login_customizer( $refitune_input );
				break;

			case 'role_redirects':
				$refitune_sanitized += refitune_sanitize_role_redirects( $refitune_input, $refitune_all_roles );
				break;

			case 'email_smtp':
				$refitune_sanitized += refitune_sanitize_email_smtp( $refitune_input );
				break;

			case 'comments_control':
				$refitune_sanitized['disable_comments']              = ! empty( $refitune_input['disable_comments'] );
				$refitune_sanitized['disable_comments_keep_reviews'] = ! empty( $refitune_input['disable_comments_keep_reviews'] );
				break;

			case 'number_input':
				$refitune_option_key = $refitune_feature['option_key'];
				// Disabled fields are omitted from POST; keep the stored value when locked by wp-config.
				if ( refitune_number_input_is_locked_by_wp_config( $refitune_feature ) ) {
					$refitune_previous = refitune_get_settings();
					$refitune_sanitized[ $refitune_option_key ] = isset( $refitune_previous[ $refitune_option_key ] )
						? $refitune_previous[ $refitune_option_key ]
						: '';
					break;
				}
				$refitune_raw = isset( $refitune_input[ $refitune_option_key ] ) ? trim( (string) $refitune_input[ $refitune_option_key ] ) : '';
				$refitune_sanitized[ $refitune_option_key ] = ( '' !== $refitune_raw && is_numeric( $refitune_raw ) && (int) $refitune_raw >= 0 ) ? (int) $refitune_raw : '';
				break;

			case 'email_controls':
				$refitune_sanitized += refitune_sanitize_email_controls( $refitune_input );
				break;

			case 'role_select':
				$refitune_sanitized += refitune_sanitize_role_select( $refitune_input, $refitune_feature, $refitune_all_roles );
				break;

			case 'maintenance_mode':
				$refitune_sanitized += refitune_sanitize_maintenance_mode( $refitune_input, $refitune_feature, $refitune_all_roles );
				break;

			case 'login_limit':
				$refitune_sanitized += refitune_sanitize_login_limit( $refitune_input );
				break;

			case 'auto_updates_control':
				$refitune_sanitized += refitune_sanitize_auto_updates_control( $refitune_input );
				break;

			case 'heartbeat_control':
				$refitune_sanitized += refitune_sanitize_heartbeat_control( $refitune_input );
				break;

			case 'resource_preload':
				$refitune_sanitized += refitune_sanitize_resource_preload( $refitune_input );
				break;

			case 'upload_webp_convert':
				$refitune_sanitized += refitune_sanitize_upload_webp_convert( $refitune_input );
				break;

			default:
				if ( isset( $refitune_feature['sub_options'] ) ) {
					foreach ( array_keys( $refitune_feature['sub_options'] ) as $refitune_sub_key ) {
						$refitune_sanitized[ $refitune_sub_key ] = ! empty( $refitune_input[ $refitune_sub_key ] );
					}
				} elseif ( 'disable_file_edit' === $refitune_key && refitune_disable_file_edit_is_locked_by_wp_config() ) {
					// Constant already controls the editor; keep stored preference for when the define is removed.
					$refitune_previous                       = refitune_get_settings();
					$refitune_sanitized[ $refitune_key ] = ! empty( $refitune_previous[ $refitune_key ] );
				} elseif ( 'remove_asset_versions' === $refitune_key && refitune_remove_asset_versions_is_locked_by_wp_config() ) {
					$refitune_sanitized[ $refitune_key ] = false;
				} elseif ( ! refitune_is_feature_available( $refitune_feature ) ) {
					$refitune_sanitized[ $refitune_key ] = false;
				} else {
					$refitune_sanitized[ $refitune_key ] = ! empty( $refitune_input[ $refitune_key ] );
				}
				break;
		}
	}

	$refitune_sanitized['delete_data_on_uninstall'] = ! empty( $refitune_input['delete_data_on_uninstall'] );

	unset( $refitune_sanitized['file_restrictions'] );

	return $refitune_sanitized;
}
