<?php
/**
 * Disable the file editor in the WordPress admin.
 *
 * The DISALLOW_FILE_EDIT WordPress constant removes the
 * Appearance > Theme File Editor and Plugins > Plugin File Editor
 * menu items, and the pages become inaccessible via direct URL
 * even for users who would otherwise have the capability.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {
	define( 'DISALLOW_FILE_EDIT', true );
}
