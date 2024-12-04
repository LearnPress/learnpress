<?php
/**
 * Class Block_Layout_Target_Archive_Course
 *
 * Handle register, render block layout
 */
class Block_Layout_Target_Archive_Course extends Abstract_Block_Layout {
	public $slug                          = 'target-archive-course';
	public $name                          = 'learnpress/target-archive-course';
	public $title                         = 'Course Target (LearnPress)';
	public $description                   = 'Layout Course Target Block';
	public $path_html_block_template_file = 'html/target-archive-course.html';
	public $path_template_render_default  = 'block/render/archive-course/target-archive-course.php';
	public $source_js                     = LP_PLUGIN_URL . 'assets/js/dist/blocks/target-archive-course.js';

	public function __construct() {
		add_action( 'save_post', [ $this, 'save_wp_template' ], 10, 2 );
		parent::__construct();
	}

	public function save_wp_template( $post_id, $post ) {

		if ( $post->post_type !== 'wp_template' ) {
			return;
		}
		$content = $post->post_content;
		if ( has_block( 'learnpress/target-archive-course', $content ) ) {
			$blocks = parse_blocks( $content );

			$attributes = find_block_by_name( $blocks, 'learnpress/target-archive-course' );
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
