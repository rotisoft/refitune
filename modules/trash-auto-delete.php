<?php
/**
 * Module: Trash Auto-Delete
 *
 * Overrides the daily trash cleanup cron with a plugin-configured retention period.
 * Does not redefine EMPTY_TRASH_DAYS (that constant is set before plugins load).
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get configured trash retention in days.
 *
 * @return int Days, or 0 when not configured.
 */
function refitune_get_empty_trash_days(): int {
	$settings = get_option( 'refitune_settings', array() );

	if ( ! isset( $settings['trash_auto_delete_days'] ) || '' === $settings['trash_auto_delete_days'] ) {
		return 0;
	}

	return max( 1, (int) $settings['trash_auto_delete_days'] );
}

/**
 * Swap core trash cleanup for the plugin handler when this module is active.
 *
 * @return void
 */
function refitune_override_scheduled_trash_delete(): void {
	if ( refitune_get_empty_trash_days() <= 0 ) {
		return;
	}

	remove_action( 'wp_scheduled_delete', 'wp_scheduled_delete' );
	add_action( 'wp_scheduled_delete', 'refitune_scheduled_empty_trash', 10 );
}
add_action( 'init', 'refitune_override_scheduled_trash_delete', 1 );

/**
 * Permanently delete trashed posts and comments past the configured retention.
 *
 * Same behaviour as wp_scheduled_delete() but uses refitune_get_empty_trash_days().
 *
 * @return void
 */
function refitune_scheduled_empty_trash(): void {
	global $wpdb;

	$days = refitune_get_empty_trash_days();

	if ( $days <= 0 ) {
		return;
	}

	$delete_timestamp = time() - ( DAY_IN_SECONDS * $days );

	$posts_to_delete = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d",
			$delete_timestamp
		),
		ARRAY_A
	);

	foreach ( (array) $posts_to_delete as $post ) {
		$post_id = (int) $post['post_id'];
		if ( ! $post_id ) {
			continue;
		}

		$del_post = get_post( $post_id );

		if ( ! $del_post || 'trash' !== $del_post->post_status ) {
			delete_post_meta( $post_id, '_wp_trash_meta_status' );
			delete_post_meta( $post_id, '_wp_trash_meta_time' );
		} else {
			wp_delete_post( $post_id );
		}
	}

	$comments_to_delete = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d",
			$delete_timestamp
		),
		ARRAY_A
	);

	foreach ( (array) $comments_to_delete as $comment ) {
		$comment_id = (int) $comment['comment_id'];
		if ( ! $comment_id ) {
			continue;
		}

		$del_comment = get_comment( $comment_id );

		if ( ! $del_comment || 'trash' !== $del_comment->comment_approved ) {
			delete_comment_meta( $comment_id, '_wp_trash_meta_time' );
			delete_comment_meta( $comment_id, '_wp_trash_meta_status' );
		} else {
			wp_delete_comment( $del_comment );
		}
	}
}
