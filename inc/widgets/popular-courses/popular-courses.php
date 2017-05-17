<?php
/**
 * Widget to display popular courses
 */

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Popular_Courses' ) ) {

	/**
	 * Class LP_Widget_Popular_Courses
	 */
	class LP_Widget_Popular_Courses extends LP_Widget {

		private $courses = array();

		/**
		 * Sets up the widgets name etc
		 */
		public function __construct() {
			$prefix        = '';
			$this->options = array(
				'title' => array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}title",
					'type' => 'text',
					'std'  => __( '', 'learnpress' )
				),
				'show_teacher' => array(
					'name' => __( 'Show teacher', 'learpnress' ),
					'id'   => "{$prefix}show_teacher",
					'type' => 'checkbox',
					'std'  => 0
				),
				'show_lesson' => array(
					'name' => __( 'Show lesson', 'learpnress' ),
					'id'   => "{$prefix}show_lesson",
					'type' => 'checkbox',
					'std'  => 0
				),
				'show_thumbnail' => array(
					'name' => __( 'Show Thumbnail', 'learpnress' ),
					'id'   => "{$prefix}show_thumbnail",
					'type' => 'checkbox',
					'std'  => 0
				),
				'limit' => array(
					'name' => __( 'Limit', 'learpnress' ),
					'id'   => "{$prefix}limit",
					'type'  => 'number',
					'min'   => 1,
					'std'  => 5
				),
				'desc_length' => array(
					'name' => __( 'Description Length', 'learpnress' ),
					'id'   => "{$prefix}desc_length",
					'type' => 'number',
					'min'   => 0,
					'std'  => 10
				),
				'show_enrolled_students' => array(
					'name' => __( 'Show Enrolled Students', 'learpnress' ),
					'id'   => "{$prefix}show_enrolled_students",
					'type' => 'checkbox',
					'std'  => 0
				),
				'show_price' => array(
					'name' => __( 'Show Price', 'learpnress' ),
					'id'   => "{$prefix}show_price",
					'type' => 'checkbox',
					'std'  => 0
				),
				'css_class' => array(
					'name' => __( 'CSS Class', 'learpnress' ),
					'id'   => "{$prefix}css_class",
					'type' => 'text',
					'std'  => ''
				),
				'bottom_link_text' => array(
					'name' => __( 'Go to Courses', 'learpnress' ),
					'id'   => "{$prefix}bottom_link_text",
					'type' => 'text',
					'std'  => 'LP Courses'
				)
			);

			parent::__construct();
			//add_filter( 'learn_press_widget_display_content-' . $this->id_base, 'learn_press_is_course' );
		}

		/**
		 * get learn press course from wordpress post object
		 *
		 * @param object -reference $post wordpress post object
		 *
		 * @return LP_Course course
		 */
		public function get_lp_course( $post ) {
			$id     = $post->ID;
			$course = null;
			if ( !empty( $id ) ) {
				$course = new LP_Course( $id );
			}

			return $course;
		}

		/**
		 * get courses
		 * @return array|null array of course
		 */
		private function get_courses() {

			if ( empty( $this->instance ) ) {
				return array();
			}

			global $wpdb;
			$query = $wpdb->prepare(
				"SELECT po.*, count(*) as number_enrolled 
					FROM {$wpdb->prefix}learnpress_user_items ui
					INNER JOIN {$wpdb->posts} po ON po.ID = ui.item_id
					WHERE ui.item_type = %s
						AND ( ui.status = %s OR ui.status = %s )
						AND po.post_status = %s
					GROUP BY ui.item_id 
					ORDER BY ui.item_id DESC
					LIMIT %d
				",
				LP_COURSE_CPT,
				'enrolled',
				'finished',
				'publish',
				(int) $this->instance['limit']
			);
			$posts = $wpdb->get_results(
				$query
			);
			$courses = array_map( array( $this, 'get_lp_course' ), $posts );
			return $courses;
		}

		public function show () {
			$this->courses = $this->get_courses();
			include learn_press_locate_widget_template( $this->get_slug() );
		}
	}

}