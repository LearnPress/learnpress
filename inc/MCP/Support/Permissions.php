<?php

namespace LearnPress\MCP\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Current-user capability helpers for MCP write/sensitive-read operations.
 *
 * These checks are defense-in-depth on top of the shared MCP permission
 * callback (which already requires a valid API key and a base capability).
 * They mirror the capability patterns used by the LearnPress post models
 * (`edit_{post_type}s` to create, `edit_post`/`delete_post` meta caps to
 * mutate a specific post) so MCP never relies on key scope alone.
 */
class Permissions {

	/**
	 * Whether the current user may create a post of the given type.
	 *
	 * Mirrors PostModel::check_capabilities_create() (`edit_{post_type}s`).
	 *
	 * @param string $post_type Post type, e.g. LP_COURSE_CPT.
	 *
	 * @return bool
	 */
	public static function can_create( string $post_type ): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'edit_' . $post_type . 's' );
	}

	/**
	 * Whether the current user may edit a specific post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function can_edit_post( int $post_id ): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Whether the current user may delete (trash) a specific post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return bool
	 */
	public static function can_delete_post( int $post_id ): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'delete_post', $post_id );
	}

	/**
	 * Whether the current user may manage LearnPress users / enrollments.
	 *
	 * Manual enrollment management is an administrative action; keep it on the
	 * base management capability rather than guessing a finer LMS capability.
	 *
	 * @return bool
	 */
	public static function can_manage_enrollments(): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'list_users' );
	}

	/**
	 * Whether the current context is privileged enough to read sensitive quiz
	 * data (correct answers, hints, explanations).
	 *
	 * Requires the ability to edit the quiz itself or to manage the site.
	 * A plain read scope alone is intentionally not sufficient.
	 *
	 * @param int $quiz_id Quiz post ID.
	 *
	 * @return bool
	 */
	public static function can_read_sensitive_quiz( int $quiz_id ): bool {
		return current_user_can( 'manage_options' )
			|| current_user_can( 'edit_post', $quiz_id );
	}
}
