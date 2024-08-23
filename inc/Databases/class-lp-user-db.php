<?php
/**
 * Class LP_Course_DB
 *
 * @author tungnx
 * @since 3.2.7.5
 */

use LearnPress\Helpers\Singleton;

defined( 'ABSPATH' ) || exit();

class LP_User_DB extends LP_Database {
	use singleton;

	public function init() {
		parent::__construct();
	}

	/**
	 * Get users
	 *
	 * @param LP_User_Filter $filter
	 * @param int $total_rows
	 *
	 * @since 4.2.6.9
	 * @version 1.0.0
	 * @return array|object|null|int|string
	 * @throws Exception
	 */
	public function get_users( LP_User_Filter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_users;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'u';
		}

		$ca = $filter->collection_alias;

		// Find ID
		if ( isset( $filter->ID ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.ID = %d", $filter->ID );
		}

		$filter = apply_filters( 'lp/user/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}

