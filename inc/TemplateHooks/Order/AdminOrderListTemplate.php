<?php

namespace LearnPress\TemplateHooks\Order;

use LearnPress\Databases\PostDB;
use LearnPress\Filters\OrderPostFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LP_Helper;
use LP_Order;
use LP_Request;
use Throwable;

/**
 * class AdminOrderListTemplate
 *
 * @since 4.3.2.8
 * @version 1.0.0
 */
class AdminOrderListTemplate {

	use Singleton;

	public function init() {

		add_action( 'manage_posts_extra_tablenav', array( $this, 'add_export_order_button' ) );
		add_filter( 'views_edit-' . LP_ORDER_CPT, array( $this, 'add_refund_requests_view' ) );
	}

	/**
	 * Add refund requests view to the admin order list.
	 *
	 * @param array $views
	 *
	 * @return array
	 * @since 4.3.9
	 * @version 1.0.0
	 */
	public function add_refund_requests_view( array $views ): array {

		$is_current = 'pending' === LP_Request::get_param( 'refund_request_status', '', 'key', 'get' );
		if ( $is_current ) {
			foreach ( $views as $key => $view ) {
				$views[ $key ] = str_replace( array( ' class="current"', " class='current'" ), '', $view );
			}
		}

		$count = self::get_refund_requests_count();
		$url   = add_query_arg(
			array(
				'post_type'             => LP_ORDER_CPT,
				'refund_request_status' => 'pending',
			),
			admin_url( 'edit.php' )
		);
		$view  = sprintf(
			'<a href="%1$s" class="%2$s">%3$s <span class="count">(%4$d)</span></a>',
			esc_url( $url ),
			$is_current ? 'current' : '',
			esc_html__( 'Refund Requests', 'learnpress' ),
			$count
		);

		return self::insert_refund_requests_view( $views, $view );
	}

	/**
	 * Insert refund requests view before terminal order statuses.
	 *
	 * @param array  $views
	 * @param string $refund_requests_view
	 *
	 * @return array
	 */
	public static function insert_refund_requests_view( array $views, string $refund_requests_view ): array {

		$result   = array();
		$inserted = false;

		foreach ( $views as $key => $view ) {
			if ( ! $inserted && in_array( $key, array( LP_ORDER_CANCELLED_DB, LP_ORDER_REFUNDED_DB, 'trash' ), true ) ) {
				$result['refund-requests'] = $refund_requests_view;
				$inserted                  = true;
			}

			$result[ $key ] = $view;
		}

		if ( ! $inserted ) {
				$result['refund-requests'] = $refund_requests_view;
		}

		return $result;
	}

	/**
	 * Count pending refund requests.
	 *
	 * @return int
	 */
	public static function get_refund_requests_count(): int {

		try {
			$filter              = new OrderPostFilter();
			$filter->query_count = true;
			LP_Order::handle_params_query_list_orders(
				$filter,
				array(
					'post_status'           => 'all',
					'refund_request_status' => 'pending',
					'posts_per_page'        => -1,
				)
			);

				return (int) PostDB::getInstance()->get_posts( $filter );
		} catch ( Throwable $e ) {
			return 0;
		}
	}

	public function add_export_order_button( $which ) {

		if ( $which !== 'top' ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || $screen->base !== 'edit' || $screen->post_type !== LP_ORDER_CPT ) {
			return;
		}

		$order_data = array(
			'action'    => 'export_order_csv',
			'export_id' => time() . '-' . uniqid(),
		);

		$data_get   = LP_Helper::sanitize_params_submitted( $_GET );
		$order_data = array_merge( $data_get, $order_data );

		$section = array(
			'wrap-start'       => '<div class="alignleft actions">',
			'btn-export-start' => sprintf(
				'<button type="button" class="button lp-button lp-btn-export-order-to-csv" data-send="%s">',
				esc_attr( Template::convert_data_to_json( $order_data ) )
			),
			'btn-text'         => esc_html__( 'Export to CSV', 'learnpress' ),
			'btn-export-end'   => '</button>',
			'wrap-end'         => '</div>',
		);

		echo Template::combine_components( $section );
	}
}
