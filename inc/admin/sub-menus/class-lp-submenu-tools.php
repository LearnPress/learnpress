<?php

class LP_Submenu_Tools extends LP_Abstract_Submenu {

	public function __construct() {
		$this->id         = 'learn-press-tools';
		$this->menu_title = __( 'Tools', 'learnpress' );
		$this->page_title = __( 'LearnPress Tools', 'learnpress' );
		$this->priority   = 40;
	}

	public function page_content() {
		echo get_class($this);
	}
}
return new LP_Submenu_Tools();