<?php
/**
 * Maintenance Mode (Karbantartási mód)
 *
 * Blokkolja a vendégeket és jogosulatlan felhasználókat az oldal eléréséből.
 * Az init hook (priority 1) használatával még a template betöltése előtt fut.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maintenance Mode ellenőrzés és blokkolás.
 */
function wprefi_maintenance_mode_check(): void {
	// Ne blokkolja az admin területet, AJAX-t, cron-t, és a login oldalt
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	// Ne blokkolja a wp-login.php oldalt (bejelentkezés)
	global $pagenow;
	if ( 'wp-login.php' === $pagenow ) {
		return;
	}

	// Beállítások betöltése
	$settings       = get_option( 'wprefi_settings', array() );
	$allowed_roles  = isset( $settings['maintenance_mode_roles'] )
		? (array) $settings['maintenance_mode_roles']
		: array();
	$custom_message = isset( $settings['maintenance_mode_message'] )
		? trim( $settings['maintenance_mode_message'] )
		: '';

	// Ha nincs engedélyezett szerepkör, mindenkit blokkolunk
	if ( empty( $allowed_roles ) ) {
		wprefi_show_maintenance_page( $custom_message );
		exit;
	}

	// Bejelentkezett felhasználó ellenőrzése
	if ( ! is_user_logged_in() ) {
		wprefi_show_maintenance_page( $custom_message );
		exit;
	}

	// Szerepkör ellenőrzés
	$user            = wp_get_current_user();
	$user_has_access = false;

	foreach ( (array) $user->roles as $role ) {
		if ( in_array( $role, $allowed_roles, true ) ) {
			$user_has_access = true;
			break;
		}
	}

	if ( ! $user_has_access ) {
		wprefi_show_maintenance_page( $custom_message );
		exit;
	}
}
add_action( 'init', 'wprefi_maintenance_mode_check', 1 );

/**
 * Admin bar figyelmeztetés megjelenítése, ha maintenance mode aktív.
 *
 * @param WP_Admin_Bar $wp_admin_bar WordPress Admin Bar objektum.
 */
function wprefi_maintenance_mode_admin_bar_notice( $wp_admin_bar ): void {
	$settings = get_option( 'wprefi_settings', array() );
	if ( empty( $settings['maintenance_mode_enabled'] ) ) {
		return;
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'wprefi-maintenance-warning',
			'title' => '<span class="ab-icon dashicons dashicons-warning"></span><span style="color: #ffffff; font-weight: bold;">Maintenance: ACTIVE!</span>',
			'href'  => admin_url( 'tools.php?page=wprefi-settings' ),
			'meta'  => array(
				'title' => __( 'Maintenance Mode is currently active', 'refinerpress' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'wprefi_maintenance_mode_admin_bar_notice', 999 );

/**
 * CSS a piros maintenance ikon megjelenítéséhez.
 */
function wprefi_maintenance_mode_admin_bar_css(): void {
	$settings = get_option( 'wprefi_settings', array() );
	if ( empty( $settings['maintenance_mode_enabled'] ) ) {
		return;
	}

	echo '<style>#wpadminbar #wp-admin-bar-wprefi-maintenance-warning .ab-icon:before { color: #dc3232 !important; }</style>';
}
add_action( 'admin_head', 'wprefi_maintenance_mode_admin_bar_css' );

/**
 * Maintenance oldal megjelenítése.
 *
 * @param string $custom_message Egyedi üzenet vagy üres.
 */
function wprefi_show_maintenance_page( string $custom_message ): void {
	// 503 HTTP status code
	status_header( 503 );
	header( 'Retry-After: 3600' ); // 1 óra múlva próbálkozz újra

	// Default üzenet ha nincs custom
	$message = ! empty( $custom_message )
		? esc_html( $custom_message )
		: esc_html__( 'This site is temporarily under maintenance. Please check back soon!', 'refinerpress' );

	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<title><?php esc_html_e( 'Maintenance Mode', 'refinerpress' ); ?> - <?php bloginfo( 'name' ); ?></title>
		<style>
			* {
				margin: 0;
				padding: 0;
				box-sizing: border-box;
			}
			body {
				font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
				background: #ffffff;
				color: #1d2327;
				display: flex;
				align-items: center;
				justify-content: center;
				min-height: 100vh;
				padding: 20px;
				line-height: 1.6;
			}
		.maintenance-container {
			text-align: center;
		}
		.maintenance-container h1 {
			font-size: 1.5rem;
			font-weight: 400;
			margin-bottom: 20px;
			color: #1d2327;
		}
		.maintenance-container p {
			font-size: 3rem;
			color: #50575e;
			white-space: pre-wrap;
			word-wrap: break-word;
		}
		</style>
	</head>
	<body>
		<div class="maintenance-container">
			<h1><?php bloginfo( 'name' ); ?></h1>
			<p><?php echo $message; // Already escaped above. ?></p>
		</div>
	</body>
	</html>
	<?php
}
