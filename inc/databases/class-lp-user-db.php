<?php
/**
 * Class LP_Course_DB
 *
 * @author tungnx
 * @since 3.2.7.5
 */

use LearnPress\Helpers\Singleton;

defined( 'ABSPATH' ) || exit();

class LP_User_DB extends LP_Database {
	use singleton;

	public function init() {
		parent::__construct();
	}

	public function get_users( LP_User_Filter $filter ) {

	}
}

LP_Course_DB::getInstance();

