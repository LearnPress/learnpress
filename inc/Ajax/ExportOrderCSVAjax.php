<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LP_Helper;
use LP_Order;
use LP_Request;
use LP_REST_Response;
use Throwable;
use WP_Query;

class ExportOrderCSVAjax extends AbstractAjax {

	/**
	 * @return void
	 */
	public function export_order_csv() {
		$response = new LP_REST_Response();
		try {
			if ( ! current_user_can( UserModel::ROLE_ADMINISTRATOR )
				&& ! current_user_can( UserModel::ROLE_INSTRUCTOR ) ) {
				throw new Exception( __( 'You do not have permission to perform this action.', 'learnpress' ) );
			}
			$data_str = LP_Request::get_param( 'data' );
			$params   = LP_Helper::json_decode( $data_str, true );
			if (
				empty( $params['_wpnonce'] ) ||
				! wp_verify_nonce( $params['_wpnonce'], 'lp_export_order' )
			) {
				throw new Exception( __( 'Invalid request (nonce).', 'learnpress' ) );
			}

			$export_id = sanitize_key( $params['export_id'] ?? '' );
			if ( ! $export_id ) {
				throw new Exception( __( 'Missing export id', 'learnpress' ) );
			}

			$paged          = max( 1, absint( $params['paged'] ?? 1 ) );
			$posts_per_page = $params['posts_per_page'] ?? 20;

			$args = [
				'post_type'       => LP_ORDER_CPT,
				'post_status'     => $params['post_status'] ?? 'any',
				'posts_per_page'  => $posts_per_page,
				'paged'           => $paged,
				'orderby'         => $params['orderby'] ?? 'ID',
				'order'           => $params['order'] ?? 'desc',
				'lp_search'       => $params['s'] ?? '',
				'lp_m'            => $params['m'] ?? 0,
				'lp_export_order' => true,
			];

			if ( empty( $args['meta_query'] ) ) {
				$args['meta_query'] = [];
			}

			if ( isset( $params['orderby'] ) ) {
				if ( $params['orderby'] === 'order_total' ) {
					$args['meta_key']     = '_order_total';
					$args['orderby']      = 'meta_value_num';
					$args['meta_query'][] = [
						'key'     => '_order_total',
						'value'   => 0,
						'compare' => '>',
						'type'    => 'NUMERIC',
					];
				} else {
					$args['orderby'] = $params['orderby'];
				}
			} else {
				$args['orderby'] = 'ID';
			}

			if ( ! empty( $params['author'] ) ) {
				$args['meta_query'][] = [
					'key'     => '_user_id',
					'value'   => '"' . (string) $params['author'] . '"',
					'compare' => 'LIKE',
				];
			}

			$query = new WP_Query( $args );

			if ( $query->have_posts() ) {
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

				while ( $query->have_posts() ) {
					$query->the_post();

					$order_id = get_the_ID();
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
				wp_reset_postdata();
			} else {
				if ( intval( $paged ) === 1 ) {
					throw new Exception( __( 'There are no orders.', 'learnpress' ) );
				}
			}

			$data             = [];
			$data['max_page'] = $query->max_num_pages;

			if ( $paged < $query->max_num_pages ) {
				$data['next_page'] = $paged + 1;
				$response->message = esc_html__( 'Orders exported!', 'learnpress' );
			} else {
				$data['download_url'] = add_query_arg(
					[
						'lp_download_order' => 1,
						'export_id'         => $export_id,
					],
					home_url( '/' )
				);
				$data['done']         = true;
				$response->message    = esc_html__( 'All orders exported!', 'learnpress' );
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
						'%s (userID#%s)',
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
	 * @param $order
	 * @return string
	 */
	private function get_order_items( $order ): string {
		$order_item_str = '';
		$items          = $order->get_all_items();

		if ( count( $items ) === 0 ) {
			return __( '(No item)', 'learnpress' );
		} else {
			foreach ( $items as $i => $itemObj ) {
				$item_type = $itemObj['item_type'] ?? '';

				if ( $item_type === LP_COURSE_CPT ) {
					$coursePostModel = CoursePostModel::find_by_id( $itemObj['item_id'], true );
					$order_item_str .= sprintf(
						'%s (courseID#%s)',
						esc_html( $itemObj['order_item_name'] ),
						$coursePostModel ? $coursePostModel->get_id() : __( 'The course does not exist now.', 'learnpress' )
					);

					if ( $i < count( $items ) ) {
						$order_item_str .= '|';
					}
				} else {
					if ( has_filter( 'learn-press/order-item-not-course-id' ) ) {
						return __( 'The course does not exist', 'learnpress' );
					}
				}
			}
		}

		return $order_item_str;
	}
}
