<?php
/**
 * Class LP_Post_Type_Filter
 *
 * Filter post type of LP
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 4.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_User_Items_Filter' ) ) {
	class LP_User_Items_Filter extends LP_Filter {
		public $user_item_id = 0;
		public $user_id = 0;
		public $item_id = 0;
		public $status = '';
		public $item_type = '';
		public $start_time = '';
		public $end_time = '';
		public $parent_id = 0;

		public function __construct() {

		}
	}
} else {
	echo sprintf( 'Class %s exists', LP_User_Items_Filter::class );
}


