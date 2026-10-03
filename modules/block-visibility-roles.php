<?php
/**
 * Block visibility control by login state and user role.
 *
 * Adds role-based options to the shared Visibility panel. Excluded blocks
 * are omitted from the front-end HTML entirely.
 *
 * @package RefiTune
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once REFITUNE_PATH . 'modules/block-visibility-shared.php';

/**
 * Enqueue shared editor assets for role visibility.
 *
 * @return void
 */
function refitune_block_visibility_roles_editor_assets(): void {
	refitune_enqueue_block_visibility_editor();
}
add_action( 'enqueue_block_editor_assets', 'refitune_block_visibility_roles_editor_assets', 10 );

/**
 * Whether the current visitor matches the block role visibility setting.
 *
 * @param array $refitune_setting Role visibility attribute (mode + roles).
 * @return bool True when the block should render.
 */
function refitune_visitor_matches_role_visibility( array $refitune_setting ): bool {
	$refitune_mode  = isset( $refitune_setting['mode'] ) ? (string) $refitune_setting['mode'] : '';
	$refitune_roles = isset( $refitune_setting['roles'] ) && is_array( $refitune_setting['roles'] )
		? array_map( 'strval', $refitune_setting['roles'] )
		: array();

	if ( '' === $refitune_mode ) {
		return true;
	}

	if ( 'guest' === $refitune_mode ) {
		return ! is_user_logged_in();
	}

	if ( 'logged_in' === $refitune_mode ) {
		return is_user_logged_in();
	}

	if ( 'roles' === $refitune_mode ) {
		if ( ! is_user_logged_in() || empty( $refitune_roles ) ) {
			return false;
		}

		$refitune_user = wp_get_current_user();
		$refitune_user_roles = is_array( $refitune_user->roles ) ? $refitune_user->roles : array();

		return ! empty( array_intersect( $refitune_user_roles, $refitune_roles ) );
	}

	return true;
}

/**
 * Filter front-end rendering by login state / role.
 *
 * @param string $block_content Block HTML output.
 * @param array  $block         Block data (name, attributes).
 * @return string Modified (or empty) HTML output.
 */
function refitune_filter_block_visibility_roles( string $block_content, array $block ): string {
	$refitune_setting = $block['attrs']['refituneRoleVisibility'] ?? null;

	if ( ! is_array( $refitune_setting ) ) {
		return $block_content;
	}

	$refitune_mode = isset( $refitune_setting['mode'] ) ? (string) $refitune_setting['mode'] : '';

	if ( '' === $refitune_mode ) {
		return $block_content;
	}

	if ( ! refitune_visitor_matches_role_visibility( $refitune_setting ) ) {
		return '';
	}

	return $block_content;
}
add_filter( 'render_block', 'refitune_filter_block_visibility_roles', 10, 2 );
