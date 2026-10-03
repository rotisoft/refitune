<?php
/**
 * Shared block editor assets for device and role visibility modules.
 *
 * Enqueues a single script/style once and localizes which visibility
 * features are active so the editor can render one Visibility panel.
 * JS i18n is injected from the loaded .mo via wp.i18n.setLocaleData
 * (no separate JSON translation files required).
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Jed locale data for block-visibility.js, sourced from PHP/.mo translations.
 *
 * @return array
 */
function refitune_block_visibility_script_locale_data(): array {
	$refitune_msgids = array(
		'Always visible',
		'Mobile only',
		'Desktop only',
		'Guests only',
		'Logged-in users',
		'Selected roles',
		'Display by device',
		'Display by role',
		'Visible to roles',
		'Visibility',
		'The block HTML is omitted entirely on devices where it should not appear.',
		'The block HTML is omitted entirely for visitors who do not match the selected login or role condition.',
		'Selected blocks have different visibility settings. Choosing an option applies it to all selected blocks.',
	);

	$refitune_locale_data = array(
		'' => array(
			'domain'       => 'refitune',
			'lang'         => determine_locale(),
			'plural-forms' => 'nplurals=2; plural=n != 1;',
		),
	);

	foreach ( $refitune_msgids as $refitune_msgid ) {
		// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- msgids must match block-visibility.js exactly.
		$refitune_locale_data[ $refitune_msgid ] = array( __( $refitune_msgid, 'refitune' ) );
	}

	return $refitune_locale_data;
}

/**
 * Enqueue shared block visibility editor assets (once per request).
 *
 * @return void
 */
function refitune_enqueue_block_visibility_editor(): void {
	static $refitune_enqueued = false;

	if ( $refitune_enqueued ) {
		return;
	}

	$refitune_enqueued = true;

	$refitune_settings = refitune_get_settings();
	$refitune_js_file  = REFITUNE_PATH . 'admin/js/block-visibility.js';
	$refitune_css_file = REFITUNE_PATH . 'admin/css/block-visibility.css';

	$refitune_device_enabled = ! empty( $refitune_settings['block_visibility'] );
	$refitune_roles_enabled  = ! empty( $refitune_settings['block_visibility_roles'] );

	if ( ! $refitune_device_enabled && ! $refitune_roles_enabled ) {
		return;
	}

	wp_enqueue_style(
		'refitune-block-visibility',
		REFITUNE_URL . 'admin/css/block-visibility.css',
		array(),
		file_exists( $refitune_css_file ) ? (string) filemtime( $refitune_css_file ) : REFITUNE_VERSION
	);

	wp_enqueue_script(
		'refitune-block-visibility',
		REFITUNE_URL . 'admin/js/block-visibility.js',
		array( 'wp-hooks', 'wp-compose', 'wp-block-editor', 'wp-components', 'wp-element', 'wp-i18n', 'wp-data' ),
		file_exists( $refitune_js_file ) ? (string) filemtime( $refitune_js_file ) : REFITUNE_VERSION,
		true
	);

	$refitune_role_list = array();

	if ( $refitune_roles_enabled ) {
		foreach ( wp_roles()->get_names() as $refitune_role_slug => $refitune_role_name ) {
			$refitune_role_list[] = array(
				'value' => (string) $refitune_role_slug,
				'label' => translate_user_role( $refitune_role_name ),
			);
		}
	}

	wp_localize_script(
		'refitune-block-visibility',
		'refituneBlockVisibility',
		array(
			'deviceEnabled' => $refitune_device_enabled,
			'rolesEnabled'  => $refitune_roles_enabled,
			'roles'         => $refitune_role_list,
		)
	);

	// Inject translations from the loaded .mo (no JSON files under languages/).
	wp_add_inline_script(
		'refitune-block-visibility',
		'wp.i18n.setLocaleData( ' . wp_json_encode( refitune_block_visibility_script_locale_data() ) . ', "refitune" );',
		'before'
	);
}
