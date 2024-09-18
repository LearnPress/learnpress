<?php

namespace LearnPress\Helpers;

class Page {
	/**
	 * @return bool
	 */
	public static function is_admin_single_course_page(): bool {
		global $pagenow, $current_screen;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
			return false;
		}

		if ( ! isset( $current_screen->post_type ) || $current_screen->post_type !== LP_COURSE_CPT ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function is_admin_single_lesson_page(): bool {
		global $pagenow, $current_screen;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
			return false;
		}

		if ( ! isset( $current_screen->post_type ) || $current_screen->post_type !== LP_LESSON_CPT ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function is_admin_single_quiz_page(): bool {
		global $pagenow, $current_screen;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
			return false;
		}

		if ( ! isset( $current_screen->post_type ) || $current_screen->post_type !== LP_QUIZ_CPT ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public static function is_admin_single_question_page(): bool {
		global $pagenow, $current_screen;

		if ( ! in_array( $pagenow, array( 'post-new.php', 'post.php' ) ) ) {
			return false;
		}

		if ( ! isset( $current_screen->post_type ) || $current_screen->post_type !== LP_QUESTION_CPT ) {
			return false;
		}

		return true;
	}
}