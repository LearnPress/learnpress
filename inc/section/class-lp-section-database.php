<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Section_DB
 */

class LP_Section_DB extends LP_Database {
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
	 * Get course id by section id
	 *
	 * @param int $section_Id
	 *
	 * @return string|null
	 */
	public function get_course_id_by_section_id( $section_Id = 0 ) {
		$query = $this->wpdb->prepare(
			"SELECT section_course_id FROM $this->tb_lp_sections
					WHERE section_id = %d",
			$section_Id );

		$result = $this->wpdb->get_var( $query );

		return $result;
	}
}

LP_Section_DB::getInstance();

