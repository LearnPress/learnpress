<?php

/**
 * Class LP_REST_Authentication
 *
 * @since 3.2.6
 */
class LP_REST_Authentication {

	/**
	 * @var string
	 */
	protected static $wp_rest_nonce = '';

	/**
	 * @var int
	 */
	protected static $wp_current_user_id = 0;

	public function __construct() {
		add_action( 'rest_authentication_errors', array( $this, 'rest_cookie_check_errors' ), 0 );
	}

	/**
	 * @param $result
	 *
	 * @return mixed
	 */
	public function rest_cookie_check_errors( $result ) {
		if ( ! empty( $result ) ) {
			return $result;
		}

		if ( is_user_logged_in() ) {
			self::$wp_rest_nonce      = wp_create_nonce( 'wp_rest' );
			self::$wp_current_user_id = get_current_user_id();
		}

		return $result;
	}

	/**
	 * @return string
	 */
	public static function get_wp_rest_nonce() {
		return self::$wp_rest_nonce;
	}

	/**
	 * @return int
	 */
	public static function get_wp_user_id() {
		return self::$wp_current_user_id;
	}

	/**
	 * Check permission of a user on a post type.
	 *
	 * @param string $post_type
	 * @param string $permission
	 * @param int    $user_id
	 *
	 * @return boolean
	 */
	public static function check_post_permissions( $post_type, $permission = 'read', $user_id = 0 ) {

		if ( ! $user_id ) {
			$user_id = self::get_wp_user_id();
		}

		$permissions = array(
			'read'   => 'read_private_posts',
			'create' => 'publish_posts',
			'edit'   => 'edit_post',
			'delete' => 'delete_post',
			'batch'  => 'edit_others_posts',
		);

		if ( 'revision' === $post_type ) {
			$user_permission = false;
		} else {
			$cap              = $permissions[ $permission ];
			$post_type_object = get_post_type_object( $post_type );
			$user_permission  = current_user_can( $post_type_object->cap->$cap, $user_id );
		}

		return apply_filters( 'learn-press/rest/check-permission', $user_permission, $permission, $user_id, $post_type );
	}

	/**
	 * Check permission if user is logged in.
	 *
	 * @return bool
	 */
	public static function check_logged_in_permission() {
		return ! ! self::get_wp_user_id();
	}

	/**
	 * Check permission if user is administrator.
	 *
	 * @return bool
	 */
	public static function check_admin_permission() {
		$user = learn_press_get_user( self::get_wp_user_id() );

		return $user && $user->is_admin();
	}
}

return new LP_REST_Authentication();