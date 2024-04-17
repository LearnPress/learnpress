<?php
/**
 * Class LP_Filter
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 4.0.2
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Filter {
	const ORDER_DESC = 'DESC';
	const ORDER_ASC  = 'ASC';
	/**
	 * @var int set -1 for no limit
	 */
	public $limit = 10;
	/**
	 * @var int
	 */
	public $max_limit = 100;
	/**
	 * @var array query ON
	 */
	public $sort_by = array();
	/**
	 * @var string
	 */
	public $group_by = '';
	/**
	 * @var string field name for order, EX ID
	 */
	public $order_by = '';
	/**
	 * @var string DESC|ASC
	 */
	public $order = '';
	/**
	 * @var string
	 */
	public $key_word = '';
	/**
	 * @var int
	 */
	public $page = 1;
	/**
	 * Name table query Or Query nested
	 * EX: FROM 'wp_posts'
	 * OR: FROM (SELECT * FROM 'wp_posts') AS P
	 *
	 * @var string
	 */
	public $collection = '';
	/**
	 * Alias collection
	 * EX: FROM 'wp_posts' AS p
	 * "p" is alias
	 *
	 * @var string
	 */
	public $collection_alias = '';
	/**
	 * @var array
	 */
	public $fields = array();
	/**
	 * For get only columns set on it
	 *
	 * @var array
	 */
	public $only_fields = array();
	/**
	 * Exclude columns set on fields
	 *
	 * @var array
	 */
	public $exclude_fields = array();
	/**
	 * @var array for purpose run query update.
	 */
	public $set = array();
	/**
	 * @var array
	 */
	public $where = array();
	/**
	 * @var array
	 */
	public $join = array();
	/**
	 * @var array
	 */
	public $union = array();
	/**
	 * @var bool set true to run query count
	 */
	public $run_query_count = true;
	/**
	 * @var bool set true to return total_rows
	 */
	public $query_count = false;
	/**
	 * @var string Ex: ID, for query: COUNT(ID)
	 */
	public $field_count = 'ID';
	/**
	 * @var bool set true to return string query
	 */
	public $return_string_query = false;
	/**
	 * @var false set true to return string query to debug
	 */
	public $debug_string_query = false;
	/**
	 * @var string
	 */
	public $query_type = 'get_results';
	/**
	 * @var object stdclass
	 */
	public $filter_extra;
}


