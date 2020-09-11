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
	 * @param int    $user_id
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

LP_Database::getInstance();

