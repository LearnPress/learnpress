<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\QuestionAnswersFilter;
use LP_Database;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class QuestionAnswersDB
 *
 * @instead of LP_Question_Answers_DB
 * @since 4.2.9
 * @version 1.0.1
 */
class QuestionAnswersDB extends DataBase {
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
	public function get_question_answers( QuestionAnswersFilter $filter, &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_question_answers;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'qa';
		}

		// By question answer id
		if ( ! empty( $filter->question_answer_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_answer_id = %d", $filter->question_answer_id );
		}

		// By question id
		if ( ! empty( $filter->question_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_id = %d", $filter->question_id );
		}

		// By title
		if ( ! empty( $filter->title ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.title LIKE %s", '%' . $this->wpdb->esc_like( $filter->title ) . '%' );
		}

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get last answer number order on question.
	 *
	 * @throws Exception
	 */
	public function get_last_number_order( int $question_id = 0 ): int {
		$query = $this->wpdb->prepare(
			"SELECT MAX(`order`)
			FROM $this->tb_lp_question_answers
			WHERE question_id = %d",
			$question_id
		);

		$number_order = intval( $this->wpdb->get_var( $query ) );

		$this->check_execute_has_error();

		return $number_order;
	}
}
