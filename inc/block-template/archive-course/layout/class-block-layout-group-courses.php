<?php
/**
 * Class Block_Layout_Group_Courses_Archive_Course
 *
 * Handle register, render block layout
 */
class Block_Layout_Group_Courses_Archive_Course extends Abstract_Block_Layout {
	public $slug                          = 'group-courses-archive-course';
	public $name                          = 'learnpress/group-courses-archive-course';
	public $title                         = 'Group Courses (LearnPress)';
	public $description                   = 'Layout Group Courses Block';
	public $path_html_block_template_file = 'html/group-courses-archive-course.html';
	public $path_template_render_default  = 'block/render/archive-course/group-courses-archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/group-courses-archive-course.js';

	public function __construct() {
		add_action( 'save_post', [ $this, 'save_wp_template' ], 10, 2 );
		parent::__construct();
	}

	public function save_wp_template( $post_id, $post ) {

		if ( $post->post_type !== 'wp_template' ) {
			return;
		}
		$content = $post->post_content;
		if ( has_block( 'learnpress/group-courses-archive-course', $content ) ) {
			$blocks = parse_blocks( $content );

			$attributes = find_block_by_name( $blocks, 'learnpress/group-courses-archive-course' );
			if ( empty( $attributes ) || ! is_array( $attributes ) ) {
				return;
			}

			$pagination = $attributes['pagination'] ?? 'number';
			$load_Ajax  = $attributes['load'] ? 'yes' : 'no';
			$custom     = $attributes['custom'] ?? '';
			if ( ! $custom ) {
				LP_Settings::update_option( 'courses_load_ajax', 'yes' );
				LP_Settings::update_option( 'courses_first_no_ajax', $load_Ajax );
				LP_Settings::update_option( 'course_pagination_type', $pagination );
			} else {
				LP_Settings::update_option( 'courses_load_ajax', 'no' );
				LP_Settings::update_option( 'courses_first_no_ajax', $load_Ajax );
				LP_Settings::update_option( 'course_pagination_type', $pagination );
			}
		}
	}
}
