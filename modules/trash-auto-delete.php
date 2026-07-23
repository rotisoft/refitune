<?php
/**
 * Module: Trash Auto-Delete
 *
 * Overrides the daily trash cleanup cron with a plugin-configured retention period.
 * Does not redefine EMPTY_TRASH_DAYS (that constant is set before plugins load).
 *
 * Deletes in bounded batches and reschedules when more expired items remain.
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
	$refitune_settings = refitune_get_settings();

	if ( ! isset( $refitune_settings['trash_auto_delete_days'] ) || '' === $refitune_settings['trash_auto_delete_days'] ) {
		return 0;
	}

	return max( 1, (int) $refitune_settings['trash_auto_delete_days'] );
}

/**
 * Maximum trash items to process per cron run.
 *
 * @return int
 */
function refitune_trash_delete_batch_size(): int {
	$refitune_batch = (int) apply_filters( 'refitune_trash_delete_batch_size', 100 );

	return max( 1, min( 500, $refitune_batch ) );
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
	add_action( 'refitune_trash_delete_continue', 'refitune_scheduled_empty_trash', 10 );
}
add_action( 'init', 'refitune_override_scheduled_trash_delete', 1 );

/**
 * Permanently delete trashed posts and comments past the configured retention.
 *
 * Processes a limited batch per run. When the batch is full, schedules another
 * pass so large trash queues do not exhaust memory or cron time limits.
 *
 * @return void
 */
function refitune_scheduled_empty_trash(): void {
	global $wpdb;

	$refitune_days = refitune_get_empty_trash_days();

	if ( $refitune_days <= 0 ) {
		return;
	}

	$refitune_delete_timestamp = time() - ( DAY_IN_SECONDS * $refitune_days );
	$refitune_batch_size       = refitune_trash_delete_batch_size();
	$refitune_needs_continue   = false;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded cron cleanup; mirrors core trash meta lookup.
	$refitune_posts_to_delete = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d LIMIT %d",
			$refitune_delete_timestamp,
			$refitune_batch_size
		),
		ARRAY_A
	);

	if ( count( (array) $refitune_posts_to_delete ) >= $refitune_batch_size ) {
		$refitune_needs_continue = true;
	}

	foreach ( (array) $refitune_posts_to_delete as $refitune_post ) {
		$refitune_post_id = (int) $refitune_post['post_id'];
		if ( ! $refitune_post_id ) {
			continue;
		}

		$refitune_del_post = get_post( $refitune_post_id );

		if ( ! $refitune_del_post || 'trash' !== $refitune_del_post->post_status ) {
			delete_post_meta( $refitune_post_id, '_wp_trash_meta_status' );
			delete_post_meta( $refitune_post_id, '_wp_trash_meta_time' );
		} else {
			wp_delete_post( $refitune_post_id );
		}
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bounded cron cleanup; mirrors core trash meta lookup.
	$refitune_comments_to_delete = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT comment_id FROM $wpdb->commentmeta WHERE meta_key = '_wp_trash_meta_time' AND meta_value < %d LIMIT %d",
			$refitune_delete_timestamp,
			$refitune_batch_size
		),
		ARRAY_A
	);

	if ( count( (array) $refitune_comments_to_delete ) >= $refitune_batch_size ) {
		$refitune_needs_continue = true;
	}

	foreach ( (array) $refitune_comments_to_delete as $refitune_comment ) {
		$refitune_comment_id = (int) $refitune_comment['comment_id'];
		if ( ! $refitune_comment_id ) {
			continue;
		}

		$refitune_del_comment = get_comment( $refitune_comment_id );

		if ( ! $refitune_del_comment || 'trash' !== $refitune_del_comment->comment_approved ) {
			delete_comment_meta( $refitune_comment_id, '_wp_trash_meta_time' );
			delete_comment_meta( $refitune_comment_id, '_wp_trash_meta_status' );
		} else {
			wp_delete_comment( $refitune_del_comment );
		}
	}

	if ( $refitune_needs_continue && ! wp_next_scheduled( 'refitune_trash_delete_continue' ) ) {
		wp_schedule_single_event( time() + MINUTE_IN_SECONDS, 'refitune_trash_delete_continue' );
	}
}
