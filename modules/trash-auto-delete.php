<?php
/**
 * Module: Trash Auto-Delete
 *
 * @package RefinerPress
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_trash_days = (int) get_option( 'wprefi_settings', array() )['trash_auto_delete_days'];

if ( $wprefi_trash_days > 0 ) {
	if ( ! defined( 'EMPTY_TRASH_DAYS' ) ) {
		define( 'EMPTY_TRASH_DAYS', $wprefi_trash_days );
	}
}
