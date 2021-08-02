<?php

/**
 * Class LP_Course_Cache
 *
 * @author tungnx
 * @since 4.0.9
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Course_Cache extends LP_Cache {
	protected static $instance;
	protected $key_group_child = 'course';

	/**
	 * Get instance
	 *
	 * @return LP_Cache
	 */
	public static function instance(): LP_Cache {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		parent::__construct();
	}
}
