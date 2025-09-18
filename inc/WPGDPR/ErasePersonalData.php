<?php
namespace LearnPress\WPGDPR;

use LearnPress\Filters\PostFilter;

use LearnPress\Helpers\Singleton;
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
	 * @param int    $page
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
			$response['messages'][] = $e->getMessage();
		}

		return $response;
	}

	/**
	 * delete related data via email(guest)
	 *
	 * @param  string $email
	 */
	public function eraser_data_via_email( $email ) {
		$lp_postdb                   = LP_Post_DB::getInstance();
		$filter                      = new LP_Order_Filter();
		$filter->only_fields[]       = 'p.ID as id';
		$filter->join[]              = "INNER JOIN $lp_postdb->tb_postmeta AS pm ON p.ID = pm.post_id";
		$filter->where               = array(
			$lp_postdb->wpdb->prepare(
				'AND pm.meta_key=%s AND pm.meta_value=%s',
				'_checkout_email',
				$email
			),
		);
		$filter->return_string_query = 1;
		$lp_order_ids_query          = $lp_postdb->get_posts( $filter );
		$lp_order_ids                = $lp_postdb->wpdb->get_col( $lp_order_ids_query );
		if ( ! empty( $lp_order_ids ) ) {
			$lp_order_ids_format = LP_Helper::db_format_array( $lp_order_ids, '%d' );
			// delete order items and order itemmeta
			$this->delete_order_items_and_meta( $lp_postdb, $lp_order_ids );

			// 2. Delete data on learnpress_user_items table with order ids found.
			$ui_delete = $lp_postdb->wpdb->query(
				$lp_postdb->wpdb->prepare(
					"DELETE FROM $lp_postdb->tb_lp_user_items AS ui
					WHERE ui.ref_id IN ($lp_order_ids_format)",
					$lp_order_ids
				)
			);

			// delete order - handle to lastest.
			foreach ( $lp_order_ids as $order_id ) {
				wp_delete_post( $order_id, true );
			}
		}
	}

	/**
	 * Erase user data
	 *
	 * @param WP_User $user
	 */
	public function eraser_user_data( WP_User $user ) {
		$user_id  = $user->ID;
		$lpuidb   = LP_User_Items_DB::getInstance();
		$um_table = $lpuidb->wpdb->usermeta;

		// delete all lp user meta
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
