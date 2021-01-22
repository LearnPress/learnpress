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
	 * Get all questions are unassigned to any quiz.
	 *
	 * @return array
	 * @since 3.0.0
	 *
	 */
	function learn_press_get_unassigned_questions() {
		if ( false === ( $questions = LP_Object_Cache::get( 'questions', 'learn-press/unassigned' ) ) ) {
			$query = $this->wpdb->prepare( "
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
			LP_QUESTION_CPT, LP_QUESTION_CPT, 'auto-draft', 'trash' );

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

		$query = $this->wpdb->prepare( "
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
			LP_QUESTION_CPT, LP_QUESTION_CPT, 'auto-draft', 'trash' );

		$query = apply_filters( 'learnpress/query_get_total_question_unassigned', $query );

		return (int) $this->wpdb->get_var( $query );
	}
}

