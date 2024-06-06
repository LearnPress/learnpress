<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 *
 * @since 3.2.8
 * @version 1.0.2
 */
class LP_Question_DB extends LP_Post_DB {
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
	 *  Get questions
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function get_questions( LP_Question_Filter $filter, &$total_rows = 0 ) {
		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'q';
		}

		return $this->get_posts( $filter, $total_rows );
	}

	/**
	 * Get all questions are unassigned to any quiz.
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function get_questions_not_assign_quiz( LP_Question_Filter $filter = null ) {
		$lp_qq_filter                      = new LP_Quiz_Questions_Filter();
		$lp_qq_filter->return_string_query = true;
		$lp_qq_filter->only_fields         = array( 'question_id' );
		$query_question_ids_assigned       = LP_Quiz_Questions_DB::getInstance()->get_quiz_questions( $lp_qq_filter );

		if ( is_null( $filter ) ) {
			$filter = new LP_Question_Filter();
		}
		$filter->only_fields      = array( 'q.ID' );
		$filter->where[]          = "AND ID NOT IN($query_question_ids_assigned)";
		$filter->where[]          = $this->wpdb->prepare( 'AND q.post_status not IN(%s, %s)', 'trash', 'auto-draft' );

		return $this->get_questions( $filter );
	}

	/**
	 * Count all questions are unassigned to any quiz.
	 *
	 * @param LP_Question_Filter|null $filter
	 *
	 * @return int
	 * @throws Exception
	 * @since 3.0.0
	 * @version 1.0.1
	 */
	function get_total_question_unassigned( LP_Question_Filter $filter = null ): int {
		if ( is_null( $filter ) ) {
			$filter = new LP_Question_Filter();
		}

		$filter->query_count = true;
		$filter->post_status = [];
		$filter->field_count = 'q.ID';

		return $this->get_questions_not_assign_quiz( $filter );
	}

	/**
	 * Get list Question ids of Quiz
	 *
	 * @param LP_Question_Filter $filter
	 *
	 * Clear cache when save quiz same id
	 *
	 * @return array
	 * @throws Exception
	 * @see   LP_Quiz_Post_Type::save
	 *
	 */
	public function get_list_question_ids_of_quiz( LP_Question_Filter $filter = null ): array {
		$key_cache = "$filter->ID/question_ids";

		// Get cache
		$lp_quiz_cache = LP_Quiz_Cache::instance();
		$quiz_ids      = $lp_quiz_cache->get_cache( $key_cache );

		if ( $quiz_ids ) {
			return $quiz_ids;
		}

		$statues = array( 'publish' );
		if ( ! empty( $filter->post_status ) ) {
			$statues = $filter->post_status;
		}

		$format = LP_Helper::format_query_IN( $statues );
		$args   = array_merge( array( LP_QUESTION_CPT, $filter->ID ), $statues );

		$query = $this->wpdb->prepare(
			"
				SELECT question_id
				FROM $this->tb_lp_quiz_questions AS quiz_q
				INNER JOIN $this->tb_posts as p
				ON p.ID = quiz_q.question_id
				AND p.post_type = %s
				AND quiz_q.quiz_id = %d
				AND p.post_status IN(" . $format . ')
				ORDER BY question_order
			',
			$args
		);

		$ids = $this->wpdb->get_col( $query );

		$this->check_execute_has_error();

		// Set cache
		$lp_quiz_cache->set_cache( $key_cache, $ids, DAY_IN_SECONDS );

		return $ids;
	}
}

