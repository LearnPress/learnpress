<?php

/**
 * Class LP_Notifications
 *
 * @since 3.2.0
 */
class LP_Notifications {
	/**
	 * @var LP_Notifications
	 */
	protected static $instance = null;

	/**
	 * LP_Notifications constructor.
	 */
	public function __construct() {
		if ( ! LP()->session->get( 'notifications' ) ) {
			LP()->session->set( 'notifications', array() );
		}
	}

	/**
	 * Add new notification into queue
	 *
	 * @param string $message
	 * @param string $type
	 * @param string $uid
	 */
	public function add( $message, $type = 'success', $uid = '' ) {

		if ( ! $uid ) {
			$uid = learn_press_uniqid();
		}

		$messages         = $this->get();
		$messages[ $uid ] = array( 'message' => $message, 'type' => $type, '_uid' => $uid );
		LP()->session->set( 'notifications', $messages );
	}

	/**
	 * @param string $uid
	 * @param bool   $clear
	 *
	 * @return array
	 */
	public function get( $uid = '', $clear = false ) {
		$notis = LP()->session->get( 'notifications' );

		if ( $notis ) {
			if ( $uid && ! empty( $notis[ $uid ] ) ) {
				$notis = $notis[ $uid ];
			} else {
				$notis = array_values( $notis );
			}

			if ( $clear ) {
				$this->clear();
			}
		}

		return $notis;
	}

	public function clear() {
		LP()->session->remove( 'notifications' );
	}

	public function __toString() {
		return wp_json_encode( $this->get() );
	}

	/**
	 * @return LP_Notifications
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

add_action( 'learn-press-ready', array( 'LP_Notifications', 'instance' ) );