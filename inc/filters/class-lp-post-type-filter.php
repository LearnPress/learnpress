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

if ( ! class_exists( 'LP_Post_Type_Filter' ) ) {
	class LP_Post_Type_Filter extends LP_Filter {
		/**
		 * @var string
		 */
		public $post_type = '';
		/**
		 * @var string
		 */
		public $post_title = '';
		/**
		 * @var array
		 */
		public $post_status = array( 'publish' );
		/**
		 * @var int
		 */
		public $post_author = 0;
		/**
		 * @var array
		 */
		public $post_authors = array();
		/**
		 * @var array
		 */
		public $term_ids = array();
		/**
		 * @var array
		 */
		public $post_ids = array();

		public function __construct() {

		}
	}
} else {
	echo sprintf( 'Class %s exists', LP_Post_Type_Filter::class );
}


