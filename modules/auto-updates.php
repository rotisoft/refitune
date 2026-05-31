<?php
/**
 * Automatic updates control — core filters and update check cron scheduling.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cron hook names used for update checks.
 *
 * @return array
 */
function refitune_auto_updates_cron_hooks(): array {
	return array(
		'wp_version_check',
		'wp_update_plugins',
		'wp_update_themes',
	);
}

/**
 * Map stored interval setting to a WordPress cron recurrence slug.
 *
 * @param string $interval Stored setting value.
 * @return string
 */
function refitune_auto_updates_interval_to_recurrence( string $interval ): string {
	$map = array(
		'default'  => 'twicedaily',
		'daily'    => 'daily',
		'3_days'   => 'refitune_3_days',
		'7_days'   => 'refitune_7_days',
		'14_days'  => 'refitune_14_days',
	);

	if ( isset( $map[ $interval ] ) ) {
		return $map[ $interval ];
	}

	return 'twicedaily';
}

/**
 * Register custom cron schedules for update checks.
 *
 * @param array $schedules Existing schedules.
 * @return array
 */
function refitune_auto_updates_cron_schedules( array $schedules ): array {
	$schedules['refitune_3_days'] = array(
		'interval' => 3 * DAY_IN_SECONDS,
		'display'  => __( 'Every 3 days', 'refitune' ),
	);
	$schedules['refitune_7_days'] = array(
		'interval' => 7 * DAY_IN_SECONDS,
		'display'  => __( 'Every 7 days', 'refitune' ),
	);
	$schedules['refitune_14_days'] = array(
		'interval' => 14 * DAY_IN_SECONDS,
		'display'  => __( 'Every 14 days', 'refitune' ),
	);

	return $schedules;
}

/**
 * Restore WordPress default (twicedaily) update check cron events.
 *
 * @return void
 */
function refitune_restore_default_update_check_schedules(): void {
	foreach ( refitune_auto_updates_cron_hooks() as $hook ) {
		wp_clear_scheduled_hook( $hook );
		wp_schedule_event( time(), 'twicedaily', $hook );
	}
}

/**
 * Whether update-check cron events need rescheduling for the target recurrence.
 *
 * @param string $recurrence WordPress cron recurrence slug.
 * @return bool
 */
function refitune_update_checks_need_reschedule( string $recurrence ): bool {
	foreach ( refitune_auto_updates_cron_hooks() as $hook ) {
		$event = wp_get_scheduled_event( $hook );

		if ( ! $event || ! isset( $event->schedule ) || $recurrence !== $event->schedule ) {
			return true;
		}
	}

	return false;
}

/**
 * Reschedule update check cron events from plugin settings.
 *
 * @param array|null $settings Optional settings array; loads option when null.
 * @param bool       $force    When true, reschedule even if recurrence already matches.
 * @return void
 */
function refitune_reschedule_update_checks( $settings = null, bool $force = false ): void {
	if ( ! is_array( $settings ) ) {
		$settings = get_option( 'refitune_settings', array() );
	}

	$interval   = isset( $settings['update_check_interval'] ) ? (string) $settings['update_check_interval'] : 'default';
	$recurrence = refitune_auto_updates_interval_to_recurrence( $interval );

	if ( ! $force && ! refitune_update_checks_need_reschedule( $recurrence ) ) {
		return;
	}

	foreach ( refitune_auto_updates_cron_hooks() as $hook ) {
		wp_clear_scheduled_hook( $hook );
		wp_schedule_event( time(), $recurrence, $hook );
	}
}

/**
 * Apply a tri-state auto-update filter when not set to WordPress default.
 *
 * @param string $filter_name WordPress filter name.
 * @param string $mode        default|enable|disable.
 * @return void
 */
function refitune_auto_updates_apply_tristate_filter( string $filter_name, string $mode ): void {
	if ( 'enable' === $mode ) {
		add_filter( $filter_name, '__return_true', 20 );
	} elseif ( 'disable' === $mode ) {
		add_filter( $filter_name, '__return_false', 20 );
	}
}

