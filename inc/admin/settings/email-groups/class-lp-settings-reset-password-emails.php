<?php

/**
 * Class LP_Settings_Processing_Order_Emails
 */
class LP_Settings_Reset_Password_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Processing_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'reset-password';
		$this->items    = array(
			'reset-password',
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Reset Password', 'learnpress' );
	}
}

return new LP_Settings_Reset_Password_Emails();
