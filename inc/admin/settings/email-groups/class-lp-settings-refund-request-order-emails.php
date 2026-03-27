<?php

/**
 * Class LP_Settings_Refund_Request_Order_Emails
 */
class LP_Settings_Refund_Request_Order_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Refund_Request_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'refund-request-order-emails';
		$this->items    = array(
			'refund-request-order-admin',
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Refund Request Order', 'learnpress' );
	}
}

return new LP_Settings_Refund_Request_Order_Emails();
