<?php

namespace LearnPress\MCP;

use LearnPress\MCP\Auth\AuthContext;
use LearnPress\MCP\Concerns\AbilityExecutors;
use LearnPress\MCP\Concerns\AbilityHelpers;
use LearnPress\MCP\Concerns\AbilitySchemas;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
defined( 'ABSPATH' ) || exit;

/**
 * Registers LearnPress abilities for the WordPress Abilities API.
 *
 * This class is intentionally small and orchestration-focused:
 * - bootstrap lifecycle hooks
 * - register category
 * - define ability manifests
 *
 * Execution logic, schemas, and mapping helpers are split into traits.
 */
class Abilities {

	use AbilitySchemas;
	use AbilityHelpers;
	use AbilityExecutors;

	/**
	 * Abilities API category slug for LearnPress abilities.
	*/
	const CATEGORY = 'learnpress';

	/**
	 * Core MCP adapter route provided by WordPress Abilities API.
 */
	const MCP_ADAPTER_ROUTE = '/mcp/mcp-adapter-default-server';

	/**
		* LearnPress MCP alias route for clients.
	*/
	const MCP_ALIAS_NAMESPACE = 'lp/v1';
	const MCP_ALIAS_ROUTE     = '/mcp';
	/**
	 * Guard flag to avoid registering hooks more than once.
	 *
	 * @var bool
	 */
	protected static $initialized = false;

	/**
	 * Initialize ability registration hooks when Abilities API exists.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized || ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', array( __CLASS__, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( __CLASS__, 'register_abilities' ) );
		add_action( 'rest_api_init', array( __CLASS__, 'register_mcp_alias_route' ), 20 );
		self::$initialized = true;
	}

	/**
	 * Register LearnPress MCP alias endpoint.
	 *
	 * Proxy requests to the default MCP adapter server so clients can use:
	 * /wp-json/lp/v1/mcp
	 *
	 * @return void
	 */
	public static function register_mcp_alias_route(): void {

		register_rest_route(
			self::MCP_ALIAS_NAMESPACE,
			self::MCP_ALIAS_ROUTE,
			array(
				'methods'             => WP_REST_Server::ALLMETHODS,
				'callback'            => array( __CLASS__, 'proxy_mcp_adapter_request' ),
				'permission_callback' => '__return_true',
			)
		);
	}

	/**
	 * Proxy LearnPress MCP alias request to the core MCP adapter route.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response|WP_Error
	 */
	public static function proxy_mcp_adapter_request( WP_REST_Request $request ) {

		$proxy_request = new WP_REST_Request( $request->get_method(), self::MCP_ADAPTER_ROUTE );
		$proxy_request->set_headers( $request->get_headers() );
		$proxy_request->set_query_params( $request->get_query_params() );
		$proxy_request->set_body_params( $request->get_body_params() );
		$proxy_request->set_file_params( $request->get_file_params() );
		$proxy_request->set_body( $request->get_body() );

		return rest_do_request( $proxy_request );
	}
	/**
	 * Register the LearnPress ability category.
	 *
	 * @return void
	 */
	public static function register_category(): void {
		wp_register_ability_category(
			self::CATEGORY,
			array(
				'label'       => __( 'LearnPress LMS', 'learnpress' ),
				'description' => __( 'Read-only abilities for LearnPress LMS data.', 'learnpress' ),
			)
		);
	}

