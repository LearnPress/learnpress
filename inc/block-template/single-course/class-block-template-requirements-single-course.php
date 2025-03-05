<?php

use LearnPress\Models\CourseModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

/**
 * Class Block_Template_Requirements_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Requirements_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'requirements-single-course';
	public $name                          = 'learnpress/requirements-single-course';
	public $title                         = 'Requirements Course (LearnPress)';
	public $description                   = 'Requirements Course Block Template';
	public $path_html_block_template_file = 'html/single-course/requirements-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/requirements-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$content = '';
		$course  = CourseModel::find( get_the_ID(), true );

		if ( $attributes['layout'] === 'modern' ) {
			ob_start();
			echo SingleCourseTemplate::instance()->html_requirements( $course );
			$content = ob_get_clean();

			return $content;
		} else {
			$attributes['title'] = ! empty( $attributes['title'] ) ? esc_html( $attributes['title'], 'learnpress' ) : esc_html( 'Title', 'learnpress' );
			$course_id           = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
			$course              = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				return;
			}
			$items               = $course->get_meta_value_by_key( '_lp_requirements' );
			$attributes['title'] = 'Requirements';

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
