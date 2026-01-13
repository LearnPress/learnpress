<?php

namespace LearnPress\Databases\Course;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Filters\Course\CourseSectionItemsFilter;
use LP_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class CourseSectionItemsDB
 *
 * Convert from LP_Section_Items_DB
 *
 * @package LearnPress/Databases/Course
 * @since  4.3.2
 * @version 1.0.0
 */
class CourseSectionItemsDB extends DataBase {
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
	 * Get section items
	 *
	 * @param CourseSectionItemsFilter $filter
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @version 1.0.2
	 * @since 4.1.6
	 */
	public function get_section_items( $filter ) {
		$default_fields = $filter->all_fields;
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_section_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'si';
		}

		foreach ( $filter->fields as $k => $field ) {
			$filter->fields[ $k ] = $filter->collection_alias . '.' . $field;
		}

		if ( ! empty( $filter->section_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.section_id = %d', $filter->section_id );
		}

		if ( ! empty( $filter->item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.item_id = %d', $filter->item_id );
		}

		if ( ! empty( $filter->item_ids ) ) {
			$filter->where[] = $this->wpdb->prepare(
				'AND si.item_id IN(' . LP_Helper::db_format_array( $filter->item_ids, '%d' ) . ')',
				$filter->item_ids
			);
		}

		if ( ! empty( $filter->section_item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.section_item_id = %d', $filter->section_item_id );
		}

		if ( ! empty( $filter->item_type ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND si.item_type = %s', $filter->item_type );
		}

		return $this->execute( $filter );
	}

	/**
	 * Update items position
	 * Update item_order of each item in section
	 *
	 * convert from LP_Section_Items_DB::update_items_position
	 *
	 * @throws Exception
	 * @since 4.3.2
	 * @version 1.0.0
	 */
	public function update_items_position( array $item_ids, $section_id ) {
		$filter             = new CourseSectionItemsFilter();
		$filter->collection = $this->tb_lp_section_items;
		$SET_SQL            = 'item_order = CASE';

		foreach ( $item_ids as $position => $item_id ) {
			++$position;
			$item_id = absint( $item_id );
			if ( empty( $item_id ) ) {
				continue;
			}

			$SET_SQL .= $this->wpdb->prepare( ' WHEN item_id = %d THEN %d', $item_id, $position );
		}

		$SET_SQL        .= ' ELSE item_order END';
		$filter->set[]   = $SET_SQL;
		$filter->where[] = $this->wpdb->prepare( 'AND section_id = %d', $section_id );

		$this->update_execute( $filter );
	}

	/**
	 * Update table
	 *
	 * @throws Exception
	 */
	public function update( CourseSectionItemsFilter $filter ) {
		$filter->collection = $this->tb_lp_section_items;
		$this->update_execute( $filter );
	}
}
