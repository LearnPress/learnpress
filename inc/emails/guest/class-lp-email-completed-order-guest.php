<?php
/**
 * Class LP_Email_Completed_Order_Guest
 *
 * Send email to customer in case they checkout as a guest.
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Completed_Order_Guest' ) ) {

	/**
	 * Class LP_Email_Completed_Order_Guest
	 */
	class LP_Email_Completed_Order_Guest extends LP_Email_Type_Order_Guest {

		/**
		 * LP_Email_Completed_Order_Guest constructor.
		 */
		public function __construct() {
			$this->id          = 'completed-order-guest';
			$this->title       = __( 'Guest', 'learnpress' );
			$this->description = __( 'Send email to the user who has bought course as guest.', 'learnpress' );

			$this->default_subject = __( 'Your order on {{order_date}} has completed', 'learnpress' );
			$this->default_heading = __( 'Your order has completed', 'learnpress' );

			parent::__construct();
		}
	}

	return new LP_Email_Completed_Order_Guest();
}
