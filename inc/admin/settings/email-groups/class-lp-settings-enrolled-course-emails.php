<?php

/**
 * Class LP_Settings_Enrolled_Course_Emails
 */
class LP_Settings_Enrolled_Course_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Enrolled_Course_Emails constructor.
	 */
	public function __construct() {

		$this->group_id = 'enrolled-course-emails';

		$this->items = apply_filters(
			'learn-press/emails/enrolled-course',
			array(
				'enrolled-course-admin',
				'enrolled-course-instructor',
				'enrolled-course-user',
			)
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Enrolled Course', 'learnpress' );
	}
}

return new LP_Settings_Enrolled_Course_Emails();
