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
	public function delete_order_itemmeta( $query_lp_order_ids_str ) {
		$db                                     = DataBase::getInstance();
		$filter_order_itemmeta                  = new FilterBase();
		$filter_order_itemmeta->only_fields[]   = 'order_item_id';
		$filter_order_itemmeta->run_query_count = false;
		$filter_order_itemmeta->collection      = $db->tb_lp_order_items;
		$filter_order_itemmeta->where[]         = "AND order_id IN ($query_lp_order_ids_str)";
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
	public function delete_order_items( $query_lp_order_ids_str ) {
		$db                            = DataBase::getInstance();
		$filter_order_item             = new FilterBase();
		$filter_order_item->where[]    = "AND order_id IN ($query_lp_order_ids_str)";
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
		$user_id  = $user->ID;
		$lpuidb   = LP_User_Items_DB::getInstance();
		$um_table = $lpuidb->wpdb->usermeta;

		// Delete data uer meta which meta_key start with _lp
		$delete_usermeta = $lpuidb->wpdb->query(
			$lpuidb->wpdb->prepare(
				"DELETE FROM $um_table AS um
				 WHERE um.user_id = %d AND um.meta_key LIKE %s",
				$user_id,
				$lpuidb->wpdb->esc_like( '_lp' ) . '%'
			)
		);

		// get all user item ids
		$ui_filter                      = new LP_User_Items_Filter();
		$ui_filter->user_id             = $user_id;
		$ui_filter->only_fields[]       = 'ui.user_item_id as ui_id';
		$ui_filter->return_string_query = 1;
		$lp_ui_ids_query                = $lpuidb->get_user_items( $ui_filter );
		$lp_ui_ids                      = $lpuidb->wpdb->get_col( $lp_ui_ids_query );
		if ( ! empty( $lp_ui_ids ) ) {
			$lp_ui_ids_format = LP_Helper::db_format_array( $lp_ui_ids, '%d' );
			// delete user itemmeta
			$uim_delete = $lpuidb->wpdb->query(
				$lpuidb->wpdb->prepare(
					"DELETE FROM $lpuidb->tb_lp_user_itemmeta AS uim
					 WHERE uim.learnpress_user_item_id IN ($lp_ui_ids_format)",
					$lp_ui_ids
				)
			);
			// delete user item result
			$uir_delete = $lpuidb->wpdb->query(
				$lpuidb->wpdb->prepare(
					"DELETE FROM $lpuidb->tb_lp_user_item_results AS uir
					 WHERE uir.user_item_id IN ($lp_ui_ids_format)",
					$lp_ui_ids
				)
			);
			// delete all user itema
			$ui_delete = $lpuidb->wpdb->query(
				$lpuidb->wpdb->prepare(
					"DELETE FROM $lpuidb->tb_lp_user_items AS ui
					 WHERE ui.user_id=%d",
					$user_id
				)
			);
		}

		// delete order which cannot search by email
		$lp_postdb                         = LP_Post_DB::getInstance();
		$order_filter                      = new LP_Order_Filter();
		$order_filter->only_fields[]       = 'p.ID as id';
		$order_filter->join[]              = "INNER JOIN $lp_postdb->tb_postmeta AS pm ON p.ID = pm.post_id";
		$order_filter->where               = array(
			$lp_postdb->wpdb->prepare(
				'AND pm.meta_key=%s AND pm.meta_value=%s',
				'_user_id',
				$user_id
			),
		);
		$order_filter->return_string_query = 1;
		$lp_order_ids_query                = $lp_postdb->get_posts( $order_filter );
		$lp_order_ids                      = $lp_postdb->wpdb->get_col( $lp_order_ids_query );
		if ( ! empty( $lp_order_ids ) ) {
			// delete order items and order itemmeta
			$this->delete_order_items_and_meta( $lp_postdb, $lp_order_ids );
			foreach ( $lp_order_ids as $order_id ) {
				wp_delete_post( $order_id, true );
			}
		}
		// search and delete order which contains multiple users
		$order_filter->where       = array(
			$lp_postdb->wpdb->prepare(
				'AND pm.meta_key=%s AND pm.meta_value LIKE %s',
				'_user_id',
				'%' . $lp_postdb->wpdb->esc_like( '"' . $user_id . '"' ) . '%'
			),
		);
		$multiple_user_order_query = $lp_postdb->get_posts( $order_filter );
		$multiple_user_order_ids   = $lp_postdb->wpdb->get_col( $multiple_user_order_query );
		if ( ! empty( $multiple_user_order_ids ) ) {
			$delete_order_ids = array();
			foreach ( $multiple_user_order_ids as $order_id ) {
				$user_ids = get_post_meta( $order_id, '_user_id', true );
				if ( is_array( $user_ids ) ) {
					if ( count( $user_ids ) <= 1 ) {
						$delete_order_ids[] = $order_id;
						wp_delete_post( $order_id, true );
					} else {
						$current_user_order_pos = array_search( $user_id, $user_ids );
						if ( $current_user_order_pos ) {
							unset( $user_ids[ $current_user_order_pos ] );
							update_post_meta( $order_id, '_user_id', $user_ids );
						}
					}
				} else {
					$delete_order_ids[] = $order_id;
					wp_delete_post( $order_id, true );
				}
			}
			if ( ! empty( $delete_order_ids ) ) {
				// delete order items and order itemmeta
				$this->delete_order_items_and_meta( $lp_postdb, $delete_order_ids );
			}
		}
	}

	/**
	 * delete order item table records, order itemmeta table records
	 *
	 * @param  LP_Post_DB $lp_postdb
	 * @param  array      $lp_order_ids
	 */
	public function delete_order_items_and_meta( $lp_postdb, array $lp_order_ids ) {
		$lp_order_ids_format         = LP_Helper::db_format_array( $lp_order_ids, '%d' );
		$filter                      = new LP_Filter();
		$filter->only_fields[]       = 'oi.order_item_id as oi_id';
		$filter->collection          = $lp_postdb->tb_lp_order_items;
		$filter->collection_alias    = 'oi';
		$filter->return_string_query = 1;
		$filter->where               = array(
			$lp_postdb->wpdb->prepare( "AND oi.order_id IN ($lp_order_ids_format)", $lp_order_ids ),
		);
		$query                       = $lp_postdb->execute( $filter );
		$oi_ids                      = $lp_postdb->wpdb->get_col( $query );
		if ( ! empty( $oi_ids ) ) {
			$oi_ids_format = LP_Helper::db_format_array( $oi_ids, '%d' );
			// delete order item
			$delete_oi = $lp_postdb->wpdb->query(
				$lp_postdb->wpdb->prepare(
					"DELETE FROM $lp_postdb->tb_lp_order_items AS oi
					 WHERE oi.order_id IN ($lp_order_ids_format)",
					$lp_order_ids
				)
			);
			// delete order item meta
			$delete_oim = $lp_postdb->wpdb->query(
				$lp_postdb->wpdb->prepare(
					"DELETE FROM $lp_postdb->tb_lp_order_itemmeta AS oim
					 WHERE oim.learnpress_order_item_id IN ($oi_ids_format)",
					$oi_ids
				)
			);
		}
	}
}
