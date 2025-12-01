<?php

namespace LearnPress\WPGDPR;

use Exception;
use LearnPress\Databases\DataBase;
use LearnPress\Databases\PostDB;
use LearnPress\Filters\FilterBase;
use LearnPress\Filters\OrderPostFilter;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserItems\UserItemModel;
use LP_Debug;
use LP_Helper;
use LP_Post_DB;
use LP_Order_Filter;
use LP_User_Items_Filter;
use LP_User_Items_DB;
use LP_Filter;
use LP_User_Items_Result_DB;
use Throwable;
use WP_User;

/**
 * class ErasePersonalData
 *
 * @since 4.2.9.3
 * @version 1.0.0
 */
class ErasePersonalData {
	use Singleton;

	public function init() {
		add_filter(
			'wp_privacy_personal_data_erasers',
			function ( $erasers ) {
				$erasers['lp_user_eraser'] = array(
					'eraser_friendly_name' => __( 'LP Data Eraser', 'learnpress' ),
					'callback'             => array( $this, 'eraser_callback' ),
				);

				return $erasers;
			}
		);
	}

	/**
	 * Erase personal data callback
	 *
	 * @param string $email
	 * @param int $page
	 *
	 * @return array
	 */
	public function eraser_callback( $email, $page ) {
		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => false,
		);

		try {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				$this->eraser_user_data( $user );
			} else {
				$this->eraser_data_via_email( $email );
			}

