<?php

/**
 * Class LP_Email_Type_Order
 */
class LP_Email_Type_Order extends LP_Email {
	/**
	 * @var int
	 */
	public $order_id = 0;

	/**
	 * @var int
	 */
	public $instructor_id = 0;

	/**
	 * LP_Email_Type_Order constructor.
	 */
	public function __construct() {
		parent::__construct();

		$this->support_variables = array_merge(
			$this->general_variables,
			array(
				'{{order_id}}',
				'{{order_user_id}}',
				'{{order_user_name}}',
				'{{order_items_table}}',
				'{{order_detail_url}}',
				'{{order_number}}',
				'{{order_key}}'
			)
		);
	}

	/**
	 * Get courses instructor.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_course_instructors() {
		$order_id = $this->order_id;
		$order    = learn_press_get_order( $order_id );

		$items       = $order->get_items();
		$instructors = array();

		if ( sizeof( $items ) ) {
			foreach ( $items as $item ) {
				$user_id = get_post_field( 'post_author', $item['course_id'] );
				if ( $user_id ) {
					if ( empty( $instructors[ $user_id ] ) ) {
						$instructors[ $user_id ] = array();
					}
					$instructors[ $user_id ][] = $item['course_id'];
				}
			}
		}

		return $instructors;
	}

	/**
	 * Get template data object.
	 *
	 * @param int $order_id
	 * @param     array
	 *
	 * @return array
	 */
	public function get_object( $order_id = 0, $more = array() ) {
		if ( ! $order_id ) {
			$order_id = $this->order_id;
		}
		$order        = learn_press_get_order( $order_id );
		$content_type = $this->email_format == 'plain' ? 'plain' : '';

		$this->object = $this->get_common_template_data(
			$this->email_format,
			array(
				'order_id'          => $order_id,
				'order_user_id'     => $order->get_user_id(),
				'order_user_name'   => $order->get_user_name(),
				'order_items_table' => learn_press_get_template_content( "emails/{$content_type}/order-items-table.php", array( 'order_id' => $order_id ) ),
				'order_detail_url'  => $order->get_view_order_url(),
				'order_number'      => $order->get_order_number(),
				'order_subtotal'    => $order->get_formatted_order_subtotal(),
				'order_total'       => $order->get_formatted_order_total(),
				'order_date'        => date_i18n( get_option( 'date_format' ), strtotime( $order->get_order_date() ) ),
				'order_key'         => $order->get_order_key()
			)
		);

		$this->get_variable();

		return $this->object;
	}

	/**
	 * @return bool|LP_Order
	 */
	public function get_order() {
		return learn_press_get_order( $this->order_id );
	}

	/**
	 * @return array
	 */
	public function get_order_items_table() {

		$order = learn_press_get_order( $this->order_id );
		$items = $order->get_items();
		if ( ! $this->instructor_id ) {
			return $items;
		}
		$order_items = array();
		if ( $items = $order->get_items() ) {
			foreach ( $items as $item_id => $item ) {
				if ( get_post_field( 'post_author', $item['course_id'] ) != $this->instructor_id ) {
					continue;
				}
				$order_items[ $item_id ] = $item;
			}
		}

		return $order_items;
	}

	/**
	 * @return int|string
	 */
	public function get_order_total() {
		$order = learn_press_get_order( $this->order_id );

		if ( ! $this->instructor_id ) {
			$total = $order->get_total();
		} else {

			$total = 0;

			if ( $items = $this->get_order_items_table() ) {
				foreach ( $items as $item ) {
					$total += $item['total'];
				}
			}
		}

		return $this->get_email_format() == 'html' ? learn_press_format_price( $total, learn_press_get_currency_symbol( $order->get_currency() ) ) : $total . " " . $order->get_currency();
	}

	/**
	 * @param $order_id
	 *
	 * @return void
	 */
	public function trigger( $order_id ) {
		if ( ! $this->enable ) {
			return;
		}
		$this->order_id = $order_id;

		LP_Emails::instance()->set_current( $this->id );
	}
}