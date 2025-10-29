<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\CourseSectionFilter;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\CourseSectionModel;
use LearnPress\Models\PostModel;
use LP_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class CourseSectionDB
 *
 * Refactor of LP_Section_DB
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class CourseSectionDB extends DataBase {
	public static $_instance;

	public function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get sections
	 *
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.2
	 */
	public function get_sections( CourseSectionFilter $filter, &$total_rows = 0 ) {
		$default_fields = $filter->all_fields;
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_sections;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'st';
		}

		$filter->field_count = 'st.section_id';

		if ( isset( $filter->section_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND st.section_id = %d', $filter->section_id );
		}

		if ( isset( $filter->section_course_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND st.section_course_id = %d', $filter->section_course_id );
		}

		if ( isset( $filter->section_name ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND st.section_name LIKE %s', '%' . $filter->section_name . '%' );
		}

		if ( ! empty( $filter->section_ids ) ) {
			$section_ids_format = LP_Helper::db_format_array( $filter->section_ids, '%d' );
			$filter->where[]    = $this->wpdb->prepare( 'AND st.section_id IN (' . $section_ids_format . ')', $filter->section_ids );
		}

		// Default Order
		if ( empty( $filter->order ) ) {
			$filter->order_by = 'st.section_order';
			$filter->order    = 'ASC';
		}

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get last section order of course
	 *
	 * @param int $course_id
	 *
	 * @return int
	 * @throws Exception
	 * @since 4.1.6.9
	 * @version 1.0.0
	 */
	public function get_last_number_order( int $course_id = 0 ): int {
		$query = $this->wpdb->prepare(
			"SELECT MAX(section_order)
			FROM $this->tb_lp_sections
			WHERE section_course_id = %d",
			$course_id
		);

		$number_order = intval( $this->wpdb->get_var( $query ) );

		$this->check_execute_has_error();

		return $number_order;
	}

	/**
	 * Update sections position
	 * Update section_order of each section in course
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.0
	 */
	public function update_sections_position( array $section_ids, $section_course_id ) {
		$filter             = new CourseSectionFilter();
		$filter->collection = $this->tb_lp_sections;
		$SET_SQL            = 'section_order = CASE';

		foreach ( $section_ids as $position => $section_id ) {
			++ $position;
			$section_id = absint( $section_id );
			if ( empty( $section_id ) ) {
				continue;
			}

			$SET_SQL .= $this->wpdb->prepare( ' WHEN section_id = %d THEN %d', $section_id, $position );
		}

		$SET_SQL        .= ' ELSE section_order END';
		$filter->set[]   = $SET_SQL;
		$filter->where[] = $this->wpdb->prepare( 'AND section_course_id = %d', $section_course_id );

		$this->update_execute( $filter );
	}
}
