<?php
/**
 * Plugin uninstall handler.
 *
 * Runs only when the user deletes the plugin from the WordPress admin.
 * The plugin file is not loaded here, so core functions are used directly.
 *
 * @package RefiTune
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Restore WordPress default update check cron schedules.
 *
 * @return void
 */
function refitune_uninstall_restore_update_checks(): void {
	foreach ( array( 'wp_version_check', 'wp_update_plugins', 'wp_update_themes' ) as $refitune_cron_hook ) {
		wp_clear_scheduled_hook( $refitune_cron_hook );
		wp_schedule_event( time(), 'twicedaily', $refitune_cron_hook );
	}
}

/**
 * Delete stored plugin data for the current site.
 *
 * Removes the settings option and leftover login-limit transient records.
 *
 * @return void
 */
function refitune_uninstall_delete_site_data(): void {
	global $wpdb;

	delete_option( 'refitune_settings' );

	$refitune_transient_prefixes = array(
		'_transient_refitune_',
		'_transient_timeout_refitune_',
	);

	foreach ( $refitune_transient_prefixes as $refitune_prefix ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup, prefix lookup has no core API.
		$refitune_transient_options = $wpdb->get_col(
			$wpdb->prepare(
				"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
				$wpdb->esc_like( $refitune_prefix ) . '%'
			)
		);

		foreach ( $refitune_transient_options as $refitune_transient_option ) {
			delete_option( $refitune_transient_option );
		}
	}
}

/**
 * Run uninstall cleanup for the current site.
 *
 * @return void
 */
function refitune_uninstall_current_site(): void {
	$refitune_settings = get_option( 'refitune_settings', array() );

	// Restore default update check schedules in case Automatic Updates Control
	// changed them and the deactivation hook did not run.
	if ( ! empty( $refitune_settings['auto_updates_control'] ) ) {
		refitune_uninstall_restore_update_checks();
	}

	if ( empty( $refitune_settings['delete_data_on_uninstall'] ) ) {
		return;
	}

	refitune_uninstall_delete_site_data();
}

if ( is_multisite() ) {
	$refitune_site_ids = get_sites(
		array(
			'fields' => 'ids',
			'number' => 0,
		)
	);

	foreach ( $refitune_site_ids as $refitune_site_id ) {
		switch_to_blog( $refitune_site_id );
		refitune_uninstall_current_site();
		restore_current_blog();
	}
} else {
	refitune_uninstall_current_site();
}
