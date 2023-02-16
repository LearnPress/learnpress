<?php

/**
 * Class LP_Settings_Cache
 *
 * @author tungnx
 * @since 4.2.2
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit();

class LP_Settings_Cache extends LP_Cache {
	/**
	 * @var string key group
	 */
	protected $key_group_child = 'settings';
	/**
	 * @var string key cache
	 */
	public $key_cache = 'learn-press-settings';

	public function __construct( $has_thim_cache = false ) {
		parent::__construct( $has_thim_cache );
	}
}
