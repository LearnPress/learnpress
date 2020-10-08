<?php

/**
 * Class LP_Settings_Processing_Order_Emails
 */
class LP_Settings_Processing_Order_Emails extends LP_Settings_Emails_Group {

	/**
	 * LP_Settings_Processing_Order_Emails constructor.
	 */
	public function __construct() {
		$this->group_id = 'processing-order-emails';
		$this->items    = array(
			'processing-order-user',
			'processing-order-guest',
		);

		parent::__construct();
	}

	public function __toString() {
		return esc_html__( 'Processing Order', 'learnpress' );
	}
}

return new LP_Settings_Processing_Order_Emails();
