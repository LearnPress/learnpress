<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Class LP_Database
 */
class LP_Database {
	private static $_instance;
	public $wpdb;
	public $tb_lp_user_items, $tb_lp_user_itemmeta;
	public $tb_posts, $tb_postmeta, $tb_users;
	public $tb_lp_order_items, $tb_lp_order_itemmeta;
	public $tb_lp_sections, $tb_lp_section_items;
	public $tb_lp_quiz_questions;

	protected function __construct() {
		/**
		 * @global wpdb
		 */
		global $wpdb;

		$this->wpdb                   = $wpdb;
		$this->tb_users               = $this->wpdb->users;
		$this->tb_posts               = $this->wpdb->posts;
		$this->tb_postmeta            = $this->wpdb->postmeta;
		$this->tb_lp_user_items       = $this->wpdb->prefix . 'learnpress_user_items';
		$this->tb_lp_user_itemmeta    = $this->wpdb->prefix . 'learnpress_user_itemmeta';
		$this->tb_lp_order_items      = $this->wpdb->prefix . 'learnpress_order_items';
		$this->tb_lp_order_itemmeta   = $this->wpdb->prefix . 'learnpress_order_itemmeta';
		$this->tb_lp_section_items    = $this->wpdb->prefix . 'learnpress_section_items';
		$this->tb_lp_sections         = $this->wpdb->prefix . 'learnpress_sections';
		$this->tb_lp_quiz_questions   = $this->wpdb->prefix . 'learnpress_quiz_questions';
		$this->tb_lp_question_answers = $this->wpdb->prefix . 'learnpress_question_answers';
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

	/**
	 * Count student enrolled course
	 *
	 * @param $course_id
	 * Count enrolled course
	 * since 3.2.8.2
	 *
	 * @editor Hungkv
	 * @return mixed
	 */
	public function count_enrolled_course( $course_id ) {
		global $wpdb;

		$query = $wpdb->prepare( "
                    SELECT count(item_id) as c
                    FROM $this->tb_lp_user_items
                    WHERE status = %s AND item_id = %s
                ", 'enrolled', $course_id );

		return $wpdb->get_var( $query );
	}

	/**
	 * Create index for table
	 *
	 * @return array
	 */
	public function create_index(): array {
		if ( current_user_can( 'administrator' ) ) {
			$this->wpdb->query( "SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'" );
			$result_index_lp_user_items       = $this->create_index_for_table(
				$this->tb_lp_user_items,
				array( 'user_id', 'item_id', 'item_type', 'ref_id', 'ref_type', 'parent_id' )
			);
			$result_index_lp_user_itemmeta    = $this->create_index_for_table(
				$this->tb_lp_user_itemmeta,
				array( 'learnpress_user_item_id', 'meta_key' )
			);
			$result_index_lp_sections         = $this->create_index_for_table(
				$this->tb_lp_sections,
				array( 'section_course_id', 'meta_key', 'section_order' )
			);
			$result_index_lp_section_items    = $this->create_index_for_table(
				$this->tb_lp_section_items,
				array( 'section_id', 'item_id', 'item_type', 'item_order' )
			);
			$result_index_lp_quiz_questions   = $this->create_index_for_table(
				$this->tb_lp_quiz_questions,
				array( 'quiz_id', 'question_id', 'question_order' )
			);
			$result_index_lp_question_answers = $this->create_index_for_table(
				$this->tb_lp_question_answers,
				array( 'question_id', 'answer_order' )
			);
			$result_index_lp_order_items      = $this->create_index_for_table(
				$this->tb_lp_order_items,
				array( 'order_id', 'order_item_name' )
			);
			$result_index_lp_order_itemmeta   = $this->create_index_for_table(
				$this->tb_lp_order_itemmeta,
				array( 'learnpress_order_item_id', 'meta_key' )
			);
			$result_index_posts               = $this->create_index_for_table(
				$this->tb_posts,
				array( 'post_type' )
			);

			$result = array_merge(
				$result_index_lp_user_items,
				$result_index_lp_user_itemmeta,
				$result_index_lp_sections,
				$result_index_lp_section_items,
				$result_index_lp_quiz_questions,
				$result_index_lp_question_answers,
				$result_index_lp_order_items,
				$result_index_lp_order_itemmeta,
				$result_index_posts
			);

			return $result;
		}
	}

	/**
	 * Create index for table
	 *
	 * @param string $tb_name
	 * @param array $fields_index
	 *
	 * @return array[]
	 */
	private function create_index_for_table( $tb_name = '', $fields_index = array() ): array {
		$query = "ALTER TABLE {$tb_name}";

		$results = array(
			$this->$tb_name => array()
		);

		foreach ( $fields_index as $key_index ) {
			${$key_index} = $query . " ADD INDEX {$key_index} ({$key_index})";

			$result = $this->wpdb->query( $this->wpdb->prepare( ${$key_index} ) );

			$results[ $tb_name ][ $key_index ] = $result;
		}

		// Optimize table
		$results[ $tb_name ]['optimize'] = $this->wpdb->query(
			$this->wpdb->prepare( "OPTIMIZE TABLE {$tb_name}" )
		);

		return $results;
	}
}
