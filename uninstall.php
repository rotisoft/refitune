<?php
/**
 * Plugin eltávolítás kezelése.
 *
 * WordPress Codex szerint: csak akkor fut le, ha a felhasználó
 * a WordPress admin felületen törli a plugint.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

if ( ! function_exists( 'get_home_path' ) ) {
	require_once ABSPATH . 'wp-admin/includes/file.php';
}

$htaccess = wp_normalize_path( get_home_path() . '.htaccess' );

if ( file_exists( $htaccess ) && is_writable( $htaccess ) ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
	$contents = file_get_contents( $htaccess );

	if ( false !== $contents ) {
		$marker   = preg_quote( 'RefinerPress File Restrictions', '/' );
		$stripped = preg_replace(
			'/[\r\n]*# BEGIN ' . $marker . '.*?# END ' . $marker . '[\r\n]*/s',
			"\n",
			$contents
		);

		if ( is_string( $stripped ) && $stripped !== $contents ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			file_put_contents( $htaccess, trim( $stripped ) . "\n" );
		}
	}
}

$wprefi_settings = get_option( 'wprefi_settings', array() );

if ( empty( $wprefi_settings['delete_data_on_uninstall'] ) ) {
	return;
}

delete_option( 'wprefi_settings' );
delete_option( 'wprefi_removed_file_restrictions_v1' );