			$response['items_removed'] = true;
			$response['messages']      = array(
				__( 'LP erasers Personal Data done', 'learnpress' ),
				__( '- Eraser Orders of User', 'learnpress' ),
				__( '- Eraser attend course of User', 'learnpress' ),
				__( '- Eraser attend lessons, quizzes... of User', 'learnpress' ),
			);
			$response['done']          = true;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
			$response['messages'][] = $e->getMessage();
			$response['done']       = true;
		}

		return $response;
	}

	/**
	 * Delete related data via email
	 *
	 * If not exists user, only email, it is Guest
	 * So only delete data on tables learnpress_user_items,
	 * posts type lp_order, learnpress_order_items, learnpress_order_itemmeta
	 *
	 * @param string $email
	 *
	 * @throws Exception
	 */
	public function eraser_data_via_email( string $email ) {
		$postDB                = PostDB::getInstance();
		$filter                = new OrderPostFilter();
		$filter->only_fields[] = 'p.ID';
		$filter->join[]        = "INNER JOIN $postDB->tb_postmeta AS pm ON p.ID = pm.post_id";
		$filter->where[]       = $postDB->wpdb->prepare(
			'AND pm.meta_key=%s AND pm.meta_value=%s',
			'_checkout_email',
			$email
		);
		$order_ids             = $postDB->get_posts( $filter );
		if ( ! $order_ids ) {
			return;
		}

		$order_ids     = PostDb::get_values_by_key( $order_ids );
		$order_ids     = array_map( 'absint', $order_ids );
		$order_ids_str = implode( ',', $order_ids );

		// Delete data on tables: order_items and order_itemmeta
		$this->delete_order_itemmeta( $order_ids_str );
		$this->delete_order_items( $order_ids_str );
		// Delete order post and meta.
		foreach ( $order_ids as $order_id ) {
			// Delete data on tables: learnpress_order_items
			$filter_user_items           = new LP_User_Items_Filter();
			$filter_user_items->ref_type = LP_ORDER_CPT;
			$filter_user_items->ref_id   = $order_id;
			$userItemModel               = UserItemModel::get_user_item_model_from_db( $filter_user_items );
			if ( $userItemModel ) {
				$userItemModel->delete();
			}

			wp_delete_post( $order_id, true );
		}
	}

	/**
	 * Find order item ids on table learnpress_order_items
	 * Then delete data on table learnpress_order_itemmeta
	 *
	 * @throws Exception
	 */
	public function delete_order_itemmeta( $order_ids_str ) {
		$db                                     = DataBase::getInstance();
		$filter_order_itemmeta                  = new FilterBase();
		$filter_order_itemmeta->only_fields[]   = 'order_item_id';
		$filter_order_itemmeta->run_query_count = false;
		$filter_order_itemmeta->collection      = $db->tb_lp_order_items;
		$filter_order_itemmeta->where[]         = "AND order_id IN ($order_ids_str)";
		$filter_order_itemmeta->return_string_query = 1;
		$query_order_item_ids_str                   = $db->execute( $filter_order_itemmeta );

		$filter             = new FilterBase();
		$filter->where[]    = "AND learnpress_order_item_id IN ($query_order_item_ids_str)";
		$filter->collection = $db->tb_lp_order_itemmeta;
		$db->delete_execute( $filter );
	}

	/**
	 * Delete order ids on table learnpress_order_items
	 *
	 * @throws Exception
	 */
	public function delete_order_items( $order_ids_str ) {
		$db                            = DataBase::getInstance();
		$filter_order_item             = new FilterBase();
		$filter_order_item->where[]    = "AND order_id IN ($order_ids_str)";
		$filter_order_item->collection = $db->tb_lp_order_items;
		$db->delete_execute( $filter_order_item );
	}

	/**
	 * Erase user data
	 *
	 * @param WP_User $user
	 *
	 * @throws Exception
	 */
	public function eraser_user_data( WP_User $user ) {
		$user_id = $user->ID;
		$db      = DataBase::getInstance();

		// Delete data uer meta which meta_key start with _lp
		$filter_usermeta             = new FilterBase();
		$filter_usermeta->where[]    = $db->wpdb->prepare(
			'AND user_id = %d AND meta_key LIKE %s',
			$user_id,
			$db->wpdb->esc_like( '_lp' ) . '%'
		);
		$filter_usermeta->collection = $db->wpdb->usermeta;
		$db->delete_execute( $filter_usermeta );
		// End delete user meta

		// Find all user item ids of user
		$filter_user_items          = new LP_User_Items_Filter();
		$filter_user_items->user_id = $user_id;
		$user_items_db              = LP_User_Items_DB::getInstance();
		$user_items                 = $user_items_db->get_user_items( $filter_user_items );
		if ( $user_items ) {
			foreach ( $user_items as $user_item ) {
				// Delete user item and user itemmeta
				$userItemModel = new UserItemModel( $user_item );
				$userItemModel->delete();

				// Delete user item results.
				$filter_user_item_results             = new FilterBase();
				$filter_user_item_results->where[]    = $db->wpdb->prepare(
					'AND user_item_id = %d',
					$userItemModel->get_user_item_id()
				);
				$filter_user_item_results->collection = $db->tb_lp_user_item_results;
				$db->delete_execute( $filter_user_item_results );
			}
		}

		// Find all orders of user
		$postDB                         = PostDB::getInstance();
		$orderPostFilter                = new OrderPostFilter();
		$orderPostFilter->only_fields[] = 'p.ID';
		$orderPostFilter->join[]        = "INNER JOIN $postDB->tb_postmeta AS pm ON p.ID = pm.post_id";
		$orderPostFilter->where[]       = $postDB->wpdb->prepare(
			'AND pm.meta_key=%s AND pm.meta_value=%s',
			'_user_id',
			$user_id
		);
		$order_ids                      = $postDB->get_posts( $orderPostFilter );
		if ( $order_ids ) {
			$order_ids     = PostDb::get_values_by_key( $order_ids );
			$order_ids     = array_map( 'absint', $order_ids );
			$order_ids_str = implode( ',', $order_ids );
			$this->delete_order_itemmeta( $order_ids_str );
			$this->delete_order_items( $order_ids_str );

			foreach ( $order_ids as $order_id ) {
				wp_delete_post( $order_id, true );
			}
		}
		// End delete orders of user

		// Find user orders which have multiple user ids
		$user_id_str            = $postDB->wpdb->prepare( '%"%d"%', $user_id );
		$orderPostFilter->where = array(
			$postDB->wpdb->prepare(
				'AND pm.meta_key=%s AND pm.meta_value LIKE %s',
				'_user_id',
				$user_id_str
			),
		);

		$order_ids_multiple_users = $postDB->get_posts( $orderPostFilter );
		if ( $order_ids_multiple_users ) {
			$order_ids = PostDb::get_values_by_key( $order_ids_multiple_users );
			$order_ids = array_map( 'absint', $order_ids );
			foreach ( $order_ids as $order_id ) {
				$user_ids = get_post_meta( $order_id, '_user_id', true );

				if ( count( $user_ids ) <= 1 ) {
					// If only 1 user id, delete order
					$this->delete_order_itemmeta( $order_id );
					$this->delete_order_items( $order_id );
					wp_delete_post( $order_id, true );
				} else {
					// Search and unset user id of user on array user ids
					$current_user_order_pos = array_search( $user_id, $user_ids );
					if ( $current_user_order_pos !== false ) {
						unset( $user_ids[ $current_user_order_pos ] );
						update_post_meta( $order_id, '_user_id', $user_ids );
					}
				}
			}
		}
	}
}
