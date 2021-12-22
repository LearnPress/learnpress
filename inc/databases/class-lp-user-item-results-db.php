<?php

/**
 * Query in table learnpress_user_item_result.
 *
 * @author Nhamdv <email@email.com>
 */
class LP_User_Items_Result_DB extends LP_Database {
	private static $_instance = null;

	protected function __construct() {
		parent::__construct();
	}

	/**
	 * Get list results.
	 *
	 * @param [type] $user_item_id
	 * @param integer $limit Number result in db.
	 * @param boolean $last Remove lastest result.
	 *
	 * @return array result
	 */
	public function get_results( $user_item_id, $limit = 3, $last = false ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return array();
		}

		$limit = absint( $limit ) ?? 3;

		$query = $wpdb->prepare(
			"SELECT result FROM $wpdb->learnpress_user_item_results
			WHERE user_item_id=%d ORDER BY id DESC LIMIT %d",
			$user_item_id,
			$limit + 1
		);

		$col = $wpdb->get_col( $query );

		if ( ! empty( $col ) && $last ) {
			unset( $col[0] );
		}

		return $col;
	}

	/**
	 * Get latest result.
	 *
	 * @param integer $user_item_id
	 *
	 * @return array|bool
	 */
	public function get_result( int $user_item_id = 0 ) {
		if ( ! $user_item_id ) {
			return false;
		}

		$result = $this->wpdb->get_var(
			$this->wpdb->prepare(
				"SELECT result FROM $this->tb_lp_user_item_results
				WHERE user_item_id = %d
				ORDER BY id DESC LIMIT 1
				",
				$user_item_id
			)
		);

		return $result && is_string( $result ) ? json_decode( $result, true ) : false;
	}

	/**
	 * Update or Insert result
	 *
	 * @param int $user_item_id
	 * @param string $result
	 *
	 * @return bool|int
	 */
	public function update( int $user_item_id = 0, string $result = '' ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return false;
		}

		$max_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MAX(id) id FROM $wpdb->learnpress_user_item_results
				WHERE user_item_id=%d
				",
				$user_item_id
			)
		);

		$data   = array( 'result' => $result );
		$where  = array(
			'id'           => absint( $max_id ),
			'user_item_id' => $user_item_id,
		);
		$format = array( '%s' );

		if ( absint( $max_id ) > 0 ) {
			$output = $wpdb->update( $wpdb->learnpress_user_item_results, $data, $where, $format );
		} else {
			$output = $this->insert( $user_item_id, $result );
		}

		return $output;
	}

	public function insert( $user_item_id = 0, $result = null ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return false;
		}

		$insert = $wpdb->insert(
			$wpdb->learnpress_user_item_results,
			array(
				'user_item_id' => $user_item_id,
				'result'       => $result,
			),
			array( '%d', '%s' )
		);

		if ( $insert ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	/**
	 * Delete all results by user_item_id.
	 *
	 * @param int $user_item_id .
	 *
	 * @return bool|int
	 */
	public function delete( $user_item_id = 0 ) {
		if ( ! current_user_can( ADMIN_ROLE ) ) {
			return false;
		}

		$delete = $this->wpdb->delete(
			$this->tb_lp_user_item_results,
			array(
				'user_item_id' => $user_item_id,
			),
			array( '%d' )
		);

		return $delete;
	}

	/**
	 * Delete all rows in user_item_ids
	 *
	 * @param LP_User_Items_Filter $filter $filter->user_item_ids
	 *
	 * @return bool|int
	 * @since 4.1.4
	 * @author tungnx
	 * @version 1.0.0
	 */
	public function remove_user_item_results( LP_User_Items_Filter $filter ) {
		// Check valid user.
		/*if ( ! is_user_logged_in() || ( ! current_user_can( 'administrator' ) && get_current_user_id() != $filter->user_id ) ) {
			throw new Exception( __( 'User invalid!', 'learnpress' ) );
		}*/

		if ( empty( $filter->user_item_ids ) ) {
			return 1;
		}

		$where = 'WHERE 1=1 ';

		$where .= $this->wpdb->prepare(
			'AND user_item_id IN(' . LP_Helper::db_format_array( $filter->user_item_ids, '%d' ) . ')',
			$filter->user_item_ids
		);

		return $this->wpdb->query(
			"DELETE FROM {$this->tb_lp_user_item_results}
			{$where}
			"
		);
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_User_Items_Result_DB::instance();
