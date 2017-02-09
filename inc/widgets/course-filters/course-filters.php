<?php
if ( !class_exists( 'LP_Widget_Course_Filters' ) ) {
	/**
	 * Class LP_Widget_Course_Filters
	 */
	class LP_Widget_Course_Filters extends LP_Widget {
		public function __construct() {
			$prefix        = '';
			$this->options = array(
				'title'     => array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}title",
					'type' => 'text',
					'std'  => __( 'Course filters', 'learnpress' )
				),
				'filter_by' => array(
					'name'    => __( 'Filter by', 'learnpress' ),
					'id'      => "{$prefix}filter_by",
					'type'    => 'checkbox_list',
					'std'     => '',
					'options' => ''
				)
			);
			parent::__construct();
			add_filter( 'learn_press_widget_display_content-' . $this->id_base, 'learn_press_is_courses' );
			if ( !is_admin() ) {
				LP_Assets::enqueue_script( 'course-filter', LP_Assets::url( 'js/frontend/course-filters.js' ) );
			}
		}

		public function normalize_options() {
			$this->options['filter_by']['options'] = $this->get_filter_by_options();
			return $this->options;
		}

		public function get_filter_by_options() {
			$options = array();
			if ( $attributes = learn_press_get_attributes() ) {
				foreach ( $attributes as $attribute ) {
					$options[LP_COURSE_ATTRIBUTE . '-' . $attribute->slug] = $attribute->name;
				}
			}
			return $options;
		}

		public function show() {
			include learn_press_locate_widget_template( $this->get_slug() );
		}
	}
}