<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 *
 * @since 3.2.8
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
	 * Get all questions are unassigned to any quiz.
	 *
	 * @return array
	 * @since 3.0.0
	 *
	 */
	function learn_press_get_unassigned_questions() {
		if ( false === ( $questions = LP_Object_Cache::get( 'questions', 'learn-press/unassigned' ) ) ) {
			$query = $this->wpdb->prepare(
				"
	            SELECT p.ID
	            FROM {$this->tb_posts} p
	            WHERE p.post_type = %s
	            AND p.ID NOT IN(
	                SELECT qq.question_id
	                FROM {$this->tb_lp_quiz_questions} qq
	                INNER JOIN {$this->tb_posts} p ON p.ID = qq.question_id
	                WHERE p.post_type = %s
	            )
	            AND p.post_status NOT IN(%s, %s)",
				LP_QUESTION_CPT,
				LP_QUESTION_CPT,
				'auto-draft',
				'trash'
			);

			$questions = $this->wpdb->get_col( $query );
			LP_Object_Cache::set( 'questions', $questions, 'learn-press/unassigned' );
		}

		return $questions;
	}

	/**
	 * Count all questions are unassigned to any quiz.
	 *
	 * @return int
	 * @since 3.0.0
	 *
	 */
	function get_total_question_unassigned() {

		$query_append = '';
		if ( ! current_user_can( 'administrator' ) ) {
			$query_append .= ' AND post_author = ' . get_current_user_id();
		}

		$query = $this->wpdb->prepare(
			"
            SELECT COUNT(p.ID) as total
            FROM {$this->tb_posts} AS p
            WHERE p.post_type = %s
            AND p.ID NOT IN(
                SELECT qq.question_id
                FROM {$this->tb_lp_quiz_questions} AS qq
                INNER JOIN {$this->tb_posts} AS p
                ON p.ID = qq.question_id
                WHERE p.post_type = %s
            )
            AND p.post_status NOT IN(%s, %s)
            $query_append",
			LP_QUESTION_CPT,
			LP_QUESTION_CPT,
			'auto-draft',
			'trash'
		);

		$query = apply_filters( 'learnpress/query_get_total_question_unassigned', $query );

		return (int) $this->wpdb->get_var( $query );
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
	public function get_list_question_ids_of_quiz( LP_Question_Filter $filter ): array {
		$key_cache       = "lp/quiz/$filter->quiz_id/question_ids";
		$key_group_cache = 'lp/quiz';

		// Get cache
		$quiz_ids = wp_cache_get( $key_cache, $key_group_cache );

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
		wp_cache_set( $key_cache, $ids, $key_group_cache, DAY_IN_SECONDS );

		return $ids;
	}
}

