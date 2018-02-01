<?php

/**
 * Course Info Widget.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Widgets
 * @version  3.0.0
 * @extends  LP_Widget
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget_Course_Info' ) ) {

	/**
	 * Class LP_Widget_Course_Info
	 */
	class LP_Widget_Course_Info extends LP_Widget {

		/**
		 * @var array
		 */
		private $courses = array();

		/**
		 * @var null
		 */
		private $curd = null;

		/**
		 * LP_Widget_Course_Info constructor.
		 */
		public function __construct() {

			// course curd
			$this->curd = new LP_Course_CURD();

			// widget options
			$this->options = array(
				'title'     => array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "title",
					'type' => 'text',
					'std'  => __( 'Course Info', 'learnpress' )
				),
				'css_class' => array(
					'name' => __( 'CSS Class', 'learnpress' ),
					'id'   => "css_class",
					'type' => 'text',
					'std'  => ''
				)
			);

			// register widget
			parent::__construct();

			add_filter( 'learn-press/widget/display-' . $this->id_base, array( $this, 'is_singular' ) );
		}

		public function is_singular() {
			return learn_press_is_course();
		}

		/**
		 * Show widget in frontend.
		 */
		public function show() {

			$widget = $this;

			include $this->get_locate_template( $this->get_slug() );
		}
	}
}