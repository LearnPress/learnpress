<?php
/**
 * Class LP_Post_DB
 *
 * @since 4.2.6.9
 * @version 1.0.0
 */
class LP_Post_DB extends LP_Database {

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
	 * @return array|null|int|string
	 * @throws Exception
	 * @since 4.1.6
	 * @version 1.0.0
	 */
	public function get_posts( LP_Post_Type_Filter $filter, int &$total_rows = 0 ) {
		$filter->fields = array_merge( $filter->all_fields, $filter->fields );

		if ( empty( $filter->collection ) ) {
			$filter->collection = $this->tb_posts;
		}

		if ( empty( $filter->collection_alias ) ) {
			$filter->collection_alias = 'p';
		}

		$ca = $filter->collection_alias;

		// Where
		$filter->where[] = $this->wpdb->prepare( "AND $ca.post_type = %s", $filter->post_type );

		// Find ID
		if ( isset( $filter->ID ) ) {
			$filter->where[]    = $this->wpdb->prepare( "AND $ca.ID = %d", $filter->ID );
		}

		// Status
		$filter->post_status = (array) $filter->post_status;
		if ( ! empty( $filter->post_status ) ) {
			$post_status_format = LP_Helper::db_format_array( $filter->post_status, '%s' );
			$filter->where[]    = $this->wpdb->prepare( "AND $ca.post_status IN (" . $post_status_format . ')', $filter->post_status );
		}

		// Term ids
		if ( ! empty( $filter->term_ids ) ) {
			// Sanitize term ids
			$filter->term_ids = array_map( 'absint', $filter->term_ids );
			$term_ids_format  = join( ',', $filter->term_ids );
			$filter->join[]   = "INNER JOIN $this->tb_term_relationships AS r_term_p ON $ca.ID = r_term_p.object_id";
			$filter->join[]   = "INNER JOIN $this->tb_term_taxonomy AS tx_p ON r_term_p.term_taxonomy_id = tx_p.term_taxonomy_id";
			$filter->where[]  = "AND r_term_p.term_taxonomy_id IN ($term_ids_format)";
			$filter->where[]  = $this->wpdb->prepare( 'AND tx_p.taxonomy = %s', $filter->taxonomy );
		}

		// Post ids
		if ( ! empty( $filter->post_ids ) ) {
			$post_ids        = array_map( 'absint', $filter->post_ids );
			$post_ids_format = join( ',', $post_ids );
			$filter->where[] = "AND $ca.ID IN ($post_ids_format)";
		}

		// Title
		if ( $filter->post_title ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_title LIKE %s", '%' . $filter->post_title . '%' );
		}

		// Name(slug)
		if ( $filter->post_name ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_name = %s", $filter->post_name );
		}

		// Author
		if ( isset( $filter->post_author ) ) {
			$filter->where[] = $this->wpdb->prepare( "AND $ca.post_author = %d", $filter->post_author );
		}

		// Authors
		if ( ! empty( $filter->post_authors ) ) {
			$post_authors_format = LP_Helper::db_format_array( $filter->post_authors, '%d' );
			$filter->where[]     = $this->wpdb->prepare( "AND $ca.ID IN (" . $post_authors_format . ')', $filter->post_authors );
		}

		$filter = apply_filters( 'lp/post/query/filter', $filter );

		return $this->execute( $filter, $total_rows );
	}
}

