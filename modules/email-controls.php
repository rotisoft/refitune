<?php
/**
 * Email notification controls - disable and redirect system emails.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_email_settings = refitune_get_settings();

// ---------------------------------------------------------------------------
// 1. Update notifications (core, plugin, theme auto-update)
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_update'] ) ) {
	$refitune_update_addr = ! empty( $refitune_email_settings['email_update_address'] )
		? sanitize_email( $refitune_email_settings['email_update_address'] )
		: '';

	if ( $refitune_update_addr ) {
		add_filter( 'auto_core_update_email',   'refitune_email_redirect_update', 10 );
		add_filter( 'auto_plugin_update_email', 'refitune_email_redirect_update', 10 );
		add_filter( 'auto_theme_update_email',  'refitune_email_redirect_update', 10 );
	} else {
		add_filter( 'auto_core_update_send_email',   '__return_false', 10 );
		add_filter( 'auto_plugin_update_send_email', '__return_false', 10 );
		add_filter( 'auto_theme_update_send_email',  '__return_false', 10 );
	}
}

/**
 * Rewrite the update email "To" field to the custom address.
 *
 * @param array $email Email data.
 * @return array
 */
function refitune_email_redirect_update( array $email ): array {
	$refitune_settings = refitune_get_settings();
	$refitune_addr     = sanitize_email( $refitune_settings['email_update_address'] ?? '' );
	if ( $refitune_addr ) {
		$email['to'] = $refitune_addr;
	}
	return $email;
}

// ---------------------------------------------------------------------------
// 2. New user registration notification (admin)
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_new_user'] ) ) {
	remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
	add_action(
		'register_new_user',
		static function ( int $user_id ): void {
			wp_new_user_notification( $user_id, null, 'user' );
		},
		10
	);
}

// ---------------------------------------------------------------------------
// 3. Password reset - admin notification
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_password_reset'] ) ) {
	remove_action( 'after_password_reset', 'wp_password_change_notification' );
}

// ---------------------------------------------------------------------------
// 4. Comment notifications
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_comments'] ) ) {
	add_filter( 'notify_moderator',   '__return_false', 10 );
	add_filter( 'notify_post_author', '__return_false', 10 );
}

// ---------------------------------------------------------------------------
// 5. Privacy (GDPR) notifications
//
// Sending is stopped via the `pre_wp_mail` filter (WP 5.7+). The flag is set
// by the specific privacy email filters right before wp_mail() is called.
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_privacy'] ) ) {
	$refitune_block_privacy_mail = false;

	add_filter(
		'wp_privacy_personal_data_email_content',
		static function ( string $content ) use ( &$refitune_block_privacy_mail ): string {
			$refitune_block_privacy_mail = true;
			return $content;
		},
		999
	);

	add_filter(
		'user_request_action_email_content',
		static function ( string $content ) use ( &$refitune_block_privacy_mail ): string {
			$refitune_block_privacy_mail = true;
			return $content;
		},
		999
	);

	add_filter(
		'user_erasure_complete_email_message',
		static function ( string $message ) use ( &$refitune_block_privacy_mail ): string {
			$refitune_block_privacy_mail = true;
			return $message;
		},
		999
	);

	add_filter(
		'pre_wp_mail',
		static function ( $null, array $atts ) use ( &$refitune_block_privacy_mail ) {
			if ( $refitune_block_privacy_mail ) {
				$refitune_block_privacy_mail = false;
				return false;
			}
			return $null;
		},
		10,
		2
	);
}

// ---------------------------------------------------------------------------
// 6. Critical error (recovery mode) email
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_critical'] ) ) {
	$refitune_critical_addr = ! empty( $refitune_email_settings['email_critical_address'] )
		? sanitize_email( $refitune_email_settings['email_critical_address'] )
		: '';

	if ( $refitune_critical_addr ) {
		// Redirect the recovery mode email to the custom address.
		add_filter(
			'recovery_mode_email',
			static function ( array $email ) use ( $refitune_critical_addr ): array {
				$email['to'] = $refitune_critical_addr;
				return $email;
			},
			10
		);
	} else {
		// No redirect address: block the send entirely. Emptying the "to"
		// field does not reliably stop wp_mail(), so flag the message on
		// recovery_mode_email and short-circuit it in pre_wp_mail.
		$refitune_block_critical_mail = false;

		add_filter(
			'recovery_mode_email',
			static function ( array $email ) use ( &$refitune_block_critical_mail ): array {
				$refitune_block_critical_mail = true;
				return $email;
			},
			999
		);

		add_filter(
			'pre_wp_mail',
			static function ( $null, array $atts ) use ( &$refitune_block_critical_mail ) {
				if ( $refitune_block_critical_mail ) {
					$refitune_block_critical_mail = false;
					return false;
				}
				return $null;
			},
			10,
			2
		);
	}
}
