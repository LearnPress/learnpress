<?php

namespace LearnPress\MCP;

use LearnPress\MCP\Concerns\AbilityExecutors;
use LearnPress\MCP\Concerns\AbilityHelpers;
use LearnPress\MCP\Concerns\AbilitySchemas;

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
	 * Capability required to execute LearnPress MCP abilities.
	 */
	const CAP = 'lp_mcp_access';

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
		self::$initialized = true;
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
	 * Shared permission callback for all LearnPress MCP abilities.
	 *
	 * @param mixed $input Ability input (unused here, signature kept for API contract).
	 *
	 * @return bool
	 */
	public static function permission_callback( $input = null ): bool {
		unset( $input );
		return current_user_can( self::CAP );
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
		wp_register_ability(
			$name,
			array(
				'label'               => $label,
				'description'         => $description,
				'category'            => self::CATEGORY,
				'execute_callback'    => $execute_callback,
				'permission_callback' => array( __CLASS__, 'permission_callback' ),
				'input_schema'        => $input_schema,
				'output_schema'       => $output_schema,
				'meta'                => array(
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
					),
					'show_in_rest' => true,
				),
			)
		);
	}
}
