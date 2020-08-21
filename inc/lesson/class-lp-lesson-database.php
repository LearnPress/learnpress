<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Lesson_DB
 */
class LP_Lesson_DB extends LP_Database {
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
	 * Get section id by lesson id
	 *
	 * @param int $lesson_id
	 *
	 * @return string|null
	 */
	public function get_section_by_lesson_id( $lesson_id = 0 ) {
		$query = $this->wpdb->prepare(
			"SELECT section_id FROM $this->tb_lp_section_items
					WHERE item_type = %s
					AND item_id = %d",
			LP_LESSON_CPT, $lesson_id );

		$result = $this->wpdb->get_var( $query );

		return $result;
	}
}

LP_Lesson_DB::getInstance();

