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
	 * Get user item metas
	 *
	 * @return array|null|int|string
	 * @throws Exception
	 * @since 4.2.5
	 * @version 1.0.0
	 */
	public function get_user_item_metas( LP_User_Item_Meta_Filter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_lp_user_itemmeta;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'uim';
		}

		if ( ! empty( $filter->meta_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND uim.meta_id = %d', $filter->meta_id );
		}

		if ( ! empty( $filter->learnpress_user_item_id ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND uim.learnpress_user_item_id = %d', $filter->learnpress_user_item_id );
		}

		if ( ! empty( $filter->meta_key ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND uim.meta_key = %s', $filter->meta_key );
		}

		if ( ! empty( $filter->meta_value ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND uim.meta_value = %s', $filter->meta_value );
		}

		if ( ! empty( $filter->extra_value ) ) {
			$filter->where[] = $this->wpdb->prepare( 'AND uim.extra_value = %s', $filter->extra_value );
		}

		$filter = apply_filters( 'lp/user_item_meta/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
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

		$result = $this->wpdb->insert( $this->tb_lp_user_itemmeta, $data );
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

