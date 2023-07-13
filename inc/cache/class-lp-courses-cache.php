<?php

/**
 * Class LP_Courses_Cache
 *
 * @author tungnx
 * @since 4.1.5
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Courses_Cache extends LP_Cache {
	/**
	 * @var LP_Courses_Cache
	 */
	protected static $instance;
	/**
	 * @var string
	 */
	protected $key_group_child = 'courses';
	/**
	 * @var string Save list keys cached to clear
	 */
	public static $keys = 'keys';

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

	public function __construct( $has_thim_cache = false ) {
		parent::__construct( $has_thim_cache );
	}

	public function save_cache_keys( string $key_cache ) {
		$keys_cache = $this->get_cache( self::$keys );
		if ( false === $keys_cache ) {
			$keys_cache = array();
		}

		$keys_cache[] = $key_cache;
		$this->set_cache( self::$keys, $keys_cache );
	}
}
