<?php

namespace LearnPress\Helpers;

use stdClass;

defined( 'ABSPATH' ) || exit;

/**
 * Class Response
 *
 * @package LearnPress/Helpers
 * @version 1.0.0
 * @since 4.3.7
 */
class Response {
	const STATUS_SUCCESS = 'success';
	const STATUS_ERROR   = 'error';
	/**
	 * Status.
	 *
	 * @var string.
	 */
	public $status = self::STATUS_ERROR;
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
