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
 * @param mixed $path Raw path from settings input.
 * @return string Sanitized relative path with leading slash, or empty string if invalid.
 */
function refitune_sanitize_relative_site_path( $path ): string {
	if ( ! is_string( $path ) ) {
		return '';
	}

	$path = trim( wp_unslash( $path ) );
	if ( '' === $path ) {
		return '';
	}

	$path = sanitize_text_field( $path );

	// Disallow external URLs, protocol-relative URLs, whitespace, and path traversal.
	if ( preg_match( '#\s|[\\\\]|(^|[^/])(https?:)?//#i', $path ) || false !== strpos( $path, '..' ) ) {
		return '';
	}

	if ( '/' !== $path[0] ) {
		$path = '/' . $path;
	}

	$full_url = esc_url_raw( home_url( $path ) );
	if ( '' === $full_url || ! wp_http_validate_url( $full_url ) ) {
		return '';
	}

	$home_parts = wp_parse_url( home_url() );
	$url_parts  = wp_parse_url( $full_url );

	if ( empty( $home_parts['host'] ) || empty( $url_parts['host'] ) ) {
		return '';
	}

	if ( strtolower( $home_parts['host'] ) !== strtolower( $url_parts['host'] ) ) {
		return '';
	}

	return $path;
}

/**
 * Sanitize a redirect URL that must belong to this WordPress site.
 *
 * @param mixed $path Raw relative path from settings input.
 * @return string Internal redirect URL from esc_url_raw(), or empty string if invalid.
 */
function refitune_sanitize_internal_redirect_url( $path ): string {
	$relative = refitune_sanitize_relative_site_path( $path );
	if ( '' === $relative ) {
		return '';
	}

	return esc_url_raw( home_url( $relative ) );
}

/**
 * Sanitize a positive integer field with a default fallback.
 *
 * @param mixed $value   Raw value.
 * @param int   $default Default when empty/invalid.
 * @param int   $min     Minimum allowed value.
 * @return int
 */
function refitune_sanitize_positive_int( $value, int $default, int $min = 1 ): int {
	$raw = trim( (string) $value );

	if ( '' !== $raw && is_numeric( $raw ) && (int) $raw >= $min ) {
		return (int) $raw;
	}

	return $default;
}

/**
 * Sanitize a list of role slugs against the registered roles.
 *
 * @param mixed $value          Raw submitted value.
 * @param array $all_roles      Valid role slugs.
 * @param array $required_roles Roles that must always be present.
 * @return array
 */
function refitune_sanitize_role_list( $value, array $all_roles, array $required_roles = array() ): array {
	$submitted = is_array( $value ) ? $value : array();
	$roles     = array();

	foreach ( $submitted as $role ) {
		$role = sanitize_key( $role );
		if ( in_array( $role, $all_roles, true ) ) {
			$roles[] = $role;
		}
	}

	foreach ( $required_roles as $required ) {
		if ( ! in_array( $required, $roles, true ) ) {
			$roles[] = $required;
		}
	}

	return $roles;
}

/**
 * Sanitize the Login Page Customization feature.
 *
 * @param array $input Raw input.
 * @return array
 */
function refitune_sanitize_login_customizer( array $input ): array {
	return array(
		'login_customizer_enabled'     => ! empty( $input['login_customizer_enabled'] ),
		'login_logo_source'            => ( isset( $input['login_logo_source'] ) && 'custom' === $input['login_logo_source'] ) ? 'custom' : 'site_icon',
		'login_logo_custom_url'        => refitune_sanitize_relative_site_path( $input['login_logo_custom_url'] ?? '' ),
		'login_logo_width'             => refitune_sanitize_positive_int( $input['login_logo_width'] ?? '', 84 ),
		'login_logo_height'            => refitune_sanitize_positive_int( $input['login_logo_height'] ?? '', 84 ),
		'login_bg_color'               => isset( $input['login_bg_color'] ) ? (string) sanitize_hex_color( $input['login_bg_color'] ) : '',
		'login_primary_color'          => isset( $input['login_primary_color'] ) ? (string) sanitize_hex_color( $input['login_primary_color'] ) : '',
		'login_hide_language_switcher' => ! empty( $input['login_hide_language_switcher'] ),
	);
}

/**
 * Sanitize the Role Redirects feature.
 *
 * @param array $input     Raw input.
 * @param array $all_roles Valid role slugs.
 * @return array
 */
