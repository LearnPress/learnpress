<?php
if ( !class_exists( 'LP_Widget_Course_Attributes' ) ) {
	/**
	 * Class LP_Widget_Course_Attribute
	 */
	class LP_Widget_Course_Attributes extends LP_Widget {
		public function __construct() {
			$prefix        = '';
			$this->options = array(
				array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}title",
					'type' => 'text',
					'std'  => __( 'Course attributes', 'learnpress' )
				)
			);
			parent::__construct();
			add_filter( 'learn_press_widget_display_content-' . $this->id_base, 'learn_press_is_course' );

		}

		public function show() {
			$postId     = get_the_ID();
			$attributes = learn_press_get_course_attributes( $postId );
			if ( !$attributes ) {
				return;
			}
			include learn_press_locate_widget_template( $this->get_slug() );
		}
	}
}