<?php

/**
 * Class LP_User_Item_Ajax
 *
 * @since 3.2.0
 */
class LP_User_Item_Ajax {
	/**
	 * @var LP_User_Item_Course
	 */
	protected static $course_data = null;

	/**
	 * @var LP_User
	 */
	protected static $user = null;

	/**
	 * Init
	 */
	public static function init() {
		$ajaxEvents = array(
			'complete-course-item'
		);

		foreach ( $ajaxEvents as $action => $callback ) {

			if ( is_numeric( $action ) ) {
				$action = $callback;
			}

			$actions = LP_Request::parse_action( $action );
			$method  = $actions['action'];

			if ( ! is_callable( $callback ) ) {
				$method   = preg_replace( '/-/', '_', $method );
				$callback = array( __CLASS__, $method );
			}

			LP_Request::register_ajax( $action, $callback );
		}

	}

	public static function complete_course_item() {

		self::verify();
		LP_Debug::startTransaction();
		$itemId      = LP_Request::get_int( 'itemId' );
		$course_data = self::$course_data;
		$response    = array();

		if ( $item = $course_data->get_item( $itemId ) ) {

			$course = $course_data->get_course();
			$it     = $course->get_item( $itemId );

			$response['result']  = $item->complete();
			$response['classes'] = array_values( $it->get_class() );
		}
		LP_Debug::rollbackTransaction();
		learn_press_send_json( $response );
	}

	public static function verify() {
		$courseId = LP_Request::get_int( 'courseId' );
		$user     = learn_press_get_current_user();

		if ( ! wp_verify_nonce( LP_Request::get_string( 'namespace' ), 'lp-' . $user->get_id() . '-' . $courseId ) ) {
			die( - 1 );
		}

		$course = learn_press_get_course( $courseId );

		if ( ! $course ) {
			die( - 2 );
		}

		self::$user        = $user;
		self::$course_data = $user->get_course_data( $courseId );
	}

}

LP_User_Item_Ajax::init();