<?php
/**
 * REST API: LP_Jwt_Auth
 *
 * @package LPJWTAuth
 * @since 1.0.0
 * @author Nhamdv <daonham95@gmail.com>
 */

class LP_Jwt_Auth {
	public static $_instance = null;

	protected $name;

	protected $version;

	public function __construct() {
		$this->name    = 'learnpress';
		$this->version = 'v1';

		// Is enable rest api?
		// if ( LP_Settings::instance()->get( 'enable_jwt_rest_api' ) !== 'yes' ) {
		// return;
		// }

		$this->includes();
		$this->define_hooks();
	}

	private function includes() {
		// JWT Classes.
		if ( ! class_exists( '\Firebase\JWT\JWT' ) ) {
			foreach ( glob( LP_PLUGIN_PATH . 'inc/jwt/includes/php-jwt/*.php' ) as $filename ) {
				require_once $filename;
			}
		}

		// Authentic.
		require_once LP_PLUGIN_PATH . 'inc/jwt/includes/class-jwt-public.php';

		// Include Rest API.
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-posts-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-courses-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-lessons-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-quiz-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-questions-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-users-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-course-category-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-sections-v1-controller.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/version1/class-lp-rest-section-items-v1-controller.php';

		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/lp-rest-function.php';
		require_once LP_PLUGIN_PATH . 'inc/jwt/rest-api/class-rest-api.php';
	}

	private function define_hooks() {
		// Authentic.
		$public = new LP_Jwt_Public( $this->name, $this->version );

		add_action( 'rest_api_init', array( $public, 'register_routes' ) );
		add_filter( 'rest_api_init', array( $public, 'add_cors_support' ) );
		add_filter( 'rest_pre_dispatch', array( $public, 'rest_pre_dispatch' ), 10, 2 );
		add_filter( 'determine_current_user', array( $public, 'determine_current_user' ), 30 );

		// Rest API
		add_action( 'init', array( $this, 'load_rest_api' ) );
	}

	public function load_rest_api() {
		LP_Jwt_RestApi::instance()->init();
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new LP_Jwt_Auth();
		}

		return self::$_instance;
	}
}

LP_Jwt_Auth::instance();
