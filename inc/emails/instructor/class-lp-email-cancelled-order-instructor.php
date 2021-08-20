<?php
/**
 * LP_Email_Cancelled_Order_Instructor.
 *
 * @author  ThimPress
 * @package Learnpress/Classes
 * @extends LP_Email_Type_Order
 * @version 3.0.0
 * @editor tungnx
 * @modify 4.1.3
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Email_Cancelled_Order_Instructor' ) ) {
	class LP_Email_Cancelled_Order_Instructor extends LP_Email_Type_Order {
		/**
		 * LP_Email_Cancelled_Order_Instructor constructor.
		 */
		public function __construct() {
			$this->id          = 'cancelled-order-instructor';
			$this->title       = __( 'Instructor', 'learnpress' );
			$this->description = __( 'Send email to course instructor when order has been cancelled', 'learnpress' );

			$this->default_subject = __( 'Order placed on {{order_date}} has been cancelled', 'learnpress' );
			$this->default_heading = __( 'User order has been cancelled', 'learnpress' );

			parent::__construct();
		}

		/**
		 * Get all instructor ids on all items of Order.
		 *
		 * @param LP_Order $order
		 * @return array
		 * @since 3.0.0
		 *
		 */
		protected function get_instructor_ids( LP_Order $order ): array {
			$items       = $order->get_items();
			$instructors = [];

			if ( count( $items ) ) {
				foreach ( $items as $item ) {
					if ( ! isset( $item['course_id'] ) ) {
						continue;
					}

					$user_id = get_post_field( 'post_author', $item['course_id'] );
					if ( $user_id ) {
						if ( empty( $instructors[ $user_id ] ) ) {
							$instructors[ $user_id ] = $user_id;
						}
					}
				}
			}

			return $instructors;
		}
	}

	return new LP_Email_Cancelled_Order_Instructor();
}
