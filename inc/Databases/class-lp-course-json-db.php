<?php
/**
 * Class LP_Course_JSON_DB
 *
 * @author tungnx
 * @since 4.2.6.9
 */

defined( 'ABSPATH' ) || exit();

class LP_Course_JSON_DB extends LP_Database {
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
	 * Get course_id of item
	 *
	 * item type lp_lesson, lp_quiz
	 *
	 * @param int $item_id
	 *
	 * @return int
	 * @throws Exception
	 */
	public function get_course( int $course_id ): int {

	}
}

LP_Course_DB::getInstance();
