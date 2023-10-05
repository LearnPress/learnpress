<?php
/**
 * Class LP_Post_Type_Filter
 *
 * Filter post type of LP
 *
 * @author  tungnx
 * @package LearnPress/Classes/Filters
 * @version 4.0.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_User_Items_Filter' ) ) {
	class LP_User_Items_Filter extends LP_Filter {
		/**
		 * @var string[] all fields of table
		 */
		public $all_fields = [
			'user_item_id',
			'user_id',
			'item_id',
			'start_time',
			'end_time',
			'item_type',
			'status',
			'graduation',
			'ref_id',
			'ref_type',
			'parent_id',
		];
		/**
		 * @var int
		 */
		public $user_item_id = 0;
		/**
		 * @var int
		 */
		public $user_id = 0;
		/**
		 * @var array int
		 */
		public $user_ids = [];
		/**
		 * @var int
		 */
		public $item_id = 0;
		/**
		 * @var array int
		 */
		public $item_ids = [];
		/**
		 * @var string
		 */
		public $status = '';
		/**
		 * @var string
		 */
		public $graduation = '';
		/**
		 * @var string
		 */
		public $item_type = '';
		/**
		 * @var int
		 */
		public $ref_id = 0;
		/**
		 * @var string
		 */
		public $ref_type = '';
		/**
		 * @var string
		 */
		public $start_time = '';
		/**
		 * @var string
		 */
		public $end_time = '';
		/**
		 * @var int
		 */
		public $parent_id = 0;
		/**
		 * @var int[]
		 */
		public $user_item_ids = [];
		/**
		 * @var string
		 */
		public $field_count = 'user_item_id';

		public function __construct() {

		}
	}
} else {
	echo sprintf( 'Class %s exists', LP_User_Items_Filter::class );
}


