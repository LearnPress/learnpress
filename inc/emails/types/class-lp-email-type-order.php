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
			)
		);
	}

	/**
	 * @param bool $include_admin
	 *
	 * @return array
	 */
	public function get_course_instructors( $include_admin = false ) {
		$order_id           = $this->order_id;
		$order              = learn_press_get_order( $order_id );
		$items              = $order->get_items();
		$course_instructors = array();

		if ( sizeof( $items ) ) {
			foreach ( $items as $item ) {
				$user_id = get_post_field( 'post_author', $item['course_id'] );
				if ( ! $include_admin ) {
					$user = get_user_by( 'ID', $user_id );
					if ( ! $user ) {
						continue;
					}
					if ( in_array( 'administrator', $user->roles ) ) {
						continue;
					}
				}

				if ( empty( $course_instructors[ $user_id ] ) ) {
					$course_instructors[ $user_id ] = array();
				}
				$course_instructors[ $user_id ][] = $item['course_id'];
			}
		}

		return $course_instructors;
	}

	/**
	 * Get template data object
	 */
	public function get_object( $order_id = 0 ) {
		if ( ! $order_id ) {
			$order_id = $this->order_id;
		}
		$order        = learn_press_get_order( $order_id );
		$this->object = $this->get_common_template_data(
			$this->email_format,
			array(
				'order_id'         => $order_id,
				'order_user_id'    => $order->get_user_id(),
				'order_user_name'  => $order->get_user_name(),
				'order_items_table' => $this->get_order_items_table(),
				'order_detail_url' => $order->get_view_order_url(),
				'order_number'     => $order->get_order_number(),
				'order_subtotal'   => $order->get_formatted_order_subtotal(),
				'order_total'      => $order->get_formatted_order_total(),
				'order_date'       => date_i18n( get_option( 'date_format' ), strtotime( $order->get_order_date() ) )
			)
		);

		return $this->object;
	}

	public function get_variable() {
		$this->variables = $this->data_to_variables( $this->object );
		return $this->variables;
	}

	/**
	 * @param int $instructor_id
	 *
	 * @return string
	 */
	public function get_order_items_table( $instructor_id = 0 ) {
		$content_type = $this->email_format == 'plain' ? 'plain/' : '';

		return learn_press_get_template_content(
			"emails/{$content_type}order-items-table.php",
			array(
				'order_id'      => $this->order_id,
				'instructor_id' => $instructor_id
			)
		);
	}
}