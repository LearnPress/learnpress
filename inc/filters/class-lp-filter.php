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
	public $limit = 10;
	public $order_by = '';
	public $order_by_desc = '';
	public $key_word = '';
	public $page = 0;

	public function __construct() {
		$this->limit = apply_filters( 'lp/filter/limit', $this->limit );
	}
}


