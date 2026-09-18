<?php

namespace LearnPress\Models\WPTables;

use LearnPress\Helpers\LPDateTime;
use LearnPress\Helpers\Response;
use LearnPress\Helpers\Template;
use LearnPress\Models\OrderPostModel;
use LP_Order;
use Throwable;
use WP_Post;
use WP_Posts_List_Table;

defined( 'ABSPATH' ) || exit;

/**
 * LearnPress Orders Table class.
 *
 * @since 4.4.8
 * @version 1.0.0
 */
class OrdersTable extends WP_Posts_List_Table {
	/**
	 * Get the table columns.
	 *
	 * @return array
	 */
	public function get_columns() {
		$existing = parent::get_columns();

		$columns = array(
			'cb'            => '<input type="checkbox" />',
			'title'         => esc_html__( 'Order', 'learnpress' ),
			'order_student' => esc_html__( 'Student', 'learnpress' ),
			'order_items'   => esc_html__( 'Purchased', 'learnpress' ),
			'order_total'   => esc_html__( 'Total', 'learnpress' ),
			'order_date'    => esc_html__( 'Date', 'learnpress' ),
			'order_status'  => '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'learnpress' ) . '">' . esc_attr__( 'Status', 'learnpress' ) . '</span>',
		);

		$columns = array_merge( $columns, $existing );

		// Unset date column default of WP
		unset( $columns['date'] );

		return $columns;
	}

	/**
	 * Set columns can be sortable.
	 *
	 * @return array
	 */
	public function get_sortable_columns() {
		$sortable_columns                = parent::get_sortable_columns();
		$sortable_columns['order_date']  = array( 'date', 'asc' );
		$sortable_columns['order_total'] = array( 'order_total', 'asc' );

		return $sortable_columns;
	}

	/**
	 * Column order student.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_order_student( $post ) {
		try {
			$orderPostModel = OrderPostModel::find_by_id( $post->ID, true );
			if ( ! $orderPostModel ) {
				return;
			}

			$lp_order = learn_press_get_order( $post->ID );
			$user_ids = $lp_order->get_users();

			if ( $user_ids ) {
				$outputs = array();
				foreach ( $user_ids as $user_id ) {
					if ( get_user_by( 'id', $user_id ) ) {
						$user      = learn_press_get_user( $user_id );
						$outputs[] = sprintf(
							'<a href="user-edit.php?user_id=%d">%s (%s)</a><span>%s</span>',
							$user_id,
							$user->get_data( 'user_login' ),
							$user->get_data( 'display_name' ),
							$user->get_data( 'user_email' )
						);
					} elseif ( sizeof( $user_ids ) === 1 ) {
						$outputs[] = $lp_order->get_customer_name();
					}
				}
				echo join( ', ', $outputs );
			} else {
				esc_html_e( '(Guest)', 'learnpress' );
			}
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column order items.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_order_items( $post ) {
		try {
			$lp_order = learn_press_get_order( $post->ID );
			do_action( 'learn-press/admin/order-items/layout', $lp_order );
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column order date.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_order_date( $post ) {
		try {
			$orderPostModel = OrderPostModel::find_by_id( $post->ID, true );
			if ( ! $orderPostModel ) {
				return;
			}

			// Check column post_date_gmt is default value.
			if ( '0000-00-00 00:00:00' === $orderPostModel->post_date_gmt ) {
				$convert_post_date_gmt         = get_gmt_from_date( $orderPostModel->post_date );
				$orderPostModel->post_date_gmt = $convert_post_date_gmt;
			}

			$time      = strtotime( $orderPostModel->post_date_gmt );
			$time_diff = time() - $time;

			$lpDateTimeGMT           = new LPDateTime( $orderPostModel->post_date_gmt );
			$time_local_format_mysql = $lpDateTimeGMT->format( LPDateTime::FORMAT_MYSQL, 'gmt_to_local' );

			if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
				$time_display = $lpDateTimeGMT->format(
					LPDateTime::FORMAT_HUMAN,
					'gmt_to_local',
					true
				);
			} else {
				$time_display = $lpDateTimeGMT->format(
					LPDateTime::FORMAT_I18N_DATE_TIME,
					'gmt_to_local',
					true
				);
			}

			printf(
				'<abbr title="%s">%s</abbr>',
				esc_attr( $time_local_format_mysql ),
				esc_html( $time_display )
			);
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column order total.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_order_total( $post ) {
		try {
			$orderPostModel = OrderPostModel::find_by_id( $post->ID, true );
			if ( ! $orderPostModel ) {
				return;
			}

			$lp_order = learn_press_get_order( $post->ID );
			echo wp_kses_post( $lp_order->get_formatted_order_total() );

			$method_title = $lp_order->get_payment_method_title();
			$method_title = apply_filters( 'learn-press/order-payment-method-title', $method_title, $lp_order );

			if ( ! empty( $method_title ) ) {
				$method_title_html = sprintf(
					/* translators: %s: payment method title */
					__( 'Pay via <strong>%s</strong>', 'learnpress' ),
					$method_title
				);
				?>
				<div class="payment-method-title">
					<?php echo wp_kses_post( $method_title_html ); ?>
				</div>
				<?php
			}
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}

	/**
	 * Column order status.
	 *
	 * @param WP_Post $post
	 *
	 * @return void
	 */
	public function column_order_status( $post ) {
		try {
			$orderPostModel = OrderPostModel::find_by_id( $post->ID, true );
			if ( ! $orderPostModel ) {
				return;
			}

			$lp_order       = learn_press_get_order( $post->ID );
			$lp_order_icons = LP_Order::get_icons_status();
			$icon           = $lp_order_icons[ $lp_order->get_status() ] ?? '';
			$badge_html     = '';

			$refund_request_status = $lp_order->get_refund_request();
			if ( 'pending' === $refund_request_status ) {
				$badge_html = sprintf(
					'<span class="lp-order-refund-request-badge">%s</span>',
					esc_html__( 'Refund Requested', 'learnpress' )
				);
			}

			printf(
				'<span class="lp-order-status %1$s">%2$s%3$s</span>%4$s',
				esc_attr( $lp_order->get_status() ),
				$icon,
				esc_html( LP_Order::get_status_label( $lp_order->get_status() ) ),
				$badge_html
			);
		} catch ( Throwable $e ) {
			Template::print_message( $e->getMessage(), Response::STATUS_ERROR );
		}
	}
}
