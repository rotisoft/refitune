<?php
/**
 * Automatic updates control - core filters and update check cron scheduling.
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
 * @param string $refitune_interval Stored setting value.
 * @return string
 */
function refitune_auto_updates_interval_to_recurrence( string $refitune_interval ): string {
	$refitune_map = array(
		'default'  => 'twicedaily',
		'daily'    => 'daily',
		'3_days'   => 'refitune_3_days',
		'7_days'   => 'refitune_7_days',
		'14_days'  => 'refitune_14_days',
	);

	if ( isset( $refitune_map[ $refitune_interval ] ) ) {
		return $refitune_map[ $refitune_interval ];
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
	foreach ( refitune_auto_updates_cron_hooks() as $refitune_hook ) {
		wp_clear_scheduled_hook( $refitune_hook );
		wp_schedule_event( time(), 'twicedaily', $refitune_hook );
	}
}

/**
 * Whether update-check cron events need rescheduling for the target recurrence.
 *
 * @param string $refitune_recurrence WordPress cron recurrence slug.
 * @return bool
 */
function refitune_update_checks_need_reschedule( string $refitune_recurrence ): bool {
	foreach ( refitune_auto_updates_cron_hooks() as $refitune_hook ) {
		$refitune_event = wp_get_scheduled_event( $refitune_hook );

		if ( ! $refitune_event || ! isset( $refitune_event->schedule ) || $refitune_recurrence !== $refitune_event->schedule ) {
			return true;
		}
	}

	return false;
}

/**
 * Reschedule update check cron events from plugin settings.
 *
 * @param array|null $refitune_settings Optional settings array; loads option when null.
 * @param bool       $refitune_force    When true, reschedule even if recurrence already matches.
 * @return void
 */
function refitune_reschedule_update_checks( $refitune_settings = null, bool $refitune_force = false ): void {
	if ( ! is_array( $refitune_settings ) ) {
		$refitune_settings = refitune_get_settings();
	}

	$refitune_interval   = isset( $refitune_settings['update_check_interval'] ) ? (string) $refitune_settings['update_check_interval'] : 'default';
	$refitune_recurrence = refitune_auto_updates_interval_to_recurrence( $refitune_interval );

	if ( ! $refitune_force && ! refitune_update_checks_need_reschedule( $refitune_recurrence ) ) {
		return;
	}

	foreach ( refitune_auto_updates_cron_hooks() as $refitune_hook ) {
		wp_clear_scheduled_hook( $refitune_hook );
		wp_schedule_event( time(), $refitune_recurrence, $refitune_hook );
	}
}

/**
 * Apply a tri-state auto-update filter when not set to WordPress default.
 *
 * @param string $refitune_filter_name WordPress filter name.
 * @param string $refitune_mode        default|enable|disable.
 * @return void
 */
function refitune_auto_updates_apply_tristate_filter( string $refitune_filter_name, string $refitune_mode ): void {
	if ( 'enable' === $refitune_mode ) {
		add_filter( $refitune_filter_name, '__return_true', 20 );
	} elseif ( 'disable' === $refitune_mode ) {
		add_filter( $refitune_filter_name, '__return_false', 20 );
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
	$refitune_settings = refitune_get_settings();

	add_filter( 'cron_schedules', 'refitune_auto_updates_cron_schedules', 10 );

	// Self-heal the cron schedule in admin and cron contexts only; running
	// this on every front-end request would add scheduled-event lookups.
	if ( is_admin() || wp_doing_cron() ) {
		add_action(
			'init',
			static function (): void {
				refitune_reschedule_update_checks();
			},
			20
		);
	}
	add_action( 'update_option_refitune_settings', 'refitune_auto_updates_on_settings_updated', 10, 2 );

	if ( is_admin() ) {
		add_action( 'admin_notices', 'refitune_auto_updates_config_notice', 10 );
	}

	// Core filters are skipped when WP_AUTO_UPDATE_CORE (or core filters) are set in wp-config.
	if ( ! refitune_auto_updates_core_is_locked_by_wp_config() ) {
		$refitune_core_filters = array(
			'auto_update_core_minor' => 'allow_minor_auto_core_updates',
			'auto_update_core_major' => 'allow_major_auto_core_updates',
			'auto_update_core_dev'   => 'allow_dev_auto_core_updates',
		);

		foreach ( $refitune_core_filters as $refitune_setting_key => $refitune_filter_name ) {
			$refitune_mode = isset( $refitune_settings[ $refitune_setting_key ] ) ? (string) $refitune_settings[ $refitune_setting_key ] : 'default';
			refitune_auto_updates_apply_tristate_filter( $refitune_filter_name, $refitune_mode );
		}
	}

	$refitune_type_filters = array(
		'refitune_plugins_auto'      => 'auto_update_' . 'plugin',
		'auto_update_themes'         => 'auto_update_theme',
		'auto_update_translations'   => 'auto_update_translation',
	);

	foreach ( $refitune_type_filters as $refitune_setting_key => $refitune_filter_name ) {
		if ( 'refitune_plugins_auto' === $refitune_setting_key ) {
			$refitune_legacy_plugins_key = 'auto_update_' . 'plugins';
			$refitune_mode               = isset( $refitune_settings[ $refitune_setting_key ] )
				? (string) $refitune_settings[ $refitune_setting_key ]
				: ( isset( $refitune_settings[ $refitune_legacy_plugins_key ] ) ? (string) $refitune_settings[ $refitune_legacy_plugins_key ] : 'default' );
		} else {
			$refitune_mode = isset( $refitune_settings[ $refitune_setting_key ] ) ? (string) $refitune_settings[ $refitune_setting_key ] : 'default';
		}

		refitune_auto_updates_apply_tristate_filter( $refitune_filter_name, $refitune_mode );
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

	$refitune_old = is_array( $old_value ) ? $old_value : array();

	if ( empty( $value['auto_updates_control'] ) ) {
		if ( ! empty( $refitune_old['auto_updates_control'] ) ) {
			refitune_restore_default_update_check_schedules();
		}
		return;
	}

	$refitune_was_off      = empty( $refitune_old['auto_updates_control'] );
	$refitune_old_interval = isset( $refitune_old['update_check_interval'] ) ? (string) $refitune_old['update_check_interval'] : 'default';
	$refitune_new_interval = isset( $value['update_check_interval'] ) ? (string) $value['update_check_interval'] : 'default';

	if ( $refitune_was_off || $refitune_old_interval !== $refitune_new_interval ) {
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

	// Show the notice only on RefiTune admin screens and the Updates screen.
	$refitune_screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $refitune_screen || ( false === strpos( $refitune_screen->id, 'refitune' ) && 'update-core' !== $refitune_screen->id ) ) {
		return;
	}

	$refitune_messages = array();

	if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) {
		$refitune_messages[] = __( 'AUTOMATIC_UPDATER_DISABLED is set to true in wp-config.php. WordPress background updates are disabled site-wide; RefiTune automatic update settings cannot take effect until that constant is removed or set to false.', 'refitune' );
	}

	if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
		$refitune_messages[] = __( 'WP_AUTO_UPDATE_CORE is defined in wp-config.php. That constant overrides RefiTune core automatic update settings.', 'refitune' );
	}

	if ( empty( $refitune_messages ) ) {
		return;
	}

	foreach ( $refitune_messages as $refitune_message ) {
		printf(
			'<div class="notice notice-warning"><p><strong>RefiTune:</strong> %s</p></div>',
			esc_html( $refitune_message )
		);
	}
}