	/**
	 * Register all Phase 1 (read-only) LearnPress abilities.
	 *
	 * @return void
	 */
	public static function register_abilities(): void {
		self::reg(
			'learnpress/get-courses',
			__( 'Get Courses', 'learnpress' ),
			__( 'List courses with optional filters and pagination.', 'learnpress' ),
			self::schema_get_courses_input(),
			self::schema_list_output( self::schema_course_summary() ),
			array( __CLASS__, 'execute_get_courses' )
		);

		self::reg(
			'learnpress/get-course-details',
			__( 'Get Course Details', 'learnpress' ),
			__( 'Get details and curriculum summary for a course.', 'learnpress' ),
			self::schema_required_id( 'course_id' ),
			self::schema_course_detail_output(),
			array( __CLASS__, 'execute_get_course_details' )
		);

		self::reg(
			'learnpress/list-lessons',
			__( 'List Lessons', 'learnpress' ),
			__( 'List lessons in a course with optional filters.', 'learnpress' ),
			self::schema_list_lessons_input(),
			self::schema_list_output( self::schema_lesson_summary() ),
			array( __CLASS__, 'execute_list_lessons' )
		);

		self::reg(
			'learnpress/get-lesson-details',
			__( 'Get Lesson Details', 'learnpress' ),
			__( 'Get lesson details including content, video intro, and materials.', 'learnpress' ),
			self::schema_required_id( 'lesson_id' ),
			self::schema_lesson_detail_output(),
			array( __CLASS__, 'execute_get_lesson_details' )
		);

		self::reg(
			'learnpress/list-quizzes',
			__( 'List Quizzes', 'learnpress' ),
			__( 'List quizzes in a course with pagination.', 'learnpress' ),
			self::schema_list_quizzes_input(),
			self::schema_list_output( self::schema_quiz_summary() ),
			array( __CLASS__, 'execute_list_quizzes' )
		);

		self::reg(
			'learnpress/get-quiz-details',
			__( 'Get Quiz Details', 'learnpress' ),
			__( 'Get quiz details including duration, passing grade, and question count.', 'learnpress' ),
			self::schema_required_id( 'quiz_id' ),
			self::schema_quiz_detail_output(),
			array( __CLASS__, 'execute_get_quiz_details' )
		);

		self::reg(
			'learnpress/get-student-progress',
			__( 'Get Student Progress', 'learnpress' ),
			__( 'Get user progress and results for a course enrollment.', 'learnpress' ),
			self::schema_progress_input(),
			self::schema_object_output( 'progress' ),
			array( __CLASS__, 'execute_get_student_progress' )
		);

		self::reg(
			'learnpress/get-enrollments',
			__( 'Get Enrollments', 'learnpress' ),
			__( 'List course enrollments with optional filters and pagination.', 'learnpress' ),
			self::schema_get_enrollments_input(),
			self::schema_list_output( array( 'type' => 'object' ) ),
			array( __CLASS__, 'execute_get_enrollments' )
		);
	}

