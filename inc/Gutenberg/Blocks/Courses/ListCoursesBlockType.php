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

	public function get_attributes() {
		return [
			'courseQuery' => [
				'type'    => 'object',
				'default' => [
					'limit'      => 3,
					'order_by'   => 'post_date',
					'pagination' => false,
					'related'    => false,
					'tag_id'     => '',
					'term_id'    => '',
				],
			],
		];
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

		try {
			$args                 = lp_archive_skeleton_get_args();
			$args['attributes']   = $attributes;
			$args['parsed_block'] = $block->parsed_block;
			$callback             = [
				'class'  => get_class( $this ),
				'method' => 'render_courses',
			];
			$html_wrapper         = [
				'<div class="lp-list-courses-default">' => '</div>',
			];
			$html                 = TemplateAJAX::load_content_via_ajax( $args, $callback );
			$html                 = Template::instance()->nest_elements( $html_wrapper, $html );
			return $html;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	public static function render_courses( array $settings = [] ) {
		$html = '';

		$parsed_block = $settings['parsed_block'] ?? '';
		$attributes   = $settings['attributes'] ?? [];

		$content              = new stdClass();
		$content->content     = '';
		$content->total_pages = 1;
		$content->paged       = 1;

		// EEEEEEEE
		$block_content = '';

		$courseQuery = $attributes['courseQuery'] ?? [];
		if ( empty( $courseQuery ) ) {
			return $html;
		}

		$total_rows = 0;
		$filter     = new LP_Course_Filter();
		Courses::handle_params_for_query_courses( $filter, $settings );

		if ( ! empty( $courseQuery['order_by'] && empty( $settings['order_by'] ) ) ) {
			$filter->order_by = $courseQuery['order_by'];
		}

		if ( ! empty( $courseQuery['term_id'] ) && empty( $settings['term_id'] ) ) {
			$term_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $courseQuery['term_id'] ?? '' ) );
			if ( ! empty( $term_ids_str ) ) {
				$term_ids         = explode( ',', $term_ids_str );
				$filter->term_ids = $term_ids;
			}
		}

		if ( ! empty( $courseQuery['tag_id'] ) && empty( $settings['tag_id'] ) ) {
			$tag_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $courseQuery['tag_id'] ?? '' ) );
			if ( ! empty( $tag_ids_str ) ) {
				$tag_ids         = explode( ',', $tag_ids_str );
				$filter->tag_ids = $tag_ids;
			}
		}

		if ( ! empty( $settings['page_term_id_current'] ) && empty( $settings['term_id'] ) ) {
			$filter->term_ids[] = $settings['page_term_id_current'];
		} elseif ( ! empty( $settings['page_tag_id_current'] ) && empty( $settings['tag_id'] ) ) {
			$filter->tag_ids[] = $settings['page_tag_id_current'];
		}

		if ( LP_Page_Controller::is_page_instructor() ) {
			$instructor = SingleInstructorTemplate::instance()->detect_instructor_by_page();

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return '';
			}

			$author_id           = $instructor->get_id();
			$filter->post_author = $author_id;
		}

		if ( isset( $courseQuery['related'] ) && $courseQuery['related'] ) {
			$courseModelCurrent = CourseModel::find( get_the_ID(), true );
			if ( ! empty( $courseModelCurrent ) ) {
				$terms    = $courseModelCurrent->get_categories();
				$term_ids = [];

				foreach ( $terms as $term ) {
					$term_ids[] = $term->term_id ?? 0;
					$term_ids[] = $term->parent ?? 0;
				}

				$filter->term_ids          = $term_ids;
				$filter->query_count       = false;
				$filter->where[]           = LP_Database::getInstance()->wpdb->prepare( 'AND p.ID != %d', get_the_ID() );
				$courseQuery['pagination'] = false;
			}
		}

		$filter->limit = $courseQuery['limit'];
		$courses       = Courses::get_courses( $filter, $total_rows );

		if ( empty( $courses ) ) {
			return $html;
		}

		$html_pagination = '';
		if ( isset( $courseQuery['pagination'] ) && $courseQuery['pagination'] ) {
			$total_pages          = LP_Database::get_total_pages( $filter->limit, $total_rows );
			$data_pagination_type = 'number';
			$data_pagination      = [
				'total_pages' => $total_pages,
				'type'        => $data_pagination_type,
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
			$context['order_by']       = $filter->order_by;
			$context['settings']       = $settings;
			return $context;
		};

		// Add filter with priority 1 so other filters have access to these values
		add_filter( 'render_block_context', $filter_block_context, 1 );
		$block_render   = new WP_Block( $parsed_block );
		$block_content .= $block_render->render( [ 'dynamic' => false ] );
		remove_filter( 'render_block_context', $filter_block_context, 1 );
		// EEEEEEEEEEEEE

		$content->content = $block_content;

		return $content;
	}
}
