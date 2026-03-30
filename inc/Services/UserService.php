<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserModel;
use LP_User_DB;
use LP_User_Filter;


/**
 * Class UserService
 *
 * Handle logic business for user.
 *
 * @since 4.3.4
 * @version 1.0.0
 */
class UserService {
	use Singleton;

	public function init() {}

	/**
	 * Check if pretty slug exists of another user.
	 *
	 * @param string $slug
	 *
	 * @return false|UserModel
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.3.4
	 */
	public function get_user_by_pretty_slug( string $slug ) {
		$slug = trim( urldecode( $slug ) );
		$slug = sanitize_title( $slug );

		if ( '' === $slug ) {
			return false;
		}

		$lp_user_db          = LP_User_DB::instance();
		$filter              = new LP_User_Filter();
		$filter->only_fields = [ 'u.ID' ];
		$lp_user_db->get_query_single_row( $filter );
		$filter->join[]  = "INNER JOIN {$lp_user_db->wpdb->usermeta} AS um ON um.user_id = u.ID";
		$filter->where[] = $lp_user_db->wpdb->prepare( 'AND um.meta_key = %s', UserModel::META_KEY_USER_SLUG );
		$filter->where[] = $lp_user_db->wpdb->prepare( 'AND um.meta_value = %s', $slug );
		$query           = $lp_user_db->get_users( $filter );

		$user_id = (int) $lp_user_db->wpdb->get_var( $query );

		return UserModel::find( $user_id, true );
	}

	/**
	 * Generate pretty slug for all users who don't have it yet.
	 *
	 * @return array [ 'processed' => int, 'generated' => int, 'skipped' => int, 'failed' => int ]
	 * @since 4.3.4
	 * @version 1.0.0
	 */
	public function generate_users_pretty_slug(): array {
		$user_ids = get_users(
			[
				'fields' => 'ids',
				'number' => - 1,
			]
		);

		$result = [
			'processed' => 0,
			'generated' => 0,
			'skipped'   => 0,
			'failed'    => 0,
		];

		foreach ( $user_ids as $user_id ) {
			$user_id = (int) $user_id;
			++ $result['processed'];

			$userModel = UserModel::find( $user_id, true );
			if ( ! $userModel instanceof UserModel ) {
				++ $result['failed'];
				continue;
			}

			if ( '' !== $userModel->get_pretty_slug( false ) ) {
				++ $result['skipped'];
				continue;
			}

			$generated = $userModel->generate_pretty_slug();
			if ( is_wp_error( $generated ) ) {
				++ $result['failed'];
			} else {
				++ $result['generated'];
			}
		}

		return $result;
	}
}
