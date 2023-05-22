<?php
defined( 'ABSPATH' ) || exit();

/**
 * Class LP_User_Guest
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */
class LP_User_Guest extends LP_User {

	/**
	 * LP_User_Guest constructor.
	 *
	 * @param int $the_user
	 */
	public function __construct( $the_user = 0 ) {
		parent::__construct( $the_user );
	}

	/**
	 * @return string, Certificate must user_id is int, so temporary comment.
	 */
	/*public function get_id(): string {
		return LP_Session_Handler::instance()->get_cookie_data();
	}*/
}
