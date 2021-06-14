<?php
/**
 * Initialize this version of the REST API.
 *
 * @author Nhamdv <daonham95@gmail.com>
 * @package LP/JWT/RestApi
 */
class LP_Jwt_RestApi {

	protected static $instance = null;

	protected $controllers = array();

	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ), 10 );
	}

	public function register_rest_routes() {
		foreach ( $this->get_rest_namespaces() as $namespace => $controllers ) {
			foreach ( $controllers as $controller_name => $controller_class ) {
				$this->controllers[ $namespace ][ $controller_name ] = new $controller_class();
				$this->controllers[ $namespace ][ $controller_name ]->register_routes();
			}
		}
	}

	protected function get_rest_namespaces() {
		return apply_filters(
			'lp_rest_api_get_rest_namespaces',
			array(
				'learnpress/v1' => $this->get_v1_controllers(),
			)
		);
	}

	protected function get_v1_controllers() {
		return array(
			'courses'   => 'LP_Jwt_Courses_V1_Controller',
			'lessons'   => 'LP_Jwt_Lessons_V1_Controller',
			'quiz'      => 'LP_Jwt_Quiz_V1_Controller',
			'questions' => 'LP_Jwt_Questions_V1_Controller',
			'users'     => 'LP_Jwt_Users_V1_Controller',
		);
	}

	public static function get_path() {
		return dirname( __DIR__ );
	}

	final public static function instance() {
		if ( null === static::$instance ) {
			static::$instance = new static();
		}
		return static::$instance;
	}
}
