<?php

/**
 * Class LP_Settings_New_Order_Emails
 */
class LP_Settings_Become_Teacher_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_New_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'become-teacher-emails';
		$this->items    = array(
			'become-an-instructor',
			'instructor-accepted',
			'instructor-denied',
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Become an Instructor', 'learnpress' );
	}
}

return new LP_Settings_Become_Teacher_Emails();
