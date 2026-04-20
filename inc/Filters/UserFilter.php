<?php

namespace LearnPress\Filters;

defined( 'ABSPATH' ) || exit();

/**
 * Class UserFilter
 *
 * Filter query for users table
 *
 * @since 4.3.6
 * @version 1.0.0
 */
class UserFilter extends FilterBase {
	const COL_ID            = 'ID';
	const COL_USER_LOGIN    = 'user_login';
	const COL_USER_NICENAME = 'user_nicename';
	const COL_USER_EMAIL    = 'user_email';
	const COL_USER_URL      = 'user_url';
	const COL_DISPLAY_NAME  = 'display_name';
	const COL_USER_STATUS   = 'user_status';

	/**
	 * @var string[] List of fields can be filtered.
	 */
	public array $all_fields = [
		self::COL_ID,
		self::COL_USER_LOGIN,
		self::COL_USER_NICENAME,
		self::COL_USER_EMAIL,
		self::COL_DISPLAY_NAME,
		self::COL_USER_URL,
		self::COL_USER_STATUS,
	];

	/**
	 * @var int User id.
	 */
	public $ID;

	/**
	 * @var int[] List of user ids.
	 */
	public $ids = [];

	/**
	 * @var string User nice name.
	 */
	public $user_nicename;

	/**
	 * @var string Email.
	 */
	public $user_email;

	/**
	 * @var string User login.
	 */
	public $user_login;

	/**
	 * @var string Display name.
	 */
	public $display_name;

	/**
	 * @var string User url.
	 */
	public $user_url;

	/**
	 * @var int User status.
	 */
	public $user_status;

	/**
	 * @var string
	 */
	public $field_count = self::COL_ID;
}
