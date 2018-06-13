<?php

/**
 * Class LP_Settings_Completed_Order_Emails
 */
class LP_Settings_Completed_Order_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Completed_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'completed-order-emails';
		$this->items    = array(
			'completed-order-admin',
			'completed-order-user',
			'completed-order-guest'
		);

		parent::__construct();
	}

	public function __toString() {
		return __('Completed Order', 'learnpress');
	}
}

return new LP_Settings_Completed_Order_Emails();