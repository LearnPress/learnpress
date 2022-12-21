<?php

/**
 * Class LP_Settings_Course_Review_Emails
 */
class LP_Settings_Course_Review_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Course_Review_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'course-review-emails';
		$this->items    = array(
			'new-course',
			'rejected-course',
			'published-course',
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Review Course', 'learnpress' );
	}
}

return new LP_Settings_Course_Review_Emails();
