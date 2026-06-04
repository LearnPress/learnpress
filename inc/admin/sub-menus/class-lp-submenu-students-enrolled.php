<?php

use LearnPress\TemplateHooks\Admin\AdminListStudentsEnrolled;

/**
 * Class LP_Submenu_Students_Enrolled
 */
class LP_Submenu_Students_Enrolled extends LP_Abstract_Submenu {
	/**
	 * LP_Submenu_Students_Enrolled constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-students-enrolled';
		$this->menu_title = __( 'Enrolled Students', 'learnpress' );
		$this->page_title = __( 'Enrolled Students', 'learnpress' );
		$this->priority   = 20;
		$this->callback   = [ AdminListStudentsEnrolled::instance(), 'admin_page_output' ];

		parent::__construct();
	}
}

return new LP_Submenu_Students_Enrolled();
