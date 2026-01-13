<?php

/**
 * Class Course Post Model
 * To replace class LP_Course old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.6.9
 */

namespace LearnPress\Models;

use Exception;
use LearnPress\Databases\Course\CourseSectionItemsDB;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Filters\Course\CourseSectionItemsFilter;
use LP_Course_Filter;

use LP_Helper;

class CoursePostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_COURSE_CPT;

	/**
	 * Const meta key
	 */
	const META_KEY_PRICE                    = '_lp_price';
	const META_KEY_REGULAR_PRICE            = '_lp_regular_price';
	const META_KEY_SALE_PRICE               = '_lp_sale_price';
	const META_KEY_SALE_START               = '_lp_sale_start';
	const META_KEY_SALE_END                 = '_lp_sale_end';
	const META_KEY_EVALUATION_TYPE          = '_lp_course_result';
	const META_KEY_PASSING_CONDITION        = '_lp_passing_condition';
	const META_KEY_DURATION                 = '_lp_duration';
	const META_KEY_BLOCK_EXPIRE_DURATION    = '_lp_block_expire_duration';
	const META_KEY_BLOCK_FINISH             = '_lp_block_finished';
	const META_KEY_ALLOW_COURSE_REPURCHASE  = '_lp_allow_course_repurchase';
	const META_KEY_COURSE_REPURCHASE_OPTION = '_lp_course_repurchase_option';
	const META_KEY_LEVEL                    = '_lp_level';
	const META_KEY_STUDENTS                 = '_lp_students'; // Fake students key
	const META_KEY_MAX_STUDENTS             = '_lp_max_students';
	const META_KEY_RETAKE_COUNT             = '_lp_retake_count';
	const META_KEY_HAS_FINISH               = '_lp_has_finish';
	const META_KEY_FEATURED                 = '_lp_featured';
	const META_KEY_FEATURED_REVIEW          = '_lp_featured_review';
	const META_KEY_EXTERNAL_LINK_BY_COURSE  = '_lp_external_link_buy_course';
	const META_KEY_IS_SALE                  = '_lp_course_is_sale';
	const META_KEY_NO_REQUIRED_ENROLL       = '_lp_no_required_enroll';
	const META_KEY_OFFLINE_COURSE           = '_lp_offline_course';
	const META_KEY_ADDRESS                  = '_lp_address';
	const META_KEY_DELIVER                  = '_lp_deliver_type';
	const META_KEY_OFFLINE_LESSON_COUNT     = '_lp_offline_lesson_count';
	const META_KEY_REQUIREMENTS             = '_lp_requirements';
	const META_KEY_TARGET                   = '_lp_target_audiences';
	const META_KEY_FEATURES                 = '_lp_key_features';
	const META_KEY_FAQS                     = '_lp_faqs';
	const META_KEY_PRICE_PREFIX             = '_lp_price_prefix';
	const META_KEY_PRICE_SUFFIX             = '_lp_price_suffix';
	const META_KEY_FINAL_QUIZ               = '_lp_final_quiz';
	const META_KEY_SAMPLE_DATA              = '_lp_sample_data';

	/**
	 * Get the regular price of course.
	 *
	 * @return float
	 */
	public function get_regular_price(): float {
		// Regular price
		$regular_price = $this->get_meta_value_by_key( self::META_KEY_PRICE, '' ); // For LP version < 1.4.1.2
		if ( metadata_exists( 'post', $this->ID, self::META_KEY_REGULAR_PRICE ) ) {
			$regular_price = $this->get_meta_value_by_key( self::META_KEY_REGULAR_PRICE, '' );
		}

		$regular_price = floatval( $regular_price );

		return apply_filters( 'learnPress/course/regular-price', $regular_price, $this );
	}

	/**
	 * Get the sale price of course. Check if sale price is set
	 * and the dates are valid.
	 *
	 * @return string|float
	 */
	public function get_sale_price() {
		$sale_price_value = $this->get_meta_value_by_key( self::META_KEY_SALE_PRICE, '' );

		if ( '' !== $sale_price_value ) {
			return floatval( $sale_price_value );
		}

		return $sale_price_value;
	}

	/**
	 * Get post course by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter_post     = new LP_Course_Filter();
		$filter_post->ID = $post_id;

		return self::get_item_model_from_db( $filter_post );
	}

	/**
	 * Get course model
	 *
	 * @return CourseModel|null
	 */
	public function get_course_model(): ?CourseModel {
		return CourseModel::find( $this->get_id(), true );
	}

	/**
	 * Add section to course
	 *
	 * @param array $data [ 'section_name' => '', 'section_description' => '' ]
	 *
	 * @throws Exception
	 * @since 4.3.0
	 * @version 1.0.0
	 */
	public function add_section( array $data ): CourseSectionModel {
		$course_id    = $this->get_id();
		$section_name = trim( $data['section_name'] ?? '' );
		if ( empty( $section_name ) ) {
			throw new Exception( __( 'Section title is required', 'learnpress' ) );
		}

		$section_description = LP_Helper::sanitize_params_submitted( $data['section_description'] ?? '', 'html' );

		// Get max section order
		$max_order = CourseSectionDB::getInstance()->get_last_number_order( $course_id );

		$sectionNew                      = new CourseSectionModel();
		$sectionNew->section_name        = $section_name;
		$sectionNew->section_description = $section_description;
		$sectionNew->section_course_id   = $course_id;
		$sectionNew->section_order       = $max_order + 1;
		$sectionNew->save();

		return $sectionNew;
	}

	/**
	 * Update section
	 *
	 * @throws Exception
	 * @since  4.3.0
	 * @version 1.0.1
	 */
	public function update_section( CourseSectionModel $courseSectionModel, array $data ) {
		foreach ( $data as $key => $value ) {
			if ( $key !== 'section_id' && property_exists( $courseSectionModel, $key ) ) {
				$courseSectionModel->{$key} = $value;
			}
		}

		$courseSectionModel->save();
	}

	/**
	 * Update sections position
	 * new_position => list of section id by order
	 *
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.3.0
	 */
	public function update_sections_position( array $data ) {
		// Check permission
		if ( ! $this->check_capabilities_update() ) {
			throw new Exception( __( 'You do not have permission to update course sections', 'learnpress' ) );
		}

		$new_position = $data['new_position'] ?? [];
		if ( ! is_array( $new_position ) ) {
			throw new Exception( __( 'Invalid section position', 'learnpress' ) );
		}

		$course_id   = $this->get_id();
		$courseModel = $this->get_course_model();
		if ( ! $courseModel ) {
			throw new Exception( __( 'Course not found', 'learnpress' ) );
		}

		CourseSectionDB::getInstance()->update_sections_position( $new_position, $course_id );

		$courseModel->sections_items = null;
		$courseModel->save();
	}

	/**
	 * Update items position in curriculum, can change section of item.
	 *
	 * @param array $data [ items_position, item_id_change, section_id_new_of_item, section_id_old_of_item ]
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function update_items_position( array $data ) {
		// Check permission
		if ( ! $this->check_capabilities_update() ) {
			throw new Exception( __( 'You do not have permission to update course items position', 'learnpress' ) );
		}

		$courseModel = $this->get_course_model();

		$items_position         = $data['items_position'] ?? [];
		$item_id_change         = $data['item_id_change'] ?? 0;
		$section_id_new_of_item = $data['section_id_new_of_item'] ?? 0;
		$section_id_old_of_item = $data['section_id_old_of_item'] ?? 0;

		if ( ! is_array( $items_position ) ) {
			throw new Exception( __( 'Invalid item position', 'learnpress' ) );
		}

		// Find item of section id old
		$filter                  = new CourseSectionItemsFilter();
		$filter->section_id      = $section_id_old_of_item;
		$filter->item_id         = $item_id_change;
		$filter->run_query_count = false;

		$courseSectionItemModel = CourseSectionItemModel::get_item_model_from_db( $filter );
		if ( ! $courseSectionItemModel ) {
			throw new Exception( __( 'Item not found in section', 'learnpress' ) );
		}

		// Update section id of item
		$courseSectionItemModel->section_id        = $section_id_new_of_item;
		$courseSectionItemModel->section_course_id = $this->get_id();
		$courseSectionItemModel->save();

		// For each section to find item then update section id of item and position of item in the new section
		$sections_items = $courseModel->get_section_items();
		foreach ( $sections_items as $section_items ) {
			$section_id = $section_items->section_id ?? 0;

			if ( $section_id != $section_id_new_of_item ) {
				continue;
			}

			// Update position of item in section
			CourseSectionItemsDB::getInstance()->update_items_position( $items_position, $section_id_new_of_item );
			break;
		}

		// Clear cache
		$courseModel->sections_items = null;
	}

	public function check_capabilities_create(): bool {
		return current_user_can( 'edit_' . $this->post_type . 's' );
	}

	public function check_capabilities_update(): bool {
		return current_user_can( 'edit_' . $this->post_type, $this->ID );
	}
}
