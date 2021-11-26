<?php
/**
 * Class LP_Filter
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 4.0.0
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
	 * @var string
	 */
	public $order_by = '';
	/**
	 * @var string
	 */
	public $order_by_desc = '';
	/**
	 * @var string
	 */
	public $key_word = '';
	/**
	 * @var int
	 */
	public $page = 1;
	/**
	 * @var string
	 */
	public $select = '';
	/**
	 * @var string
	 */
	public $query_type = 'get_results';

	public function __construct() {
		$this->limit = apply_filters( 'lp/filter/limit', $this->limit );
	}
}


