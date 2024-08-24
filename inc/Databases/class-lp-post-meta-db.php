<?php

/**
 * Class LP_Post_DB
 *
 * @since 4.2.6.9
 * @version 1.0.0
 */
class LP_Post_Meta_DB extends LP_Database {

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
	 *  Get questions
	 *
	 * @return array|object|null|int|string
	 * @throws Exception
	 * @since 4.2.6.9
	 * @version 1.0.0
	 */
	public function get_post_metas( LP_Post_Meta_Filter $filter, &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );
		$col_meta_id    = LP_Post_Meta_Filter::COL_META_ID;
		$col_post_id    = LP_Post_Meta_Filter::COL_POST_ID;
		$col_meta_key   = LP_Post_Meta_Filter::COL_META_KEY;

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_postmeta;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'pm';
		}

		$ca = $filter->collection_alias;

		// Find meta_id
		if ( isset( $filter->meta_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$ca}.{$col_meta_id} = %d", $filter->meta_id );
		}

		// Find post_id
		if ( isset( $filter->post_id ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$ca}.{$col_post_id} = %d", $filter->post_id );
		}

		// Find meta_key
		if ( isset( $filter->meta_key ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND {$ca}.{$col_meta_key} = %s", $filter->meta_key );
		}

		$filter = apply_filters( 'lp/post-meta/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}

