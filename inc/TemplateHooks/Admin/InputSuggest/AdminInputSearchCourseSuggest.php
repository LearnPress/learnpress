<?php

namespace LearnPress\TemplateHooks\Admin\InputSuggest;

use Exception;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\UserModel;
use LearnPress\Services\CourseService;
use LearnPress\TemplateHooks\TemplateAJAX;
use stdClass;
use Throwable;

/**
 * Admin search-suggest input for courses.
 *
 * Usage: echo AdminInputSearchCourseSuggest::instance()->layout( [ 'name' => 'course_id' ] );
 *
 * @since 4.4.14
 * @version 1.0.0
 */
class AdminInputSearchCourseSuggest extends AdminInputSearchSuggest {
	use Singleton;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Allow callback for AJAX.
	 *
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		/** @use self::render_courses */
		$callbacks[] = get_class( $this ) . ':render_courses';

		return $callbacks;
	}

	/**
	 * HTML of the search course input.
	 *
	 * @param array $data {@see AdminInputSearchSuggest::layout()}
	 *
	 * @return string
	 */
	public function layout( array $data = [] ): string {
		$args = [
			'id_url'                  => 'lp-search-course-suggest',
			'search'                  => '',
			// Empty string: nothing is loaded until the user types.
			'html_no_load_ajax_first' => '',
			'enableScrollToView'      => false,
			'enableUpdateParamsUrl'   => false,
		];

		/** @use self::render_courses */
		$callback = [
			'class'  => self::class,
			'method' => 'render_courses',
		];

		$data['html_result'] = TemplateAJAX::load_content_via_ajax( $args, $callback );
		if ( ! isset( $data['placeholder'] ) ) {
			$data['placeholder'] = __( 'Search course...', 'learnpress' );
		}

		return parent::layout( $data );
	}

	/**
	 * Render list courses suggest, called via AJAX.
	 *
	 * Permission: administrator = all courses, instructor = own courses only.
	 *
	 * @param array $data [ 'search' => keyword, 'type_select' => 'multiple' to render the items with checkbox ]
	 *
	 * @return stdClass
	 */
	public static function render_courses( array $data = [] ): stdClass {
		$content          = new stdClass();
		$content->content = '';

		try {
			$filter = new CourseJsonFilter();

			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				if ( ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
					throw new Exception(
						esc_html__(
							'You do not have permission to search courses.',
							'learnpress'
						)
					);
				}

				$filter->post_author = get_current_user_id();
			}

			$search = self::get_search( $data );
			if ( $search === '' ) {
				return $content;
			}

			$filter->only_fields = [ 'ID', 'post_title' ];
			$filter->post_title  = $search;
			// All statuses of the admin list, without trash (and auto-draft, excluded by the DB class).
			$filter->post_status     = [ 'publish' ];
			$filter->order_by        = 'post_title';
			$filter->limit           = self::LIMIT;
			$filter->page            = 1;
			$filter->run_query_count = false;

			$courses = CourseService::get_list_courses( $filter );
			$items   = [];
			foreach ( $courses as $course ) {
				$items[] = [
					'id'   => (int) ( $course->ID ?? 0 ),
					'text' => (string) ( $course->post_title ?? '' ),
				];
			}

			$content->content = self::html_list_result( $items );
		} catch ( Throwable $e ) {
			$content->content = Template::print_message( $e->getMessage(), 'error', false );
		}

		return $content;
	}
}
