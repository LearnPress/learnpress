<?php

/**
 * Class LP_Theme_Support_TwentyTwenty
 *
 * @since 4.x.x
 */
class LP_Theme_Support_TwentyTwenty extends LP_Theme_Support_Base {
	public function __construct() {
		parent::__construct();
	}

	public function content_wrapper_start() {
		///echo '<main id="site-content" role="main">';
	}

	public function content_wrapper_end() {
		//echo '</main>';
	}
}

return new LP_Theme_Support_TwentyTwenty();
