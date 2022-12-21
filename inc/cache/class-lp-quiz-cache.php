<?php

/**
 * Class LP_Quiz_Cache
 *
 * @author tungnx
 * @since 4.0.9
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Quiz_Cache extends LP_Cache {
	protected static $instance;
	protected $key_group_child = 'quiz';

	/**
	 * Get instance
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	protected function __construct() {
		parent::__construct();
	}
}
