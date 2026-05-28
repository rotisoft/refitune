<?php
/**
 * Maintenance Mode (Karbantartási mód)
 *
 * Blokkolja a vendégeket és jogosulatlan felhasználókat az oldal eléréséből.
 * Az init hook (priority 1) használatával még a template betöltése előtt fut.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maintenance Mode ellenőrzés és blokkolás.
 */
function refitune_maintenance_mode_check(): void {
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
	$settings       = get_option( 'refitune_settings', array() );
	$allowed_roles  = isset( $settings['maintenance_mode_roles'] )
		? (array) $settings['maintenance_mode_roles']
		: array();
	$custom_message = isset( $settings['maintenance_mode_message'] )
		? trim( $settings['maintenance_mode_message'] )
		: '';

	// Ha nincs engedélyezett szerepkör, mindenkit blokkolunk
	if ( empty( $allowed_roles ) ) {
		refitune_show_maintenance_page( $custom_message );
		exit;
	}

	// Bejelentkezett felhasználó ellenőrzése
	if ( ! is_user_logged_in() ) {
		refitune_show_maintenance_page( $custom_message );
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
		refitune_show_maintenance_page( $custom_message );
		exit;
	}
}
add_action( 'init', 'refitune_maintenance_mode_check', 1 );

/**
 * Admin bar figyelmeztetés megjelenítése, ha maintenance mode aktív.
 *
 * @param WP_Admin_Bar $wp_admin_bar WordPress Admin Bar objektum.
 */
function refitune_maintenance_mode_admin_bar_notice( $wp_admin_bar ): void {
	$settings = get_option( 'refitune_settings', array() );
	if ( empty( $settings['maintenance_mode_enabled'] ) ) {
		return;
	}

	$wp_admin_bar->add_node(
		array(
			'id'    => 'refitune-maintenance-warning',
			'title' => '<span class="ab-icon dashicons dashicons-warning"></span><span class="refitune-maintenance-admin-bar-label">' . esc_html__( 'Maintenance: ACTIVE!', 'refitune' ) . '</span>',
			'href'  => admin_url( 'tools.php?page=refitune-settings' ),
			'meta'  => array(
				'title' => __( 'Maintenance Mode is currently active', 'refitune' ),
			),
		)
	);
}
add_action( 'admin_bar_menu', 'refitune_maintenance_mode_admin_bar_notice', 999 );

/**
 * Whether maintenance mode admin bar styles should load.
 *
 * @return bool
 */
function refitune_maintenance_mode_admin_bar_styles_needed(): bool {
	$settings = get_option( 'refitune_settings', array() );
	return ! empty( $settings['maintenance_mode_enabled'] );
}

/**
 * Enqueue admin bar styles (admin and front-end when toolbar is visible).
 */
function refitune_maintenance_mode_enqueue_admin_bar_styles(): void {
	if ( ! refitune_maintenance_mode_admin_bar_styles_needed() ) {
		return;
	}

	$css_file = REFITUNE_PATH . 'modules/css/maintenance-admin-bar.css';

	wp_enqueue_style(
		'refitune-maintenance-admin-bar',
		REFITUNE_URL . 'modules/css/maintenance-admin-bar.css',
		array(),
		file_exists( $css_file ) ? (string) filemtime( $css_file ) : REFITUNE_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'refitune_maintenance_mode_enqueue_admin_bar_styles', 10 );
add_action( 'wp_enqueue_scripts', 'refitune_maintenance_mode_enqueue_admin_bar_styles', 10 );

/**
 * URL for the maintenance page stylesheet (standalone template exits before wp_enqueue_scripts).
 *
 * @return string
 */
function refitune_maintenance_mode_get_page_stylesheet_url(): string {
	$css_file = REFITUNE_PATH . 'modules/css/maintenance-page.css';
	$version  = file_exists( $css_file ) ? (string) filemtime( $css_file ) : REFITUNE_VERSION;

	return add_query_arg(
		'ver',
		$version,
		REFITUNE_URL . 'modules/css/maintenance-page.css'
	);
}

/**
 * Maintenance oldal megjelenítése.
 *
 * @param string $custom_message Egyedi üzenet vagy üres.
 */
function refitune_show_maintenance_page( string $custom_message ): void {
	// 503 HTTP status code
	status_header( 503 );
	header( 'Retry-After: 3600' ); // 1 óra múlva próbálkozz újra

	// Default üzenet ha nincs custom
	$message = ! empty( $custom_message )
		? esc_html( $custom_message )
		: esc_html__( 'This site is temporarily under maintenance. Please check back soon!', 'refitune' );

	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<title><?php esc_html_e( 'Maintenance Mode', 'refitune' ); ?> - <?php bloginfo( 'name' ); ?></title>
		<link rel="stylesheet" href="<?php echo esc_url( refitune_maintenance_mode_get_page_stylesheet_url() ); ?>" />
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
