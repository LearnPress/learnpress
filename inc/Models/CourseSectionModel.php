<?php

namespace LearnPress\Models;

use Exception;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Filters\Course\CourseSectionItemsFilter;
use LearnPress\Filters\CourseSectionFilter;
use LP_Background_Single_Course;
use LP_Cache;
use LP_Database;
use LP_Helper;
use LP_Section_DB;
use LP_Section_Filter;
use LP_Section_Items_DB;
use LP_Section_Items_Filter;
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
	public $section_id = 0;
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
	 * Get course model
	 *
	 * @return false|CourseModel
	 */
	public function get_course_model() {
		return CourseModel::find( $this->section_course_id, true );
	}

	/**
	 * Get course post model
	 *
	 * @return false|CoursePostModel
	 * @version 1.0.0
	 * @since 4.3.2
	 */
	public function get_course_post_model() {
		$courseModel = $this->get_course_model();
		if ( $courseModel instanceof CourseModel ) {
			return new CoursePostModel( $courseModel );
		}

		return false;
	}

	/**
	 * Get section by course id
	 *
	 * @return false|CourseSectionModel
	 */
	public static function find( int $section_id, int $course_id, $check_cache = true ) {
		$filter                    = new LP_Section_Filter();
		$filter->section_id        = $section_id;
		$filter->section_course_id = $course_id;
		$key_cache                 = "courseSection/find/{$section_id}/{$course_id}";
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
	 * Get section by course id
	 *
	 * @return false|CourseSectionModel
	 */
	public static function find_by_course( int $course_id, $check_cache = true ) {
		$filter                    = new LP_Section_Filter();
		$filter->section_course_id = $course_id;
		$key_cache                 = "courseSection/find/course/{$course_id}";
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
	 * If exists, return CourseSectionModel.
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
	 * Create item and add to section.
	 *
	 * @param array $data [ 'item_type' => '', 'item_title' => '', 'item_content' => '' ]
	 *
	 * @return CourseSectionItemModel
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public function create_item_and_add( array $data ): CourseSectionItemModel {
		$item_type    = trim( $data['item_type'] ?? '' );
		$item_title   = $data['item_title'] ?? '';
		$item_content = $data['item_content'] ?? '';
		$courseModel  = $this->get_course_model();
		$section_id   = $this->get_section_id();

		if ( ! $courseModel instanceof CourseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$item_types = CourseModel::item_types_support();
		if ( ! in_array( $item_type, $item_types ) ) {
			throw new Exception( __( 'Item type invalid', 'learnpress' ) );
		}

		// Create new item
		if ( empty( $item_title ) ) {
			throw new Exception( __( 'Item title is required', 'learnpress' ) );
		}

		// Create item
		$itemModelNew               = new PostModel();
		$itemModelNew->post_type    = $item_type;
		$itemModelNew->post_title   = $item_title;
		$itemModelNew->post_content = $item_content;
		$itemModelNew->post_status  = 'publish';
		$itemModelNew->post_author  = get_current_user_id();
		$itemModelNew->save();
		$item_id = $itemModelNew->get_id();

		/*$post_args = [
			'post_author' => get_current_user_id(),
			'post_title'  => $item_title,
			'post_type'   => $item_type,
			'post_status' => 'publish',
		];
		$item_id   = wp_insert_post( $post_args );
		if ( is_wp_error( $item_id ) ) {
			throw new Exception( $item_id->get_error_message() );
		}*/

		// Get max item order
		$max_order = LP_Section_Items_DB::getInstance()->get_last_number_order( $section_id );

		// Add item to section
		$courseSectionItemModel                    = new CourseSectionItemModel();
		$courseSectionItemModel->item_id           = $item_id;
		$courseSectionItemModel->item_type         = $item_type;
		$courseSectionItemModel->section_id        = $section_id;
		$courseSectionItemModel->item_order        = $max_order + 1;
		$courseSectionItemModel->section_course_id = $this->section_course_id;
		$courseSectionItemModel->save();

		return $courseSectionItemModel;
	}

	/**
	 * Add items created to section.
	 *
	 * @param array $data
	 *
	 * @return array
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.2
	 */
	public function add_items( array $data ): array {
		$courseSectionItems = [];
		$courseModel        = $this->get_course_model();
		$section_id         = $this->get_section_id();

		if ( ! $courseModel instanceof CourseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		$items = $data['items'] ?? [];
		foreach ( $items as $item ) {
			if ( ! key_exists( 'id', $item ) || ! key_exists( 'type', $item ) ) {
				throw new Exception( __( 'Keys data invalid!', 'learnpress' ) );
			}

			$item_id   = intval( $item['id'] ?? 0 );
			$item_type = $item['type'] ?? '';
			if ( ! $item_id ) {
				continue;
			}

			// Check if item already exists in course.
			$lp_db                  = LP_Database::getInstance();
			$filter                 = new CourseSectionItemsFilter();
			$filter->item_id        = $item_id;
			$filter->item_type      = $item_type;
			$filter->join[]         = 'LEFT JOIN ' . $lp_db->tb_lp_sections . ' AS cs ON si.section_id = cs.section_id';
			$filter->where[]        = $lp_db->wpdb->prepare( 'AND cs.section_course_id = %d', $this->section_course_id );
			$courseSectionItemModel = CourseSectionItemModel::get_item_model_from_db( $filter );
			if ( $courseSectionItemModel instanceof CourseSectionItemModel ) {
				throw new Exception( __( 'Item already exists in this course', 'learnpress' ) );
			}

			// Get max item order
			$max_order = LP_Section_Items_DB::getInstance()->get_last_number_order( $section_id );

			// Add item to section
			$courseSectionItemModel                    = new CourseSectionItemModel();
			$courseSectionItemModel->item_id           = $item_id;
			$courseSectionItemModel->item_type         = $item_type;
			$courseSectionItemModel->item_order        = ++ $max_order;
			$courseSectionItemModel->section_id        = $section_id;
			$courseSectionItemModel->section_course_id = $this->section_course_id;
			$courseSectionItemModel->save();

			$courseSectionItems[] = $courseSectionItemModel;
		}

		return $courseSectionItems;
	}

	/**
	 * Save course data to table learnpress_sections.
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.2
	 */
	public function save(): CourseSectionModel {
		// Check permission
		$this->check_permission();

		$courseSectionDB = CourseSectionDB::getInstance();

		$data = get_object_vars( $this );

		$args = [
			'data'               => $data,
			'filter'             => new CourseSectionFilter(),
			'table_name'         => $courseSectionDB->tb_lp_sections,
			'key_auto_increment' => 'section_id',
		];

		if ( $data['section_id'] === 0 ) { // Insert data.
			$section_id       = $courseSectionDB->insert_data( $args );
			$this->section_id = $section_id;
		} else { // Update data.
			$args['where_key'] = 'section_id';
			$courseSectionDB->update_data( $args );
		}

		// Clear cache
		$this->clean_caches();

		return $this;
	}

	/**
	 * Delete row
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.2
	 */
	public function delete() {
		// Check permission
		$this->check_permission();

		// Unassign items of section
		$lp_section_items_db = LP_Section_Items_DB::getInstance();
		$filter              = new LP_Section_Items_Filter();
		$filter->where[]     = $lp_section_items_db->wpdb->prepare( 'AND section_id = %d', $this->section_id );
		$filter->collection  = $lp_section_items_db->tb_lp_section_items;
		$lp_section_items_db->delete_execute( $filter );

		// Delete section
		$lp_section_db      = LP_Section_DB::getInstance();
		$filter             = new LP_Section_Filter();
		$filter->where[]    = $lp_section_db->wpdb->prepare( 'AND section_id = %d', $this->section_id );
		$filter->collection = $lp_section_db->tb_lp_sections;
		$lp_section_db->delete_execute( $filter );

		// Clear cache
		$this->clean_caches();
	}

	/**
	 * Check permission to handle
	 *
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function check_permission() {
		$coursePostModel = $this->get_course_post_model();
		if ( ! $coursePostModel || ! $coursePostModel->check_capabilities_update() ) {
			throw new Exception( __( 'You do not have permission to delete section', 'learnpress' ) );
		}
	}

	/**
	 * Clean caches
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function clean_caches() {
		// Call background multiple times will not cause any problem.
		/*$bg = LP_Background_Single_Course::instance();
		$bg->data(
			array(
				'handle_name' => 'save_post',
				'course_id'   => $this->section_course_id,
				'data'        => [],
			)
		)->dispatch();*/
		$courseModel                 = CourseModel::find( $this->section_course_id, true );
		$courseModel->sections_items = null;
		$courseModel->save();

		$key_cache        = "courseSection/find/{$this->get_section_id()}/{$this->section_course_id}";
		$key_cache_course = "courseSection/find/course/{$this->section_course_id}";
		$lp_course_cache  = new LP_Cache();
		$lp_course_cache->clear( $key_cache );
		$lp_course_cache->clear( $key_cache_course );
	}
}
