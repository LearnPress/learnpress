<?php

namespace LearnPress\Services;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserModel;
use Throwable;
use WP_Error;
use WP_User;


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
	 * @version 1.0.1
	 * @since 4.3.4
	 */
	/*public function get_user_by_pretty_slug( string $slug ) {
		if ( '' === $slug ) {
			return false;
		}

		$lp_user_db          = UserDB::getInstance();
		$filter              = new UserFilter();
		$filter->only_fields = [ 'u.ID' ];
		$lp_user_db->get_query_single_row( $filter );
		$filter->join[]  = "INNER JOIN {$lp_user_db->wpdb->usermeta} AS um ON um.user_id = u.ID";
		$filter->where[] = $lp_user_db->wpdb->prepare( 'AND um.meta_key = %s', UserModel::META_KEY_USER_SLUG );
		$filter->where[] = $lp_user_db->wpdb->prepare( 'AND um.meta_value = %s', $slug );
		$query           = $lp_user_db->get_users( $filter );

		$user_id = (int) $lp_user_db->wpdb->get_var( $query );

		return UserModel::find( $user_id, true );
	}*/

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

			if ( '' !== $userModel->get_slug_link() ) {
				++ $result['skipped'];
				continue;
			}

			$generated = $this->generate_pretty_slug( $userModel );
			if ( is_wp_error( $generated ) ) {
				++ $result['failed'];
			} else {
				++ $result['generated'];
			}
		}

		return $result;
	}

	/**
	 * Create a unique pretty slug for user.
	 *
	 * If the user already has a pretty slug, it will return the existing one without generating a new one.
	 * The slug is generated based on the user's first name and last name.
	 * If empty user's first name and last name, it will use the username with uniqid() to generate a slug.
	 *
	 * @return string|WP_Error
	 * @since 4.3.4
	 * @version 1.0.1
	 */
	public function generate_pretty_slug( UserModel $userModel ) {
		$user_slug_new = '';

		try {
			// Check if pretty slug already exists, if exists, return it without generating a new one.
			$existing_slug = $userModel->get_slug_link();
			if ( ! empty( $existing_slug ) ) {
				return $existing_slug;
			}

			// Generate pretty slug based on first name and last name.
			$first_name  = $userModel->get_meta_value_by_key( 'first_name', '' );
			$last_name   = $userModel->get_meta_value_by_key( 'last_name', '' );
			$base_source = trim( "{$first_name} {$last_name}" );
			$base_slug   = sanitize_title( $base_source );

			if ( empty( $base_slug ) ) {
				// Shuffle username with uniqid to make it more unique and less guessable, get first 10 characters to make slug shorter.
				$base_slug = substr( str_shuffle( sanitize_title( $userModel->user_login . uniqid() ) ), 0, 10 );
			} else {
				$base_slug = $base_slug . substr( str_shuffle( uniqid() ), 0, 3 );
			}

			// Check slug exists.
			$userModelFind = UserService::instance()->get_user_by_slug_link( $base_slug );
			if ( ! $userModelFind ) {
				$userModel->user_nicename = $base_slug;
				$userModel->save();
			} else {
				// Regenerate slug by adding random string at the end of base slug until it is unique.
				$user_slug_new = $this->generate_pretty_slug( $userModel );
			}
		} catch ( Throwable $e ) {
			return new WP_Error( 'lp_user_slug_generation_failed', $e->getMessage() );
		}

		return $user_slug_new;
	}

	/**
	 * Detected user by slug link.
	 *
	 * @param string $slug_link
	 *
	 * @return false|UserModel
	 * @since 4.3.6
	 * @version 1.0.0
	 */
	public function get_user_by_slug_link( string $slug_link ) {
		// Get from column `user_nicename` in table `wp_users`.
		$wp_user = get_user_by( 'slug', $slug_link );
		if ( ! $wp_user instanceof WP_User ) {
			return false;
		}

		return new UserModel( $wp_user->data );
	}
}
