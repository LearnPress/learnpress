<?php

namespace LearnPress\Databases;

use Exception;
use LearnPress\Filters\QuestionAnswerConfigFilter;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionAnswerConfigDB
 *
 * Handle table learnpress_question_answer_config
 *
 * @since 4.5.0
 * @version 1.0.0
 */
class QuestionAnswerConfigDB extends DataBase {
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
	 * Get rows from learnpress_question_answer_config
	 *
	 * @throws Exception
	 */
	public function get_question_answer_configs( QuestionAnswerConfigFilter $filter, &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_question_answer_config;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'qac';
		}

		// By question answer config id
		if ( ! empty( $filter->question_answer_id ) ) {
			$filter->where[] = $this->wpdb->prepare(
				"AND {$filter->collection_alias}.question_answer_id = %d",
				$filter->question_answer_id
			);
		}

		// By question id
		if ( ! empty( $filter->question_id ) ) {
			$filter->where[] = $this->wpdb->prepare(
				"AND {$filter->collection_alias}.question_id = %d",
				$filter->question_id
			);
		}

		// By list question ids
		if ( ! empty( $filter->question_ids ) ) {
			$question_ids_in = implode( ',', array_map( 'absint', $filter->question_ids ) );
			$filter->where[] = "AND {$filter->collection_alias}.question_id IN ({$question_ids_in})";
		}

		return $this->execute( $filter, $total_rows );
	}
}
