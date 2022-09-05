<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Quiz_Questions_DB
 *
 * @since 4.1.6
 * @version 1.0.0
 */
class LP_Quiz_Questions_DB extends LP_Database {
	private static $_instance;

	protected function __construct() {
		parent::__construct();
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * @throws Exception
	 */
	public function get_quiz_questions( LP_Quiz_Questions_Filter $filter, &$total_rows = 0 ) {
		$default_fields = $this->get_cols_of_table( $this->tb_lp_quiz_questions );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_quiz_questions;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'qq';
		}

		// Question ids
		if ( ! empty( $filter->question_ids ) ) {
			$question_ids_format = LP_Helper::db_format_array( $filter->question_ids, '%s' );
			$filter->where[]     = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_id IN (" . $question_ids_format . ')', $filter->question_ids );
		}

		// Quiz ids
		if ( ! empty( $filter->quiz_ids ) ) {
			$quiz_ids_format = LP_Helper::db_format_array( $filter->question_ids, '%s' );
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.quiz_id IN (" . $quiz_ids_format . ')', $filter->quiz_ids );
		}

		return $this->execute( $filter, $total_rows );
	}
}