/**
 * Register hooks for automatic updates and cron rescheduling.
 *
 * Called from refitune.php only when the feature master switch is on.
 *
 * @return void
 */
function refitune_auto_updates_module_init(): void {
	$settings = get_option( 'refitune_settings', array() );

	add_filter( 'cron_schedules', 'refitune_auto_updates_cron_schedules', 10 );

	add_action(
		'init',
		static function (): void {
			refitune_reschedule_update_checks();
		},
		20
	);
	add_action( 'update_option_refitune_settings', 'refitune_auto_updates_on_settings_updated', 10, 2 );

	if ( is_admin() ) {
		add_action( 'admin_notices', 'refitune_auto_updates_config_notice', 10 );
	}

	$core_filters = array(
		'auto_update_core_minor' => 'allow_minor_auto_core_updates',
		'auto_update_core_major' => 'allow_major_auto_core_updates',
		'auto_update_core_dev'   => 'allow_dev_auto_core_updates',
	);

	foreach ( $core_filters as $setting_key => $filter_name ) {
		$mode = isset( $settings[ $setting_key ] ) ? (string) $settings[ $setting_key ] : 'default';
		refitune_auto_updates_apply_tristate_filter( $filter_name, $mode );
	}

	$type_filters = array(
		'refitune_plugins_auto'      => 'auto_update_' . 'plugin',
		'auto_update_themes'         => 'auto_update_theme',
		'auto_update_translations'   => 'auto_update_translation',
	);

	foreach ( $type_filters as $setting_key => $filter_name ) {
		if ( 'refitune_plugins_auto' === $setting_key ) {
			$legacy_plugins_key = 'auto_update_' . 'plugins';
			$mode               = isset( $settings[ $setting_key ] )
				? (string) $settings[ $setting_key ]
				: ( isset( $settings[ $legacy_plugins_key ] ) ? (string) $settings[ $legacy_plugins_key ] : 'default' );
		} else {
			$mode = isset( $settings[ $setting_key ] ) ? (string) $settings[ $setting_key ] : 'default';
		}

		refitune_auto_updates_apply_tristate_filter( $filter_name, $mode );
	}
}

/**
 * Reschedule or restore cron when settings are saved.
 *
 * @param mixed $old_value Previous option value.
 * @param mixed $value     New option value.
 * @return void
 */
function refitune_auto_updates_on_settings_updated( $old_value, $value ): void {
	if ( ! is_array( $value ) ) {
		return;
	}

	$old = is_array( $old_value ) ? $old_value : array();

	if ( empty( $value['auto_updates_control'] ) ) {
		if ( ! empty( $old['auto_updates_control'] ) ) {
			refitune_restore_default_update_check_schedules();
		}
		return;
	}

	$was_off      = empty( $old['auto_updates_control'] );
	$old_interval = isset( $old['update_check_interval'] ) ? (string) $old['update_check_interval'] : 'default';
	$new_interval = isset( $value['update_check_interval'] ) ? (string) $value['update_check_interval'] : 'default';

	if ( $was_off || $old_interval !== $new_interval ) {
		refitune_reschedule_update_checks( $value, true );
	} else {
		refitune_reschedule_update_checks( $value, false );
	}
}

/**
 * Warn when wp-config constants override automatic update behavior.
 *
 * @return void
 */
function refitune_auto_updates_config_notice(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$messages = array();

	if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) {
		$messages[] = __( 'AUTOMATIC_UPDATER_DISABLED is set to true in wp-config.php. WordPress background updates are disabled site-wide; RefiTune automatic update settings cannot take effect until that constant is removed or set to false.', 'refitune' );
	}

	if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
		$messages[] = __( 'WP_AUTO_UPDATE_CORE is defined in wp-config.php. That constant overrides RefiTune core automatic update settings.', 'refitune' );
	}

	if ( empty( $messages ) ) {
		return;
	}

	foreach ( $messages as $message ) {
		printf(
			'<div class="notice notice-warning"><p><strong>RefiTune:</strong> %s</p></div>',
			esc_html( $message )
		);
	}
}
