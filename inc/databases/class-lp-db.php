<?php
/**
 * Class LP_Database
 *
 * @author tungnx
 * @since 3.2.7.5
 */
defined( 'ABSPATH' ) || exit();

class LP_Database {
	private static $_instance;
	public $wpdb;
	public $tb_lp_user_items, $tb_lp_user_itemmeta, $tb_lp_user_item_results;
	public $tb_posts, $tb_postmeta;
	public $tb_lp_order_items, $tb_lp_order_itemmeta;
	public $tb_lp_sections, $tb_lp_section_items;
	public $tb_lp_quiz_questions;

	protected function __construct() {
		global $wpdb;

		$prefix = $wpdb->prefix;

		$this->wpdb                    = $wpdb;
		$this->tb_users                = $wpdb->users;
		$this->tb_posts                = $wpdb->posts;
		$this->tb_postmeta             = $wpdb->postmeta;
		$this->tb_lp_user_items        = $prefix . 'learnpress_user_items';
		$this->tb_lp_user_itemmeta     = $prefix . 'learnpress_user_itemmeta';
		$this->tb_lp_user_item_results = $prefix . 'learnpress_user_item_results';
		$this->tb_lp_order_items       = $prefix . 'learnpress_order_items';
		$this->tb_lp_order_itemmeta    = $prefix . 'learnpress_order_itemmeta';
		$this->tb_lp_section_items     = $prefix . 'learnpress_section_items';
		$this->tb_lp_sections          = $prefix . 'learnpress_sections';
		$this->tb_lp_quiz_questions    = $prefix . 'learnpress_quiz_questions';
	}

	/**
	 * Get Instance
	 *
	 * @return LP_Database
	 */
	public static function getInstance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get list Item by post type and user id
	 *
	 * @param string $post_type .
	 * @param int    $user_id .
	 *
	 * @return array
	 */
	public function getListItem( $post_type = '', $user_id = 0 ) {
		$query = $this->wpdb->prepare(
			"
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
	 * @param LP_Post_Type_Filter $filter
	 *
	 * @return int
	 * @since 3.2.8
	 */
	public function get_count_post_of_user( $filter ) {
		$query_append = '';

		$cache_key = _count_posts_cache_key( $filter->post_type );

		// Get cache
		$counts = wp_cache_get( $cache_key );
		if ( false !== $counts ) {
			return $counts;
		}

		if ( ! empty( $filter->post_status ) ) {
			$query_append .= ' AND post_status = \'' . $filter->post_status . '\'';
		}

		$query = $this->wpdb->prepare(
			"
			SELECT Count(ID) FROM $this->tb_posts
			WHERE post_type = '%s'
			AND post_author = %d
			{$query_append}",
			$filter->post_type,
			$filter->post_author
		);

		$query = apply_filters( 'learnpress/query/get_total_post_of_user', $query );

		$counts = (int) $this->wpdb->get_var( $query );

		// Set cache
		wp_cache_set( $cache_key, $counts );

		return $counts;
	}

	/**
	 * Get post by post_type and slug
	 *
	 * @param string $post_type .
	 * @param string $slug .
	 *
	 * @return string
	 */
	public function getPostAuthorByTypeAndSlug( $post_type = '', $slug = '' ) {
		$query = $this->wpdb->prepare(
			"
			SELECT post_author FROM $this->tb_posts
			WHERE post_type = %s
			AND post_name = %s",
			$post_type,
			$slug
		);

		return $this->wpdb->get_var( $query );
	}
}
