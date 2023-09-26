<?php
/**
 * Class LP_Question_Filter
 *
 * @author  ThimPress
 * @package LearnPress/Classes/Filters
 * @version 3.2.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( class_exists( 'LP_Question_Filter' ) ) {
	return;
}

class LP_User_Filter extends LP_Filter {
	/**
	 * @var string[] List of fields can be filtered.
	 */
	public $all_fields = [
		'ID',
		'user_login',
		'user_nicename',
		'user_email',
		'display_name',
	];
	/**
	 * @var int[] List of user ids.
	 */
	public $ids = [];
	/**
	 * @var string User nice name.
	 */
	public $user_nicename = '';
	/**
	 * @var string Email.
	 */
	public $user_email = '';
}
