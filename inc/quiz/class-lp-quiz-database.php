<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 *
 * @since 3.2.7.4
 */
class LP_Quiz_DB extends LP_Database {
	public static $_instance;

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
	 * Get quiz_id of question
	 *
	 * @param int $question_id
	 *
	 * @return int
	 */
	public function get_quiz_id_by_question( $question_id = 0 ) {
		$query = $this->wpdb->prepare("
			SELECT quiz_id
			FROM {$this->tb_lp_quiz_questions}
			WHERE question_id = %d",
			$question_id );

		return (int) $this->wpdb->get_var( $query );
	}
}

LP_Quiz_DB::getInstance();