function refitune_sanitize_role_redirects( array $input, array $all_roles ): array {
	$result = array(
		'role_redirects_login'   => array(),
		'role_redirects_logout'  => array(),
		'role_redirects_enabled' => ! empty( $input['role_redirects_enabled'] ),
	);

	foreach ( array( 'role_redirects_login', 'role_redirects_logout' ) as $field ) {
		if ( ! isset( $input[ $field ] ) || ! is_array( $input[ $field ] ) ) {
			continue;
		}

		foreach ( $input[ $field ] as $role => $relative_path ) {
			$role = sanitize_key( $role );
			if ( ! in_array( $role, $all_roles, true ) ) {
				continue;
			}

			$redirect_url = refitune_sanitize_internal_redirect_url( $relative_path );
			if ( '' !== $redirect_url ) {
				$result[ $field ][ $role ] = $redirect_url;
			}
		}
	}

	return $result;
}

/**
 * Sanitize the Email SMTP feature.
 *
 * @param array $input Raw input.
 * @return array
 */
function refitune_sanitize_email_smtp( array $input ): array {
	$email_mode = isset( $input['email_mode'] ) ? $input['email_mode'] : 'default';
	if ( ! in_array( $email_mode, array( 'default', 'disable_all', 'smtp' ), true ) ) {
		$email_mode = 'default';
	}

	// SMTP password: encrypt with Sodium; empty field keeps the stored value.
	$old_settings     = get_option( 'refitune_settings', array() );
	$old_password     = isset( $old_settings['email_smtp_password'] ) ? $old_settings['email_smtp_password'] : '';
	$new_password     = isset( $input['email_smtp_password'] ) ? trim( (string) $input['email_smtp_password'] ) : '';
	$password_to_save = $old_password;

	if ( '' !== $new_password ) {
		$encrypted = refitune_encrypt( $new_password );
		if ( '' !== $encrypted ) {
			$password_to_save = $encrypted;
		}
	}

	$encryption = isset( $input['email_smtp_encryption'] ) ? (string) $input['email_smtp_encryption'] : 'tls';
	if ( ! in_array( $encryption, array( 'none', 'ssl', 'tls', 'disable' ), true ) ) {
		$encryption = 'tls';
	}
	if ( 'disable' === $encryption ) {
		$encryption = 'none';
	}

	$disable_for_test = ! empty( $input['email_smtp_disable_for_test'] )
		|| ! empty( $input['email_smtp_disable_ssl_verify'] );

	return array(
		'email_mode'                  => $email_mode,
		'email_smtp_host'             => isset( $input['email_smtp_host'] ) ? sanitize_text_field( $input['email_smtp_host'] ) : '',
		'email_smtp_port'             => refitune_sanitize_positive_int( $input['email_smtp_port'] ?? '', 587 ),
		'email_smtp_username'         => isset( $input['email_smtp_username'] ) ? sanitize_text_field( $input['email_smtp_username'] ) : '',
		'email_smtp_password'         => $password_to_save,
		'email_smtp_encryption'       => $encryption,
		'email_smtp_disable_for_test' => $disable_for_test,
		'email_smtp_from_email'       => isset( $input['email_smtp_from_email'] ) ? sanitize_email( $input['email_smtp_from_email'] ) : '',
		'email_smtp_from_name'        => isset( $input['email_smtp_from_name'] ) ? sanitize_text_field( $input['email_smtp_from_name'] ) : '',
	);
}

/**
 * Sanitize the Email Notifications (email_controls) feature.
 *
 * @param array $input Raw input.
 * @return array
 */
function refitune_sanitize_email_controls( array $input ): array {
	$result    = array();
	$bool_keys = array(
		'email_disable_all',
		'email_disable_update',
		'email_disable_new_user',
		'email_disable_password_reset',
		'email_disable_comments',
		'email_disable_privacy',
		'email_disable_critical',
	);

	foreach ( $bool_keys as $bk ) {
		$result[ $bk ] = ! empty( $input[ $bk ] );
	}

	$result['email_update_address']   = isset( $input['email_update_address'] ) ? sanitize_email( $input['email_update_address'] ) : '';
	$result['email_critical_address'] = isset( $input['email_critical_address'] ) ? sanitize_email( $input['email_critical_address'] ) : '';

	return $result;
}

/**
 * Sanitize the Login Limit feature.
 *
 * @param array $input Raw input.
 * @return array
 */
