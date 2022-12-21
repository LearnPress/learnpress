<?php

/**
 * Class LP_Settings_Finished_Course_Emails
 */
class LP_Settings_Finished_Course_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Finished_Course_Emails constructor.
	 */
	public function __construct() {

		$this->group_id = 'finished-course-emails';

		$this->items = apply_filters(
			'learn-press/emails/finished-course',
			array(
				'finished-course-admin',
				'finished-course-instructor',
				'finished-course-user',
			)
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Finished Course', 'learnpress' );
	}
}

return new LP_Settings_Finished_Course_Emails();
