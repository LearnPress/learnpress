<?php
/**
 * Class LP_Gateway_None
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LP_Gateway_None extends LP_Gateway_Abstract {
	/**
	 * @param $order_id
	 *
	 * @return mixed
	 */
	public function process_payment( $order_id ) {
		$order = new LP_Order( $order_id );

		$order->payment_complete();

		return array( 'result' => 'success' );
	}
}
