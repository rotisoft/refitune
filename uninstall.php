<?php
/**
 * Plugin eltávolítás kezelése.
 *
 * WordPress Codex szerint: csak akkor fut le, ha a felhasználó
 * a WordPress admin felületen törli a plugint.
 *
 * @package RefiTune
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$refitune_settings = get_option( 'refitune_settings', array() );

if ( empty( $refitune_settings['delete_data_on_uninstall'] ) ) {
	return;
}

delete_option( 'refitune_settings' );
