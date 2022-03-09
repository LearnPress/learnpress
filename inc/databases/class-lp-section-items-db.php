<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Section_Items_DB
 */

class LP_Section_Items_DB extends LP_Database {
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
	 * Get section items
	 *
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.0
	 * @return array|null|int|string
	 */
	public function get_section_items( LP_Section_Items_Filter $filter ) {
		$default_fields = $this->get_cols_of_table( $this->tb_lp_section_items );
		$filter->fields = array_merge( $default_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_section_items;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'si';
		}

		return $this->execute( $filter );
	}
}

LP_Section_DB::getInstance();

