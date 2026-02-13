<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\OrderPostFilter;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LP_Helper;
use LP_Order;
use LP_Request;
use LP_REST_Response;
use Throwable;

/**
 * Class ExportOrderCSVAjax
 *
 * @author ThimPress
 * @package LearnPress\Ajax
 * @since  4.3.2.8
 * @version 1.0.0
 */
class ExportOrderCSVAjax extends AbstractAjax {
	/**
	 * Export orders to CSV file
	 *
	 * @return void
	 */
	public function export_order_csv() {
		$response = new LP_REST_Response();
		try {
			// Check permission
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}

			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );

			$export_id = sanitize_key( $params['export_id'] ?? '' );
			if ( ! $export_id ) {
				throw new Exception( __( 'Missing export id', 'learnpress' ) );
			}

			$paged = max( 1, intval( $params['paged'] ?? 1 ) );

			$post_db           = PostDB::getInstance();
			$filter            = new OrderPostFilter();
			$filter->post_type = LP_ORDER_CPT;
			LP_Order::handle_params_query_list_orders( $filter, $params );
			$total_rows = 0;
			$lp_orders  = $post_db->get_posts( $filter, $total_rows );

			if ( count( $lp_orders ) > 0 ) {
				$file   = $this->get_export_csv_path( $export_id );
				$handle = fopen( $file, $paged === 1 ? 'w' : 'a' );

				if ( $paged === 1 ) {
					fprintf( $handle, "\xEF\xBB\xBF" );
					fputcsv(
						$handle,
						[
							'Order',
							'Student',
							'Purchased',
							'Date',
							'Total',
							'Status',
						]
					);
				}

				foreach ( $lp_orders as $order ) {
					$order_id = $order->ID;
					$order    = learn_press_get_order( $order_id );
					if ( ! $order ) {
						continue;
					}
					$currency_symbol = learn_press_get_currency_symbol( $order->get_currency() ?? learn_press_get_currency() );
					fputcsv(
						$handle,
						[
							$order->get_order_number(),
							$this->get_order_users( $order ),
							$this->get_order_items( $order ),
							get_post_field( 'post_date', $order_id ),
							html_entity_decode( learn_press_format_price( $order->get_data( 'order_total' ), $currency_symbol ), ENT_QUOTES, 'UTF-8' ),
							LP_Order::get_status_label( $order->get_status() ),
						]
					);
				}

				fclose( $handle );
			} else {
				if ( intval( $paged ) === 1 ) {
					throw new Exception( __( 'There are no orders.', 'learnpress' ) );
				}
			}

			$data             = [];
			$max_page         = $post_db::get_total_pages( $filter->limit, $total_rows );
			$data['max_page'] = $max_page;

			if ( $paged < $max_page ) {
				$data['next_page'] = $paged + 1;
			} else {
				$data['download_url'] = add_query_arg(
					[
						'lp_download_order' => 1,
						'export_id'         => $export_id,
					],
					home_url( '/' )
				);
				$data['done']         = true;
				$response->message    = esc_html__( 'Orders export successfully!', 'learnpress' );
			}

			$response->data   = $data;
			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * @param $export_id
	 *
	 * @return string
	 */
	public function get_export_csv_path( $export_id ) {
		$upload = wp_upload_dir();
		$dir    = $upload['basedir'] . '/lp-order-export';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir . "/orders-{$export_id}.csv";
	}

	/**
	 * @param $order
	 *
	 * @return string|null
	 */
	public function get_order_users( $order ) {
		$user_ids = $order->get_users();

		if ( $user_ids ) {
			$outputs = array();
			foreach ( $user_ids as $user_id ) {
				if ( get_user_by( 'id', $user_id ) ) {
					$user      = learn_press_get_user( $user_id );
					$outputs[] = sprintf(
						'%s (#%d)',
						$user->get_data( 'user_login' ),
						$user_id,
					);
				} else {
					if ( sizeof( $user_ids ) == 1 ) {
						$outputs[] = $order->get_customer_name();
					}
				}
			}

			return join( ', ', $outputs );
		} else {
			return __( '(Guest)', 'learnpress' );
		}
	}

	/**
	 * @param LP_Order $order
	 *
	 * @return string
	 */
	private function get_order_items( $order ): string {
		$order_item_str = '';
		$items          = $order->get_all_items();
		$total_items    = count( $items );

		if ( $total_items === 0 ) {
			return __( '(No item)', 'learnpress' );
		} else {
			foreach ( $items as $i => $itemObj ) {
				$item_type      = $itemObj['item_type'] ?? '';
				$item_id        = $itemObj['item_id'] ?? 0;
				$item_name      = $itemObj['order_item_name'] ?? '';
				$course_id_meta = learn_press_get_order_item_meta( $itemObj['order_item_id'], '_course_id' );
				// For old data, the data not migrated yet to new column.
				if ( ( empty( $item_type ) || empty( $item_id ) )
					&& ! empty( $course_id_meta ) ) {
					$item_id   = $course_id_meta;
					$item_type = LP_COURSE_CPT;
				}

				if ( $item_type === LP_COURSE_CPT ) {
					$coursePostModel = CoursePostModel::find_by_id( $item_id, true );
					$order_item_str .= sprintf(
						'%s (#%d)',
						esc_html( $item_name ),
						$coursePostModel ? $coursePostModel->get_id() : __( 'The course does not exist now.', 'learnpress' )
					);

				} else {
					$order_item_str .= sprintf(
						'%s (#%d)',
						esc_html( $item_name ),
						$item_id
					);

				}

				if ( $i < $total_items && $total_items > 1 ) {
					$order_item_str .= '|';
				}
			}
		}

		return $order_item_str;
	}
}
