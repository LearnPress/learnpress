<?php
namespace LearnPress\Background;

use Exception;
use LearnPress;
use LP_Debug;
use LP_Helper;
use Throwable;

defined( 'ABSPATH' ) || exit;

/**
 * Class LPBackgroundTrigger
 * To handle a function that can be run in background
 * Via call class:method with params
 *
 * @since 4.2.8.7
 * @version 1.0.0
 */
class LPBackgroundTrigger extends LPAsyncRequest {
	protected $action = 'background_trigger';
	protected static $instance;

	/**
	 * Method async handle
	 */
	protected function handle() {
		ini_set( 'max_execution_time', 0 );
		try {
			$params = LP_Helper::sanitize_params_submitted( $_POST['params'] ?? false );
			$class  = LP_Helper::sanitize_params_submitted( $_POST['class'] ?? false );
			$method = LP_Helper::sanitize_params_submitted( $_POST['method'] ?? false );

			if ( ! $class || ! $params || ! $method ) {
				throw new Exception( 'Params send on background is invalid' );
			}

			// Security: check callback is registered.
			$allow_callbacks = apply_filters(
				'lp/background/allow_callback',
				[]
			);

			$callBackStr = $class . ':' . $method;
			if ( ! in_array( $callBackStr, $allow_callbacks ) ) {
				throw new Exception( 'Error: callback is not register!' );
			}

			// Check class and method is callable.
			if ( is_callable( [ $class, $method ] ) ) {
				call_user_func( [ $class, $method ], $params );
			} else {
				throw new Exception( 'Error: callback is not callable!' );
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		ini_set( 'max_execution_time', LearnPress::$time_limit_default_of_sever );
		die;
	}

	/**
	 * @return LPBackgroundTrigger
	 */
	public static function instance(): self {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}

// Must run instance to register ajax.
LPBackgroundTrigger::instance();
