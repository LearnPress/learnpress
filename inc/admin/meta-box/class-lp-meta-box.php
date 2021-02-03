<?php

/**
 * LP Admin Metabox
 *
 * @author Nhamdv
 * @version 4.0.0
 */
class LP_Meta_Box {

	private static $saved_meta_boxes = false;

	/**
	 * LP_Meta_Box constructor.
	 *
	 * @see LP_Meta_Box_Course::save
	 * @see LP_Meta_Box_Lesson::save
	 * @see LP_Meta_Box_Question::save
	 * @see LP_Meta_Box_Quiz::save
	 * @see LP_Meta_Box_Course::save_eduma_child_metabox_v3
	 */
	public function __construct() {
		add_action( 'save_post', array( $this, 'save_meta_boxes' ), 100, 2 );

		add_action( 'learnpress_save_lp_course_metabox', 'LP_Meta_Box_Course::save' );
		add_action( 'learnpress_save_lp_lesson_metabox', 'LP_Meta_Box_Lesson::save' );
		add_action( 'learnpress_save_lp_question_metabox', 'LP_Meta_Box_Question::save' );
		add_action( 'learnpress_save_lp_quiz_metabox', 'LP_Meta_Box_Quiz::save' );
		add_action( 'learnpress_save_lp_course_metabox', 'LP_Meta_Box_Course::save_eduma_child_metabox_v3', 10 );
	}

	/**
	 * @param id      $post_id
	 * @param WP_Post $post
	 */
	public function save_meta_boxes( $post_id = 0, $post = null ) {
		$post_id = absint( $post_id );

		if ( empty( $post_id ) || empty( $post ) || self::$saved_meta_boxes ) {
			return;
		}

		if ( empty( $_POST['learnpress_meta_box_nonce'] ) ||
		     ! wp_verify_nonce( wp_unslash( $_POST['learnpress_meta_box_nonce'] ), 'learnpress_save_meta_box' ) ) {
			return;
		}

		if ( empty( $_POST['post_ID'] ) || absint( $_POST['post_ID'] ) !== $post_id ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		self::$saved_meta_boxes = true;

		do_action( 'learnpress_save_' . $post->post_type . '_metabox', $post_id, $post );
	}
}

new LP_Meta_Box();
