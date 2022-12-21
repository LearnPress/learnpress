<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 *
 * @since 3.2.8
 * @version 1.0.1
 */
class LP_Question_DB extends LP_Database {
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
		$default_fields = $this->get_cols_of_table( $this->tb_posts );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'q';
		}

		$ca = $filter->collection_alias;

		// Where
		$filter->where[] = $this->wpdb->prepare( "AND $ca.post_type = %s", $filter->post_type );

		// Status
		$filter->post_status = (array) $filter->post_status;
		if ( ! empty( $filter->post_status ) ) {
			$post_status_format = LP_Helper::db_format_array( $filter->post_status, '%s' );
			$filter->where[]    = $this->wpdb->prepare( "AND $ca.post_status IN (" . $post_status_format . ')', $filter->post_status );
		}

		// Term ids
		if ( ! empty( $filter->term_ids ) ) {
			$filter->join[] = "INNER JOIN $this->tb_term_relationships AS r_term ON p.ID = r_term.object_id";

			$term_ids_format = LP_Helper::db_format_array( $filter->term_ids, '%d' );
			$filter->where[] = $this->wpdb->prepare( 'AND r_term.term_taxonomy_id IN (' . $term_ids_format . ')', $filter->term_ids );
		}

		// Question ids
		if ( ! empty( $filter->post_ids ) ) {
			$list_ids_format = LP_Helper::db_format_array( $filter->post_ids, '%d' );
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID IN (" . $list_ids_format . ')', $filter->post_ids );
		}

		// Title
		if ( $filter->post_title ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_title LIKE %s", '%' . $filter->post_title . '%' );
		}

		// Author
		if ( $filter->post_author ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_author = %d", $filter->post_author );
		}

		// Authors
		if ( ! empty( $filter->post_authors ) ) {
			$post_authors_format = LP_Helper::db_format_array( $filter->post_authors, '%d' );
			$filter->where[]     = $this->wpdb->prepare( "AND $ca.ID IN (" . $post_authors_format . ')', $filter->post_authors );
		}

		$filter = apply_filters( 'lp/question/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
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
		$filter->collection_alias = 'q';
		$filter->only_fields      = array( 'q.ID' );
		$filter->where[]          = 'AND ID NOT IN(' . $query_question_ids_assigned . ')';
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
		$filter->post_status = array();
		$filter->field_count = 'ID';

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
		$key_cache = "$filter->quiz_id/question_ids";

		// Get cache
		$lp_quiz_cache = LP_Quiz_Cache::instance();
		$quiz_ids      = $lp_quiz_cache->get_cache( $key_cache );

		if ( $quiz_ids ) {
			return $quiz_ids;
		}

		$statues = array( 'publish' );
		if ( ! empty( $filter->statues ) ) {
			$statues = $filter->statues;
		}

		$format = LP_Helper::format_query_IN( $statues );
		$args   = array_merge( array( LP_QUESTION_CPT, $filter->quiz_id ), $statues );

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

