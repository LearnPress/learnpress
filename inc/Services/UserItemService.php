<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Databases\UserItemResultsDB;
use LearnPress\Databases\UserItemsDB;
use LearnPress\Filters\UserItemsFilter;
use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserModel;
use Throwable;
use WP_Error;
use WP_Role;
use WP_User;


/**
 * Class UserItemService
 *
 * Handle logic business for user item.
 *
 * @since 4.5.0
 * @version 1.0.0
 */
class UserItemService {
	use Singleton;

	public function init(): void {}
}
