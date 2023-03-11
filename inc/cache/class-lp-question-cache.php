<?php

/**
 * Class LP_Quiz_Cache
 *
 * @author tungnx
 * @since 4.2.0
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Question_Cache extends LP_Cache {
	/**
	 * @var null|LP_Question_Cache
	 */
	protected static $instance;
	/**
	 * @var string
	 */
	protected $key_group_child = 'question';

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
