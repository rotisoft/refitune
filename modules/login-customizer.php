<?php
/**
 * WordPress login page (wp-login.php) customization.
 *
 * - Logo customization (Site Icon or custom URL)
 * - Background color setting
 * - Primary color setting
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Customize the logo URL and text
// ---------------------------------------------------------------------------
add_filter(
	'login_headerurl',
	static function (): string {
		return home_url( '/' );
	},
	10
);

add_filter(
	'login_headertext',
	static function (): string {
		return get_bloginfo( 'name' );
	},
	10
);

/**
 * Build dynamic login page CSS from plugin settings.
 *
 * @return string CSS rules (no style tags).
 */
function refitune_login_customizer_get_inline_css(): string {
	$refitune_settings = refitune_get_settings();
	$refitune_rules    = array();

	$refitune_logo_source = isset( $refitune_settings['login_logo_source'] ) ? $refitune_settings['login_logo_source'] : 'site_icon';
	$refitune_logo_url    = '';

	if ( 'custom' === $refitune_logo_source && ! empty( $refitune_settings['login_logo_custom_url'] ) ) {
		$refitune_logo_url = home_url( $refitune_settings['login_logo_custom_url'] );
	} else {
		$refitune_site_icon_id = get_option( 'site_icon' );
		if ( $refitune_site_icon_id ) {
			$refitune_logo_url = wp_get_attachment_image_url( $refitune_site_icon_id, 'full' );
		}
	}

	$refitune_logo_width  = isset( $refitune_settings['login_logo_width'] ) && '' !== $refitune_settings['login_logo_width']
		? (int) $refitune_settings['login_logo_width']
		: 84;
	$refitune_logo_height = isset( $refitune_settings['login_logo_height'] ) && '' !== $refitune_settings['login_logo_height']
		? (int) $refitune_settings['login_logo_height']
		: 84;

	if ( $refitune_logo_url ) {
		$refitune_rules[] = sprintf(
			'#login h1 a, .login h1 a { background-image: url(%s); width: %dpx; height: %dpx; background-size: contain; background-position: center; background-repeat: no-repeat; }',
			esc_url( $refitune_logo_url ),
			$refitune_logo_width,
			$refitune_logo_height
		);
	}

	$refitune_bg_color = isset( $refitune_settings['login_bg_color'] ) && '' !== $refitune_settings['login_bg_color']
		? sanitize_hex_color( $refitune_settings['login_bg_color'] )
		: '';

	if ( $refitune_bg_color ) {
		$refitune_rules[] = sprintf( 'body.login { background: %s !important; }', esc_attr( $refitune_bg_color ) );
	}

	$refitune_primary_color = isset( $refitune_settings['login_primary_color'] ) && '' !== $refitune_settings['login_primary_color']
		? sanitize_hex_color( $refitune_settings['login_primary_color'] )
		: '';

	if ( $refitune_primary_color ) {
		$refitune_color = esc_attr( $refitune_primary_color );
		$refitune_rules[] = sprintf(
			'.wp-core-ui .button-primary { background: %1$s !important; border-color: %1$s !important; }',
			$refitune_color
		);
		$refitune_rules[] = sprintf(
			'.wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus { background: %1$s !important; border-color: %1$s !important; opacity: 0.9; }',
			$refitune_color
		);
		$refitune_rules[] = sprintf( '.login .language-switcher .button { color: %1$s !important; border-color: %1$s !important; }', $refitune_color );
		$refitune_rules[] = sprintf( '.login .button.wp-hide-pw .dashicons { color: %1$s !important; }', $refitune_color );
		$refitune_rules[] = sprintf(
			'.login #backtoblog a, .login #nav a { color: %1$s !important; }',
			$refitune_color
		);
		$refitune_rules[] = sprintf(
			'.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover { color: %1$s !important; }',
			$refitune_color
		);
		$refitune_rules[] = sprintf(
			'.login #backtoblog a:focus, .login #nav a:focus, .login h1 a:focus { color: %1$s !important; }',
			$refitune_color
		);
		$refitune_rules[] = sprintf( '.language-switcher label .dashicons { color: %1$s !important; }', $refitune_color );
	}

	if ( ! empty( $refitune_settings['login_hide_language_switcher'] ) ) {
		$refitune_rules[] = '.language-switcher { display: none !important; }';
	}

	return implode( "\n", $refitune_rules );
}

/**
 * Enqueue login page styles via login_enqueue_scripts.
 */
function refitune_login_customizer_enqueue_styles(): void {
	$refitune_css_file = REFITUNE_PATH . 'modules/css/login-customizer.css';
	$refitune_version  = file_exists( $refitune_css_file ) ? (string) filemtime( $refitune_css_file ) : REFITUNE_VERSION;

	wp_enqueue_style(
		'refitune-login-customizer',
		REFITUNE_URL . 'modules/css/login-customizer.css',
		array( 'login' ),
		$refitune_version
	);

	$refitune_inline_css = refitune_login_customizer_get_inline_css();

	if ( '' !== $refitune_inline_css ) {
		wp_add_inline_style( 'refitune-login-customizer', $refitune_inline_css );
	}
}
add_action( 'login_enqueue_scripts', 'refitune_login_customizer_enqueue_styles', 10 );
