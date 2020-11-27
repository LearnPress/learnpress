<?php

/**
 * Class LP_Asset_Key
 *
 * @author  tungnx
 * @package LearnPress/Classes
 * @version 1.0
 * @since 3.2.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class LP_REST_Response {
	public $status = 'error';
	public $message = '';
	/**
	 * @var array|object
	 */
	public $data;
}
