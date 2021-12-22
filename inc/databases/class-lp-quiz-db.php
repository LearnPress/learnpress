<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 *
 * @since 3.2.7.4
 * @author tungnx
 * @version 1.0.0
 */
class LP_Quiz_DB extends LP_Database {
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
	 * Get quiz_id of question
	 *
	 * @param int $question_id
	 *
	 * @return int
	 */
	public function get_quiz_id_by_question( int $question_id = 0 ) : int {
		$query = $this->wpdb->prepare(
			"
			SELECT quiz_id
			FROM $this->tb_lp_quiz_questions
			WHERE question_id = %d",
			$question_id
		);

		return (int) $this->wpdb->get_var( $query );
	}

	/**
	 * Get total questions assign for Quiz
	 *
	 * @param int $quiz_id
	 * @author tungnx
	 * @since 4.1.4.1
	 * @version 1.0.0
	 * @return int
	 */
	public function get_total_question( int $quiz_id = 0 ) : int {
		$query = $this->wpdb->prepare(
			"SELECT COUNT(question_id) AS total
			FROM $this->tb_lp_quiz_questions
			WHERE quiz_id = %d",
			$quiz_id
		);

		return (int) $this->wpdb->get_var( $query );
	}
}

LP_Quiz_DB::getInstance();

