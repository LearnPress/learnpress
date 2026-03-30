<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\Course\CourseJsonDB;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Filters\Course\CourseJsonFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LP_Debug;
use LP_Helper;
use LP_Section_DB;
use LP_Settings;
use stdClass;
use Throwable;

/**
 * Class CourseService
 *
 * Create course with data.
 *
 * @package LearnPress\Services
 * @since 4.3.0
 * @version 1.0.1
 */
class CourseService {
	use Singleton;

	public function init() {
	}

	/**
	 * Create course info main
	 *
	 * @param array $data [ 'post_title' => '', 'post_content' => '', 'post_status' => '', 'post_author' => , ... ]
	 *
	 * @throws Exception
	 */
	public function create_info_main( array $data ): CoursePostModel {
		$coursePostModelNew = new CoursePostModel( $data );
		$coursePostModelNew->save();

		return $coursePostModelNew;
	}

	/**
	 * Create metadata for course
	 *
	 * @param CoursePostModel $coursePostModel
	 * @param array $data
	 *
	 * @throws Exception
	 */
	public function create_meta_data( CoursePostModel $coursePostModel, array $data ) {
		foreach ( $data as $key => $value ) {
			$coursePostModel->save_meta_value_by_key( $key, $value );
		}
	}

	/**
	 * Get list courses with filter
	 *
	 * @param CourseJsonFilter $filter
	 * @param int $total_rows
	 *
	 * @return array
	 * @since 4.3.5
	 */
	public function get_courses( CourseJsonFilter $filter, int &$total_rows = 0 ): array {
		$db = CourseJsonDB::getInstance();

		try {
			// Order by
			switch ( $filter->order_by ) {
				case 'price':
				case 'price_low':
					if ( 'price_low' === $filter->order_by ) {
						$filter->order = 'ASC';
					} else {
						$filter->order = 'DESC';
					}

					$filter->order_by = 'price_to_sort';
					break;
				case 'popular':
					$filter = $db->get_courses_order_by_popular( $filter );
					break;
				case 'post_title':
					$filter->order = 'ASC';
					break;
				case 'post_title_desc':
					$filter->order_by = 'post_title';
					$filter->order    = 'DESC';
					break;
				case 'menu_order':
					$filter->order_by = 'menu_order';
					$filter->order    = 'ASC';
					break;
				default:
					$filter = apply_filters( 'lp/courses-json/filter/order_by/' . $filter->order_by, $filter );
					break;
			}

			// Query get results
			/**
			 * @var CourseJsonFilter $filter
			 */
			$filter  = apply_filters( 'lp/courses-json/filter', $filter );
			$courses = $db->get_courses( $filter, $total_rows );
		} catch ( Throwable $e ) {
			$courses = [];
			LP_Debug::error_log( $e );
		}

		return $courses;
	}
}
