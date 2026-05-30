<?php
/**
 * Email SMTP konfiguráció és teljes levélküldés letiltása.
 *
 * - Teljes email letiltás: blokkolja az összes wp_mail() hívást.
 * - SMTP beállítások: PHPMailer konfigurálása egyedi SMTP szerverrel.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_smtp_settings = get_option( 'refitune_settings', array() );
$email_mode           = isset( $refitune_smtp_settings['email_mode'] ) ? $refitune_smtp_settings['email_mode'] : 'default';

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
					'id'     => 'refitune-email-disabled',
					'title'  => '<span class="ab-icon dashicons dashicons-email"></span><span style="color: #ffffff; font-weight: bold;">' . esc_html__( 'Emails: DISABLED!', 'refitune' ) . '</span>',
					'href'   => admin_url( 'tools.php?page=refitune-settings' ),
					'meta'   => array(
						'title' => __( 'All email sending is disabled', 'refitune' ),
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
			echo '<style>#wpadminbar #wp-admin-bar-refitune-email-disabled .ab-icon:before { color: #dc3232 !important; }</style>';
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

$smtp_host = isset( $refitune_smtp_settings['email_smtp_host'] ) ? trim( $refitune_smtp_settings['email_smtp_host'] ) : '';

if ( '' === $smtp_host ) {
	return;
}

// SMTP requires the stored password to be decryptable. Without Sodium we cannot
// safely read the credentials, so the SMTP configuration is disabled and the
// admin is warned (see refitune_encryption_admin_notice()).
if ( ! empty( $refitune_smtp_settings['email_smtp_password'] ) && ! refitune_encryption_available() ) {
	return;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) use ( $refitune_smtp_settings ): void {
		$phpmailer->isSMTP();

	$phpmailer->Host = sanitize_text_field( $refitune_smtp_settings['email_smtp_host'] ?? '' );
	$phpmailer->Port = isset( $refitune_smtp_settings['email_smtp_port'] ) ? (int) $refitune_smtp_settings['email_smtp_port'] : 587;

	$encryption = isset( $refitune_smtp_settings['email_smtp_encryption'] ) ? $refitune_smtp_settings['email_smtp_encryption'] : 'tls';
	if ( 'none' !== $encryption ) {
		$phpmailer->SMTPSecure = $encryption;
	}

	// SSL/TLS options: only when explicitly enabled in wp-config.php (development).
	if ( defined( 'REFITUNE_SMTP_DISABLE_SSL_VERIFY' ) && REFITUNE_SMTP_DISABLE_SSL_VERIFY ) {
		$phpmailer->SMTPOptions = array(
			'ssl' => array(
				'verify_peer'       => false,
				'verify_peer_name'  => false,
				'allow_self_signed' => true,
			),
		);
	}

	$username        = isset( $refitune_smtp_settings['email_smtp_username'] ) ? trim( $refitune_smtp_settings['email_smtp_username'] ) : '';
	$password_stored = isset( $refitune_smtp_settings['email_smtp_password'] ) ? $refitune_smtp_settings['email_smtp_password'] : '';

	// Jelszó dekódolása (Sodium titkosítással tárolva).
	$password = refitune_decrypt( $password_stored );

	if ( '' !== $username ) {
		$phpmailer->SMTPAuth = true;
		$phpmailer->Username = $username;
		$phpmailer->Password = $password;
	}

		// Feladó email: beállítás vagy WordPress admin email.
		$from_email_setting = isset( $refitune_smtp_settings['email_smtp_from_email'] ) ? sanitize_email( $refitune_smtp_settings['email_smtp_from_email'] ) : '';
		$from_email         = '' !== $from_email_setting ? $from_email_setting : get_option( 'admin_email' );

		// Feladó név: beállítás vagy WordPress site title.
		$from_name_setting = isset( $refitune_smtp_settings['email_smtp_from_name'] ) ? sanitize_text_field( $refitune_smtp_settings['email_smtp_from_name'] ) : '';
		$from_name         = '' !== $from_name_setting ? $from_name_setting : get_option( 'blogname' );

		$phpmailer->setFrom( $from_email, $from_name );
	}
);
