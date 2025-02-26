<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Target_Audiences_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Target_Audiences_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'target-audiences-single-course';
	public $name                          = 'learnpress/target-audiences-single-course';
	public $title                         = 'Target audiences Course (LearnPress)';
	public $description                   = 'Target audiences Course Block Template';
	public $path_html_block_template_file = 'html/single-course/target-audiences-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/target-audiences-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$attributes['title'] = ! empty( $attributes['title'] ) ? esc_html( $attributes['title'], 'learnpress' ) : esc_html( 'Title', 'learnpress' );
		$course_id           = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
		$course              = CourseModel::find( $course_id, true );
		if ( ! $course ) {
			return;
		}

		$course               = CourseModel::find( get_the_ID(), true );
		$layout_single_course = LP_Settings::get_option( 'layout_single_course', 'classic' );
		if ( $layout_single_course === 'modern' ) {
			ob_start();
			echo SingleCourseTemplate::instance()->html_target( $course );
			$content = ob_get_clean();
			return $content;
		} else {
			$items               = $course->get_meta_value_by_key( '_lp_target_audiences' );
			$attributes['title'] = 'Target audiences';

			if ( ! empty( $items ) && is_string( ( $items ) ) ) {
				$items = unserialize( $items );
			}

			if ( ! empty( $items ) && is_array( $items ) ) :
				ob_start();
				?>
				<ul>
					<?php foreach ( $items as $item ) : ?>
						<li><?php echo wp_kses_post( $item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<?php
				$attributes['list'] = ob_get_clean();
			endif;

			$order = [ 'courseId', 'title', 'list' ];
			foreach ( $order as $key ) {
				$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
			}

			return parent::render_content_block_template( $sortedAttributes );
		}
	}
}
