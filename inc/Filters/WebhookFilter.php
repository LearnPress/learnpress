<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit;

/**
 * Filter query for the learnpress_webhooks table.
 */
class WebhookFilter extends FilterBase {
	const COL_WEBHOOK_ID   = 'webhook_id';
	const COL_USER_ID      = 'user_id';
	const COL_STATUS       = 'status';
	const COL_NAME         = 'name';
	const COL_DELIVERY_URL = 'delivery_url';
	const COL_SECRET       = 'secret';
	const COL_EVENTS       = 'events';
	const COL_CREATED_AT   = 'created_at';
	const COL_UPDATED_AT   = 'updated_at';

	/**
	 * @var string[]
	 */
	public array $all_fields = array(
		self::COL_WEBHOOK_ID,
		self::COL_USER_ID,
		self::COL_STATUS,
		self::COL_NAME,
		self::COL_DELIVERY_URL,
		self::COL_SECRET,
		self::COL_EVENTS,
		self::COL_CREATED_AT,
		self::COL_UPDATED_AT,
	);

	/**
	 * @var int
	 */
	public $webhook_id;

	/**
	 * @var int[]
	 */
	public $webhook_ids = array();

	/**
	 * @var int
	 */
	public $user_id;

	/**
	 * @var string
	 */
	public $status;

	/**
	 * @var string[]
	 */
	public $statuses = array();

	/**
	 * @var string
	 */
	public $name;

	/**
	 * @var string
	 */
	public $delivery_url;

	/**
	 * @var string
	 */
	public $event;

	/**
	 * @var string
	 */
	public $field_count = self::COL_WEBHOOK_ID;
}
