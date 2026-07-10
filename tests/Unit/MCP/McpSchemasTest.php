<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\MCP;

use LearnPress\MCP\Schemas\CourseSchemas;
use LearnPress\MCP\Schemas\EnrollmentSchemas;
use LearnPress\MCP\Schemas\LessonSchemas;
use LearnPress\MCP\Schemas\QuestionSchemas;
use LearnPress\MCP\Schemas\QuizSchemas;
use LearnPress\MCP\Schemas\SectionSchemas;
use LearnPress\MCP\Support\Schemas;
use LearnPress\MCP\Support\Validator;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Schema contract tests for Phase 2 MCP write tools.
 */
class McpSchemasTest extends BrainMonkeyTestCase {

	/**
	 * Every write input schema.
	 *
	 * @return array
	 */
	private function input_schemas(): array {
		return array(
			'create-course'        => CourseSchemas::create_input(),
			'update-course'        => CourseSchemas::update_input(),
			'delete-course'        => CourseSchemas::delete_input(),
			'create-section'       => SectionSchemas::create_input(),
			'update-section'       => SectionSchemas::update_input(),
			'delete-section'       => SectionSchemas::delete_input(),
			'create-lesson'        => LessonSchemas::create_input(),
			'update-lesson'        => LessonSchemas::update_input(),
			'delete-lesson'        => LessonSchemas::delete_input(),
			'create-quiz'          => QuizSchemas::create_input(),
			'update-quiz'          => QuizSchemas::update_input(),
			'delete-quiz'          => QuizSchemas::delete_input(),
			'add-quiz-question'    => QuestionSchemas::add_input(),
			'update-quiz-question' => QuestionSchemas::update_input(),
			'delete-quiz-question' => QuestionSchemas::delete_input(),
			'enroll-student'       => EnrollmentSchemas::enroll_input(),
			'update-enrollment'    => EnrollmentSchemas::update_input(),
		);
	}

	#[Test]
	public function all_write_input_schemas_reject_unknown_properties(): void {
		foreach ( $this->input_schemas() as $name => $schema ) {
			$this->assertArrayHasKey( 'additionalProperties', $schema, "$name missing additionalProperties" );
			$this->assertFalse( $schema['additionalProperties'], "$name must set additionalProperties=false" );
			$this->assertSame( 'object', $schema['type'], "$name must be an object schema" );
		}
	}

	#[Test]
	public function required_id_fields_are_declared(): void {
		$expected = array(
			'update-course'        => array( 'course_id' ),
			'delete-course'        => array( 'course_id' ),
			'create-section'       => array( 'course_id', 'name' ),
			'delete-section'       => array( 'course_id', 'section_id' ),
			'create-lesson'        => array( 'course_id', 'section_id', 'title' ),
			'delete-lesson'        => array( 'lesson_id' ),
			'add-quiz-question'    => array( 'quiz_id', 'title', 'type' ),
			'delete-quiz-question' => array( 'quiz_id', 'question_id' ),
			'enroll-student'       => array( 'user_id', 'course_id' ),
			'update-enrollment'    => array( 'enrollment_id' ),
		);

		$schemas = $this->input_schemas();
		foreach ( $expected as $name => $required ) {
			$this->assertSame( $required, $schemas[ $name ]['required'] ?? array(), "$name required mismatch" );
		}
	}

	#[Test]
	public function delete_schemas_never_expose_destructive_force_flags(): void {
		$forbidden = array( 'force', 'hard_delete', 'delete_items', 'delete_question_post', 'permanent' );
		$deletes   = array(
			CourseSchemas::delete_input(),
			SectionSchemas::delete_input(),
			LessonSchemas::delete_input(),
			QuizSchemas::delete_input(),
			QuestionSchemas::delete_input(),
		);

		foreach ( $deletes as $schema ) {
			$keys = array_keys( $schema['properties'] ?? array() );
			foreach ( $forbidden as $bad ) {
				$this->assertNotContains( $bad, $keys, "delete schema must not expose $bad" );
			}
		}
	}

	#[Test]
	public function post_status_input_is_restricted_to_phase_two_allowlist(): void {
		$this->assertSame( array( 'draft', 'publish', 'pending' ), Schemas::status()['enum'] );
		$this->assertSame( array( 'draft', 'publish', 'pending' ), Schemas::allowed_statuses() );
	}

	#[Test]
	public function enrollment_and_graduation_allowlists_match_learnpress_states(): void {
		$this->assertSame(
			array( 'enrolled', 'finished', 'purchased', 'completed', 'cancel' ),
			Validator::enrollment_statuses()
		);
		$this->assertSame(
			array( 'in-progress', 'passed', 'failed' ),
			Validator::graduations()
		);
	}

	#[Test]
	public function manual_enrollment_creation_is_restricted_to_active_statuses(): void {
		// L3a: enroll-student may only create active enrollments; finished/completed/cancel
		// are reached via update-enrollment.
		$this->assertSame( array( 'enrolled', 'purchased' ), Validator::enroll_create_statuses() );

		$enroll = EnrollmentSchemas::enroll_input();
		$this->assertSame( array( 'enrolled', 'purchased' ), $enroll['properties']['status']['enum'] );

		// update-enrollment still accepts the full status set.
		$update = EnrollmentSchemas::update_input();
		$this->assertSame(
			array( 'enrolled', 'finished', 'purchased', 'completed', 'cancel' ),
			$update['properties']['status']['enum']
		);
	}
}
