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

if ( ! class_exists( 'LP_Post_Type_Filter' ) ) {
	class LP_Post_Type_Filter extends LP_Filter {
		public $post_type = '';
		public $post_status = '';
		public $post_author = '';

		public function __construct() {

		}
	}
} else {
	echo sprintf( 'Class %s exists', LP_Post_Type_Filter::class );
}


