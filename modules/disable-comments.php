<?php
/**
 * Disable comments site-wide.
 *
 * - Closes comments on every post at runtime (no DB changes).
 * - Removes comment support from all post types.
 * - Blocks submissions via the REST API and wp-comments-post.php.
 * - Removes the Comments menu and the dashboard widget.
 * - Optionally keeps product reviews when WooCommerce is active.
 *
 * WooCommerce detection runs on plugins_loaded so load order does not matter.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register disable-comments hooks after all plugins have loaded.
 *
 * @return void
 */
function refitune_disable_comments_init(): void {
	$refitune_comments_settings = refitune_get_settings();
	$refitune_keep_reviews      = class_exists( 'WooCommerce' )
		&& ! empty( $refitune_comments_settings['disable_comments_keep_reviews'] );

	// ---------------------------------------------------------------------------
	// 1. Close comments on every post (at runtime, without touching the DB)
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
	// 2. New posts default to closed comments
	// ---------------------------------------------------------------------------
	add_filter( 'default_comment_status', '__return_false', 10 );

	// ---------------------------------------------------------------------------
	// 3. Remove comment support from all post types (init prio 100 so that
	//    WooCommerce post types are already registered)
	// ---------------------------------------------------------------------------
	add_action(
		'init',
		static function () use ( $refitune_keep_reviews ): void {
			foreach ( get_post_types() as $refitune_post_type ) {
				if ( $refitune_keep_reviews && 'product' === $refitune_post_type ) {
					continue;
				}
				if ( post_type_supports( $refitune_post_type, 'comments' ) ) {
					remove_post_type_support( $refitune_post_type, 'comments' );
				}
			}
		},
		100
	);

	// ---------------------------------------------------------------------------
	// 4. REST API: block comment submission
	//    (WP REST endpoint: /wp/v2/comments)
	// ---------------------------------------------------------------------------
	add_filter(
		'rest_pre_insert_comment',
		static function ( $prepared, $request ) use ( $refitune_keep_reviews ) {
			unset( $request );

			if ( $refitune_keep_reviews ) {
				$refitune_post_id = isset( $prepared->comment_post_ID ) ? (int) $prepared->comment_post_ID : 0;
				if ( $refitune_post_id && get_post_type( $refitune_post_id ) === 'product' ) {
					return $prepared;
				}
			}
			return new WP_Error(
				'rest_comment_forbidden',
				__( 'Comments are disabled on this site.', 'refitune' ),
				array( 'status' => 403 )
			);
		},
		1,
		2
	);

	// ---------------------------------------------------------------------------
	// 5. Block classic form submission (wp-comments-post.php)
	//    Extra protection layer next to the comments_open filter.
	// ---------------------------------------------------------------------------
	add_action(
		'pre_comment_on_post',
		static function ( int $post_id ) use ( $refitune_keep_reviews ): void {
			if ( $refitune_keep_reviews && get_post_type( $post_id ) === 'product' ) {
				return;
			}
			wp_die(
				esc_html__( 'Comments are disabled on this site.', 'refitune' ),
				'',
				array( 'response' => 403 )
			);
		},
		10
	);

	// ---------------------------------------------------------------------------
	// 6. Admin menu: remove the Comments menu item
	// ---------------------------------------------------------------------------
	add_action(
		'admin_menu',
		static function (): void {
			remove_menu_page( 'edit-comments.php' );
		},
		99
	);

	// ---------------------------------------------------------------------------
	// 7. Admin bar: remove the comments icon
	// ---------------------------------------------------------------------------
	add_action(
		'wp_before_admin_bar_render',
		static function (): void {
			global $wp_admin_bar;
			if ( $wp_admin_bar instanceof WP_Admin_Bar ) {
				$wp_admin_bar->remove_menu( 'comments' );
			}
		},
		10
	);

	// ---------------------------------------------------------------------------
	// 8. Dashboard widget: remove recent comments
	// ---------------------------------------------------------------------------
	add_action(
		'wp_dashboard_setup',
		static function (): void {
			remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
		},
		10
	);

	// ---------------------------------------------------------------------------
	// 9. Post edit screens: remove the Discussion meta boxes
	// ---------------------------------------------------------------------------
	add_action(
		'admin_init',
		static function () use ( $refitune_keep_reviews ): void {
			foreach ( get_post_types( array( 'public' => true ) ) as $refitune_post_type ) {
				if ( $refitune_keep_reviews && 'product' === $refitune_post_type ) {
					continue;
				}
				remove_meta_box( 'commentstatusdiv', $refitune_post_type, 'normal' );
				remove_meta_box( 'commentsdiv', $refitune_post_type, 'normal' );
			}
		},
		10
	);
}
add_action( 'plugins_loaded', 'refitune_disable_comments_init', 20 );
