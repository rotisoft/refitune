<?php
/**
 * Maintenance Mode
 *
 * Blocks guests and unauthorized users from accessing the site.
 * Runs on the init hook (priority 1), before the template loads.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Whether the current user may access the site during maintenance.
 *
 * @return bool
 */
function refitune_maintenance_mode_user_has_access(): bool {
	$refitune_settings      = refitune_get_settings();
	$refitune_allowed_roles = isset( $refitune_settings['maintenance_mode_roles'] )
		? (array) $refitune_settings['maintenance_mode_roles']
		: array();

	// No allowed roles configured: block everyone.
	if ( empty( $refitune_allowed_roles ) || ! is_user_logged_in() ) {
		return false;
	}

	$refitune_user = wp_get_current_user();

	foreach ( (array) $refitune_user->roles as $refitune_role ) {
		if ( in_array( $refitune_role, $refitune_allowed_roles, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Maintenance Mode front-end check and blocking.
 */
function refitune_maintenance_mode_check(): void {
	// Do not block the admin area, AJAX, or cron.
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() ) {
		return;
	}

	// Do not block wp-login.php (users must be able to log in).
	global $pagenow;
	if ( 'wp-login.php' === $pagenow ) {
		return;
	}

	// REST requests are handled separately on rest_pre_dispatch.
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return;
	}

	if ( refitune_maintenance_mode_user_has_access() ) {
		return;
	}

	$refitune_settings       = refitune_get_settings();
	$refitune_custom_message = isset( $refitune_settings['maintenance_mode_message'] )
		? trim( $refitune_settings['maintenance_mode_message'] )
		: '';

	refitune_show_maintenance_page( $refitune_custom_message );
	exit;
}
add_action( 'init', 'refitune_maintenance_mode_check', 1 );

/**
 * Block REST API requests during maintenance with a 503 response.
 *
 * @param mixed $result Dispatch result to short-circuit with.
 * @return mixed
 */
function refitune_maintenance_mode_block_rest( $result ) {
	if ( refitune_maintenance_mode_user_has_access() ) {
		return $result;
	}

	return new WP_Error(
		'maintenance_mode',
		__( 'This site is temporarily under maintenance. Please check back soon!', 'refitune' ),
		array( 'status' => 503 )
	);
}
add_filter( 'rest_pre_dispatch', 'refitune_maintenance_mode_block_rest', 10 );

/**
 * Show an admin bar warning while maintenance mode is active.
 *
 * @param WP_Admin_Bar $wp_admin_bar WordPress Admin Bar object.
 */
function refitune_maintenance_mode_admin_bar_notice( $wp_admin_bar ): void {
	$refitune_settings = refitune_get_settings();
	if ( empty( $refitune_settings['maintenance_mode_enabled'] ) ) {
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
	$refitune_settings = refitune_get_settings();
	return ! empty( $refitune_settings['maintenance_mode_enabled'] );
}

/**
 * Enqueue admin bar styles (admin and front-end when toolbar is visible).
 */
function refitune_maintenance_mode_enqueue_admin_bar_styles(): void {
	if ( ! refitune_maintenance_mode_admin_bar_styles_needed() ) {
		return;
	}

	$refitune_css_file = REFITUNE_PATH . 'modules/css/maintenance-admin-bar.css';

	wp_enqueue_style(
		'refitune-maintenance-admin-bar',
		REFITUNE_URL . 'modules/css/maintenance-admin-bar.css',
		array(),
		file_exists( $refitune_css_file ) ? (string) filemtime( $refitune_css_file ) : REFITUNE_VERSION
	);
}
add_action( 'admin_enqueue_scripts', 'refitune_maintenance_mode_enqueue_admin_bar_styles', 10 );
add_action( 'wp_enqueue_scripts', 'refitune_maintenance_mode_enqueue_admin_bar_styles', 10 );

/**
 * Render the maintenance page.
 *
 * @param string $refitune_custom_message Custom message or empty string.
 */
function refitune_show_maintenance_page( string $refitune_custom_message ): void {
	status_header( 503 );
	header( 'Retry-After: 3600' ); // Ask clients to retry after one hour.

	$refitune_css_file = REFITUNE_PATH . 'modules/css/maintenance-page.css';

	wp_enqueue_style(
		'refitune-maintenance-page',
		REFITUNE_URL . 'modules/css/maintenance-page.css',
		array(),
		file_exists( $refitune_css_file ) ? (string) filemtime( $refitune_css_file ) : REFITUNE_VERSION
	);

	$refitune_message = ! empty( $refitune_custom_message )
		? $refitune_custom_message
		: __( 'This site is temporarily under maintenance. Please check back soon!', 'refitune' );

	?>
	<!DOCTYPE html>
	<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="robots" content="noindex, nofollow">
		<title><?php esc_html_e( 'Maintenance Mode', 'refitune' ); ?> - <?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
		<?php wp_print_styles( array( 'refitune-maintenance-page' ) ); ?>
	</head>
	<body>
		<div class="maintenance-container">
			<h1><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			<p><?php echo esc_html( $refitune_message ); ?></p>
		</div>
	</body>
	</html>
	<?php
}
