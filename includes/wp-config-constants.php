<?php
/**
 * Detect constants and filters defined in wp-config.php.
 *
 * WordPress defines EMPTY_TRASH_DAYS, WP_POST_REVISIONS, and AUTOSAVE_INTERVAL
 * in default-constants.php when missing, so defined() alone cannot tell whether
 * a value was set in wp-config.php. This helper scans the config file instead.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve the path to wp-config.php (ABSPATH or one level above).
 *
 * @return string Absolute path, or empty string if not found.
 */
function refitune_get_wp_config_path(): string {
	if ( is_readable( ABSPATH . 'wp-config.php' ) ) {
		return ABSPATH . 'wp-config.php';
	}

	$refitune_parent = dirname( ABSPATH ) . '/wp-config.php';

	// Match core bootstrap: parent wp-config.php only when wp-settings.php is not beside it.
	if ( is_readable( $refitune_parent ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
		return $refitune_parent;
	}

	return '';
}

/**
 * Comment-stripped contents of wp-config.php.
 *
 * @return string Empty string when unreadable.
 */
function refitune_get_wp_config_stripped_contents(): string {
	static $refitune_cache = null;

	if ( null !== $refitune_cache ) {
		return $refitune_cache;
	}

	$refitune_cache = '';
	$refitune_path  = refitune_get_wp_config_path();

	if ( '' === $refitune_path ) {
		return $refitune_cache;
	}

	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read-only scan of wp-config.php; WP_Filesystem is unnecessary here.
	$refitune_contents = file_get_contents( $refitune_path );

	if ( false === $refitune_contents || '' === $refitune_contents ) {
		return $refitune_cache;
	}

	// Strip block and line comments so commented-out code is ignored.
	$refitune_stripped = preg_replace( '!/\*.*?\*/!s', '', $refitune_contents );
	$refitune_stripped = preg_replace( '/^\s*\/\/.*$/m', '', (string) $refitune_stripped );
	$refitune_stripped = preg_replace( '/^\s*#.*$/m', '', (string) $refitune_stripped );

	$refitune_cache = is_string( $refitune_stripped ) ? $refitune_stripped : '';

	return $refitune_cache;
}

/**
 * Names of constants that appear in a define() call inside wp-config.php.
 *
 * @return array<string, true> Uppercase constant name => true.
 */
function refitune_get_wp_config_defined_names(): array {
	static $refitune_cache = null;

	if ( null !== $refitune_cache ) {
		return $refitune_cache;
	}

	$refitune_cache    = array();
	$refitune_stripped = refitune_get_wp_config_stripped_contents();

	if ( '' === $refitune_stripped ) {
		return $refitune_cache;
	}

	if ( preg_match_all( "/define\s*\(\s*['\"]([A-Z0-9_]+)['\"]\s*,/i", $refitune_stripped, $refitune_matches ) ) {
		foreach ( $refitune_matches[1] as $refitune_name ) {
			$refitune_cache[ strtoupper( $refitune_name ) ] = true;
		}
	}

	return $refitune_cache;
}

/**
 * Whether a constant is defined via define() in wp-config.php.
 *
 * @param string $refitune_constant Constant name (e.g. WP_POST_REVISIONS).
 * @return bool
 */
function refitune_wp_config_defines_constant( string $refitune_constant ): bool {
	$refitune_names = refitune_get_wp_config_defined_names();

	return isset( $refitune_names[ strtoupper( $refitune_constant ) ] );
}

/**
 * Raw second argument of a define() call in wp-config.php.
 *
 * @param string $refitune_constant Constant name.
 * @return string|null Raw PHP expression, or null when not found.
 */
function refitune_wp_config_get_define_raw_value( string $refitune_constant ): ?string {
	$refitune_stripped = refitune_get_wp_config_stripped_contents();

	if ( '' === $refitune_stripped ) {
		return null;
	}

	$refitune_pattern = "/define\s*\(\s*['\"]" . preg_quote( $refitune_constant, '/' ) . "['\"]\s*,\s*(.+?)\s*\)\s*;/i";

	if ( ! preg_match( $refitune_pattern, $refitune_stripped, $refitune_matches ) ) {
		return null;
	}

	return trim( $refitune_matches[1] );
}

/**
 * Whether a wp-config define() value is truthy (true / 1).
 *
 * @param string $refitune_constant Constant name.
 * @return bool
 */
function refitune_wp_config_define_is_truthy( string $refitune_constant ): bool {
	$refitune_raw = refitune_wp_config_get_define_raw_value( $refitune_constant );

	if ( null === $refitune_raw ) {
		return false;
	}

	$refitune_normalized = strtolower( trim( $refitune_raw, " \t\n\r\0\x0B\"'" ) );

	return in_array( $refitune_normalized, array( 'true', '1' ), true );
}

/**
 * Whether a wp-config define() value is explicitly falsey (false / 0).
 *
 * @param string $refitune_constant Constant name.
 * @return bool
 */
function refitune_wp_config_define_is_falsey( string $refitune_constant ): bool {
	$refitune_raw = refitune_wp_config_get_define_raw_value( $refitune_constant );

	if ( null === $refitune_raw ) {
		return false;
	}

	$refitune_normalized = strtolower( trim( $refitune_raw, " \t\n\r\0\x0B\"'" ) );

	return in_array( $refitune_normalized, array( 'false', '0' ), true );
}

/**
 * Whether wp-config.php contains an add_filter() for the given hook.
 *
 * @param string $refitune_filter Filter name.
 * @return bool
 */
function refitune_wp_config_has_add_filter( string $refitune_filter ): bool {
	$refitune_stripped = refitune_get_wp_config_stripped_contents();

	if ( '' === $refitune_stripped ) {
		return false;
	}

	$refitune_pattern = "/add_filter\s*\(\s*['\"]" . preg_quote( $refitune_filter, '/' ) . "['\"]/i";

	return 1 === preg_match( $refitune_pattern, $refitune_stripped );
}

/**
 * Shared yellow warning when a setting is locked by wp-config.php.
 *
 * @return string
 */
function refitune_wp_config_locked_message(): string {
	return __( 'This value is defined in wp-config.php, so it cannot be changed here.', 'refitune' );
}

/**
 * Filters that disable the entire automatic updater (all update types).
 *
 * @return string[]
 */
function refitune_auto_updates_full_lock_filters(): array {
	return array(
		'automatic_updater_disabled',
	);
}

/**
 * Filters that only affect core automatic updates.
 *
 * @return string[]
 */
function refitune_auto_updates_core_lock_filters(): array {
	return array(
		'auto_update_core',
		'allow_dev_auto_core_updates',
		'allow_minor_auto_core_updates',
		'allow_major_auto_core_updates',
	);
}

/**
 * Whether the entire automatic updater is locked (all types, including checks).
 *
 * WP_AUTO_UPDATE_CORE alone does not fully lock: it only controls core updates.
 * Plugin, theme, and translation auto-updates remain independent.
 *
 * @return bool
 */
function refitune_auto_updates_is_fully_locked_by_wp_config(): bool {
	if ( refitune_wp_config_defines_constant( 'AUTOMATIC_UPDATER_DISABLED' ) ) {
		return true;
	}

	foreach ( refitune_auto_updates_full_lock_filters() as $refitune_filter ) {
		if ( refitune_wp_config_has_add_filter( $refitune_filter ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Whether core automatic update fields are locked by wp-config.
 *
 * @return bool
 */
function refitune_auto_updates_core_is_locked_by_wp_config(): bool {
	if ( refitune_auto_updates_is_fully_locked_by_wp_config() ) {
		return true;
	}

	if ( refitune_wp_config_defines_constant( 'WP_AUTO_UPDATE_CORE' ) ) {
		return true;
	}

	foreach ( refitune_auto_updates_core_lock_filters() as $refitune_filter ) {
		if ( refitune_wp_config_has_add_filter( $refitune_filter ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Normalized WP_AUTO_UPDATE_CORE value from wp-config.php.
 *
 * @return string|null One of true, false, minor, beta, rc, development,
 *                     branch-development; null when not defined or unknown.
 */
function refitune_auto_updates_get_wp_auto_update_core_value(): ?string {
	if ( ! refitune_wp_config_defines_constant( 'WP_AUTO_UPDATE_CORE' ) ) {
		return null;
	}

	$refitune_raw = refitune_wp_config_get_define_raw_value( 'WP_AUTO_UPDATE_CORE' );

	if ( null === $refitune_raw ) {
		return null;
	}

	$refitune_normalized = strtolower( trim( $refitune_raw, " \t\n\r\0\x0B\"'" ) );

	if ( in_array( $refitune_normalized, array( 'false', '0' ), true ) ) {
		return 'false';
	}

	if ( in_array( $refitune_normalized, array( 'true', '1' ), true ) ) {
		return 'true';
	}

	if ( in_array(
		$refitune_normalized,
		array( 'minor', 'beta', 'rc', 'development', 'branch-development' ),
		true
	) ) {
		return $refitune_normalized;
	}

	return null;
}

/**
 * Effective core auto-update modes implied by WP_AUTO_UPDATE_CORE in wp-config.
 *
 * Keys: minor, major, dev. Values: enable or disable.
 *
 * @return array<string, string>|null Null when WP_AUTO_UPDATE_CORE is not set in wp-config.
 */
function refitune_auto_updates_core_modes_from_wp_config(): ?array {
	$refitune_value = refitune_auto_updates_get_wp_auto_update_core_value();

	if ( null === $refitune_value ) {
		return null;
	}

	if ( 'false' === $refitune_value ) {
		return array(
			'minor' => 'disable',
			'major' => 'disable',
			'dev'   => 'disable',
		);
	}

	if ( 'minor' === $refitune_value ) {
		return array(
			'minor' => 'enable',
			'major' => 'disable',
			'dev'   => 'disable',
		);
	}

	// true, or development-channel strings (beta / rc / development / branch-development).
	// Boolean true: minor + major on, development off (UI mapping for production use).
	if ( 'true' === $refitune_value ) {
		return array(
			'minor' => 'enable',
			'major' => 'enable',
			'dev'   => 'disable',
		);
	}

	// Development-channel constants enable all three core update types in WordPress core.
	return array(
		'minor' => 'enable',
		'major' => 'enable',
		'dev'   => 'enable',
	);
}

/**
 * Display value for a core auto-update select (wp-config mapping when locked).
 *
 * @param string $refitune_field  Option key (auto_update_core_minor|major|dev).
 * @param mixed  $refitune_stored Stored setting value.
 * @return string default|enable|disable
 */
function refitune_get_auto_update_core_field_value( string $refitune_field, $refitune_stored ): string {
	$refitune_modes = refitune_auto_updates_core_modes_from_wp_config();

	if ( null !== $refitune_modes ) {
		$refitune_map = array(
			'auto_update_core_minor' => 'minor',
			'auto_update_core_major' => 'major',
			'auto_update_core_dev'   => 'dev',
		);

		if ( isset( $refitune_map[ $refitune_field ], $refitune_modes[ $refitune_map[ $refitune_field ] ] ) ) {
			return $refitune_modes[ $refitune_map[ $refitune_field ] ];
		}
	}

	$refitune_value = is_string( $refitune_stored ) ? $refitune_stored : 'default';

	return in_array( $refitune_value, array( 'default', 'enable', 'disable' ), true )
		? $refitune_value
		: 'default';
}

/**
 * Whether WP_AUTO_UPDATE_CORE is set to false in wp-config.php.
 *
 * @return bool
 */
function refitune_auto_updates_core_disabled_in_wp_config(): bool {
	return 'false' === refitune_auto_updates_get_wp_auto_update_core_value();
}

/**
 * Danger message when core auto-updates are disabled via WP_AUTO_UPDATE_CORE false.
 *
 * @return string
 */
function refitune_auto_updates_core_disabled_danger_message(): string {
	return __( 'Core automatic updates are disabled, which is very dangerous. Remove the define( \'WP_AUTO_UPDATE_CORE\', false ); line from wp-config.php to fix this.', 'refitune' );
}

/**
 * Whether any automatic-updates UI lock applies (full or core-only).
 *
 * @return bool
 */
function refitune_auto_updates_is_locked_by_wp_config(): bool {
	return refitune_auto_updates_is_fully_locked_by_wp_config()
		|| refitune_auto_updates_core_is_locked_by_wp_config();
}

/**
 * Whether Disable File Editor is locked by DISALLOW_FILE_EDIT in wp-config.php.
 *
 * @return bool
 */
function refitune_disable_file_edit_is_locked_by_wp_config(): bool {
	return refitune_wp_config_defines_constant( 'DISALLOW_FILE_EDIT' );
}

/**
 * Whether WP_CACHE is set to true in wp-config.php.
 *
 * @return bool
 */
function refitune_wp_cache_is_true_in_wp_config(): bool {
	return refitune_wp_config_defines_constant( 'WP_CACHE' )
		&& refitune_wp_config_define_is_truthy( 'WP_CACHE' );
}

/**
 * Whether Remove Asset Version Query Strings is locked because WP_CACHE is true.
 *
 * @return bool
 */
function refitune_remove_asset_versions_is_locked_by_wp_config(): bool {
	return refitune_wp_cache_is_true_in_wp_config();
}

/**
 * Warning when asset version removal is blocked by WP_CACHE.
 *
 * @return string
 */
function refitune_remove_asset_versions_wp_cache_warning(): string {
	return __( 'WP_CACHE is set to true in wp-config.php, so removing asset version query strings cannot be enabled.', 'refitune' );
}

/**
 * Whether a number_input feature is locked because its wp-config constant is set.
 *
 * @param array $refitune_feature Feature definition from refitune_get_features().
 * @return bool
 */
function refitune_number_input_is_locked_by_wp_config( array $refitune_feature ): bool {
	if ( empty( $refitune_feature['wp_config_constant'] ) || ! is_string( $refitune_feature['wp_config_constant'] ) ) {
		return false;
	}

	return refitune_wp_config_defines_constant( $refitune_feature['wp_config_constant'] );
}

/**
 * Field value for a number_input, preferring the wp-config constant when locked.
 *
 * @param array $refitune_feature Feature definition.
 * @param mixed $refitune_stored  Value stored in refitune_settings.
 * @return int|string Empty string when unlimited (true) or unset; otherwise int.
 */
function refitune_get_number_input_field_value( array $refitune_feature, $refitune_stored ) {
	if ( ! refitune_number_input_is_locked_by_wp_config( $refitune_feature ) ) {
		return $refitune_stored;
	}

	$refitune_constant = (string) $refitune_feature['wp_config_constant'];

	if ( ! defined( $refitune_constant ) ) {
		return $refitune_stored;
	}

	$refitune_value = constant( $refitune_constant );

	if ( false === $refitune_value ) {
		return 0;
	}

	// true means unlimited revisions - no numeric value to show.
	if ( true === $refitune_value ) {
		return '';
	}

	if ( is_numeric( $refitune_value ) ) {
		return (int) $refitune_value;
	}

	return $refitune_stored;
}
