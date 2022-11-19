<?php
/**
 * Class LP_Submenu_Tools
 */
class LP_Submenu_Categories extends LP_Abstract_Submenu {
	/**
	 * LP_Submenu_Tools constructor.
	 */
	public function __construct() {
		$this->id         = 'edit-tags.php?taxonomy=course_category';
		$this->menu_title = __( 'Categories', 'learnpress' );
		$this->page_title = __( 'Categories', 'learnpress' );
		$this->priority   = 1;
	}
}

return new LP_Submenu_Categories();
