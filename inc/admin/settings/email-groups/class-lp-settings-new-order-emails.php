<?php

/**
 * Class LP_Settings_New_Order_Emails
 */
class LP_Settings_New_Order_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_New_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'new-order-emails';
		$this->items    = array(
			'new-order-admin',
			'new-order-instructor',
			'new-order-user',
			'new-order-guest'
		);

		parent::__construct();
	}

	public function __toString() {
		return __('New Order', 'learnpress');
	}
}

return new LP_Settings_New_Order_Emails();