	/**
	 * Shared permission callback for LearnPress MCP abilities.
	 *
	 * @param string $ability_name Ability ID.
	 * @param mixed  $input        Ability input.
	 *
	 * @return bool|WP_Error
	 */
	public static function permission_callback( string $ability_name, $input = null ) {

		if ( ! AuthContext::is_api_key_auth() ) {
			return self::error_missing_auth();
		}

		$current_user_id = get_current_user_id();
		$base_capability = self::get_base_capability( $ability_name, $input );

		if ( $current_user_id <= 0 ) {
			return self::error_missing_auth();
		}

		if ( ! current_user_can( $base_capability ) ) {
			return self::error_missing_base_capability( $base_capability );
		}

		$required_scope = self::get_required_scope( $ability_name, $input );
		$granted_scope  = AuthContext::get_permissions();

		if ( ! self::scope_allows( $granted_scope, $required_scope ) ) {
			return self::error_insufficient_scope( $required_scope, $granted_scope );
		}

		return true;
	}
	/**
	 * Register a single ability with common metadata annotations.
	 *
	 * @param string   $name             Ability name.
	 * @param string   $label            Human-readable label.
	 * @param string   $description      Description for clients.
	 * @param array    $input_schema     Input JSON schema.
	 * @param array    $output_schema    Output JSON schema.
	 * @param callable $execute_callback Callback that executes the ability.
	 *
	 * @return void
	 */
	protected static function reg(
		string $name,
		string $label,
		string $description,
		array $input_schema,
		array $output_schema,
		$execute_callback
	): void {
		$permission_callback = static function ( $input = null ) use ( $name ) {
			return self::permission_callback( $name, $input );
		};

		wp_register_ability(
			$name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => self::CATEGORY,
				'execute_callback'    => $execute_callback,
				'permission_callback' => $permission_callback,
				'input_schema'        => $input_schema,
				'output_schema'       => $output_schema,
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'mcp'          => array(
						'public'         => true,
						'type'           => 'tool',
						'required_scope' => self::get_required_scope( $name ),
					),
					'show_in_rest' => true,
				),
			)
		);
	}

	/**
	 * Resolve base capability required for ability execution.
	 *
	 * @param string $ability_name Ability ID.
	 * @param mixed  $input        Ability input payload.
	 *
	 * @return string
	 */
	protected static function get_base_capability( string $ability_name, $input = null ): string {

		$capability = apply_filters( 'learn-press/mcp/api-keys/base-capability', 'manage_options', $ability_name, $input );

		return is_string( $capability ) && '' !== $capability ? $capability : 'manage_options';
	}

	/**
	 * Resolve required key scope for an ability.
	 *
	 * @param string $ability_name Ability ID.
	 * @param mixed  $input        Ability input payload.
	 *
	 * @return string
	 */
	protected static function get_required_scope( string $ability_name, $input = null ): string {

		$default_scopes = array(
			'learnpress/get-courses'          => 'read',
			'learnpress/get-course-details'   => 'read',
			'learnpress/list-lessons'         => 'read',
			'learnpress/get-lesson-details'   => 'read',
			'learnpress/list-quizzes'         => 'read',
			'learnpress/get-quiz-details'     => 'read',
			'learnpress/get-student-progress' => 'read',
			'learnpress/get-enrollments'      => 'read',
		);

		$scope = $default_scopes[ $ability_name ] ?? 'read';
		$scope = apply_filters( 'learn-press/mcp/ability-required-scope', $scope, $ability_name, $input );

		return in_array( $scope, array( 'read', 'write', 'read_write' ), true ) ? $scope : 'read';
	}

	/**
	 * Check if granted key scope satisfies required scope.
	 *
	 * @param string $granted_scope  Scope attached to current API key.
	 * @param string $required_scope Scope required by the ability.
	 *
	 * @return bool
	 */
	protected static function scope_allows( string $granted_scope, string $required_scope ): bool {

		if ( 'read_write' === $granted_scope ) {
			return true;
		}

		return $granted_scope === $required_scope;
	}

	/**
	 * Error for missing/invalid authentication.
	 *
	 * @param string $message Optional custom error message.
	 *
	 * @return WP_Error
	 */
	protected static function error_missing_auth( string $message = '' ): WP_Error {

		if ( '' === $message ) {
			$message = __( 'Missing or invalid MCP authentication.', 'learnpress' );
		}

		return new WP_Error(
			'learnpress_mcp_missing_auth',
			$message,
			array( 'status' => 401 )
		);
	}

	/**
	 * Error for base capability failure.
	 *
	 * @param string $capability Required capability name.
	 *
	 * @return WP_Error
	 */
	protected static function error_missing_base_capability( string $capability ): WP_Error {

		return new WP_Error(
			'learnpress_mcp_missing_base_capability',
			sprintf(
				/* translators: %s: capability. */
				__( 'Current user does not have required base capability: %s.', 'learnpress' ),
				$capability
			),
			array( 'status' => 403 )
		);
	}

	/**
	 * Error for scope mismatch.
	 *
	 * @param string $required_scope Required scope for the ability.
	 * @param string $granted_scope  Scope granted by authenticated API key.
	 *
	 * @return WP_Error
	 */
	protected static function error_insufficient_scope( string $required_scope, string $granted_scope ): WP_Error {

		return new WP_Error(
			'learnpress_mcp_insufficient_scope',
			sprintf(
				/* translators: 1: required scope, 2: granted scope. */
				__( 'API key scope is insufficient. Required: %1$s. Granted: %2$s.', 'learnpress' ),
				$required_scope,
				$granted_scope
			),
			array( 'status' => 403 )
		);
	}
}
