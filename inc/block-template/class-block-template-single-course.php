<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;

/**
 * Class Block_Template_Single_Course
 *
 * Handle register, render block template
 */
class Block_Template_Single_Course extends Abstract_Block_Template {
	public $slug                          = 'single-lp_course';
	public $name                          = 'learnpress/single-course';
	public $title                         = 'Single Course (LearnPress)';
	public $description                   = 'Single Course Block Template';
	public $path_html_block_template_file = 'html/single-lp_course.html';
	public $path_template_render_default  = 'single-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/single-course.js';

	public function __construct() {
		parent::__construct();
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes ) {
		global $wp;
		$object = get_queried_object();
		$vars   = $wp->query_vars;
		// Todo: For item course current display on post_type course
		// After when handle display item course on correct post_type item, remove this code.
		if ( ! empty( $vars['course-item'] ) ) {
			global $post;
			setup_postdata( $post );
			$this->path_template_render_default = 'content-single-item.php';
		} elseif ( $object ) {
			$page_template = 'single-course-layout.php';
			$course        = CourseModel::find( $object->ID, true );

			// Check condition to load single course layout classic or modern.
			$is_override_single_course   = Template::check_template_is_override( 'single-course.php' );
			$option_single_course_layout = LP_Settings::get_option( 'layout_single_course', '' );

			if ( $is_override_single_course ) { // Old file template
				$page_template = 'single-course.php';
			} elseif ( empty( $option_single_course_layout )
				|| $option_single_course_layout === 'classic' ) {
				$page_template = 'single-course-layout-classic.php';
				// Set temporary old single course layout.
				$page_template = 'single-course.php';
			}

			// Old single course layout.
			//$page_template = 'single-course.php';

			if ( $course && $course->is_offline() ) {
				$page_template = 'single-course-offline.php';
			}

			$this->path_template_render_default = $page_template;
		}

		return parent::render_content_block_template( $attributes );
	}
}
