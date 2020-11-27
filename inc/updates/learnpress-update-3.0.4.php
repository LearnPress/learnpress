<?php
/**
 * Todo: update emails
 */

include_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Update_304
 *
 * Helper class for updating database to 3.0.4
 */
class LP_Update_304 extends LP_Update_Base {

	public function __construct() {
		$this->version = '3.0.4';
		$this->steps   = array( 'update_item_meta' );

		parent::__construct();
	}

	public function update_item_meta() {

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
			return true;
		}

		$sqlUpdate = "
			INSERT INTO {$wpdb->learnpress_user_itemmeta}(learnpress_user_item_id, meta_key, meta_value)
			VALUES
		";

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

		return false;
	}
}

$updater = new LP_Update_304();
$return  = $updater->update(LP_Request::get( 'force' ) == 'true');

return array( 'done' => $return, 'percent' => $updater->get_percent() );
