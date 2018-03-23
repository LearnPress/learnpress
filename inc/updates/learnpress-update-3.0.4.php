<?php
/**
 * Todo: update emails
 */


/**
 * Class LP_Update_304
 *
 * Helper class for updating database to 3.0.4
 */
class LP_Update_304 {

	/**
	 * Entry point
	 */
	public static function update() {
		LP_Debug::startTransaction();
		try {
			self::update_item_meta();

			LP_Install::update_db_version();
			LP_Install::update_version();

			set_transient( 'lp_upgraded_30', 'yes', DAY_IN_SECONDS );
			LP_Debug::commitTransaction();
		}
		catch ( Exception $exception ) {
			LP_Debug::rollbackTransaction();
		}
	}

	public static function update_item_meta(){

	}

	protected static function _update_item_meta() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT pm1.learnpress_user_item_id, pm1.meta_value AS question_answers, pm2.meta_value AS _question_answers
			FROM  {$wpdb->learnpress_user_itemmeta} pm1 
			LEFT JOIN {$wpdb->learnpress_user_itemmeta} pm2 ON pm1.learnpress_user_item_id = pm2.learnpress_user_item_id AND pm2.meta_key = %s
			WHERE pm1.meta_key = %s
			HAVING _question_answers IS NULL
			LIMIT 0, 100
		", '_question_answers', 'question_answers' );

		if ( ! $rows = $wpdb->get_results( $query ) ) {
			return false;
		}

		$sqlUpdate = $wpdb->prepare( "
			INSERT INTO {$wpdb->learnpress_user_itemmeta}(learnpress_user_item_id, meta_key, meta_value)
			VALUES
		" );

		$updateRows = array();
		$count      = 0;
		$total      = sizeof( $rows );

		foreach ( $rows as $k => $row ) {

			if ( $row->_question_answers ) {
				continue;
			}

			$updateRows[] = $wpdb->prepare( "(%d, %s, %s)", $row->learnpress_user_item_id, '_question_answers', $row->question_answers );
			$count ++;

			if ( ( $count == 10 ) || ( $k == $total - 1 ) ) {
				$query = $sqlUpdate . join( ',', $updateRows );
				$wpdb->query( $query );
				$count      = 0;
				$updateRows = array();
			}
		}

		return true;
	}
}

//LP_Update_304::update();
