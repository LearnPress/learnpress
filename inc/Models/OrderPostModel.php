<?php

namespace LearnPress\Models;

defined( 'ABSPATH' ) || exit;

use Exception;
use LearnPress\Databases\Course\CourseSectionItemsDB;
use LearnPress\Databases\CourseSectionDB;
use LearnPress\Filters\Course\CourseSectionItemsFilter;
use LP_Course_Filter;

use LP_Helper;

/**
 * class order post model
 * to replace class lp_order old
 *
 * @package learnpress/classes
 * @version 1.0.0
 * @since 4.4.2
 */
class OrderPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_ORDER_CPT;

	/**
	 * Const status
	 */
	const STATUS_COMPLETED  = 'lp-completed';
	const STATUS_PENDING    = 'lp-pending';
	const STATUS_PROCESSING = 'lp-processing';
	const STATUS_CANCELLED  = 'lp-cancelled';
	const STATUS_FAILED     = 'lp-failed';
	const STATUS_REFUNDED   = 'lp-refunded';
	const STATUS_TRASH      = 'trash';

	/**
	 * Const meta key
	 */
	const META_KEY_USER_ID               = '_user_id';
	const META_KEY_ORDER_CURRENCY        = '_order_currency';
	const META_KEY_ORDER_SUBTOTAL        = '_order_subtotal';
	const META_KEY_ORDER_TOTAL           = '_order_total';
	const META_KEY_PAYMENT_METHOD        = '_payment_method';
	const META_KEY_PAYMENT_METHOD_TITLE  = '_payment_method_title';
	const META_KEY_ORDER_VERSION         = '_order_version';
	const META_KEY_EDIT_LAST             = '_edit_last';
	const META_KEY_EDIT_LOCK             = '_edit_lock';
	const META_KEY_PRICES_INCLUDE_TAX    = '_prices_include_tax';
	const META_KEY_ORDER_KEY             = '_order_key';
	const META_KEY_USER_IP               = '_user_ip';
	const META_KEY_USER_IP_ADDRESS       = '_user_ip_address';
	const META_KEY_USER_AGENT            = '_user_agent';
	const META_KEY_CHECKOUT_EMAIL        = '_checkout_email';
	const META_KEY_CREATED_VIA           = '_created_via';
	const META_KEY_TRANSACTION_ID        = '_transaction_id';
	const META_KEY_REFUND_REQUEST        = '_lp_refund_request';
	const META_KEY_REFUND_REQUEST_REASON = '_lp_refund_request_reason';
	const META_KEY_REFUNDED_BY           = '_lp_refunded_by';
	const META_KEY_REFUNDED_AT           = '_lp_refunded_at';
	const META_KEY_REFUNDED_AMOUNT       = '_lp_refunded_amount';

	/**
	 * Get list status of LP Order
	 *
	 * @return array
	 */
	public static function get_order_statuses(): array {
		$order_statuses = array(
			self::STATUS_COMPLETED  => self::get_status_label( self::STATUS_COMPLETED ),
			self::STATUS_PENDING    => self::get_status_label( self::STATUS_PENDING ),
			self::STATUS_PROCESSING => self::get_status_label( self::STATUS_PROCESSING ),
			self::STATUS_CANCELLED  => self::get_status_label( self::STATUS_CANCELLED ),
			self::STATUS_FAILED     => self::get_status_label( self::STATUS_FAILED ),
			self::STATUS_REFUNDED   => self::get_status_label( self::STATUS_REFUNDED ),
		);

		return apply_filters( 'learn-press/order/statuses', $order_statuses );
	}


	/**
	 * Get label of lp order status
	 *
	 * @param string $status
	 *
	 * @return string
	 * @since 4.2.0
	 * @version 1.0.0
	 */
	public static function get_status_label( string $status ): string {
		switch ( $status ) {
			case self::STATUS_COMPLETED:
				$status = __( 'Completed', 'learnpress' );
				break;
			case self::STATUS_PENDING:
				$status = __( 'Pending', 'learnpress' );
				break;
			case self::STATUS_PROCESSING:
				$status = __( 'Processing', 'learnpress' );
				break;
			case self::STATUS_CANCELLED:
				$status = __( 'Cancelled', 'learnpress' );
				break;
			case self::STATUS_FAILED:
				$status = __( 'Failed', 'learnpress' );
				break;
			case self::STATUS_TRASH:
				$status = __( 'Trash', 'learnpress' );
				break;
			case 'on-hold':
				$status = __( 'On hold', 'learnpress' );
				break;
			case self::STATUS_REFUNDED:
				$status = __( 'Refunded', 'learnpress' );
				break;
			default:
				$status = '';
				break;
		}

		if ( ! is_string( $status ) ) {
			$status = '';
		}

		return $status;
	}
}
