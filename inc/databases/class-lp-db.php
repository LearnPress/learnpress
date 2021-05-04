<?php
/**
 * Class LP_Database
 *
 * @author tungnx
 * @since 3.2.7.5
 * @version 2.0.0
 */
defined( 'ABSPATH' ) || exit();

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

class LP_Database {
	private static $_instance;
	public $wpdb;
	public $tb_lp_user_items, $tb_lp_user_itemmeta;
	public $tb_posts, $tb_postmeta, $tb_options;
	public $tb_lp_order_items, $tb_lp_order_itemmeta;
	public $tb_lp_sections, $tb_lp_section_items;
	public $tb_lp_quiz_questions;
	public $tb_lp_user_item_results;
	public $tb_lp_question_answers;
	public $tb_lp_question_answermeta;
	public $tb_lp_upgrade_db;

	protected function __construct() {
		/**
		 * @global wpdb
		 */
		global $wpdb;
		$prefix = $wpdb->prefix;

		$this->wpdb                      = $wpdb;
		$this->tb_users                  = $wpdb->users;
		$this->tb_posts                  = $wpdb->posts;
		$this->tb_postmeta               = $wpdb->postmeta;
		$this->tb_options                = $wpdb->options;
		$this->tb_lp_user_items          = $prefix . 'learnpress_user_items';
		$this->tb_lp_user_itemmeta       = $prefix . 'learnpress_user_itemmeta';
		$this->tb_lp_order_items         = $prefix . 'learnpress_order_items';
		$this->tb_lp_order_itemmeta      = $prefix . 'learnpress_order_itemmeta';
		$this->tb_lp_section_items       = $prefix . 'learnpress_section_items';
		$this->tb_lp_sections            = $prefix . 'learnpress_sections';
		$this->tb_lp_quiz_questions      = $prefix . 'learnpress_quiz_questions';
		$this->tb_lp_user_item_results   = $prefix . 'learnpress_user_item_results';
		$this->tb_lp_question_answers    = $prefix . 'learnpress_question_answers';
		$this->tb_lp_question_answermeta = $prefix . 'learnpress_question_answermeta';
		$this->tb_lp_upgrade_db          = $prefix . 'learnpress_upgrade_db';
		$this->wpdb->hide_errors();
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

	/**
	 * Check table exists.
	 *
	 * @param string $name_table
	 *
	 * @return bool|int
	 */
	public function check_table_exists( string $name_table ) {
		return $this->wpdb->query( $this->wpdb->prepare( "SHOW TABLES LIKE '%s'", $name_table ) );
	}

	/**
	 * Clone table
	 *
	 * @param string $name_table .
	 *
	 * @return bool|int
	 */
	public function clone_table( string $name_table ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		$table_bk = $name_table . '_bk';

		// Drop table bk if exists.
		$this->drop_table( $table_bk );

		$query = dbDelta(
			"CREATE TABLE $table_bk LIKE $name_table;
			INSERT INTO $table_bk SELECT * FROM $name_table;"
		);

		return $query;
	}

	/**
	 * Check column table
	 *
	 * @param string $name_table .
	 * @param string $name_col .
	 *
	 * @return bool|int
	 */
	public function check_col_table( string $name_table = '', string $name_col = '' ) {
		$query = $this->wpdb->prepare( "SHOW COLUMNS FROM $name_table LIKE '%s'", $name_col );

		return $this->wpdb->query( $query );
	}

	/**
	 * Drop Column of Table
	 *
	 * @param string $name_table .
	 * @param string $name_col .
	 *
	 * @return bool|int
	 */
	public function drop_col_table( string $name_table = '', string $name_col = '' ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		$check_table = $this->check_col_table( $this->tb_lp_user_items, $name_col );

		if ( $check_table ) {
			$query = $this->wpdb->prepare( "ALTER TABLE $name_table DROP COLUMN $name_col", 1 );

			return $this->wpdb->query( $query );
		}

		return true;
	}

	/**
	 * Add Column of Table
	 *
	 * @param string $name_table .
	 * @param string $name_col .
	 * @param string $type .
	 * @param string $after_col .
	 *
	 * @return bool|int
	 */
	public function add_col_table( string $name_table, string $name_col, string $type, string $after_col = '' ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		$query_add = '';

		$col_exists = $this->check_col_table( $name_table, $name_col );

		if ( ! empty( $after_col ) ) {
			$query_add .= "AFTER $after_col";
		}

		if ( ! $col_exists ) {
			return $this->wpdb->query( "ALTER TABLE $name_table ADD COLUMN $name_col $type $query_add");
		}

		return true;
	}

	/**
	 * Drop Index of Table
	 *
	 * @param string $name_table .
	 *
	 * @return bool|int
	 */
	public function drop_indexs_table( string $name_table ) {
		$show_index = "SHOW INDEX FROM $name_table";
		$indexs     = $this->wpdb->get_results( $show_index );

		foreach ( $indexs as $index ) {
			if ( 'PRIMARY' === $index->Key_name || '1' !== $index->Seq_in_index ) {
				continue;
			}

			$query = $this->wpdb->prepare( "ALTER TABLE $name_table DROP INDEX $index->Key_name", 1 );

			$this->wpdb->query( $query );
		}
	}

	/**
	 * Add Index of Table
	 *
	 * @param string $name_table .
	 * @param array  $indexs .
	 *
	 * @return bool|int
	 */
	public function add_indexs_table( string $name_table, array $indexs ) {
		$add_index    = '';
		$count_indexs = count( $indexs ) - 1;

		// Drop indexs .
		$this->drop_indexs_table( $name_table );

		foreach ( $indexs as $index ) {
			if ( $count_indexs === array_search( $index, $indexs ) ) {
				$add_index .= ' ADD INDEX ' . $index . ' (' . $index . ')';
			} else {
				$add_index .= ' ADD INDEX ' . $index . ' (' . $index . '),';
			}
		}

		$query = $this->wpdb->prepare(
			"
				ALTER TABLE {$name_table}
				$add_index
			", 1
		);

		return $this->wpdb->query( $query );
	}

	/**
	 * Drop table
	 *
	 * @param string $name_table .
	 *
	 * @return bool|int
	 */
	public function drop_table( string $name_table = '' ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		// Check table exists.
		$tb_exists = $this->check_table_exists( $name_table );
		if ( $tb_exists ) {
			return $this->wpdb->query( "DROP TABLE $name_table" );
		}

		return true;
	}

	/**
	 * Create table learnpress_user_item_results
	 *
	 * @return bool|int
	 */
	public function create_tb_lp_user_item_results() {
		$query = $this->wpdb->prepare(
			"
				CREATE TABLE IF NOT EXISTS $this->tb_lp_user_item_results(
					id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
					user_item_id bigint(20) unsigned NOT NULL,
					result longtext,
					PRIMARY KEY (id),
					KEY user_item_id (user_item_id)
				)
			", 1
		);

		return $this->wpdb->query( $query );
	}

	/**
	 * Create table learnpress_upgrade_db
	 *
	 * @return bool|int
	 */
	public function create_tb_lp_upgrade_db() {
		$query = $this->wpdb->prepare(
			"
				CREATE TABLE IF NOT EXISTS {$this->tb_lp_upgrade_db}(
					step varchar(255) PRIMARY KEY UNIQUE,
					status varchar(20),
					KEY status (status)
				)
			", 1
		);

		return $this->wpdb->query( $query );
	}

	/**
	 * Set step completed.
	 *
	 * @param string $step .
	 * @param string $status .
	 *
	 * @return int|bool
	 */
	public function set_step_complete( string $step, string $status ) {
		if ( ! current_user_can( 'administrator' ) ) {
			return false;
		}

		return $this->wpdb->insert(
			$this->tb_lp_upgrade_db,
			array( 'step' => $step, 'status' => $status ),
			array( '%s', '%s' )
		);
	}

	/**
	 * Get steps completed.
	 *
	 * @return array|object|null
	 */
	public function get_steps_completed() {
		return $this->wpdb->get_results( "SELECT step, status FROM {$this->tb_lp_upgrade_db}", OBJECT_K );
	}
}
