<?php
defined( 'ABSPATH' ) || exit();

add_action( 'comment_form', 'learn_press_lesson_comment_form_fields' );
function learn_press_lesson_comment_form_fields( $post_id ) {
	if ( empty( $_REQUEST['content-item-only'] ) ) {
		return;
	}
	?>
	<input type="hidden" name="content-item-only-redirect" value="<?php echo learn_press_get_current_url(); ?>" />
	<input type="hidden" name="content-item-only" value="yes" />
	<?php
}

add_filter( 'comment_post_redirect', 'learn_press_get_only_content_permalink', 10, 2 );

if ( !function_exists( 'learn_press_get_only_content_permalink' ) ) {
	function learn_press_get_only_content_permalink( $redirect, $comment ) {
		if ( empty( $_REQUEST['content-item-only'] ) || $_REQUEST['content-item-only'] !== 'yes' ) {
			return $redirect;
		}
		if ( empty( $_REQUEST['content-item-only-redirect'] ) ) {
			return $redirect;
		}
		if ( get_post_type( $comment->comment_post_ID ) != 'lp_lesson' ) {
			return $redirect;
		}
		return add_query_arg( 'content-item-only', 'yes', $_REQUEST['content-item-only-redirect'] );
	}
}

function learn_press_lesson_comment_form( $lesson_id, $course_id, $deprecated = null ) {
	global $post;
	if ( get_post_type( $lesson_id ) != LP_LESSON_CPT ) {
		return;
	}

	$user = learn_press_get_current_user();
	if ( !$user->has_enrolled_course( $course_id ) ) {
		return;
	}

	$post = get_post( $lesson_id );
	setup_postdata( $post );
	if ( comments_open() || get_comments_number() ) {
		comments_template();
	}
	wp_reset_postdata();
}

/**
 * Add class css js-action to element comment reply
 */
add_filter('comment_reply_link', 'lesson_comment_reply_link', 10, 4);

if (!function_exists('lesson_comment_reply_link')) {

    function lesson_comment_reply_link($link, $args, $comment, $post) {

        $link = str_replace('comment-reply-link', 'comment-reply-link js-action', $link);
        return $link;
    }
}

/**
 * Add class css js-action to element cancel comment reply link
 */
add_filter( 'cancel_comment_reply_link', 'lesson_cancel_comment_reply_link', 10, 3 );

if ( !function_exists( 'lesson_cancel_comment_reply_link' ) ) {
	function lesson_cancel_comment_reply_link( $formatted_link, $link, $text ) {

		$formatted_link = str_replace( 'cancel-comment-reply-link"', 'cancel-comment-reply-link" class="js-action"', $formatted_link );
		return $formatted_link;
	}
}

/**
 * Remove data section after remove lesson
 */
add_action( 'delete_post', 'learn_press_lesson_before_delete_post', 10, 2 );
function learn_press_lesson_before_delete_post( $post_id, $force=false ) {
	global $wpdb;
	if( 'lp_lesson' === get_post_type( $post_id ) ) {
		$sql = 'DELETE FROM `'.$wpdb->prefix.'learnpress_section_items` WHERE `item_id` = '.$post_id.' AND `item_type` = "lp_lesson"';
		$wpdb->query($sql);
	}
}
