<?php
/**
 * Class LP_Email_Instructor_Denied
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
 * @author tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Instructor_Denied' ) ) {
	class LP_Email_Instructor_Denied extends LP_Email_Type_Become_An_Instructor {
		/**
		 * LP_Email_Instructor_Denied constructor.
		 */
		public function __construct() {
			$this->id          = 'instructor-denied';
			$this->title       = __( 'Denied', 'learnpress' );
			$this->description = __( 'Your email requesting to become an instructor has been denied.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] Your request to become an instructor has been denied', 'learnpress' );
			$this->default_heading = __( 'Your request to become an instructor has been denied', 'learnpress' );

			parent::__construct();
		}
	}
}

return new LP_Email_Instructor_Denied();
