<?php

namespace LearnPress\Models;

use Exception;
use LP_Background_Single_Course;
use LP_Cache;
use LP_Section_DB;
use LP_Section_Filter;
use stdClass;
use Throwable;

/**
 * Class CourseSectionModel
 *
 * Handle all method about section course
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.8.6
 */
class CourseSectionModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	private $section_id = 0;
	/**
	 * Title of the section
	 *
	 * @var int
	 */
	public $section_name = '';
	/**
	 * Foreign key, Course ID
	 *
	 * @var int
	 */
	public $section_course_id = 0;
	/**
	 * Order of the section
	 *
	 * @var int
	 */
	public $section_order = 0;
	/**
	 * Description of the section
	 *
	 * @var string
	 */
	public $section_description = '';

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}

	/**
	 * Map array, object data to CourseSectionModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return CourseSectionModel
	 */
	public function map_to_object( $data ): CourseSectionModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get section id
	 *
	 * @return int
	 */
	public function get_section_id(): int {
		return $this->section_id;
	}

	/**
	 * Get section by course id
	 *
	 * @return false|CourseSectionModel
	 */
	public static function find( int $course_id, $check_cache = true ) {
		$filter                    = new LP_Section_Filter();
		$filter->section_course_id = $course_id;
		$key_cache                 = "courseSection/find/{$course_id}";
		$lpSectionCache            = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$courseSectionModel = $lpSectionCache->get_cache( $key_cache );
			if ( $courseSectionModel instanceof CourseSectionModel ) {
				return $courseSectionModel;
			}
		}

		$courseSectionModel = static::get_item_model_from_db( $filter );

		// Set cache
		if ( $courseSectionModel instanceof CourseSectionModel ) {
			$lpSectionCache->set_cache( $key_cache, $courseSectionModel );
		}

		return $courseSectionModel;
	}

	/**
	 * Get post from database.
	 * If not exists, return false.
	 * If exists, return PostModel.
	 *
	 * @param LP_Section_Filter $filter
	 *
	 * @return CourseSectionModel|false|static
	 * @version 1.0.0
	 */
	public static function get_item_model_from_db( LP_Section_Filter $filter ) {
		$lp_section_db = LP_Section_DB::getInstance();
		$sectionModel  = false;

		try {
			$lp_section_db->get_query_single_row( $filter );
			$query_single_row = $lp_section_db->get_sections( $filter );
			$section_rs       = $lp_section_db->wpdb->get_row( $query_single_row );

			if ( $section_rs instanceof stdClass ) {
				$sectionModel = new static( $section_rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $sectionModel;
	}

	/**
	 * Save course data to table learnpress_courses.
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function save(): CourseSectionModel {
		$lp_section_db = LP_Section_DB::getInstance();

		$data = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		if ( $data['section_id'] === 0 ) { // Insert data.
			$section_id       = $lp_section_db->insert_data( $data );
			$this->section_id = $section_id;
		} else { // Update data.
			$lp_section_db->update_data( $data );
		}

		// Clear cache
		$this->clean_caches();

		return $this;
	}

	/**
	 * Delete row
	 *
	 * @throws Exception
	 */
	public function delete() {
		$lp_section_db      = LP_Section_DB::getInstance();
		$filter             = new LP_Section_Filter();
		$filter->where[]    = $lp_section_db->wpdb->prepare( 'AND section_id = %d', $this->section_id );
		$filter->collection = $lp_section_db->tb_lp_sections;
		$lp_section_db->delete_execute( $filter );

		// Clear cache
		$this->clean_caches();
	}

	/**
	 * Clean caches
	 *
	 * @since 4.2.8.6
	 * @version 1.0.0
	 * @return void
	 */
	public function clean_caches() {
		$bg = LP_Background_Single_Course::instance();
		$bg->data(
			array(
				'handle_name' => 'save_post',
				'course_id'   => $this->section_course_id,
				'data'        => [],
			)
		)->dispatch();

		$key_cache       = "courseSection/find/id/{$this->get_section_id()}";
		$lp_course_cache = new LP_Cache();
		$lp_course_cache->clear( $key_cache );
	}
}
