<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Database
 */
class LP_Database {
	public static $_instance;
	public $wpdb;
	public $tb_lp_user_items, $tb_lp_user_itemmeta;
	public $tb_posts, $tb_postmeta;
	public $tb_lp_order_items, $tb_lp_order_itemmeta;
	public $tb_lp_sections, $tb_lp_section_items;
	public $tb_lp_quiz_questions;

	protected function __construct() {
		/**
		 * @global wpdb
		 */
		global $wpdb;

		$this->wpdb                 = $wpdb;
		$this->tb_posts             = $this->wpdb->posts;
		$this->tb_postmeta          = $this->wpdb->postmeta;
		$this->tb_lp_user_items     = $this->wpdb->prefix . 'learnpress_user_items';
		$this->tb_lp_user_itemmeta  = $this->wpdb->prefix . 'learnpress_user_itemmeta';
		$this->tb_lp_order_items    = $this->wpdb->prefix . 'learnpress_order_items';
		$this->tb_lp_order_itemmeta = $this->wpdb->prefix . 'learnpress_order_itemmeta';
		$this->tb_lp_section_items  = $this->wpdb->prefix . 'learnpress_section_items';
		$this->tb_lp_sections       = $this->wpdb->prefix . 'learnpress_sections';
		$this->tb_lp_quiz_questions = $this->wpdb->prefix . 'learnpress_quiz_questions';
	}

	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get list Item by post type and user id
	 *
	 * @param string $post_type
	 * @param int $user_id
	 *
	 * @return array
	 */
	public function getListItem( $post_type = '', $user_id = 0 ) {
		$query = $this->wpdb->prepare( "
			SELECT ID FROM $this->tb_posts
			WHERE post_type = %s
			AND post_author = %d",
			$post_type,
			$user_id
		);

		return $this->wpdb->get_col( $query );
	}

	/**
	 * Get total Item by post type and user id
	 *
	 * @param LP_Question_Filter $filter
	 *
	 * @return int
	 * @since 3.2.8
	 *
	 */
	public function get_count_post_of_user( $filter ) {
		$query_append = '';

		$cache_key = _count_posts_cache_key( $filter->_post_type, '' );

		// Get cache
		$counts = wp_cache_get( $cache_key );
		if ( false !== $counts ) {
			return $counts;
		}

		if ( isset( $filter->_post_status ) && in_array( $filter->_post_status, array(
				'publish',
				'trash',
				'pending',
				'draft'
			) ) ) {
			$query_append .= ' AND post_status = \'' . $filter->_post_status . '\'';
		}

		$query = $this->wpdb->prepare( "
			SELECT Count(ID) FROM $this->tb_posts
			WHERE post_type = '%s'
			AND post_author = %d
			{$query_append}",
			$filter->_post_type,
			$filter->_user_id
		);

		$query = apply_filters( 'learnpress/query_get_total_post_of_user', $query );

		$counts = (int) $this->wpdb->get_var( $query );

		// Set cache
		wp_cache_set( $cache_key, $counts );

		return $counts;
	}

	/**
	 * Get post by post_type and slug
	 *
	 * @param string $post_type
	 * @param string $slug
	 *
	 * @return string
	 */
	public function getPostAuthorByTypeAndSlug( $post_type = '', $slug = '' ) {
		$query = $this->wpdb->prepare( "
			SELECT post_author FROM $this->tb_posts
			WHERE post_type = %s
			AND post_name = %s",
			$post_type,
			$slug
		);

		return $this->wpdb->get_var( $query );
	}
}
