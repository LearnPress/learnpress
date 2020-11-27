<?php

/**
 * Class LP_Theme_Support
 *
 * @since 4.x.x
 */
class LP_Theme_Support {

	/**
	 * @var LP_Theme_Support_Base
	 */
	public $theme = null;

	/**
	 * LP_Theme_Support constructor.
	 */
	public function __construct() {
		$template      = get_template();
		$theme_support = LP_PLUGIN_PATH . "inc/theme-support/{$template}/class-{$template}.php";

		if ( file_exists( $theme_support ) ) {
			$this->theme = include_once $theme_support;
		}
	}

	/**
	 * @return LP_Theme_Support
	 */
	public static function instance() {
		static $instance = null;

		if ( ! $instance ) {
			$instance = new self();
		}

		return $instance;
	}
}

return LP_Theme_Support::instance();
