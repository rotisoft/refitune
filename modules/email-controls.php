<?php
/**
 * Email értesítések vezérlése – letiltás és átirányítás.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_email_settings = get_option( 'refitune_settings', array() );

// ---------------------------------------------------------------------------
// 1. Frissítési értesítők (core, plugin, téma auto-update)
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_update'] ) ) {
	$refitune_update_addr = ! empty( $refitune_email_settings['email_update_address'] )
		? sanitize_email( $refitune_email_settings['email_update_address'] )
		: '';

	if ( $refitune_update_addr ) {
		add_filter( 'auto_core_update_email',   'refitune_email_redirect_update' );
		add_filter( 'auto_plugin_update_email', 'refitune_email_redirect_update' );
		add_filter( 'auto_theme_update_email',  'refitune_email_redirect_update' );
	} else {
		add_filter( 'auto_core_update_send_email',   '__return_false' );
		add_filter( 'auto_plugin_update_send_email', '__return_false' );
		add_filter( 'auto_theme_update_send_email',  '__return_false' );
	}
}

/**
 * Frissítési email „To" mezőjének átírása az egyedi címre.
 *
 * @param array $email Email adatok.
 * @return array
 */
function refitune_email_redirect_update( array $email ): array {
	$settings = get_option( 'refitune_settings', array() );
	$addr     = sanitize_email( $settings['email_update_address'] ?? '' );
	if ( $addr ) {
		$email['to'] = $addr;
	}
	return $email;
}

// ---------------------------------------------------------------------------
// 2. Új felhasználó regisztrációs értesítő (admin)
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_new_user'] ) ) {
	remove_action( 'register_new_user', 'wp_send_new_user_notifications' );
	add_action(
		'register_new_user',
		static function ( int $user_id ): void {
			wp_new_user_notification( $user_id, null, 'user' );
		}
	);
}

// ---------------------------------------------------------------------------
// 3. Jelszó visszaállítás – admin értesítő
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_password_reset'] ) ) {
	remove_action( 'after_password_reset', 'wp_password_change_notification' );
}

// ---------------------------------------------------------------------------
// 4. Komment értesítők
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_comments'] ) ) {
	add_filter( 'notify_moderator',   '__return_false' );
	add_filter( 'notify_post_author', '__return_false' );
}

// ---------------------------------------------------------------------------
// 5. Adatvédelmi (GDPR) értesítők
//
// A `pre_wp_mail` filter (WP 5.7+) segítségével leállítjuk a küldést.
// A flag-et a konkrét privacy-email filterek állítják be, közvetlenül
// a wp_mail() hívása előtt.
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
// 6. Kritikus hiba email
// ---------------------------------------------------------------------------
if ( ! empty( $refitune_email_settings['email_disable_critical'] ) ) {
	$refitune_critical_addr = ! empty( $refitune_email_settings['email_critical_address'] )
		? sanitize_email( $refitune_email_settings['email_critical_address'] )
		: '';

	add_filter(
		'recovery_mode_email',
		static function ( array $email ) use ( $refitune_critical_addr ): array {
			$email['to'] = $refitune_critical_addr ?: '';
			return $email;
		}
	);
}
