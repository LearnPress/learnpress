<?php
/**
 * LP_Email_Finished_Course_User.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Finished_Course_User' ) ) {

	/**
	 * Class LP_Email_Finished_Course_User
	 */
	class LP_Email_Finished_Course_User extends LP_Email_Type_Finished_Course {
		public function __construct() {
			$this->id          = 'finished-course-user';
			$this->title       = __( 'User', 'learnpress' );
			$this->description = __( 'Send this email to the user when they have finished the course.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] You have finished the course', 'learnpress' );
			$this->default_heading = __( 'You have finished the course', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Finished_Course_User();
}
