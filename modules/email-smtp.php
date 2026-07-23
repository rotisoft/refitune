<?php
/**
 * Email SMTP configuration and full email disable.
 *
 * - Disable all emails: blocks every wp_mail() call.
 * - SMTP settings: configure PHPMailer with a custom SMTP server.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_smtp_settings = refitune_get_settings();
$refitune_email_mode    = isset( $refitune_smtp_settings['email_mode'] ) ? $refitune_smtp_settings['email_mode'] : 'default';

// ---------------------------------------------------------------------------
// 1. Disable all emails (block every wp_mail() call)
// ---------------------------------------------------------------------------
if ( 'disable_all' === $refitune_email_mode ) {
	add_filter(
		'pre_wp_mail',
		static function (): bool {
			return false;
		},
		1
	);

	// Admin bar warning with a red email icon.
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

	// CSS for the red email icon.
	add_action(
		'admin_head',
		static function (): void {
			echo '<style>#wpadminbar #wp-admin-bar-refitune-email-disabled .ab-icon:before { color: #dc3232 !important; }</style>';
		},
		10
	);

	// When all emails are disabled, the SMTP configuration does not run.
	return;
}

// ---------------------------------------------------------------------------
// 2. SMTP configuration (only when 'smtp' mode is set)
// ---------------------------------------------------------------------------
if ( 'smtp' !== $refitune_email_mode ) {
	return;
}

$refitune_smtp_host = isset( $refitune_smtp_settings['email_smtp_host'] ) ? trim( $refitune_smtp_settings['email_smtp_host'] ) : '';

if ( '' === $refitune_smtp_host ) {
	return;
}

// SMTP requires the stored password to be decryptable. Without Sodium we cannot
// safely read the credentials, so the SMTP configuration is disabled and the
// admin is warned (see refitune_encryption_admin_notice()).
if ( ! empty( $refitune_smtp_settings['email_smtp_password'] ) && ! refitune_encryption_available() ) {
	return;
}

/**
 * Disable SSL certificate verification on PHPMailer (development / test only).
 *
 * @param PHPMailer\PHPMailer\PHPMailer $phpmailer PHPMailer instance.
 * @return void
 */
function refitune_smtp_disable_ssl_verify( $phpmailer ): void {
	$phpmailer->SMTPOptions = array(
		'ssl' => array(
			'verify_peer'       => false,
			'verify_peer_name'  => false,
			'allow_self_signed' => true,
		),
	);
}

/**
 * Whether SMTP test mode is enabled (no encryption + no cert verify).
 *
 * Always false in production environments.
 *
 * @param array $refitune_settings Plugin settings.
 * @return bool
 */
function refitune_smtp_is_test_mode( array $refitune_settings ): bool {
	if ( 'production' === wp_get_environment_type() ) {
		return false;
	}

	if ( ! empty( $refitune_settings['email_smtp_disable_for_test'] ) ) {
		return true;
	}

	// Legacy option keys from older RefiTune / WPRefi builds.
	if ( ! empty( $refitune_settings['email_smtp_disable_ssl_verify'] ) ) {
		return true;
	}

	return 'disable' === ( $refitune_settings['email_smtp_encryption'] ?? '' );
}

/**
 * Whether SSL certificate verification may be disabled for SMTP.
 *
 * @return bool
 */
function refitune_smtp_may_disable_ssl_verify(): bool {
	if ( 'production' === wp_get_environment_type() ) {
		return false;
	}

	return defined( 'REFITUNE_SMTP_DISABLE_SSL_VERIFY' ) && REFITUNE_SMTP_DISABLE_SSL_VERIFY;
}

add_action(
	'phpmailer_init',
	static function ( $phpmailer ) use ( $refitune_smtp_settings ): void {
		$phpmailer->isSMTP();

		$phpmailer->Host = sanitize_text_field( $refitune_smtp_settings['email_smtp_host'] ?? '' );
		$phpmailer->Port = isset( $refitune_smtp_settings['email_smtp_port'] ) ? (int) $refitune_smtp_settings['email_smtp_port'] : 587;

		$refitune_encryption = isset( $refitune_smtp_settings['email_smtp_encryption'] ) ? (string) $refitune_smtp_settings['email_smtp_encryption'] : 'tls';
		if ( 'disable' === $refitune_encryption ) {
			$refitune_encryption = 'none';
		}

		$refitune_test_mode   = refitune_smtp_is_test_mode( $refitune_smtp_settings );
		$refitune_is_production = ( 'production' === wp_get_environment_type() );

		// Production fail-closed: never run without TLS/SSL verification.
		if ( $refitune_is_production && 'none' === $refitune_encryption ) {
			$refitune_encryption = 'tls';
		}

		if ( $refitune_test_mode || 'none' === $refitune_encryption ) {
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		} else {
			$phpmailer->SMTPSecure = $refitune_encryption;
		}

		if ( $refitune_test_mode || refitune_smtp_may_disable_ssl_verify() ) {
			refitune_smtp_disable_ssl_verify( $phpmailer );
		}

		$refitune_username        = isset( $refitune_smtp_settings['email_smtp_username'] ) ? trim( $refitune_smtp_settings['email_smtp_username'] ) : '';
		$refitune_password_stored = isset( $refitune_smtp_settings['email_smtp_password'] ) ? $refitune_smtp_settings['email_smtp_password'] : '';

		// Decrypt the password (stored with Sodium encryption).
		$refitune_password = refitune_decrypt( $refitune_password_stored );

		if ( '' !== $refitune_username ) {
			$phpmailer->SMTPAuth = true;
			$phpmailer->Username = $refitune_username;
			$phpmailer->Password = $refitune_password;
		}

		// From email: setting or the WordPress admin email.
		$refitune_from_email_setting = isset( $refitune_smtp_settings['email_smtp_from_email'] ) ? sanitize_email( $refitune_smtp_settings['email_smtp_from_email'] ) : '';
		$refitune_from_email         = '' !== $refitune_from_email_setting ? $refitune_from_email_setting : get_option( 'admin_email' );

		// From name: setting or the WordPress site title.
		$refitune_from_name_setting = isset( $refitune_smtp_settings['email_smtp_from_name'] ) ? sanitize_text_field( $refitune_smtp_settings['email_smtp_from_name'] ) : '';
		$refitune_from_name         = '' !== $refitune_from_name_setting ? $refitune_from_name_setting : get_option( 'blogname' );

		$phpmailer->setFrom( $refitune_from_email, $refitune_from_name );
	}
);
