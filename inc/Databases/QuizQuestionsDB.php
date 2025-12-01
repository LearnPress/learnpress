<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\QuizQuestionsFilter;
use LP_Database;
use LP_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class QuizQuestionsDB
 *
 * @since 4.2.9
 * @version 1.0.1
 */
class QuizQuestionsDB extends DataBase {
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
	public function get_quiz_questions( QuizQuestionsFilter $filter, &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_quiz_questions;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'qq';
		}

		// By quiz id
		if ( ! empty( $filter->quiz_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.quiz_id = %d", $filter->quiz_id );
		}

		// By question id
		if ( ! empty( $filter->question_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_id = %d", $filter->question_id );
		}

		// Question ids
		if ( ! empty( $filter->question_ids ) ) {
			$question_ids_format = LP_Helper::db_format_array( $filter->question_ids );
			$filter->where[]     = $this->wpdb->prepare( "AND {$filter->collection_alias}.question_id IN (" . $question_ids_format . ')', $filter->question_ids );
		}

		// Quiz ids
		if ( ! empty( $filter->quiz_ids ) ) {
			$quiz_ids_format = LP_Helper::db_format_array( $filter->question_ids );
			$filter->where[] = $this->wpdb->prepare( "AND {$filter->collection_alias}.quiz_id IN (" . $quiz_ids_format . ')', $filter->quiz_ids );
		}

		return $this->execute( $filter, $total_rows );
	}

	/**
	 * Get last item number order on section
	 *
	 * @throws Exception
	 */
	public function get_last_number_order( int $quiz_id = 0 ): int {
		$query = $this->wpdb->prepare(
			"SELECT MAX(question_order)
			FROM $this->tb_lp_quiz_questions
			WHERE quiz_id = %d",
			$quiz_id
		);

		$number_order = intval( $this->wpdb->get_var( $query ) );

		$this->check_execute_has_error();

		return $number_order;
	}

	/**
	 * Insert data
	 *
	 * @param array $data
	 *
	 * @return int
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.2.9
	 */
	public function insert_data( array $data ): int {
		$filter = new QuizQuestionsFilter();

		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields ) ) {
				unset( $data[ $col_name ] );
			}
		}

		// quiz_question_id is auto increment.
		unset( $data['quiz_question_id'] );

		$this->wpdb->insert( $this->tb_lp_quiz_questions, $data );

		$this->check_execute_has_error();

		return $this->wpdb->insert_id;
	}

	/**
	 * Update data
	 *
	 * @param array $data
	 *
	 * @return bool
	 *
	 * @throws Exception
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function update_data( array $data ): bool {
		if ( empty( $data['quiz_question_id'] ) ) {
			throw new Exception( __( 'Invalid quiz_question_id!', 'learnpress' ) . ' | ' . __FUNCTION__ );
		}

		$filter             = new QuizQuestionsFilter();
		$filter->collection = $this->tb_lp_quiz_questions;
		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields ) ) {
				continue;
			}

			if ( is_null( $value ) ) {
				$filter->set[] = $col_name . ' = null';
			} else {
				$filter->set[] = $this->wpdb->prepare( $col_name . ' = %s', $value );
			}
		}

		$filter->where[] = $this->wpdb->prepare( 'AND quiz_question_id = %d', $data['quiz_question_id'] );
		$this->update_execute( $filter );

		return true;
	}

	/**
	 * Update questions position
	 * Update question_order of each item in quiz.
	 *
	 * @throws Exception
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function update_question_position( array $question_ids, $quiz_id ) {
		$filter             = new QuizQuestionsFilter();
		$filter->collection = $this->tb_lp_quiz_questions;
		$SET_SQL            = 'question_order = CASE';

		foreach ( $question_ids as $position => $question_id ) {
			++$position;
			$question_id = absint( $question_id );
			if ( empty( $question_id ) ) {
				continue;
			}

			$SET_SQL .= $this->wpdb->prepare( ' WHEN question_id = %d THEN %d', $question_id, $position );
		}

		$SET_SQL        .= ' ELSE question_order END';
		$filter->set[]   = $SET_SQL;
		$filter->where[] = $this->wpdb->prepare( 'AND quiz_id = %d', $quiz_id );

		$this->update_execute( $filter );
	}
}
