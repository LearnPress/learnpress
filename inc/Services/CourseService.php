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

	/**
	 * Update categories for course
	 * Create categories if not exists
	 *
	 * @param $course_id
	 * @param int[] $category_ids
	 *
	 * @return void
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function update_categories( $course_id, array $category_ids ) {
		// Create categories if not exists
		foreach ( $category_ids as $category_id ) {
			$term_check = term_exists( $category_id, CoursePostModel::TAXONOMY_CATEGORY );
			if ( ! $term_check ) {
				wp_insert_term( $category_id, CoursePostModel::TAXONOMY_CATEGORY );
			}
		}

		wp_set_post_terms( $course_id, $category_ids, CoursePostModel::TAXONOMY_CATEGORY );
	}

	/**
	 * Update tags for course
	 * Create tags if not exists
	 *
	 * @param $course_id
	 * @param array $tag_ids
	 *
	 * @return void
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function update_tags( $course_id, array $tag_ids ) {
		// Create tags if not exists
		foreach ( $tag_ids as $tag_id ) {
			$term_check = term_exists( $tag_id, CoursePostModel::TAXONOMY_TAG );
			if ( ! $term_check ) {
				wp_insert_term( $tag_id, CoursePostModel::TAXONOMY_TAG );
			}
		}

		wp_set_post_terms( $course_id, $tag_ids, CoursePostModel::TAXONOMY_TAG );
	}
}
