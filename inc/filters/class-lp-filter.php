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

if ( ! class_exists( 'LP_Filter' ) ) {
	class LP_Filter {
		public $limit = 10;
		public $order_by = '';
		public $order_by_desc = '';
		public $key_word = '';

		public function __construct() {
			$this->limit = apply_filters( 'lp/filter/limit', $this->limit );
		}
	}
} else {
	echo sprintf( 'Class %s exists', LP_Filter::class );
}


