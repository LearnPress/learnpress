<?php

/**
 * Class LP_Quiz_Cache
 *
 * @author tungnx
 * @since 4.0.9
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Session_Cache extends LP_Cache {
	protected static $instance;
	protected $key_group_child = 'session';

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

	public function __construct() {
		parent::__construct();
	}
}
