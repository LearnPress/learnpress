<?php

/**
 * Class LP_User_Guest
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_User_Guest extends LP_Abstract_User {

	/**
	 * @param $the_user
	 */
	public function __construct( $the_user ) {
		parent::__construct( $the_user );
	}
}