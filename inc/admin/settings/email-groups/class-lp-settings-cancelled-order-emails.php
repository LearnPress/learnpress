<?php

/**
 * Class LP_Settings_Cancelled_Order_Emails
 */
class LP_Settings_Cancelled_Order_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Cancelled_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'cancelled-order-emails';
		$this->items    = array(
			'cancelled-order-admin',
			'cancelled-order-instructor',
			'cancelled-order-user',
			'cancelled-order-guest'
		);

		parent::__construct();
	}

	public function __toString() {
		return __('Cancelled Order', 'learnpress');
	}
}

return new LP_Settings_Cancelled_Order_Emails();