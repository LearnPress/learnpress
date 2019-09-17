<?php

/**
 * Course Progress Widget.
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

if ( ! class_exists( 'LP_Widget_Course_Extra' ) ) {

	/**
	 * Class LP_Widget_Course_Extra
	 */
	class LP_Widget_Course_Extra extends LP_Widget {

		/**
		 * LP_Widget_Course_Extra constructor.
		 */
		public function __construct() {

			// widget options
			$this->options = array(
				'type'      => array(
					'name'    => __( 'Type', 'learnpress' ),
					'id'      => 'type',
					'type'    => 'select',
					'options' => array(
						'key_features'    => __( 'Key features', 'learnpress' ),
						'target_audience' => __( 'Target audience', 'learnpress' ),
						'requirements'    => __( 'Requirements', 'learnpress' )
					),
					'std'     => __( '', 'learnpress' )
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

			add_filter( 'learn-press/widget/display-' . $this->id_base, 'learn_press_is_course' );
		}

		/**
		 * Show widget in frontend.
		 */
		public function show() {
			switch ( $this->instance['type'] ) {
				case 'key_features':
					LP()->template()->course_extra_key_features();
					break;
				case 'target_audience':
					LP()->template()->course_extra_target_audiences();
					break;
				case 'requirements':
					LP()->template()->course_extra_requirements();
					break;
			}
		}
	}
}