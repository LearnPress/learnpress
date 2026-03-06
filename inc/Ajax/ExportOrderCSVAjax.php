<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\OrderPostFilter;
use LearnPress\Models\UserModel;
use LP_Datetime;
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
	 * Export orders to CSV file, action call from js file: export-order.js
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
				$file   = self::get_export_csv_path( $export_id );
				$handle = fopen( $file, $paged === 1 ? 'w' : 'a' );

				if ( $paged === 1 ) {
					fprintf( $handle, "\xEF\xBB\xBF" );
					fputcsv(
						$handle,
						[
							__( 'Order', 'learnpress' ),
							__( 'Student', 'learnpress' ),
							__( 'Purchased', 'learnpress' ),
							__( 'Date', 'learnpress' ),
							__( 'Total', 'learnpress' ),
							__( 'Status', 'learnpress' ),
						],
						',',
						'"',
						'\\',
					);
				}

				foreach ( $lp_orders as $order ) {
					$order_id = $order->ID;
					$order    = learn_press_get_order( $order_id );
					if ( ! $order ) {
						continue;
					}
					fputcsv(
						$handle,
						[
							$order->get_order_number(),
							$this->get_order_users( $order ),
							$this->get_order_items( $order ),
							$order->get_order_date( 'edit' )->format( LP_Datetime::I18N_FORMAT_HAS_TIME ),
							$order->get_formatted_order_total(),
							LP_Order::get_status_label( $order->get_status() ),
						],
						',',
						'"',
						'\\',
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
	 * Get export CSV file path
	 *
	 * @param string $export_id
	 *
	 * @return string
	 */
	public static function get_export_csv_path( string $export_id = '' ): string {
		$upload = wp_upload_dir();
		$dir    = $upload['basedir'] . '/lp-order-export';

		if ( ! file_exists( $dir ) ) {
			wp_mkdir_p( $dir );
		}

		return $dir . "/lp-orders-{$export_id}.csv";
	}

	/**
	 * @param LP_Order $order
	 *
	 * @return string|null
	 */
	public function get_order_users( $order ) {
		$user_ids       = $order->get_users();
		$checkout_email = $order->get_checkout_email();

		if ( $order->is_manual() && empty( $user_ids ) ) {
			return __( '(No customer)', 'learnpress' );
		} else {
			$outputs = array();
			foreach ( $user_ids as $user_id ) {
				$userModel = UserModel::find( $user_id, true );
				if ( $userModel ) {
					$outputs[] = sprintf(
						'%s (#%d)',
						$userModel->get_display_name(),
						$user_id,
					);
				} elseif ( $user_id > 0 ) {
					$outputs[] = sprintf(
						__( 'User #%d (Deleted)', 'learnpress' ),
						$user_id,
					);
				} elseif ( ! empty( $checkout_email ) ) {
					$outputs[] = sprintf(
						'%s',
						$order->get_checkout_email(),
					);
				} else {
					$outputs[] = __( '(Guest)', 'learnpress' );
				}
			}

			return join( ', ', $outputs );
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

				$order_item_str .= sprintf(
					'%s (#%d)',
					esc_html( $item_name ),
					$item_id
				);

				if ( $i < $total_items && $total_items > 1 ) {
					$order_item_str .= '|';
				}
			}
		}

		return $order_item_str;
	}
}
