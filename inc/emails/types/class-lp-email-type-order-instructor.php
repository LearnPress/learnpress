<?php
/**
 * Class LP_Email_Type_Order_Student
 *
 * @version 4.0.0
 * @editor tungnx
 * @modify 4.1.3
 */
class LP_Email_Type_Order_Instructor extends LP_Email_Type_Order_Admin {
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

	/**
	 * Trigger email.
	 * Receive 2 params: order_id, old_status
	 *
	 * @param array $params
	 * @author tungnx
	 * @since 4.1.1
	 */
	public function handle( array $params ) {
		try {
			$order = $this->check_and_get_order( $params );
			if ( ! $order ) {
				return;
			}

			$this->set_data_content( $order );
			$instructor_ids = $this->get_instructor_ids( $order );

			foreach ( $instructor_ids as $instructor_id ) {
				$instructor = get_user_by( 'ID', $instructor_id );
				if ( $instructor ) {
					$this->set_receive( $instructor->user_email );
					$this->send_email();
				}
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}
}
