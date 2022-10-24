<?php
/**
 * Class LP_Submenu_Tools
 */
class LP_Submenu_Tags extends LP_Abstract_Submenu {
	/**
	 * LP_Submenu_Tools constructor.
	 */
	public function __construct() {
		$this->id         = 'edit-tags.php?taxonomy=course_tag';
		$this->menu_title = __( 'Tags', 'learnpress' );
		$this->page_title = __( 'Tags', 'learnpress' );
		$this->priority   = 2;
	}
}

return new LP_Submenu_Tags();
