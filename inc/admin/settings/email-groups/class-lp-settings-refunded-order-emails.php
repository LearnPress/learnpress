<?php

/**
 * Class LP_Settings_Refunded_Order_Emails
 */
class LP_Settings_Refunded_Order_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Refunded_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'refunded-order-emails';
		$this->items    = array(
			'refunded-order-admin',
			'refunded-order-instructor',
			'refunded-order-user',
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Refunded Order', 'learnpress' );
	}
}

return new LP_Settings_Refunded_Order_Emails();
