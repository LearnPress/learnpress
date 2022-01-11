<?php
/**
 * Class LP_Filter
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 4.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

class LP_Filter {
	/**
	 * @var int
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
	 * @var string For direct query
	 */
	public $order_by = '';
	/**
	 * @var string
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
	 * @var array
	 */
	public $fields = array();
	/**
	 * @var array
	 */
	public $where = array();
	/**
	 * @var array
	 */
	public $join = array();
	/**
	 * @var bool set true to return total_rows only
	 */
	public $query_count = false;
	/**
	 * @var string Ex: ID, for query: COUNT(ID)
	 */
	public $field_count = '';
	/**
	 * @var bool set true to return string query
	 */
	public $return_string_query = false;
	/**
	 * @var string
	 */
	public $query_type = 'get_results';

	public function __construct() {
		$this->limit     = apply_filters( 'lp/filter/limit', $this->limit );
		$this->max_limit = apply_filters( 'lp/filter/max/limit', $this->max_limit );
	}
}


