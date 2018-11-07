<?php

/**
 * Class LP_User
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

class LP_User extends LP_Abstract_User {
	public static function read_course_x( $the_post ) {
		///$this->_curd->read_course( $this->get_id(), $the_course );
		/// learn
		if ( LP_COURSE_CPT != get_post_type( $the_post->ID ) ) {
			return;
		}

		$curd = new LP_User_CURD( get_current_user_id(), $the_post->ID );
	}
}

add_action( 'the_post', array( 'LP_User', 'read_course_x' ) );