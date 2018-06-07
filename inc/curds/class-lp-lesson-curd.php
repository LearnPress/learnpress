<?php
/**
 * Class LP_Lesson_CURD
 *
 * @author  ThimPress
 * @package LearnPress/Classes/CURD
 * @since   3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Lesson_CURD' ) ) {

	/**
	 * Class LP_Lesson_CURD
	 */
	class LP_Lesson_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {

		/**
		 * Create lesson, with default meta.
		 *
		 * @param $args
		 *
		 * @return int|WP_Error
		 */
		public function create( &$args ) {

			$args = wp_parse_args( $args, array(
					'id'      => '',
					'status'  => 'publish',
					'title'   => __( 'New Lesson', 'learnpress' ),
					'content' => '',
					'author'  => learn_press_get_current_user_id()
				)
			);

			$lesson_id = wp_insert_post( array(
				'ID'           => $args['id'],
				'post_type'    => LP_LESSON_CPT,
				'post_status'  => $args['status'],
				'post_title'   => $args['title'],
				'post_content' => $args['content'],
				'post_author'  => $args['author']
			) );

			if ( $lesson_id ) {
				// add default meta for new lesson
				$default_meta = LP_Lesson::get_default_meta();

				if ( is_array( $default_meta ) ) {
					foreach ( $default_meta as $key => $value ) {
						update_post_meta( $lesson_id, '_lp_' . $key, $value );
					}
				}
			}

			return $lesson_id;
		}

		/**
		 * @param object $lesson
		 */
		public function update( &$lesson ) {
			// TODO: Implement update() method.
		}

		/**
		 * Delete lesson.
		 *
		 * @since 3.0.0
		 *
		 * @param object $lesson_id
		 */
		public function delete( &$lesson_id ) {
			// course curd
			$curd = new LP_Course_CURD();

			// allow hook
			do_action( 'learn-press/before-delete-lesson', $lesson_id );

			// remove lesson from course items
			$curd->remove_item( $lesson_id );
		}

		/**
		 * Duplicate lesson.
		 *
		 * @since 3.0.0
		 *
		 * @param $lesson_id
		 * @param array $args
		 *
		 * @return mixed|WP_Error
		 */
		public function duplicate( &$lesson_id, $args = array() ) {

			if ( ! $lesson_id ) {
				return new WP_Error( __( '<p>Op! ID not found</p>', 'learnpress' ) );
			}

			if ( get_post_type( $lesson_id ) != LP_LESSON_CPT ) {
				return new WP_Error( __( '<p>Op! The lesson does not exist</p>', 'learnpress' ) );
			}

			// ensure that user can create lesson
			if ( ! current_user_can( 'edit_posts' ) ) {
				return new WP_Error( __( '<p>Sorry! You don\'t have permission to duplicate this lesson</p>', 'learnpress' ) );
			}

			// duplicate lesson
			$new_lesson_id = learn_press_duplicate_post( $lesson_id, $args );

			if ( ! $new_lesson_id || is_wp_error( $new_lesson_id ) ) {
				return new WP_Error( __( '<p>Sorry! Failed to duplicate lesson!</p>', 'learnpress' ) );
			} else {
				return $new_lesson_id;
			}
		}

		/**
		 * Load lesson data.
		 *
		 * @since 3.0.0
		 *
		 * @param object $lesson
		 *
		 * @return object
		 * @throws Exception
		 */
		public function load( &$lesson ) {
			// lesson id
			$id = $lesson->get_id();

			if ( ! $id || get_post_type( $id ) !== LP_LESSON_CPT ) {
				throw new Exception( sprintf( __( 'Invalid lesson with ID "%d".', 'learnpress' ), $id ) );
			}

			$lesson->set_data(
				array(
					'preview' => get_post_meta( $id, '_lp_preview', true )
				)
			);

			return $lesson;
		}
	}

}