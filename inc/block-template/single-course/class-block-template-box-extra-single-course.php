<?php

use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Box_Extra_Single_Course extends Abstract_Block_Template_Widget_Single_Course {
	public $slug                          = 'box-extra-single-lp_course';
	public $name                          = 'learnpress/box-extra-single-course';
	public $title                         = 'Box Extra Course (LearnPress)';
	public $description                   = 'Box Extra Course Block Template';
	public $path_html_block_template_file = 'html/single-course/box-extra-single-course.html';
	public $single_course_func            = 'html_course_box_extra';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/box-extra-single-course.js';

	public function render_content_block_template( array $attributes ) {
		$meta_key            = ! empty( $attributes['metaKey'] ) ? '_lp_' . $attributes['metaKey'] : '';
		$attributes['title'] = ! empty( $attributes['title'] ) ? esc_html( $attributes['title'], 'learnpress' ) : esc_html( 'Title', 'learnpress' );
		if ( empty( $meta_key ) ) {
			return '';
		}

		$course_id = ! empty( $attributes['courseId'] ) ? (int) $attributes['courseId'] : get_the_ID();
		$course    = CourseModel::find( $course_id, true );
		$items     = $course->get_meta_value_by_key( $meta_key );
		if ( ! empty( $items ) && is_array( $items ) ) :
			ob_start();
			?>
			<ul>
				<?php foreach ( $items as $item ) : ?>
					<li><?php echo wp_kses_post( $item ); ?></li>
				<?php endforeach; ?>
			</ul>
			<?php
			$attributes['html_list'] = ob_get_clean();
		endif;

		$order = [ 'courseId', 'title', 'html_list' ];
		foreach ( $order as $key ) {
			$sortedAttributes[ $key ] = isset( $attributes[ $key ] ) ? $attributes[ $key ] : '';
		}

		return parent::render_content_block_template( $sortedAttributes );
	}
}
