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
	/**
	 * Status.
	 *
	 * @var string.
	 */
	public $status = 'error';
	/**
	 * Message.
	 *
	 * @var string .
	 */
	public $message = '';
	/**
	 * Extra data
	 *
	 * @var object
	 */
	public $data;

	/**
	 * LP_REST_Response constructor.
	 */
	public function __construct() {
		$this->data = new stdClass();
	}
}
