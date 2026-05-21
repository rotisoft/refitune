<?php
/**
 * WordPress bejelentkezési oldal (wp-login.php) testreszabása.
 *
 * - Logo testreszabás (Site Icon vagy egyedi URL)
 * - Háttérszín beállítás
 * - Primary szín beállítás
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_login_settings = get_option( 'wprefi_settings', array() );

// ---------------------------------------------------------------------------
// 1. Logo URL és szöveg customizálása
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

// ---------------------------------------------------------------------------
// 2. Logo, háttérszín és primary szín CSS injektálása
// ---------------------------------------------------------------------------
add_action(
	'login_head',
	static function () use ( $wprefi_login_settings ): void {
		$logo_source = isset( $wprefi_login_settings['login_logo_source'] ) ? $wprefi_login_settings['login_logo_source'] : 'site_icon';
		$logo_url    = '';

		// Logo URL meghatározása.
		if ( 'custom' === $logo_source && ! empty( $wprefi_login_settings['login_logo_custom_url'] ) ) {
			$logo_url = home_url( $wprefi_login_settings['login_logo_custom_url'] );
		} else {
			// Site Icon használata (ha van).
			$site_icon_id = get_option( 'site_icon' );
			if ( $site_icon_id ) {
				$logo_url = wp_get_attachment_image_url( $site_icon_id, 'full' );
			}
		}

		// Logo méret.
		$logo_width  = isset( $wprefi_login_settings['login_logo_width'] ) && '' !== $wprefi_login_settings['login_logo_width']
			? (int) $wprefi_login_settings['login_logo_width']
			: 84;
		$logo_height = isset( $wprefi_login_settings['login_logo_height'] ) && '' !== $wprefi_login_settings['login_logo_height']
			? (int) $wprefi_login_settings['login_logo_height']
			: 84;

		// Háttérszín.
		$bg_color = isset( $wprefi_login_settings['login_bg_color'] ) && '' !== $wprefi_login_settings['login_bg_color']
			? sanitize_hex_color( $wprefi_login_settings['login_bg_color'] )
			: '';

	// Primary szín.
	$primary_color = isset( $wprefi_login_settings['login_primary_color'] ) && '' !== $wprefi_login_settings['login_primary_color']
		? sanitize_hex_color( $wprefi_login_settings['login_primary_color'] )
		: '';

	// Nyelvválasztó elrejtése.
	$hide_language_switcher = ! empty( $wprefi_login_settings['login_hide_language_switcher'] );

	// CSS output.
	?>
	<style type="text/css">
			<?php if ( $logo_url ) : ?>
			#login h1 a,
			.login h1 a {
				background-image: url('<?php echo esc_url( $logo_url ); ?>');
				width: <?php echo (int) $logo_width; ?>px;
				height: <?php echo (int) $logo_height; ?>px;
				background-size: contain;
				background-position: center;
				background-repeat: no-repeat;
			}
			<?php endif; ?>

			<?php if ( $bg_color ) : ?>
			body.login {
				background: <?php echo esc_attr( $bg_color ); ?> !important;
			}
			<?php endif; ?>

			<?php if ( $primary_color ) : ?>
			.wp-core-ui .button-primary {
				background: <?php echo esc_attr( $primary_color ); ?> !important;
				border-color: <?php echo esc_attr( $primary_color ); ?> !important;
			}
			.wp-core-ui .button-primary:hover,
			.wp-core-ui .button-primary:focus {
				background: <?php echo esc_attr( $primary_color ); ?> !important;
				border-color: <?php echo esc_attr( $primary_color ); ?> !important;
				opacity: 0.9;
			}
			.login .language-switcher .button {
				color: <?php echo esc_attr( $primary_color ); ?> !important;
				border-color: <?php echo esc_attr( $primary_color ); ?> !important;
			}
			.login .button.wp-hide-pw .dashicons {
				color: <?php echo esc_attr( $primary_color ); ?> !important;
			}
			.login #backtoblog a,
			.login #nav a {
				color: <?php echo esc_attr( $primary_color ); ?> !important;
			}
			.login #backtoblog a:hover,
			.login #nav a:hover,
			.login h1 a:hover {
				color: <?php echo esc_attr( $primary_color ); ?> !important;
			}
			.login #backtoblog a:focus,
			.login #nav a:focus,
			.login h1 a:focus {
				color: <?php echo esc_attr( $primary_color ); ?> !important;
			}
		.language-switcher label .dashicons {
			color: <?php echo esc_attr( $primary_color ); ?> !important;
		}
		<?php endif; ?>

		<?php if ( $hide_language_switcher ) : ?>
		.language-switcher {
			display: none !important;
		}
		<?php endif; ?>
	</style>
	<?php
}
);
