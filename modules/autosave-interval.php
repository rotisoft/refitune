<?php
/**
 * Module: Auto-save Interval
 *
 * @package RefinerPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_autosave_interval = (int) get_option( 'wprefi_settings', array() )['autosave_interval'];

if ( $wprefi_autosave_interval > 0 ) {
	if ( ! defined( 'AUTOSAVE_INTERVAL' ) ) {
		define( 'AUTOSAVE_INTERVAL', $wprefi_autosave_interval );
	}
}
