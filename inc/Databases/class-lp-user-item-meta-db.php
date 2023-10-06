<?php

/**
 * Class LP_User_Item_Meta_DB
 *
 * @since 4.2.5
 * @version 1.0.0
 * @author tungnx
 */
class LP_User_Item_Meta_DB extends LP_Database {
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
	 * Insert data
	 *
	 * @param array $data [ meta_id, learnpress_user_item_id, meta_key, meta_value, extra_value ]
	 * @return int
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function insert_data( array $data ): int {
		$filter = new LP_User_Item_Meta_Filter();
		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields ) ) {
				unset( $data[ $col_name ] );
			}
		}

		$this->wpdb->insert( $this->tb_lp_user_itemmeta, $data );
		return $this->wpdb->insert_id;
	}

	/**
	 * Update data
	 *
	 * @param array $data [ meta_id, learnpress_user_item_id, meta_key, meta_value, extra_value ]
	 * @return bool
	 *
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function update_data( array $data ): bool {
		if ( empty( $data['meta_id'] ) ) {
			throw new Exception( __( 'Invalid meta id!', 'learnpress' ) . ' | ' . __METHOD__ );
		}

		$filter             = new LP_User_Item_Meta_Filter();
		$filter->collection = $this->tb_lp_user_itemmeta;
		foreach ( $data as $col_name => $value ) {
			if ( ! in_array( $col_name, $filter->all_fields ) ) {
				continue;
			}

			$filter->set[] = $this->wpdb->prepare( $col_name . ' = %s', $value );
		}
		$filter->where[] = $this->wpdb->prepare( 'AND meta_id = %d', $data['meta_id'] );
		$this->update_execute( $filter );

		return true;
	}
}

LP_User_Item_Meta_DB::getInstance();