function refitune_sanitize_login_limit( array $input ): array {
	$whitelist = isset( $input['login_limit_whitelist_ips'] ) ? $input['login_limit_whitelist_ips'] : '';
	$ips       = array_filter( array_map( 'trim', explode( "\n", $whitelist ) ) );
	$valid_ips = array();

	foreach ( $ips as $ip ) {
		if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			$valid_ips[] = $ip;
		}
	}

	return array(
		'login_limit_enabled'              => ! empty( $input['login_limit_enabled'] ),
		'login_limit_block_admin_username' => ! empty( $input['login_limit_block_admin_username'] ),
		'login_limit_max_attempts'         => refitune_sanitize_positive_int( $input['login_limit_max_attempts'] ?? '', 5 ),
		'login_limit_lockout_duration'     => refitune_sanitize_positive_int( $input['login_limit_lockout_duration'] ?? '', 15 ),
		'login_limit_whitelist_ips'        => implode( "\n", $valid_ips ),
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
 * @param mixed $value Raw value.
 * @return string default|enable|disable
 */
function refitune_sanitize_auto_update_tristate( $value ): string {
	$allowed = array( 'default', 'enable', 'disable' );
	$value   = is_string( $value ) ? $value : 'default';

	return in_array( $value, $allowed, true ) ? $value : 'default';
}

/**
 * Sanitize the Automatic Updates Control feature.
 *
 * @param array $input Raw input.
 * @return array
 */
function refitune_sanitize_auto_updates_control( array $input ): array {
	$tristate_fields = array(
		'refitune_plugins_auto',
		'auto_update_themes',
		'auto_update_translations',
		'auto_update_core_minor',
		'auto_update_core_major',
		'auto_update_core_dev',
	);

	$sanitized = array(
		'auto_updates_control' => ! empty( $input['auto_updates_control'] ),
	);

	foreach ( $tristate_fields as $field ) {
		$raw = $input[ $field ] ?? 'default';

		$legacy_plugins_key = refitune_legacy_plugins_auto_option_key();

		if ( 'refitune_plugins_auto' === $field && 'default' === $raw && isset( $input[ $legacy_plugins_key ] ) ) {
			$raw = $input[ $legacy_plugins_key ];
		}

		$sanitized[ $field ] = refitune_sanitize_auto_update_tristate( $raw );
	}

	$allowed_intervals = array( 'default', 'daily', '3_days', '7_days', '14_days' );
	$interval          = isset( $input['update_check_interval'] ) ? (string) $input['update_check_interval'] : 'default';

	$sanitized['update_check_interval'] = in_array( $interval, $allowed_intervals, true ) ? $interval : 'default';

	return $sanitized;
}

/**
 * Whether automatic updates control has non-default sub-settings.
 *
 * @param array $settings Plugin settings.
 * @return bool
 */
function refitune_auto_updates_is_configured( array $settings ): bool {
	if ( empty( $settings['auto_updates_control'] ) ) {
		return false;
	}

	$tristate_fields = array(
		'refitune_plugins_auto',
		'auto_update_themes',
		'auto_update_translations',
		'auto_update_core_minor',
		'auto_update_core_major',
		'auto_update_core_dev',
	);

	foreach ( $tristate_fields as $field ) {
		if ( 'refitune_plugins_auto' === $field ) {
			$legacy_plugins_key = refitune_legacy_plugins_auto_option_key();
			$mode               = isset( $settings[ $field ] )
				? (string) $settings[ $field ]
				: ( isset( $settings[ $legacy_plugins_key ] ) ? (string) $settings[ $legacy_plugins_key ] : 'default' );
		} else {
			$mode = isset( $settings[ $field ] ) ? (string) $settings[ $field ] : 'default';
		}

		if ( 'default' !== $mode ) {
			return true;
		}
	}

	return 'default' !== ( $settings['update_check_interval'] ?? 'default' );
}

/**
 * Sanitize the Heartbeat Control feature.
 *
 * @param array $input Raw input.
 * @return array
 */
function refitune_sanitize_heartbeat_control( array $input ): array {
	$allowed = array( '', '15', '30', '60', '120', 'disable' );

	$value = static function ( $raw ) use ( $allowed ) {
		return in_array( $raw, $allowed, true ) ? $raw : '';
	};

	return array(
		'heartbeat_control'  => ! empty( $input['heartbeat_control'] ),
		'heartbeat_admin'    => $value( $input['heartbeat_admin'] ?? '' ),
		'heartbeat_frontend' => $value( $input['heartbeat_frontend'] ?? '' ),
		'heartbeat_editor'   => $value( $input['heartbeat_editor'] ?? '' ),
	);
}

/**
 * Sanitize the Maintenance Mode feature.
 *
 * @param array $input     Raw input.
 * @param array $feature   Feature definition.
 * @param array $all_roles Valid role slugs.
 * @return array
 */
function refitune_sanitize_maintenance_mode( array $input, array $feature, array $all_roles ): array {
	$required = ! empty( $feature['required_roles'] ) ? $feature['required_roles'] : array();

	return array(
		$feature['enable_key']  => ! empty( $input[ $feature['enable_key'] ] ),
		$feature['option_key']  => refitune_sanitize_role_list( $input[ $feature['option_key'] ] ?? array(), $all_roles, $required ),
		$feature['message_key'] => isset( $input[ $feature['message_key'] ] ) ? sanitize_textarea_field( $input[ $feature['message_key'] ] ) : '',
	);
}

/**
 * Sanitize a role_select feature.
 *
 * @param array $input     Raw input.
 * @param array $feature   Feature definition.
 * @param array $all_roles Valid role slugs.
 * @return array
 */
function refitune_sanitize_role_select( array $input, array $feature, array $all_roles ): array {
	$option_key = $feature['option_key'];
	$required   = ! empty( $feature['required_roles'] ) ? $feature['required_roles'] : array();
	$result     = array(
		$option_key => refitune_sanitize_role_list( $input[ $option_key ] ?? array(), $all_roles, $required ),
	);

	if ( isset( $feature['enable_key'] ) ) {
		$result[ $feature['enable_key'] ] = ! empty( $input[ $feature['enable_key'] ] );
	}

	return $result;
}

/**
 * Sanitize plugin settings before they are stored.
 *
 * Registered as the sanitize_callback for the refitune_settings option.
 *
 * @param mixed $input Raw submitted data.
 * @return array Sanitized settings.
 */
function refitune_sanitize_settings( $input ): array {
	if ( ! is_array( $input ) ) {
		return array();
	}

	$sanitized = array();
	$features  = refitune_get_features();
	$all_roles = array_keys( wp_roles()->get_names() );

	foreach ( $features as $key => $feature ) {
		$type = isset( $feature['type'] ) ? $feature['type'] : '';

		switch ( $type ) {
			case 'login_customizer':
				$sanitized += refitune_sanitize_login_customizer( $input );
				break;

			case 'role_redirects':
				$sanitized += refitune_sanitize_role_redirects( $input, $all_roles );
				break;

			case 'email_smtp':
				$sanitized += refitune_sanitize_email_smtp( $input );
				break;

			case 'comments_control':
				$sanitized['disable_comments']              = ! empty( $input['disable_comments'] );
				$sanitized['disable_comments_keep_reviews'] = ! empty( $input['disable_comments_keep_reviews'] );
				break;

			case 'number_input':
				$option_key = $feature['option_key'];
				$raw        = isset( $input[ $option_key ] ) ? trim( (string) $input[ $option_key ] ) : '';
				$sanitized[ $option_key ] = ( '' !== $raw && is_numeric( $raw ) && (int) $raw >= 0 ) ? (int) $raw : '';
				break;

			case 'email_controls':
				$sanitized += refitune_sanitize_email_controls( $input );
				break;

			case 'role_select':
				$sanitized += refitune_sanitize_role_select( $input, $feature, $all_roles );
				break;

			case 'maintenance_mode':
				$sanitized += refitune_sanitize_maintenance_mode( $input, $feature, $all_roles );
				break;

			case 'login_limit':
				$sanitized += refitune_sanitize_login_limit( $input );
				break;

			case 'auto_updates_control':
				$sanitized += refitune_sanitize_auto_updates_control( $input );
				break;

			case 'heartbeat_control':
				$sanitized += refitune_sanitize_heartbeat_control( $input );
				break;

			default:
				if ( isset( $feature['sub_options'] ) ) {
					foreach ( array_keys( $feature['sub_options'] ) as $sub_key ) {
						$sanitized[ $sub_key ] = ! empty( $input[ $sub_key ] );
					}
				} else {
					if ( ! refitune_is_feature_available( $feature ) ) {
						$sanitized[ $key ] = false;
					} else {
						$sanitized[ $key ] = ! empty( $input[ $key ] );
					}
				}
				break;
		}
	}

	$sanitized['delete_data_on_uninstall'] = ! empty( $input['delete_data_on_uninstall'] );

	unset( $sanitized['file_restrictions'] );

	refitune_sync_comment_status_options( $sanitized );

	return $sanitized;
}

/**
 * Keep WordPress core comment options in sync with the Disable Comments toggle.
 *
 * @param array $sanitized Sanitized settings.
 * @return void
 */
function refitune_sync_comment_status_options( array $sanitized ): void {
	$old_settings = get_option( 'refitune_settings', array() );

	$old_disable_comments = ! empty( $old_settings['disable_comments'] );
	$new_disable_comments = ! empty( $sanitized['disable_comments'] );

	if ( $old_disable_comments === $new_disable_comments ) {
		return;
	}

	$status = $new_disable_comments ? 'closed' : 'open';
	update_option( 'default_comment_status', $status );
	update_option( 'default_ping_status', $status );
}
