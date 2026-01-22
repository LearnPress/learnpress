<?php

namespace LearnPress\Ajax;

use Exception;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\PostFilter;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LP_Helper;
use LP_Order;
use LP_Request;
use LP_REST_Response;
use Throwable;

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

			$post_filter = new PostFilter();
			$post_db     = PostDB::getInstance();

			$post_filter->post_type = LP_ORDER_CPT;
			$user_of_order          = $params['author'];
			if ( ! empty( $user_of_order ) ) {
				$user_id              = absint( $user_of_order );
				$post_filter->join[]  = "INNER JOIN {$post_db->tb_postmeta} pm1 ON p.ID = pm1.post_id AND pm1.meta_key = '_user_id'";
				$post_filter->where[] = "AND ( pm1.meta_value like '%\"$user_id\"%' OR pm1.meta_value = $user_id )";
			}

			if ( empty( $params['post_status'] ) || $params['post_status'] === 'all' ) {
				$post_filter->where[] = $post_db->wpdb->prepare( 'AND p.post_status != %s', LP_ORDER_TRASH );
			} else {
				$post_filter->post_status = (array) $params['post_status'];
			}

			if ( isset( $params['orderby'] ) ) {
				if ( $params['orderby'] === 'order_total' ) {
					$post_filter->join[]   = "INNER JOIN {$post_db->tb_postmeta} pm2 ON p.ID = pm2.post_id AND pm2.meta_key = '_order_total'";
					$post_filter->where[]  = 'AND CAST(pm2.meta_value AS UNSIGNED)';
					$post_filter->order_by = 'pm2.meta_value';
				} elseif ( $params['orderby'] === 'date' ) {
					$post_filter->order_by = 'post_date';
				} else {
					$post_filter->order_by = $params['orderby'];
				}
			} else {
				$post_filter->order_by = 'ID';
			}

			$post_filter->order = $params['order'] ?? 'desc';

			$key = $params['s'] ?? '';
			if ( ! empty( $key ) ) {
				$pattern          = '/^#\d+$/';
				$is_order_id_sure = false;
				if ( preg_match( $pattern, $key ) ) {
					$is_order_id_sure = true;
					$key              = str_replace( '#', '', $key );
				}

				$pattern2 = '#^0+.*\d+$#';
				if ( preg_match( $pattern2, $key ) ) {
					$key = (int) $key;
				}

				$key = trim( $key );

				if ( $is_order_id_sure ) {
					$post_filter->where[] = $post_db->wpdb->prepare( 'AND p.ID = %d', $key );
				} else {
					$post_filter->join[]  = "INNER JOIN {$post_db->tb_lp_order_items} lpori ON p.ID = lpori.order_id";
					$post_filter->where[] = $post_db->wpdb->prepare(
						'AND (p.ID = %d OR lpori.order_item_name like %s)',
						$key,
						'%' . $key . '%'
					);
				}
			}

			$month = $params['m'] ?? 0;
			if ( ! empty( $month ) ) {
				$year                 = substr( $month, 0, 4 );
				$post_filter->where[] = "AND YEAR(p.post_date) = $year";
				if ( strlen( $month ) > 5 ) {
					$mon                  = substr( $month, 4, 2 );
					$post_filter->where[] = "AND MONTH(p.post_date) = $mon";
				}
			}

			$post_filter->limit = $posts_per_page;
			$post_filter->page  = $paged;

			$total_rows = 0;
			$lp_orders  = $post_db->get_posts( $post_filter, $total_rows );

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
			$max_page         = ceil( $total_rows / $posts_per_page );
			$data['max_page'] = $max_page;

			if ( $paged < $max_page ) {
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
