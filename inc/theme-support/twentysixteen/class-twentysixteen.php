<?php

/**
 * Class LP_Theme_Support_TwentySixteen
 *
 * @since 4.x.x
 */
class LP_Theme_Support_TwentySixteen extends LP_Theme_Support_Base {
	public function __construct() {
		parent::__construct();
	}

	public function content_wrapper_start() {
		//echo '<div id="primary" class="lp-content-area twentysixteen"><main id="main" class="site-main" role="main">';
	}

	public function content_wrapper_end() {
		//echo '</main></div>';
	}
}

return new LP_Theme_Support_TwentySixteen();