<?php
namespace LearnPress\WPGDPR;

use LearnPress\Filters\PostFilter;
use LearnPress\Helpers\Singleton;
use LP_Database;
use LP_Helper;
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
			'messages'       => [],
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

	public function eraser_data_via_email( $email ) {
		$lpdb               = LP_Database::getInstance();
		$lp_order_ids_query = $lpdb->wpdb->prepare(
			"SELECT p.ID FROM $lpdb->tb_posts AS p INNER JOIN $lpdb->tb_postmeta AS pm ON p.ID = pm.post_id WHERE p.post_type=%s AND pm.meta_key=%s AND pm.meta_value=%s",
			LP_ORDER_CPT,
			'_checkout_email',
			$email
		);
		// Su dung filter de xu ly query, ko viet cau lenh truc tiep.
		$lp_order_ids = $lpdb->wpdb->get_col( $lp_order_ids_query );

		if ( ! empty( $lp_order_ids ) ) {
			$lp_order_ids_format = LP_Helper::db_format_array( $lp_order_ids, '%d' );
			$oi_query            = $lpdb->wpdb->prepare(
				"SELECT oi.order_item_id FROM $lpdb->tb_lp_order_items AS oi
				 WHERE oi.order_id IN ($lp_order_ids_format)",
				$lp_order_ids
			);
			$oi_ids              = $lpdb->wpdb->get_col( $oi_query );

			// 1. Find learnpress_user_itemmeta tabel and delete them.

			//

			// 2. Delete data on learnpress_user_items table with order ids found.
			// Khong can check user guest, chi can tim xoa list order id la duoc.
			$ui_delete = $lpdb->wpdb->query(
				$lpdb->wpdb->prepare(
					"DELETE FROM $lpdb->tb_lp_user_items AS ui
					 WHERE ui.user_id=%d AND ui.ref_id IN ($lp_order_ids_format)",
					0,
					...$lp_order_ids
				)
			);

			if ( ! empty( $oi_ids ) ) {
				$oi_ids_format = LP_Helper::db_format_array( $oi_ids, '%d' );
				// delete order item
				$delete_oi = $lpdb->wpdb->query(
					$lpdb->wpdb->prepare(
						"DELETE FROM $lpdb->tb_lp_order_items AS oi
						 WHERE oi.order_id IN ($lp_order_ids_format)",
						$lp_order_ids
					)
				);
				// delete order item meta
				$delete_oim = $lpdb->wpdb->query(
					$lpdb->wpdb->prepare(
						"DELETE FROM $lpdb->tb_lp_order_itemmeta AS oim
						 WHERE oim.learnpress_order_item_id IN ($oi_ids_format)",
						$oi_ids
					)
				);
			}

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
		$lpdb     = LP_Database::getInstance();
		$um_table = $lpdb->wpdb->usermeta;

		// delete all lp user meta
		$delete_usermeta = $lpdb->wpdb->query(
			$lpdb->wpdb->prepare(
				"DELETE FROM $um_table AS um
				 WHERE um.user_id = %d AND um.meta_key LIKE %s",
				$user_id,
				$lpdb->wpdb->esc_like( '_lp' ) . '%'
			)
		);

		// get all user item ids
		$lp_ui_ids_query = $lpdb->wpdb->prepare(
			"SELECT ui.user_item_id FROM $lpdb->tb_lp_user_items AS ui WHERE ui.user_id=%d",
			$user_id
		);
		$lp_ui_ids       = $lpdb->wpdb->get_col( $lp_ui_ids_query );
		if ( ! empty( $lp_ui_ids ) ) {
			$lp_ui_ids_format = LP_Helper::db_format_array( $lp_ui_ids, '%d' );
			// delete user itemmeta
			$uim_delete = $lpdb->wpdb->query(
				$lpdb->wpdb->prepare(
					"DELETE FROM $lpdb->tb_lp_user_itemmeta AS uim
					 WHERE uim.learnpress_user_item_id IN ($lp_ui_ids_format)",
					$lp_ui_ids
				)
			);
			// delete user item result
			$uir_delete = $lpdb->wpdb->query(
				$lpdb->wpdb->prepare(
					"DELETE FROM $lpdb->tb_lp_user_item_results AS uir
					 WHERE uir.user_item_id IN ($lp_ui_ids_format)",
					$lp_ui_ids
				)
			);
			// delete all user itema
			$ui_delete = $lpdb->wpdb->query(
				$lpdb->wpdb->prepare(
					"DELETE FROM $lpdb->tb_lp_user_items AS ui
					 WHERE ui.user_id=%d",
					$user_id
				)
			);
		}
		// delete order which cannot search by email
		$lp_order_ids_query = $lpdb->wpdb->prepare(
			"SELECT p.ID FROM $lpdb->tb_posts AS p INNER JOIN $lpdb->tb_postmeta AS pm ON p.ID = pm.post_id WHERE p.post_type=%s AND pm.meta_key=%s AND pm.meta_value=%s",
			LP_ORDER_CPT,
			'_user_id',
			$user_id
		);
		$lp_order_ids       = $lpdb->wpdb->get_col( $lp_order_ids_query );
		if ( ! empty( $lp_order_ids ) ) {
			foreach ( $lp_order_ids as $order_id ) {
				wp_delete_post( $order_id, true );
			}
		}
		// search and delete order which contains multiple users
		$multiple_user_order_query = $lpdb->wpdb->prepare(
			"SELECT p.ID FROM $lpdb->tb_posts AS p INNER JOIN $lpdb->tb_postmeta AS pm ON p.ID = pm.post_id WHERE p.post_type=%s AND pm.meta_key=%s AND pm.meta_value LIKE %s",
			LP_ORDER_CPT,
			'_user_id',
			'%' . $lpdb->wpdb->esc_like( '"' . $user_id . '"' ) . '%'
		);
		$multiple_user_order_ids   = $lpdb->wpdb->get_col( $multiple_user_order_query );
		if ( ! empty( $multiple_user_order_ids ) ) {
			foreach ( $multiple_user_order_ids as $order_id ) {
				$user_ids = get_post_meta( $order_id, '_user_id', true );
				if ( is_array( $user_ids ) ) {
					if ( count( $user_ids ) <= 1 ) {
						wp_delete_post( $order_id, true );
					} else {
						$current_user_order_pos = array_search( $user_id, $user_ids );
						if ( $current_user_order_pos ) {
							unset( $user_ids[ $current_user_order_pos ] );
							update_post_meta( $order_id, '_user_id', $user_ids );
						}
					}
				} else {
					wp_delete_post( $order_id, true );
				}
			}
		}
	}
}
