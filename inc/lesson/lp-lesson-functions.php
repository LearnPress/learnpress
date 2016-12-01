<?php
defined( 'ABSPATH' ) || exit();

if ( !function_exists( 'learn_press_get_lesson_course_id' ) ) {
	/**
	 * Get Course id by lesson_id
	 *
	 * @global type $wpdb
	 *
	 * @param type  $lesson_id
	 *
	 * @return $course_id
	 */
	function learn_press_get_lesson_course_id( $lesson_id = null ) {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT section.section_course_id FROM {$wpdb->learnpress_sections} AS section"
			. " INNER JOIN {$wpdb->learnpress_section_items} AS item ON item.section_id = section.section_id"
			. " INNER JOIN {$wpdb->posts} AS course ON course.ID = section.section_course_id"
			. " WHERE course.post_type = %s"
			. " AND course.post_status = %s"
			. " AND item.item_type = %s"
			. " AND item.item_id = %d"
			. " LIMIT 1", LP_COURSE_CPT, 'publish', LP_LESSON_CPT, $lesson_id );

		return apply_filters( 'learn_press_get_lesson_course_id', absint( $wpdb->get_var( $query ) ), $lesson_id );
	}
}

add_filter( 'post_type_link', 'learn_press_lesson_permalink', 10, 2 );
if ( !function_exists( 'learn_press_lesson_permalink' ) ) {
	/**
	 * Change Lesson URL
	 *
	 * @param type $permalink
	 * @param type $post
	 *
	 * @return string lesson url
	 */
	function learn_press_lesson_permalink( $permalink, $post ) {
		remove_filter( 'post_type_link', 'learn_press_lesson_permalink', 10, 2 );
		if ( $post->post_type !== LP_LESSON_CPT ) {
			return $permalink;
		}

		$course_id = learn_press_get_lesson_course_id( $post->ID );
		if ( $course_id ) {
			$permalink = learn_press_get_course_item_url( $course_id, $post->ID );
		}
		add_filter( 'post_type_link', 'learn_press_lesson_permalink', 10, 2 );
		return $permalink;
	}
}


add_filter( 'comment_post_redirect', 'learn_press_get_only_content_permalink', 10, 2 );

if ( !function_exists( 'learn_press_get_only_content_permalink' ) ) {
	function learn_press_get_only_content_permalink( $redirect, $comment ) {

		if ( get_post_type( $comment->comment_post_ID ) == 'lp_lesson' ) {
			return get_permalink( $comment->comment_post_ID ) . '?content-item-only=yes';
		} else {
			return $redirect;
		}
	}
}