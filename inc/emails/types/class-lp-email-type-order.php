<?php
/**
 * Class LP_Email_Type_Order
 *
 * @version 4.0.0
 * @editor tungnx
 * @modify 4.1.3
 */
class LP_Email_Type_Order extends LP_Email {
	/**
	 * LP_Email_Type_Order constructor.
	 */
	public function __construct() {
		parent::__construct();

		$variable_on_email_support = apply_filters(
			'lp/email/type-order/variables-support',
			[
				'{{order_id}}',
				'{{order_user_id}}',
				'{{order_user_name}}',
				'{{order_items_table}}',
				'{{order_detail_url}}',
				'{{order_number}}',
				'{{order_key}}',
				'{{order_date}}',
			]
		);

		$this->support_variables = array_merge( $this->support_variables, $variable_on_email_support );
	}

	/**
	 * Check email enable option
	 * Check param valid
	 * Return Order
	 *
	 * @param array $params
	 * @return LP_Order|bool
	 * @throws Exception
	 */
	protected function check_and_get_order( array $params ) {
		if ( ! $this->enable ) {
			return false;
		}

		if ( count( $params ) < 1 ) {
			return false;
		}

		$order_id = $params[0] ?? 0;
		return new LP_Order( $order_id );
	}

	/**
	 * Set variables for content email.
	 *
	 * @param LP_Order $order
	 * @editor tungnx
	 * @since 4.1.1
	 */
	public function set_data_content( LP_Order $order ) {
		$user_ids    = $order->get_user_id();
		$user_id_str = $user_ids;

		if ( is_array( $user_ids ) ) {
			$user_id_str = implode( ',', $user_ids );
		}

		$this->variables = apply_filters(
			'lp/email/type-order/variables-mapper',
			[
				'{{order_id}}'          => $order->get_id(),
				'{{order_date}}'        => date_i18n( get_option( 'date_format' ), strtotime( $order->get_order_date() ) ),
				'{{order_user_id}}'     => $user_id_str,
				'{{order_user_name}}'   => $order->get_user_name(),
				'{{order_items_table}}' => learn_press_get_template_content(
					'emails/' . ( $this->email_format == 'plain' ? 'plain/' : '' ) . 'order-items-table.php',
					[
						'order' => $order,
					]
				),
				'{{order_detail_url}}'  => $order->get_view_order_url(),
				'{{order_number}}'      => $order->get_order_number(),
				'{{order_key}}'         => $order->get_order_key(),
			]
		);

		$variables_common = $this->get_common_variables( $this->email_format );
		$this->variables  = array_merge( $this->variables, $variables_common );
	}
}
