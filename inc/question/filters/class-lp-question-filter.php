<?php
/**
 * Class LP_Question_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 3.2.8
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Question_Filter' ) ) {
	return;
}

class LP_Question_Filter {
	public $_post_type = '';
	public $_user_id = 0;
	public $_post_status = '';
}
