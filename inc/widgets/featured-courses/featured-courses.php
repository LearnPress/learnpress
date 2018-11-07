<?php
/**
 * Feature Courses Widget.
 *
 * @author  ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  3.0.0
 * @extends  LP_Widget
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Featured_Courses' ) ) {

	/**
	 * Class LP_Widget_Featured_Courses
	 */
	class LP_Widget_Featured_Courses extends LP_Widget {

		/**
		 * @var array
		 */
		private $courses = array();

		/**
		 * @var null
		 */
		private $curd = null;

		/**
		 * LP_Widget_Featured_Courses constructor.
		 */
		public function __construct() {

			// course curd
			$this->curd = new LP_Course_CURD();

			// widget options
			$this->options = array(
				'title'                  => array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "title",
					'type' => 'text',
					'std'  => __( 'Featured Courses', 'learnpress' )
				),
				'show_teacher'           => array(
					'name' => __( 'Show teacher', 'learnpress' ),
					'id'   => "show_teacher",
					'type' => 'checkbox',
					'std'  => 1
				),
				'show_lesson'            => array(
					'name' => __( 'Show lesson', 'learnpress' ),
					'id'   => "show_lesson",
					'type' => 'checkbox',
					'std'  => 1
				),
				'show_thumbnail'         => array(
					'name' => __( 'Show Thumbnail', 'learnpress' ),
					'id'   => "show_thumbnail",
					'type' => 'checkbox',
					'std'  => 1
				),
				'limit'                  => array(
					'name' => __( 'Limit', 'learnpress' ),
					'id'   => "limit",
					'type' => 'number',
					'min'  => 1,
					'std'  => 4
				),
				'desc_length'            => array(
					'name' => __( 'Description Length', 'learnpress' ),
					'id'   => "desc_length",
					'type' => 'number',
					'min'  => 0,
					'std'  => 10
				),
				'show_enrolled_students' => array(
					'name' => __( 'Show Enrolled Students', 'learnpress' ),
					'id'   => "show_enrolled_students",
					'type' => 'checkbox',
					'std'  => 1
				),
				'show_price'             => array(
					'name' => __( 'Show Price', 'learnpress' ),
					'id'   => "show_price",
					'type' => 'checkbox',
					'std'  => 1
				),
				'css_class'              => array(
					'name' => __( 'CSS Class', 'learnpress' ),
					'id'   => "css_class",
					'type' => 'text',
					'std'  => ''
				),
				'bottom_link_text'       => array(
					'name' => __( 'Go to Courses', 'learnpress' ),
					'id'   => "bottom_link_text",
					'type' => 'text',
					'std'  => 'LP Courses'
				)
			);

			// register widget
			parent::__construct();
		}

		/**
		 * Show widget in frontend.
		 */
		public function show() {
			// query courses
			$courses = $this->curd->get_featured_courses( array( 'limit' => (int) $this->instance['limit'] ) );

			include $this->get_locate_template( $this->get_slug() );
		}
	}
}