<?php

namespace LearnPress\Gutenberg\Blocks\Courses;

use LearnPress\Gutenberg\Blocks\AbstractBlockType;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LP_Course_Filter;
use LP_Database;
use LP_Debug;
use LP_Helper;
use LP_Page_Controller;
use stdClass;
use Throwable;
use WP_Block;

/**
 * Class ListCoursesBlockType
 *
 */
class ListCoursesBlockType extends AbstractBlockType {
	public $block_name      = 'list-courses';
	public $path_block_json = LP_PLUGIN_PATH . 'assets/src/apps/js/blocks/courses/list-courses';

	public function __construct() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
		parent::__construct();
	}

	/**
	 * Get supports of block
	 *
	 * @return array
	 */
	public function get_supports() {
		return [
			'align' => [ 'wide', 'full' ],
		];
	}

	/**
	 * Allow callback for block
	 *
	 * @param array $callbacks.
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		/**
		 * @uses render_courses
		 */
		$callbacks[] = get_class( $this ) . ':render_courses';

		return $callbacks;
	}

	/**
	 * Render content of block tag
	 *
	 * @param array $attributes | Attributes of block tag.
	 *
	 * @return false|string
	 */
	public function render_content_block_template( array $attributes, $content, $block ): string {
		$html = '';

		wp_enqueue_script( 'lp-courses' );
		wp_enqueue_script( 'lp-courses-v2' );

		try {
			$args                 = lp_archive_skeleton_get_args();
			$args['attributes']   = $attributes;
			$args['parsed_block'] = $block->parsed_block;
			$courseQuery          = $attributes['courseQuery'] ?? [];
			$load_ajax            = $courseQuery['load_ajax'] ?? false;
			$callback             = [
				'class'  => get_class( $this ),
				'method' => 'render_courses',
			];
			$html_wrapper         = [
				'<div class="lp-list-courses-default">' => '</div>',
			];

			if ( ! $load_ajax ) {
				$content_obj                     = ListCoursesBlockType::render_courses( $args );
				$args['html_no_load_ajax_first'] = sprintf( '<div class="lp-list-courses-default">%s</div>', $content_obj->content );
			}

			$html = TemplateAJAX::load_content_via_ajax( $args, $callback );

			return $this->get_output( Template::instance()->nest_elements( $html_wrapper, $html ) );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * Render template list courses with settings param.
	 *
	 * @param array $settings
	 *
	 * @return stdClass { content: string_html }
	 * @since 4.2.8.4
	 * @version 1.0.0
	 */
	public static function render_courses( array $settings = [] ) {
		$content          = new stdClass();
		$content->content = '';

		$parsed_block = $settings['parsed_block'] ?? '';
		$attributes   = $settings['attributes'] ?? [];
		$courseQuery  = $attributes['courseQuery'] ?? [];
		if ( empty( $courseQuery ) ) {
			return $content;
		}

		$total_rows           = 0;
		$filter               = new LP_Course_Filter();
		$settings['order_by'] = $settings['order_by'] ?? $courseQuery['order_by'] ?? 'post_date';
		$settings['limit']    = $courseQuery['limit'] ?? 10;
		Courses::handle_params_for_query_courses( $filter, $settings );

		self::get_courses_of_instructor( $filter );

		if ( isset( $courseQuery['related'] ) && $courseQuery['related'] ) {
			$courseQuery['pagination'] = false;
			self::get_courses_related( $filter );
		}

		$courses = Courses::get_courses( $filter, $total_rows );
		if ( empty( $courses ) ) {
			$content->content = Template::print_message( __( 'No courses found', 'learnpress' ), 'info', false );
			return $content;
		}

		$html_pagination = '';
		if ( isset( $courseQuery['pagination'] ) && $courseQuery['pagination'] ) {
			$total_pages          = LP_Database::get_total_pages( $filter->limit, $total_rows );
			$content->total_pages = $total_pages;
			$data_pagination      = [
				'total_pages' => $total_pages,
				'type'        => $courseQuery['pagination_type'] ?? 'number',
				'base'        => add_query_arg( 'paged', '%#%', $settings['url_current'] ?? '' ),
				'paged'       => $settings['paged'] ?? 1,
				'wrapper'     => [ '<nav class="learnpress-block-pagination navigation pagination">' => '</nav>' ],
			];
			$html_pagination      = ListCoursesTemplate::instance()->html_pagination( $data_pagination );
		}

		$filter_block_context = static function ( $context ) use ( $courses, $html_pagination, $filter, $settings ) {
			$context['is_list_course'] = true;
			$context['courses']        = $courses ?? [];
			$context['pagination']     = $html_pagination ?? '';
			$context['settings']       = $settings;
			return $context;
		};

		// Add filter with priority 1 so other filters have access to these values
		add_filter( 'render_block_context', $filter_block_context, 1 );
		$block_render  = new WP_Block( $parsed_block );
		$block_content = $block_render->render( [ 'dynamic' => false ] );
		remove_filter( 'render_block_context', $filter_block_context, 1 );

		$content->content = $block_content;
		$content->paged   = $settings['paged'] ?? 1;

		return $content;
	}

	/**
	 * Detect instructor by page and get courses of instructor.
	 *
	 * @param LP_Course_Filter $filter
	 *
	 * @since 4.2.8.4
	 * @return void
	 */
	public static function get_courses_of_instructor( LP_Course_Filter &$filter ) {
		if ( ! LP_Page_Controller::is_page_instructor() ) {
			return;
		}

		$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();
		if ( ! $instructor || ! $instructor->is_instructor() ) {
			return;
		}

		$author_id           = $instructor->get_id();
		$filter->post_author = $author_id;
	}

	/**
	 * Get courses related to current course.
	 *
	 * @param LP_Course_Filter $filter
	 *
	 * @since 4.2.8.4
	 * @return void
	 */
	public static function get_courses_related( LP_Course_Filter &$filter ) {
		$courseModelCurrent = CourseModel::find( get_the_ID(), true );
		if ( empty( $courseModelCurrent ) ) {
			return;
		}

		$terms    = $courseModelCurrent->get_categories();
		$term_ids = [];

		foreach ( $terms as $term ) {
			$term_ids[] = $term->term_id ?? 0;
			$term_ids[] = $term->parent ?? 0;
		}

		$filter->term_ids    = $term_ids;
		$filter->query_count = false;
		$filter->where[]     = LP_Database::getInstance()->wpdb->prepare( 'AND p.ID != %d', $courseModelCurrent->get_id() );
	}
}
