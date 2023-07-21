<?php
/**
 * Class LP_Request
 *
 * Process actions by request param
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class LP_Request
 */
class LP_Request {

	/**
	 * @var bool
	 */
	public static $ajax_shutdown = true;

	/**
	 * Constructor
	 */
	public static function init() {

		self::$ajax_shutdown = learn_press_is_ajax();

		if ( is_admin() ) {
			add_action( 'init', array( __CLASS__, 'process_request' ), 50 );
		} else {
			add_action( 'template_include', array( __CLASS__, 'process_request' ), 50 );
		}

		self::register( 'lp-ajax', array( __CLASS__, 'do_ajax' ) );

		add_action( 'wp_loaded', array( 'LP_Forms_Handler', 'init' ), 10 );
	}

	/**
	 * Get value by key from $_REQUEST
	 *
	 * @param string $key
	 * @param mixed $default
	 * @param string $sanitize_type
	 * @param string $method
	 *
	 * @since 4.1.7.2
	 * @version 1.0.0
	 * @return array|float|int|string
	 */
	public static function get_param( string $key, $default = '', string $sanitize_type = 'text', string $method = '' ) {
		switch ( strtolower( $method ) ) {
			case 'post':
				$values = $_POST ?? [];
				break;
			case 'get':
				$values = $_GET ?? [];
				break;
			default:
				$values = $_REQUEST ?? [];
		}

		return LP_Helper::sanitize_params_submitted( $values[ $key ] ?? $default, $sanitize_type );
	}

	public static function get_header() {
		ob_start();
	}

	/**
	 * Process actions
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public static function process_request( $template ) {
		if ( ! empty( $_REQUEST ) ) {
			foreach ( $_REQUEST as $key => $value ) {
				$key   = LP_Helper::sanitize_params_submitted( $key );
				$value = LP_Helper::sanitize_params_submitted( $value );
				do_action( 'learn_press_request_handler_' . $key, $value, $key );
			}
		}

		return $template;
	}

	/**
	 * Register new request
	 *
	 * @param string|array $action
	 * @param mixed $function
	 * @param int $priority
	 */
	public static function register( $action, $function = '', $priority = 5 ) {
		if ( is_array( $action ) ) {
			foreach ( $action as $item ) {
				$item = wp_parse_args(
					$item,
					array(
						'action'   => '',
						'callback' => '',
						'priority' => 5,
					)
				);
				if ( ! $item['action'] || ! $item['callback'] ) {
					continue;
				}

				list( $action, $callback, $priority ) = array_values( $item );
				add_action( 'learn_press_request_handler_' . $action, $callback, $priority, 2 );
			}
		} else {
			add_action( 'learn_press_request_handler_' . $action, $function, $priority, 2 );
		}
	}

	/**
	 * Register ajax action.
	 * Add ajax into queue by an action and then LP check if there is a request
	 * with key lp-ajax=action-name then do the action "action-name". By default,
	 * ajax action is called if user is logged. But, it can be call in case user
	 * is not logged in if the action is passed with key :nopriv at the end.
	 *
	 * E.g:
	 *      + Only for user is logged in
	 *      LP_Request::register_ajax( 'action', 'function_to_call', 5 )
	 *
	 *      + For guest
	 *      LP_Request::register_ajax( 'action:nopriv', 'function_to_call', 5 )
	 *
	 * @param string $action
	 * @param mixed $function
	 * @param int $priority
	 */
	public static function register_ajax( $action, $function, $priority = 5 ) {
		if ( is_array( $action ) ) {
			foreach ( $action as $args ) {
				if ( ! empty( $args['action'] ) && ! empty( $args['callback'] ) ) {
					self::register_ajax( $args['action'], $args['callback'], ! empty( $args['priority'] ) ? $args['priority'] : 5 );
				}
			}

			return;
		}
		$actions = self::parse_action( $action );

		if ( isset( $actions['nonce'] ) ) {
			add_filter( 'learn-press/ajax/verify-none/' . $actions['action'], array( __CLASS__, 'verify_nonce' ) );
		}

		add_action( 'learn-press/ajax/' . $actions['action'], $function, $priority );

		/**
		 * No requires logged in?
		 */
		if ( isset( $actions['nopriv'] ) ) {
			add_action( 'learn-press/ajax/no-priv/' . $actions['action'], $function, $priority );
		}
	}

