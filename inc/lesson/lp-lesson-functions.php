<?php
/**
 * Lession function.
 *
 * @author ThimPress
 * @version 4.0.0
 * @deprecated 4.2.2
 */

defined( 'ABSPATH' ) || exit();

function learn_press_lesson_comment_form_fields( $post_id ) {
	if ( empty( $_REQUEST['content-item-only'] ) ) {
		return;
	}
	?>

	<input type="hidden" name="content-item-only-redirect" value="<?php echo learn_press_get_current_url(); ?>"/>
	<input type="hidden" name="content-item-only" value="yes"/>

	<?php
}
//add_action( 'comment_form', 'learn_press_lesson_comment_form_fields' );

if ( ! function_exists( 'learn_press_get_only_content_permalink' ) ) {
	function learn_press_get_only_content_permalink( $redirect, $comment ) {
		if ( empty( $_REQUEST['content-item-only'] ) || sanitize_text_field( $_REQUEST['content-item-only'] ) !== 'yes' ) {
			return $redirect;
		}

		if ( empty( $_REQUEST['content-item-only-redirect'] ) ) {
			return $redirect;
		}

		if ( get_post_type( $comment->comment_post_ID ) != 'lp_lesson' ) {
			return $redirect;
		}

		return esc_url_raw( add_query_arg( 'content-item-only', 'yes', LP_Helper::sanitize_params_submitted( $_REQUEST['content-item-only-redirect'] ) ) );
	}
}
//add_filter( 'comment_post_redirect', 'learn_press_get_only_content_permalink', 10, 2 );

/*function learn_press_lesson_comment_form() {
	global $post;

	$course = learn_press_get_course();
	if ( ! $course ) {
		return;
	}

	$lesson = LP_Global::course_item();
	if ( ! $lesson ) {
		return;
	}

	$user = learn_press_get_current_user();

	if ( $lesson->setup_postdata() ) {
		if ( comments_open() || get_comments_number() ) {
			add_filter( 'deprecated_file_trigger_error', '__return_false' );
			comments_template();
			remove_filter( 'deprecated_file_trigger_error', '__return_false' );
		}

		$lesson->reset_postdata();
	}

}*/

/**
 * Remove data section after remove lesson
 */
function learn_press_lesson_before_delete_post( $post_id, $force = false ) {
	global $wpdb;

	if ( 'lp_lesson' === get_post_type( $post_id ) ) {
		$sql = 'DELETE FROM `' . $wpdb->prefix . 'learnpress_section_items` WHERE `item_id` = ' . $post_id . ' AND `item_type` = "lp_lesson"';
		$wpdb->query( $sql );
	}
}
//add_action( 'delete_post', 'learn_press_lesson_before_delete_post', 10, 2 );
