<?php
if ( !class_exists( 'LP_Widget_Course_Filters' ) ) {
	/**
	 * Class LP_Widget_Course_Filters
	 */
	class LP_Widget_Course_Filters extends LP_Widget {
		public function __construct() {
			$prefix        = '';
			$this->options = array(
				array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}title",
					'type' => 'text',
					'std'  => __( 'Course attributes', 'learnpress' )
				),
				array(
					'name' => __( 'Title', 'learnpress' ),
					'id'   => "{$prefix}x-title",
					'type' => 'text',
					'std'  => __( 'Course attributes', 'learnpress' )
				),
				array(
					'name'    => __( 'Radio', 'learnpress' ),
					'id'      => "{$prefix}radio",
					'type'    => 'radio',
					'std'     => __( '3', 'learnpress' ),
					'options' => array(
						'1' => 'One',
						'2' => 'Two',
						'3' => 'Three',
						'4' => 'Four',
						'5' => 'Five'
					)
				)
			);
			parent::__construct();
			add_filter( 'learn_press_widget_display_content-' . $this->id_base, 'learn_press_is_courses' );

		}

		public function show() {
			$postId     = get_the_ID();
			$attributes = learn_press_get_course_attributes( $postId );
			if ( !$attributes ) {
				return;
			}
			$start  = microtime( true );
			$output = '<ul>';
			?>
			<?php
			$object_terms = array();

			foreach ( $attributes as $attribute ) {
				$object_terms[] = LP_COURSE_ATTRIBUTE . '-' . $attribute['name'];
			}
			$object_terms = ( wp_get_object_terms( $postId, $object_terms ) );
			foreach ( $attributes as $attribute ) {
				$taxonomy = get_term_by( 'slug', $attribute['name'], LP_COURSE_ATTRIBUTE );
				$output .= '<li><h4>' . $taxonomy->name . '</h4>';
				/*$values = wp_get_object_terms( $postId, LP_COURSE_ATTRIBUTE . '-' . $attribute['name'] );
				if ( !$values ) {
					continue;
				}*/
				$output .= '<ul>';
				foreach ( $object_terms as $value ) {
					if ( $value->taxonomy != LP_COURSE_ATTRIBUTE . '-' . $attribute['name'] ) {
						continue;
					}
					$output .= '<li>' . $value->name . '</li>';
				}
				$output .= '</ul></li>';
			}
			$output .= '</ul>';
			echo $output;
			echo microtime( true ) - $start;
		}
	}
}