	/**
	 * Do ajax if there is a 'lp-ajax' in $_REQUEST
	 *
	 * @param string $action
	 */
	public static function do_ajax( $action ) {

		if ( ! defined( 'LP_DOING_AJAX' ) ) {
			define( 'LP_DOING_AJAX', true );
		}

		LP_Gateways::instance()->get_available_payment_gateways();

		if ( has_filter( 'learn-press/ajax/verify-none/' . $action ) ) {
			if ( ! self::verify_nonce( $action ) ) {
				die( '0' );
			}
		}

		if ( is_user_logged_in() ) {
			$has_action = has_action( 'learn-press/ajax/' . $action );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/ajax/' . $action );
		} else {

			$has_action = has_action( 'learn-press/ajax/no-priv/' . $action );

			/**
			 * @since 3.0.0
			 */
			do_action( 'learn-press/ajax/no-priv/' . $action );
		}

		if ( $has_action && self::$ajax_shutdown ) {
			die( '{END_AJAX}' );
		}
	}

	public static function verify_nonce( $action, $nonce = '' ) {
		return wp_verify_nonce( $nonce ? $nonce : self::get_string( "{$action}-nonce" ), $action );
	}

	public static function parse_action( $action ) {
		$args    = explode( ':', $action );
		$actions = array(
			'action' => $args[0],
		);

		if ( sizeof( $args ) > 1 ) {
			array_shift( $args );
			foreach ( $args as $arg ) {
				$actions[ $arg ] = $arg;
			}
		}

		return $actions;
	}

	/**
	 * Get variable value from Server environment.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @param string $type
	 * @param string $env
	 *
	 * @return mixed
	 * @deprecated 4.2.1
	 */
	public static function get( $var, $default = false, $type = '', $env = 'request' ) {
		switch ( strtolower( $env ) ) {
			case 'post':
				$env = LP_Helper::sanitize_params_submitted( $_POST );
				break;
			case 'get':
				$env = LP_Helper::sanitize_params_submitted( $_GET );
				break;
			case 'put':
			case 'delete':
				$data = file_get_contents( 'php://input' );
				$env  = array();
				parse_str( $data, $env );
				break;
			case 'wp':
				global $wp;
				$env = $wp->query_vars;
				break;
			default:
				$env = LP_Helper::sanitize_params_submitted( $_REQUEST );
		}

		$return = array_key_exists( $var, $env ) ? $env[ $var ] : $default;
		switch ( $type ) {
			case 'int':
				$return = intval( $return );
				break;
			case 'float':
				$return = floatval( $return );
				break;
			case 'bool':
				try {
					$value = strtolower( $return );
				} catch ( Exception $e ) {
					$value = $return;
				}

				if ( in_array( $value, array( 'true', 'yes', 'on', 'enable' ) ) ) {
					$return = true;
				} elseif ( in_array( $value, array( 'false', 'no', 'off', 'disable' ) ) ) {
					$return = false;
				} else {
					$return = ! ! $return;
				}
				break;
			case 'string':
				$return = (string) $return;
				break;
			case 'array':
				$return = $return ? (array) $return : array();
				break;
		}

		LP_Helper::sanitize_params_submitted( $return );

		return $return;
	}

