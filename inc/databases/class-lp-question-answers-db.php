<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Quiz_Questions_DB
 *
 * @since 4.1.7
 * @version 1.0.0
 */
class LP_Question_Answers_DB extends LP_Database {
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
	 * Get question answers
	 *
	 * @throws Exception
	 */
	public function get_question_asnwers( LP_Question_Answers_Filter $filter, &$total_rows = 0 ) {
		$default_fields           = $this->get_cols_of_table( $this->tb_lp_question_answers );
		$filter->fields           = array_merge( $default_fields, $filter->fields );
		$filter->exclude_fields[] = 'order';
		$filter->fields[]         = '`order`';

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_question_answers;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'qa';
		}

		// Question ids
		if ( ! empty( $filter->question_ids ) ) {
			$question_ids_format = LP_Helper::db_format_array( $filter->question_ids, '%d' );
			$filter->where[]     = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_id IN (" . $question_ids_format . ')', $filter->question_ids );
		}

		// question_answer_ids
		if ( ! empty( $filter->question_answer_ids ) ) {
			$question_answer_ids_format = LP_Helper::db_format_array( $filter->question_answer_ids, '%d' );
			$filter->where[]            = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_answer_id IN (" . $question_answer_ids_format . ')', $filter->question_answer_ids );
		}

		return $this->execute( $filter, $total_rows );
	}
}

