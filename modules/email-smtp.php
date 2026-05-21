<?php
/**
 * Email SMTP konfiguráció és teljes levélküldés letiltása.
 *
 * - Teljes email letiltás: blokkolja az összes wp_mail() hívást.
 * - SMTP beállítások: PHPMailer konfigurálása egyedi SMTP szerverrel.
 *
 * @package WP_Refiner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$wprefi_smtp_settings = get_option( 'wprefi_settings', array() );
$email_mode           = isset( $wprefi_smtp_settings['email_mode'] ) ? $wprefi_smtp_settings['email_mode'] : 'default';

// ---------------------------------------------------------------------------
// 1. Teljes email letiltás (minden wp_mail() hívás blokkolása)
// ---------------------------------------------------------------------------
if ( 'disable_all' === $email_mode ) {
	add_filter(
		'pre_wp_mail',
		static function (): bool {
			return false;
		},
		1
	);

	// Admin bar figyelmeztetés piros email ikonnal.
	add_action(
		'admin_bar_menu',
		static function ( $wp_admin_bar ): void {
			$wp_admin_bar->add_node(
				array(
					'id'     => 'wprefi-email-disabled',
					'title'  => '<span class="ab-icon dashicons dashicons-email"></span><span style="color: #ffffff; font-weight: bold;">' . esc_html__( 'Emails: DISABLED!', 'refinerpress' ) . '</span>',
					'href'   => admin_url( 'tools.php?page=wprefi-settings' ),
					'meta'   => array(
						'title' => __( 'All email sending is disabled', 'refinerpress' ),
					),
				)
			);
		},
		999
	);

	// CSS a piros email ikon megjelenítéséhez.
	add_action(
		'admin_head',
		static function (): void {
			echo '<style>#wpadminbar #wp-admin-bar-wprefi-email-disabled .ab-icon:before { color: #dc3232 !important; }</style>';
		}
	);

	// Ha minden email le van tiltva, az SMTP konfig nem fut le.
	return;
}

// ---------------------------------------------------------------------------
// 2. SMTP konfiguráció (csak ha 'smtp' mód van beállítva)
// ---------------------------------------------------------------------------
if ( 'smtp' !== $email_mode ) {
	return;
}

$smtp_host = isset( $wprefi_smtp_settings['email_smtp_host'] ) ? trim( $wprefi_smtp_settings['email_smtp_host'] ) : '';

if ( '' === $smtp_host ) {
	return;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) use ( $wprefi_smtp_settings ): void {
		$phpmailer->isSMTP();

	$phpmailer->Host = sanitize_text_field( $wprefi_smtp_settings['email_smtp_host'] ?? '' );
	$phpmailer->Port = isset( $wprefi_smtp_settings['email_smtp_port'] ) ? (int) $wprefi_smtp_settings['email_smtp_port'] : 587;

	$encryption = isset( $wprefi_smtp_settings['email_smtp_encryption'] ) ? $wprefi_smtp_settings['email_smtp_encryption'] : 'tls';
	if ( 'none' !== $encryption ) {
		$phpmailer->SMTPSecure = $encryption;
	}

	// SSL/TLS opciók beállítása (főleg Windows/fejlesztői környezetben hasznos).
	if ( ! empty( $wprefi_smtp_settings['email_smtp_disable_ssl_verify'] ) ) {
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			),
		);
	}

	$username        = isset( $wprefi_smtp_settings['email_smtp_username'] ) ? trim( $wprefi_smtp_settings['email_smtp_username'] ) : '';
	$password_stored = isset( $wprefi_smtp_settings['email_smtp_password'] ) ? $wprefi_smtp_settings['email_smtp_password'] : '';

	// Jelszó dekódolása (Sodium titkosítással tárolva).
	$password = wprefi_decrypt( $password_stored );

	if ( '' !== $username ) {
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $username;
		$phpmailer->Password = $password;
	}

		// Feladó email: beállítás vagy WordPress admin email.
		$from_email_setting = isset( $wprefi_smtp_settings['email_smtp_from_email'] ) ? sanitize_email( $wprefi_smtp_settings['email_smtp_from_email'] ) : '';
		$from_email         = '' !== $from_email_setting ? $from_email_setting : get_option( 'admin_email' );

		// Feladó név: beállítás vagy WordPress site title.
		$from_name_setting = isset( $wprefi_smtp_settings['email_smtp_from_name'] ) ? sanitize_text_field( $wprefi_smtp_settings['email_smtp_from_name'] ) : '';
		$from_name         = '' !== $from_name_setting ? $from_name_setting : get_option( 'blogname' );

		$phpmailer->setFrom( $from_email, $from_name );
	}
);
