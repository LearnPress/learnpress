<?php
/**
 * Todo: update emails
 */

include_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Update_XXX
 *
 * Helper class for updating database to X.X.X
 */
class LP_Update_999 extends LP_Update_Base {

	public function __construct() {
		$this->version = '9.9.9';
		$this->steps   = array(
			'alter_datetime_default_value',
			'alter_tables',
			'update_expiration_time',
			'update_time_field_from_time_gmt',
			'remove_time_gmt',
			'add_question'
		);

		parent::__construct();
	}

	/**
	 * Alter table user_items to change the default values for datetime fields
	 * from '0000-00-00 00:00:00' to NULL.
	 *
	 * @return bool
	 */
	public function alter_datetime_default_value() {
		global $wpdb;

		$query = "
			ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
			CHANGE `start_time` `start_time` DATETIME NULL DEFAULT NULL, 
			CHANGE `start_time_gmt` `start_time_gmt` DATETIME NULL DEFAULT NULL, 
			CHANGE `end_time` `end_time` DATETIME NULL DEFAULT NULL, 
			CHANGE `end_time_gmt` `end_time_gmt` DATETIME NULL DEFAULT NULL
		";

		$wpdb->query( $query );

		return true;
	}

	/**
	 * Add new columns to user_items.
	 *
	 * @return bool
	 */
	public function alter_tables() {
		global $wpdb;
		$query = "
     		 ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
     		 ADD `expiration_time` DATETIME NULL DEFAULT NULL AFTER `end_time_gmt`, 
     		 ADD `expiration_time_gmt` DATETIME NULL DEFAULT NULL AFTER `expiration_time`;
		";

		$query = "
     		 ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
     		 ADD `expiration_time` DATETIME NULL DEFAULT NULL AFTER `end_time_gmt`;
		";
		$wpdb->query( $query );

		$query = "
			ALTER TABLE `{$wpdb->prefix}learnpress_question_answers` ADD INDEX(`question_id`);
		";
		$wpdb->query( $query );

		$query = "
			ALTER TABLE `{$wpdb->prefix}learnpress_user_itemmeta` ADD INDEX(`learnpress_user_item_id`);
		";
		$wpdb->query( $query );

		$query = "
			ALTER TABLE `{$wpdb->prefix}learnpress_user_itemmeta` ADD INDEX(`meta_key`);
		";
		$wpdb->query( $query );

		return true;
	}

	/**
	 * Update expiration time for all user items
	 */
	public function update_expiration_time() {
		global $wpdb;
		$query = $wpdb->prepare( "
			SELECT p.ID, pm.meta_value duration
			FROM {$wpdb->posts} p 
			INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
			WHERE 
				p.post_type IN(%s, %s) AND meta_value NOT LIKE %s
		", '_lp_duration', LP_COURSE_CPT, LP_QUIZ_CPT, $wpdb->esc_like( '0 ' ) . '%' );

		if ( ! $courses = $wpdb->get_results( $query ) ) {
			return true;
		}

		foreach ( $courses as $course ) {

			$query = $wpdb->prepare( "
				SELECT *
				FROM {$wpdb->learnpress_user_items}
				WHERE item_id = %d
				AND status IN(%s, %s, %s)
				LIMIT 0, 500
			", $course->ID, 'enrolled', 'viewed', 'started' );

			if ( $course_items = $wpdb->get_results( $query ) ) {

				foreach ( $course_items as $course_item ) {
					$date            = new LP_Datetime( $course_item->start_time );
					$expiration_time = $date->getPeriod( $course->duration );
					//$expiration_time_gmt = $date->toSql( false );

					$query = $wpdb->prepare( "
						UPDATE {$wpdb->learnpress_user_items}
						SET expiration_time = %s ##, expiration_time_gmt = %s
						WHERE item_id = %d
					", $expiration_time, /*$expiration_time_gmt,*/
						$course->ID );

					$wpdb->query( $query );

				}
			}
		}

		return true;
	}

	public function update_time_field_from_time_gmt() {
		global $wpdb;

		$query = $wpdb->prepare( "
			UPDATE {$wpdb->learnpress_user_items} 
			SET 
				`start_time` = `start_time_gmt`,
				`end_time` = `end_time_gmt`
			WHERE %d
		", 1 );

		$wpdb->query( $query );

		return true;
	}

	public function remove_time_gmt() {
		global $wpdb;

		$wpdb->query( "ALTER TABLE {$wpdb->learnpress_user_items} DROP `start_time_gmt`, DROP `end_time_gmt`" );

		return true;
	}
}

$updater = new LP_Update_999();
$return  = $updater->update( LP_Request::get( 'force' ) == 'true' );

return array( 'done' => $return, 'percent' => $updater->get_percent() );