<?php
/**
 * WordPress bejelentkezési oldal (wp-login.php) testreszabása.
 *
 * - Logo testreszabás (Site Icon vagy egyedi URL)
 * - Háttérszín beállítás
 * - Primary szín beállítás
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Logo URL és szöveg customizálása
// ---------------------------------------------------------------------------
add_filter(
	'login_headerurl',
	static function (): string {
		return home_url( '/' );
	}
);

add_filter(
	'login_headertext',
	static function (): string {
		return get_bloginfo( 'name' );
	}
);

/**
 * Build dynamic login page CSS from plugin settings.
 *
 * @return string CSS rules (no style tags).
 */
function refitune_login_customizer_get_inline_css(): string {
	$settings = get_option( 'refitune_settings', array() );
	$rules    = array();

	$logo_source = isset( $settings['login_logo_source'] ) ? $settings['login_logo_source'] : 'site_icon';
	$logo_url    = '';

	if ( 'custom' === $logo_source && ! empty( $settings['login_logo_custom_url'] ) ) {
		$logo_url = home_url( $settings['login_logo_custom_url'] );
	} else {
		$site_icon_id = get_option( 'site_icon' );
		if ( $site_icon_id ) {
			$logo_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
		}
	}

	$logo_width  = isset( $settings['login_logo_width'] ) && '' !== $settings['login_logo_width']
		? (int) $settings['login_logo_width']
		: 84;
	$logo_height = isset( $settings['login_logo_height'] ) && '' !== $settings['login_logo_height']
		? (int) $settings['login_logo_height']
		: 84;

	if ( $logo_url ) {
		$rules[] = sprintf(
			'#login h1 a, .login h1 a { background-image: url(%s); width: %dpx; height: %dpx; background-size: contain; background-position: center; background-repeat: no-repeat; }',
			esc_url( $logo_url ),
			$logo_width,
			$logo_height
		);
	}

	$bg_color = isset( $settings['login_bg_color'] ) && '' !== $settings['login_bg_color']
		? sanitize_hex_color( $settings['login_bg_color'] )
		: '';

	if ( $bg_color ) {
		$rules[] = sprintf( 'body.login { background: %s !important; }', esc_attr( $bg_color ) );
	}

	$primary_color = isset( $settings['login_primary_color'] ) && '' !== $settings['login_primary_color']
		? sanitize_hex_color( $settings['login_primary_color'] )
		: '';

	if ( $primary_color ) {
		$color = esc_attr( $primary_color );
		$rules[] = sprintf(
			'.wp-core-ui .button-primary { background: %1$s !important; border-color: %1$s !important; }',
			$color
		);
		$rules[] = sprintf(
			'.wp-core-ui .button-primary:hover, .wp-core-ui .button-primary:focus { background: %1$s !important; border-color: %1$s !important; opacity: 0.9; }',
			$color
		);
		$rules[] = sprintf( '.login .language-switcher .button { color: %1$s !important; border-color: %1$s !important; }', $color );
		$rules[] = sprintf( '.login .button.wp-hide-pw .dashicons { color: %1$s !important; }', $color );
		$rules[] = sprintf(
			'.login #backtoblog a, .login #nav a { color: %1$s !important; }',
			$color
		);
		$rules[] = sprintf(
			'.login #backtoblog a:hover, .login #nav a:hover, .login h1 a:hover { color: %1$s !important; }',
			$color
		);
		$rules[] = sprintf(
			'.login #backtoblog a:focus, .login #nav a:focus, .login h1 a:focus { color: %1$s !important; }',
			$color
		);
		$rules[] = sprintf( '.language-switcher label .dashicons { color: %1$s !important; }', $color );
	}

	if ( ! empty( $settings['login_hide_language_switcher'] ) ) {
		$rules[] = '.language-switcher { display: none !important; }';
	}

	return implode( "\n", $rules );
}

/**
 * Enqueue login page styles via login_enqueue_scripts.
 */
function refitune_login_customizer_enqueue_styles(): void {
	$css_file = REFITUNE_PATH . 'modules/css/login-customizer.css';
	$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : REFITUNE_VERSION;

	wp_enqueue_style(
		'refitune-login-customizer',
		REFITUNE_URL . 'modules/css/login-customizer.css',
		array( 'login' ),
		$version
	);

	$inline_css = refitune_login_customizer_get_inline_css();

	if ( '' !== $inline_css ) {
		wp_add_inline_style( 'refitune-login-customizer', $inline_css );
	}
}
add_action( 'login_enqueue_scripts', 'refitune_login_customizer_enqueue_styles', 10 );