	/**
	 * Get value int from environment.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @param string $env
	 *
	 * @return int
	 */
	public static function get_int( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'int', $env );
	}

	/**
	 * Get value float from environment.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @param string $env
	 *
	 * @return float
	 */
	public static function get_float( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'float', $env );
	}

	/**
	 * Get value bool from environment.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @param string $env
	 *
	 * @return bool
	 */
	public static function get_bool( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'bool', $env );
	}

	/**
	 * Get value string from environment.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @param string $env
	 *
	 * @return string
	 */
	public static function get_string( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'string', $env );
	}

	/**
	 * Get value array from environment.
	 *
	 * @param string $var
	 * @param mixed $default
	 * @param string $env
	 *
	 * @return array
	 */
	public static function get_array( $var, $default = false, $env = 'request' ) {
		return self::get( $var, $default, 'array', $env );
	}

	/**
	 * Get value from $_POST.
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return mixed
	 */
	public static function get_post( $var, $default = false, $type = '' ) {
		return self::get( $var, $default, $type, 'post' );
	}

	/**
	 * Get value int from $_POST.
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return int
	 */
	public static function get_post_int( $var, $default = false ) {
		return self::get_post( $var, $default, 'int' );
	}

	/**
	 * Get value float from $_POST.
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return float
	 */
	public static function get_post_float( $var, $default = false ) {
		return self::get_post( $var, $default, 'float' );
	}

	/**
	 * Get value bool from $_POST.
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return bool
	 */
	public static function get_post_bool( $var, $default = false ) {
		return self::get_post( $var, $default, 'bool' );
	}

	/**
	 * Get value string from $_POST.
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return string
	 */
	public static function get_post_string( $var, $default = false ) {
		return self::get_post( $var, $default, 'string' );
	}

	/**
	 * Get value array from $_POST.
	 *
	 * @param string $var
	 * @param mixed $default
	 *
	 * @return array
	 */
	public static function get_post_array( $var, $default = false ) {
		return self::get_post( $var, $default, 'array' );
	}

	/**
	 * Get email field and validate.
	 *
	 * @param string $var
	 * @param bool $default
	 *
	 * @return bool|string
	 */
	public static function get_email( $var, $default = false ) {
		$email = self::get_string( $var, $default );
		if ( ! is_email( $email ) ) {
			$email = $default;
		}

		return $email;
	}

	/**
	 * Get a batch of params from request into an array.
	 *
	 * @return array
	 */
	public static function get_list() {
		if ( func_num_args() < 1 ) {
			return array();
		}

		$list = array();
		foreach ( func_get_args() as $key ) {
			$list[ $key ] = self::get( $key );
		}

		return $list;
	}

	/**
	 * Parse string from request to array by comma.
	 *
	 * @param string $var
	 * @param string $separator
	 *
	 * @return array
	 */
	public static function get_list_array( $var, $separator = ',' ) {
		$list = self::get_string( $var );

		if ( ! $list ) {
			return array();
		}

		if ( $separator === ',' ) {
			$list = preg_split( '!\s?,\s?!', $list );
		} else {
			$list = explode( $separator, $list );
		}

		return array_map( 'trim', $list );
	}

	/**
	 * Get param 'redirect' in request.
	 *
	 * @param string $default
	 *
	 * @return string
	 */
	public static function get_redirect( $default = '' ) {
		$redirect = self::get_string( 'redirect' );

		if ( $redirect ) {
			$redirect = urldecode( $redirect );
		} else {
			$redirect = $default;
		}

		return $redirect;
	}

	public static function get_cookie( $name, $def = false, $global = false ) {
		if ( $global ) {
			return $_COOKIE[ $name ] ?? $def;
		}

		$cookie = isset( $_COOKIE['LP'] ) ? (array) json_decode( stripslashes( $_COOKIE['LP'] ) ) : array();

		return $cookie[ $name ] ?? $def;
	}

	/*public static function set_cookie( $name, $value, $expires = '', $domain = '', $path = '', $secure = false ) {
		if ( func_num_args() > 2 ) {
			learn_press_setcookie( $name, $value, $expires, $secure );
		} else {
			$cookie = isset( $_COOKIE['LP'] ) ? maybe_unserialize( $_COOKIE['LP'] ) : array();

			$cookie[ $name ] = $value;
			learn_press_setcookie( 'LP', $value );
		}
	}*/
}

LP_Request::init();

/**
 * @deprecated 4.1.7.3
 * using in the addon course review 4.0.3, wishlist 4.0.3
 */
class LP_Request_Handler extends LP_Request {

}
