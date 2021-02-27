<?php
/**
 * Query in table learnpress_user_item_result.
 *
 * @author Nhamdv <email@email.com>
 */
class LP_User_Items_Result_DB extends LP_Database {
	private static $_instance = null;

	protected function __construct() {
		parent::__construct();
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Get current result evaluation.
	 *
	 * @param integer $user_item_id
	 * @return void
	 */
	public function get_result( $user_item_id = 0 ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return false;
		}

		$max_id = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) id from $wpdb->learnpress_user_item_results where user_item_id=%d", $user_item_id ) );

		if ( ! $max_id ) {
			return false;
		}

		$result = $wpdb->get_results( $wpdb->prepare( "SELECT result FROM $wpdb->learnpress_user_item_results WHERE id=%d", $max_id ) );

		return $result ? json_decode( $result[0]->result, true ) : false;
	}

	public function update( $user_item_id = 0, $result = null ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return false;
		}

		$max_id = $wpdb->get_var( $wpdb->prepare( "SELECT MAX(id) id from $wpdb->learnpress_user_item_results where user_item_id=%d", $user_item_id ) );

		$data   = array( 'result' => $result );
		$where  = array(
			'id'           => absint( $max_id ),
			'user_item_id' => $user_item_id,
		);
		$format = array( '%s' );

		if ( absint( $max_id ) > 0 ) {
			$output = $wpdb->update( $wpdb->learnpress_user_item_results, $data, $where, $format );
		} else {
			$output = $this->insert( $user_item_id, $result );
		}

		return $output;
	}

	public function insert( $user_item_id = 0, $result = null ) {
		global $wpdb;

		if ( ! $user_item_id ) {
			return false;
		}

		$insert = $wpdb->insert(
			$wpdb->learnpress_user_item_results,
			array(
				'user_item_id' => $user_item_id,
				'result'       => $result,
			),
			array( '%d', '%s' )
		);

		if ( $insert ) {
			return $wpdb->insert_id;
		}

		return false;
	}

	public function delete( $user_item_id = 0 ) {
		global $wpdb;

		$delete = $wpdb->delete(
			$wpdb->learnpress_user_item_results,
			array(
				'user_item_id' => $user_item_id,
			),
			array( '%d' )
		);

		return $delete;
	}

	/** Delete all record in table */
	public function delete_all() {
		global $wpdb;

		$wpdb->query( "DELETE FROM {$wpdb->learnpress_user_item_results}" );
	}
}
LP_User_Items_Result_DB::instance();
