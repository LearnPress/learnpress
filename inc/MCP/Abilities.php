<?php

namespace LearnPress\MCP;

use LearnPress\MCP\Auth\AuthContext;
use LearnPress\MCP\Domain\CourseTools;
use LearnPress\MCP\Domain\SectionTools;
use LearnPress\MCP\Domain\LessonTools;
use LearnPress\MCP\Domain\QuizTools;
use LearnPress\MCP\Domain\QuestionTools;
use LearnPress\MCP\Domain\EnrollmentTools;
use LearnPress\MCP\Schemas\CourseSchemas;
use LearnPress\MCP\Schemas\SectionSchemas;
use LearnPress\MCP\Schemas\LessonSchemas;
use LearnPress\MCP\Schemas\QuizSchemas;
use LearnPress\MCP\Schemas\QuestionSchemas;
use LearnPress\MCP\Schemas\EnrollmentSchemas;
use LearnPress\MCP\Support\Errors;
use LearnPress\MCP\Support\Pagination;
use LearnPress\MCP\Support\Schemas;
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
	 * Initialize ability registration hooks when the WordPress Abilities API runtime is available.
	 *
	 * @return void
	 */
	public static function init(): void {
		if ( self::$initialized
			|| ! function_exists( 'wp_register_ability' )
			|| ! function_exists( 'wp_register_ability_category' ) ) {
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
			CourseSchemas::get_courses_input(),
			Pagination::list_output( CourseSchemas::course_summary() ),
			array( CourseTools::class, 'get_courses' )
		);

		self::reg(
			'learnpress/get-course-details',
			__( 'Get Course Details', 'learnpress' ),
			__( 'Get details and curriculum summary for a course.', 'learnpress' ),
			Schemas::required_id( 'course_id' ),
			Schemas::object_output( 'course' ),
			array( CourseTools::class, 'get_course_details' )
		);

		self::reg(
			'learnpress/list-lessons',
			__( 'List Lessons', 'learnpress' ),
			__( 'List lessons in a course with optional filters.', 'learnpress' ),
			LessonSchemas::list_lessons_input(),
			Pagination::list_output( LessonSchemas::lesson_summary() ),
			array( LessonTools::class, 'list_lessons' )
		);

		self::reg(
			'learnpress/get-lesson-details',
			__( 'Get Lesson Details', 'learnpress' ),
			__( 'Get lesson details including content, video intro, and materials.', 'learnpress' ),
			Schemas::required_id( 'lesson_id' ),
			Schemas::object_output( 'lesson' ),
			array( LessonTools::class, 'get_lesson_details' )
		);

		self::reg(
			'learnpress/list-quizzes',
			__( 'List Quizzes', 'learnpress' ),
			__( 'List quizzes in a course with pagination.', 'learnpress' ),
			QuizSchemas::list_quizzes_input(),
			Pagination::list_output( QuizSchemas::quiz_summary() ),
			array( QuizTools::class, 'list_quizzes' )
		);

		self::reg(
			'learnpress/get-quiz-details',
			__( 'Get Quiz Details', 'learnpress' ),
			__( 'Get quiz details including duration, passing grade, and question count.', 'learnpress' ),
			Schemas::required_id( 'quiz_id' ),
			Schemas::object_output( 'quiz' ),
			array( QuizTools::class, 'get_quiz_details' )
		);

		self::reg(
			'learnpress/get-student-progress',
			__( 'Get Student Progress', 'learnpress' ),
			__( 'Get user progress and results for a course enrollment.', 'learnpress' ),
			EnrollmentSchemas::progress_input(),
			Schemas::object_output( 'progress' ),
			array( EnrollmentTools::class, 'get_student_progress' )
		);

		self::reg(
			'learnpress/get-enrollments',
			__( 'Get Enrollments', 'learnpress' ),
			__( 'List course enrollments with optional filters and pagination.', 'learnpress' ),
			EnrollmentSchemas::get_enrollments_input(),
			Pagination::list_output( array( 'type' => 'object' ) ),
			array( EnrollmentTools::class, 'get_enrollments' )
		);

		self::register_write_abilities();
	}

	/**
	 * Register all Phase 2 write abilities (course, section, lesson, quiz,
	 * quiz question, and enrollment management).
	 *
	 * Domain logic lives in focused `LearnPress\MCP\Domain` executors and
	 * `LearnPress\MCP\Schemas` providers, not in this orchestration class.
	 *
	 * @return void
	 */
	protected static function register_write_abilities(): void {
		// Course tools.
		self::reg(
			'learnpress/create-course',
			__( 'Create Course', 'learnpress' ),
			__( 'Create a new LearnPress course.', 'learnpress' ),
			CourseSchemas::create_input(),
			CourseSchemas::write_output(),
			array( CourseTools::class, 'create_course' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/update-course',
			__( 'Update Course', 'learnpress' ),
			__( 'Update an existing LearnPress course.', 'learnpress' ),
			CourseSchemas::update_input(),
			CourseSchemas::write_output(),
			array( CourseTools::class, 'update_course' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/delete-course',
			__( 'Delete Course', 'learnpress' ),
			__( 'Move a LearnPress course to trash (reversible).', 'learnpress' ),
			CourseSchemas::delete_input(),
			CourseSchemas::delete_output(),
			array( CourseTools::class, 'delete_course' ),
			self::destructive_annotations()
		);

		// Section tools.
		self::reg(
			'learnpress/create-section',
			__( 'Create Section', 'learnpress' ),
			__( 'Create a curriculum section in a course.', 'learnpress' ),
			SectionSchemas::create_input(),
			SectionSchemas::write_output(),
			array( SectionTools::class, 'create_section' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/update-section',
			__( 'Update Section', 'learnpress' ),
			__( 'Update a curriculum section in a course.', 'learnpress' ),
			SectionSchemas::update_input(),
			SectionSchemas::write_output(),
			array( SectionTools::class, 'update_section' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/delete-section',
			__( 'Delete Section', 'learnpress' ),
			__( 'Remove a section relationship while preserving its lessons/quizzes (reversible).', 'learnpress' ),
			SectionSchemas::delete_input(),
			SectionSchemas::delete_output(),
			array( SectionTools::class, 'delete_section' ),
			self::destructive_annotations()
		);

		// Lesson tools.
		self::reg(
			'learnpress/create-lesson',
			__( 'Create Lesson', 'learnpress' ),
			__( 'Create a lesson and assign it to a course section.', 'learnpress' ),
			LessonSchemas::create_input(),
			LessonSchemas::write_output(),
			array( LessonTools::class, 'create_lesson' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/update-lesson',
			__( 'Update Lesson', 'learnpress' ),
			__( 'Update an existing lesson.', 'learnpress' ),
			LessonSchemas::update_input(),
			LessonSchemas::write_output(),
			array( LessonTools::class, 'update_lesson' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/delete-lesson',
			__( 'Delete Lesson', 'learnpress' ),
			__( 'Move a lesson to trash and remove it from the curriculum (reversible).', 'learnpress' ),
			LessonSchemas::delete_input(),
			LessonSchemas::delete_output(),
			array( LessonTools::class, 'delete_lesson' ),
			self::destructive_annotations()
		);

		// Quiz tools.
		self::reg(
			'learnpress/create-quiz',
			__( 'Create Quiz', 'learnpress' ),
			__( 'Create a quiz and assign it to a course section.', 'learnpress' ),
			QuizSchemas::create_input(),
			QuizSchemas::write_output(),
			array( QuizTools::class, 'create_quiz' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/update-quiz',
			__( 'Update Quiz', 'learnpress' ),
			__( 'Update an existing quiz and its settings.', 'learnpress' ),
			QuizSchemas::update_input(),
			QuizSchemas::write_output(),
			array( QuizTools::class, 'update_quiz' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/delete-quiz',
			__( 'Delete Quiz', 'learnpress' ),
			__( 'Move a quiz to trash and remove it from the curriculum (reversible).', 'learnpress' ),
			QuizSchemas::delete_input(),
			QuizSchemas::delete_output(),
			array( QuizTools::class, 'delete_quiz' ),
			self::destructive_annotations()
		);

		// Quiz question tools.
		self::reg(
			'learnpress/add-quiz-question',
			__( 'Add Quiz Question', 'learnpress' ),
			__( 'Create a question and add it to a quiz.', 'learnpress' ),
			QuestionSchemas::add_input(),
			QuestionSchemas::add_output(),
			array( QuestionTools::class, 'add_quiz_question' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/update-quiz-question',
			__( 'Update Quiz Question', 'learnpress' ),
			__( 'Update a quiz question and its answers.', 'learnpress' ),
			QuestionSchemas::update_input(),
			QuestionSchemas::write_output(),
			array( QuestionTools::class, 'update_quiz_question' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/delete-quiz-question',
			__( 'Delete Quiz Question', 'learnpress' ),
			__( 'Remove a question from a quiz while preserving the question post (reversible).', 'learnpress' ),
			QuestionSchemas::delete_input(),
			QuestionSchemas::delete_output(),
			array( QuestionTools::class, 'delete_quiz_question' ),
			self::destructive_annotations()
		);

		// Enrollment tools.
		self::reg(
			'learnpress/enroll-student',
			__( 'Enroll Student', 'learnpress' ),
			__( 'Manually enroll a student in a course.', 'learnpress' ),
			EnrollmentSchemas::enroll_input(),
			EnrollmentSchemas::enroll_output(),
			array( EnrollmentTools::class, 'enroll_student' ),
			self::write_annotations()
		);
		self::reg(
			'learnpress/update-enrollment',
			__( 'Update Enrollment', 'learnpress' ),
			__( 'Update enrollment status and learning result metadata.', 'learnpress' ),
			EnrollmentSchemas::update_input(),
			EnrollmentSchemas::write_output(),
			array( EnrollmentTools::class, 'update_enrollment' ),
			self::write_annotations()
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
			return Errors::missing_auth();
		}

		$current_user_id = get_current_user_id();
		$base_capability = self::get_base_capability( $ability_name, $input );

		if ( $current_user_id <= 0 ) {
			return Errors::missing_auth();
		}

		if ( ! current_user_can( $base_capability ) ) {
			return Errors::missing_capability( $base_capability );
		}

		$required_scope = self::get_required_scope( $ability_name, $input );
		$granted_scope  = AuthContext::get_permissions();

		if ( ! self::scope_allows( $granted_scope, $required_scope ) ) {
			return Errors::insufficient_scope( $required_scope, $granted_scope );
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
	 * @param array    $annotations      Optional MCP annotation overrides
	 *                                   (readonly, destructive, idempotent).
	 *                                   Read tools keep the read-only defaults.
	 *
	 * @return void
	 */
	protected static function reg(
		string $name,
		string $label,
		string $description,
		array $input_schema,
		array $output_schema,
		$execute_callback,
		array $annotations = array()
	): void {
		$permission_callback = static function ( $input = null ) use ( $name ) {
			return self::permission_callback( $name, $input );
		};

		$annotations = array_merge(
			array(
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			),
			$annotations
		);

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
					'annotations'  => $annotations,
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
	 * Annotation set for create/update write tools.
	 *
	 * @return array
	 */
	protected static function write_annotations(): array {
		return array(
			'readonly'    => false,
			'destructive' => false,
			'idempotent'  => false,
		);
	}

	/**
	 * Annotation set for destructive (delete) write tools.
	 *
	 * @return array
	 */
	protected static function destructive_annotations(): array {
		return array(
			'readonly'    => false,
			'destructive' => true,
			'idempotent'  => false,
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
			// Phase 2 write tools require write (or read_write) scope.
			'learnpress/create-course'        => 'write',
			'learnpress/update-course'        => 'write',
			'learnpress/delete-course'        => 'write',
			'learnpress/create-section'       => 'write',
			'learnpress/update-section'       => 'write',
			'learnpress/delete-section'       => 'write',
			'learnpress/create-lesson'        => 'write',
			'learnpress/update-lesson'        => 'write',
			'learnpress/delete-lesson'        => 'write',
			'learnpress/create-quiz'          => 'write',
			'learnpress/update-quiz'          => 'write',
			'learnpress/delete-quiz'          => 'write',
			'learnpress/add-quiz-question'    => 'write',
			'learnpress/update-quiz-question' => 'write',
			'learnpress/delete-quiz-question' => 'write',
			'learnpress/enroll-student'       => 'write',
			'learnpress/update-enrollment'    => 'write',
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
}
