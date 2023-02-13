<?php

/**
 * Class LP_API_Base
 *
 * Base class for api
 *
 * @since 3.2.6
 */
abstract class LP_Abstract_API {
	/**
	 * @var string
	 */
	public $version = 'v1';

	/**
	 * @var string
	 */
	public $endpoint = '';

	/**
	 * @var WC_REST_Controller[]|string[]
	 */
	public $controllers = array();

	/**
	 * LP_API_Base constructor.
	 */
	public function __construct() {
		$this->rest_api_init();
	}

	/**
	 * Init REST.
	 *
	 * @since 3.2.6
	 */
	public function rest_api_init() {
		if ( ! class_exists( 'WP_REST_Server' ) ) {
			return;
		}

		if ( ! LP_Helper::isRestApiLP() ) {
			return;
		}

		$this->rest_api_includes();

		add_action( 'rest_api_init', array( $this, 'rest_api_register_routes' ), 10 );

	}

	public function rest_api_includes() {

	}

	/**
	 * Register routes
	 *
	 * @since 3.2.6
	 */
	public function rest_api_register_routes() {
		if ( ! $this->controllers ) {
			return;
		}

		$controllers = array();

		foreach ( $this->controllers as $name => $controller ) {

			if ( is_string( $controller ) ) {
				$name                 = $controller;
				$controllers[ $name ] = new $controller();
			} else {
				$controllers[ $name ] = $controller;
			}

			$controllers[ $name ]->register_routes();
		}

		$this->controllers = $controllers;
	}

	/**
	 * Check permission if user is administrator.
	 *
	 * @return bool
	 */
	public static function check_admin_permission(): bool {
		return current_user_can( ADMIN_ROLE );
	}
}
