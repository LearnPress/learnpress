<?php
/**
 * Class LP_Section_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 4.1.4.1
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Section_Filter' ) ) {
	return;
}

class LP_Session_Filter extends LP_Filter {
	/**
	 * @var string[]
	 */
	public $all_fields = [ 'session_id', 'session_key', 'session_value', 'session_expiry' ];
	/**
	 * @var string
	 */
	public $field_count = 'session_id';
	/**
	 * @var int
	 */
	public $session_id = 0;
	/**
	 * @var string
	 */
	public $session_key = '';
	/**
	 * @var string
	 */
	public $session_value = '';
	/**
	 * @var string
	 */
	public $session_expiry = '';
}
