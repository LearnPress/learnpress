<?php

namespace LearnPress\Gutenberg\Blocks\Legacy;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LP_Settings;

/**
 * Class SingleCourseBlockLegacy
 *
 * Handle register, render block template
 */
class SingleCourseBlockLegacy extends AbstractBlockType {
	public $block_name = 'single-course-legacy';

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		wp_enqueue_style( 'learnpress' );
		$html = '';
		global $wp;
		$object = get_queried_object();
		$vars   = $wp->query_vars;
		// Todo: For item course current display on post_type course
		// After when handle display item course on correct post_type item, remove this code.
		$page_template = '';
		if ( ! empty( $vars['course-item'] ) ) {
			global $post;
			setup_postdata( $post );
			$page_template = 'content-single-item.php';
		} elseif ( $object ) {
			$course = CourseModel::find( $object->ID, true );

			$page_template = 'single-course-layout.php';

			// Check condition to load single course layout classic or modern.
			$is_override_single_course   = Template::check_template_is_override( 'single-course.php' );
			$option_single_course_layout = LP_Settings::get_option( 'layout_single_course', '' );

			// Old single course layout.
			//$page_template = 'single-course.php';

			if ( $course && $course->is_offline() ) {
				$page_template = 'single-course-offline.php';
			}
		}

		if ( $page_template === '' ) {
			return '';
		}

		ob_start();
		Template::instance()->get_frontend_template( $page_template, compact( 'attributes' ) );
		$html = ob_get_clean();

		return $html;
	}
}
