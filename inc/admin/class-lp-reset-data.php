<?php
class LP_Reset_Data {
	public static function init() {
		$ajax_events = array(
			'reset-user-item'    => 'ajax_reset_user_item',
		);

		foreach ( $ajax_events as $action => $callback ) {
			if ( is_numeric( $action ) ) {
				$action = $callback;
			}

			$actions = LP_Request::parse_action( $action );
			$method  = $actions['action'];

			if ( ! is_callable( $callback ) ) {
				$callback = array( __CLASS__, $callback );

				if ( ! is_callable( $callback ) ) {
					$method   = preg_replace( '/-/', '_', $method );
					$callback = array( __CLASS__, $method );
				}
			}
			LP_Request::register_ajax( "rs-{$action}", $callback );
		}
	}

	public static function ajax_reset_user_item() {
		if ( ! current_user_can( ADMIN_ROLE ) ) {
			return;
		}

		$nonce = LP_Request::get_param( 'nonce' );
		if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			die( 'Nonce is invalid!' );
		}

		$user_id = LP_Request::get_string( 'user_id' );
		$item_id = LP_Request::get_int( 'item_id' );

		if ( ! is_numeric( $user_id ) ) {
			$user_email = get_user_by( 'email', $user_id );
			$user_login = get_user_by( 'login', $user_id );

			if ( $user_email ) {
				$user_id = $user_email->ID;
			} elseif ( $user_login ) {
				$user_id = $user_login->ID;
			}
		}

		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT user_item_id
			FROM {$wpdb->learnpress_user_items}
			WHERE user_id = %d AND item_id = %d
		",
			$user_id,
			$item_id
		);

		$user_item_ids = $wpdb->get_col( $query );
		if ( $user_item_ids ) {
			$query   = "
				SELECT DISTINCT parent_id AS parent, item_id
				FROM {$wpdb->learnpress_user_items}
				WHERE user_item_id IN(" . join( ',', $user_item_ids ) . ')
			';
			$parents = $wpdb->get_results( $query );

			$format = array_fill( 0, sizeof( $user_item_ids ), '%d' );
			$query  = $wpdb->prepare(
				"
				DELETE
				FROM {$wpdb->learnpress_user_itemmeta}
				WHERE learnpress_user_item_id IN(" . join( ',', $format ) . ')
			',
				$user_item_ids
			);
			$wpdb->query( $query );

			$query = $wpdb->prepare(
				"
				DELETE
				FROM {$wpdb->learnpress_user_items}
				WHERE user_id = %d AND item_id = %d
			",
				$user_id,
				$item_id
			);

			$wpdb->query( $query );

			if ( $parents ) {
				foreach ( $parents as $parent ) {
					$retaken_items = learn_press_get_user_item_meta( $parent->parent, '_retaken_items', true );
					if ( $retaken_items ) {
						if ( ! isset( $retaken_items[ $parent->item_id ] ) ) {
							continue;
						}

						unset( $retaken_items[ $parent->item_id ] );
						learn_press_update_user_item_meta( $parent->parent, '_retaken_items', $retaken_items );
					}
				}
			}

			echo __( 'Item progress is deleted', 'learnpress' );
		} else {
			echo __( 'No data found', 'learnpress' );
		}
		// LP_Debug::rollbackTransaction();
		die();
	}
}
LP_Reset_Data::init();
