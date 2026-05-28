<?php
/**
 * Hozzászólások teljes letiltása.
 *
 * - Lezárja a hozzászólásokat minden bejegyzésen futásidőben (DB módosítás nélkül).
 * - Eltávolítja a comment support-ot minden post type-ból.
 * - Blokkolja a REST API-n és wp-comments-post.php-n keresztüli beküldést.
 * - Eltávolítja a Hozzászólások menüt és a dashboard widzetet.
 * - WooCommerce aktív esetén opcionálisan megőrzi a termék értékeléseket.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$refitune_comments_settings = get_option( 'refitune_settings', array() );
$refitune_keep_reviews      = class_exists( 'WooCommerce' )
	&& ! empty( $refitune_comments_settings['disable_comments_keep_reviews'] );

// Egyszeri WordPress core opció frissítés (Site Health kompatibilitás).
// Ha a Disable Comments aktív, de a WordPress opciókat még nem állítottuk át,
// akkor most megtesszük.
if ( ! get_transient( 'refitune_disable_comments_migrated' ) ) {
	if ( get_option( 'default_comment_status' ) !== 'closed' ) {
		update_option( 'default_comment_status', 'closed' );
	}
	if ( get_option( 'default_ping_status' ) !== 'closed' ) {
		update_option( 'default_ping_status', 'closed' );
	}
	set_transient( 'refitune_disable_comments_migrated', true, WEEK_IN_SECONDS );
}

// ---------------------------------------------------------------------------
// 1. Hozzászólások lezárása minden bejegyzésen (futásidőben, DB érintése nélkül)
// ---------------------------------------------------------------------------
add_filter(
	'comments_open',
	static function ( $open, $post_id ) use ( $refitune_keep_reviews ): bool {
		if ( $refitune_keep_reviews && $post_id && get_post_type( $post_id ) === 'product' ) {
			return (bool) $open;
		}
		return false;
	},
	99,
	2
);

// ---------------------------------------------------------------------------
// 2. Új bejegyzéseknél alapértelmezetten zárva
// ---------------------------------------------------------------------------
add_filter( 'default_comment_status', '__return_false' );

// ---------------------------------------------------------------------------
// 3. Comment support eltávolítása minden post type-ból (init prio 100,
//    hogy a WooCommerce post type-ok is regisztrálva legyenek már)
// ---------------------------------------------------------------------------
add_action(
	'init',
	static function () use ( $refitune_keep_reviews ): void {
		foreach ( get_post_types() as $post_type ) {
			if ( $refitune_keep_reviews && 'product' === $post_type ) {
				continue;
			}
			if ( post_type_supports( $post_type, 'comments' ) ) {
				remove_post_type_support( $post_type, 'comments' );
			}
		}
	},
	100
);

// ---------------------------------------------------------------------------
// 4. REST API: hozzászólás beküldés blokkolása
//    (WP REST endpoint: /wp/v2/comments)
// ---------------------------------------------------------------------------
add_filter(
	'rest_pre_insert_comment',
	static function ( $prepared, $request ) use ( $refitune_keep_reviews ) {
		if ( $refitune_keep_reviews ) {
			$post_id = isset( $prepared->comment_post_ID ) ? (int) $prepared->comment_post_ID : 0;
			if ( $post_id && get_post_type( $post_id ) === 'product' ) {
				return $prepared;
			}
		}
		return new WP_Error(
			'rest_comment_forbidden',
			__( 'Hozzászólások le vannak tiltva ezen a webhelyen.', 'refitune' ),
			array( 'response' => 403 )
		);
	},
	1,
	2
);

// ---------------------------------------------------------------------------
// 5. Hagyományos form beküldés blokkolása (wp-comments-post.php)
//    Extra védelmi réteg a comments_open filter mellett.
// ---------------------------------------------------------------------------
add_action(
	'pre_comment_on_post',
	static function ( int $post_id ) use ( $refitune_keep_reviews ): void {
		if ( $refitune_keep_reviews && get_post_type( $post_id ) === 'product' ) {
			return;
		}
		wp_die(
			esc_html__( 'Hozzászólások le vannak tiltva ezen a webhelyen.', 'refitune' ),
			'',
			array( 'response' => 403 )
		);
	}
);

// ---------------------------------------------------------------------------
// 6. Admin menü: Hozzászólások menüpont eltávolítása
// ---------------------------------------------------------------------------
add_action(
	'admin_menu',
	static function (): void {
		remove_menu_page( 'edit-comments.php' );
	},
	99
);

// ---------------------------------------------------------------------------
// 7. Admin sáv: hozzászólás ikon eltávolítása
// ---------------------------------------------------------------------------
add_action(
	'wp_before_admin_bar_render',
	static function (): void {
		global $wp_admin_bar;
		if ( $wp_admin_bar instanceof WP_Admin_Bar ) {
			$wp_admin_bar->remove_menu( 'comments' );
		}
	}
);

// ---------------------------------------------------------------------------
// 8. Dashboard widget: legutóbbi hozzászólások eltávolítása
// ---------------------------------------------------------------------------
add_action(
	'wp_dashboard_setup',
	static function (): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}
);

// ---------------------------------------------------------------------------
// 9. Admin bejegyzés listánál: Discussion metabox eltávolítása
// ---------------------------------------------------------------------------
add_action(
	'admin_init',
	static function () use ( $refitune_keep_reviews ): void {
		foreach ( get_post_types( array( 'public' => true ) ) as $post_type ) {
			if ( $refitune_keep_reviews && 'product' === $post_type ) {
				continue;
			}
			remove_meta_box( 'commentstatusdiv', $post_type, 'normal' );
			remove_meta_box( 'commentsdiv',      $post_type, 'normal' );
		}
	}
);
