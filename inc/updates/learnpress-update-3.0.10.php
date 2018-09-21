<?php
/**
 * Todo: update emails
 */

include_once dirname( __FILE__ ) . '/learnpress-update-base.php';

/**
 * Class LP_Update_900
 *
 * Helper class for updating database to 9.0.0
 */
class LP_Update_900 extends LP_Update_Base {

	public function __construct() {
		$this->version = '3.0.14';
		$this->steps   = array( 'update_item_meta' );

		parent::__construct();
	}

	public function update_item_meta() {
		$this->_next_step();

		return;
	}
}

return new LP_Update_900();