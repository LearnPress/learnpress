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
class LP_Update_XXX extends LP_Update_Base {

	public function __construct() {
		$this->version = 'X.X.X';
		$this->steps   = array( 'alter_datetime_default_value', 'add_columns' );

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

		$query = $wpdb->prepare("
			ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
			CHANGE `start_time` `start_time` DATETIME NULL DEFAULT NULL, 
			CHANGE `start_time_gmt` `start_time_gmt` DATETIME NULL DEFAULT NULL, 
			CHANGE `end_time` `end_time` DATETIME NULL DEFAULT NULL, 
			CHANGE `end_time_gmt` `end_time_gmt` DATETIME NULL DEFAULT NULL
		");

		$wpdb->query($query);

		return true;
	}

	/**
	 * Add new columns to user_items.
	 *
	 * @return bool
	 */
	public function add_columns(){
		global $wpdb;
		$query = $wpdb->prepare("
     		 ALTER TABLE `{$wpdb->prefix}learnpress_user_items` 
     		 ADD `expiration_time` DATETIME NULL DEFAULT NULL AFTER `end_time_gmt`, 
     		 ADD `expiration_time_gmt` DATETIME NULL DEFAULT NULL AFTER `expiration_time`;
		");

		$wpdb->query($query);

		return true;
	}
}

$updater = new LP_Update_308();
$return  = $updater->update( LP_Request::get( 'force' ) == 'true' );

return array( 'done' => $return, 'percent' => $updater->get_percent() );