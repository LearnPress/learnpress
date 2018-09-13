<?php
/**
 * Todo: update emails
 */

include_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Update_308
 *
 * Helper class for updating database to 3.0.8
 */
class LP_Update_308 extends LP_Update_Base {

	public function __construct() {
		$this->version = '3.0.8';
		$this->steps   = array( 'update_item_meta' );

		parent::__construct();
	}

	public function update_item_meta() {
		return true;
	}
}

$updater = new LP_Update_308();
$return  = $updater->update( LP_Request::get( 'force' ) == 'true' );

return array( 'done' => $return, 'percent' => $updater->get_percent() );