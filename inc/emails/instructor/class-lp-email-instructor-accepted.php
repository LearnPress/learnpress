<?php
/**
 * Class LP_Email_Instructor_Accepted
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.1
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Instructor_Accepted' ) ) {
	/**
	 * Class LP_Email_Instructor_Accepted
	 */
	class LP_Email_Instructor_Accepted extends LP_Email_Type_Become_An_Instructor {

		/**
		 * LP_Email_Instructor_Accepted constructor.
		 */
		public function __construct() {
			$this->id          = 'instructor-accepted';
			$this->title       = __( 'Accepted', 'learnpress' );
			$this->description = __( 'Become an instructor email accepted.', 'learnpress' );

			$this->default_subject = __( '[{{site_title}}] Your request to become an instructor accepted', 'learnpress' );
			$this->default_heading = __( 'Become an instructor accepted', 'learnpress' );

			parent::__construct();
		}
	}
}

return new LP_Email_Instructor_Accepted();
