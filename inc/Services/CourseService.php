<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionModel;
use LP_Helper;
use LP_Section_DB;
use LP_Settings;
use stdClass;

/**
 * Class CourseService
 *
 * Create course with data.
 *
 * @package LearnPress\Services
 * @since 4.3.0
 * @version 1.0.0
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
}
