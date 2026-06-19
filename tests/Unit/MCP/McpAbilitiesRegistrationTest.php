<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\MCP;

use Brain\Monkey\Functions;
use LearnPress\MCP\Abilities;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;

/**
 * Registration metadata + scope tests for Phase 1 and Phase 2 MCP abilities.
 */
class McpAbilitiesRegistrationTest extends BrainMonkeyTestCase {

	/**
	 * @var array<string,array> Captured ability registrations.
	 */
	private array $registered = array();

	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'LP_ORDER_CPT' ) ) {
			define( 'LP_ORDER_CPT', 'lp_order' );
		}
		if ( ! defined( 'LP_QUESTION_CPT' ) ) {
			define( 'LP_QUESTION_CPT', 'lp_question' );
		}

		$this->registered = array();
		Functions\when( 'wp_register_ability' )->alias(
			function ( $name, $args ) {
				$this->registered[ $name ] = $args;
				return true;
			}
		);

		Abilities::register_abilities();
	}

	#[Test]
	public function registers_all_phase_one_and_phase_two_abilities(): void {
		$this->assertCount( 25, $this->registered );

		$expected = array(
			'learnpress/get-courses',
			'learnpress/get-quiz-details',
			'learnpress/create-course',
			'learnpress/update-course',
			'learnpress/delete-course',
			'learnpress/create-section',
			'learnpress/update-section',
			'learnpress/delete-section',
			'learnpress/create-lesson',
			'learnpress/update-lesson',
			'learnpress/delete-lesson',
			'learnpress/create-quiz',
			'learnpress/update-quiz',
			'learnpress/delete-quiz',
			'learnpress/add-quiz-question',
			'learnpress/update-quiz-question',
			'learnpress/delete-quiz-question',
			'learnpress/enroll-student',
			'learnpress/update-enrollment',
		);
		foreach ( $expected as $id ) {
			$this->assertArrayHasKey( $id, $this->registered, "missing ability $id" );
		}
	}

	#[Test]
	public function read_tools_keep_read_only_metadata_and_read_scope(): void {
		foreach ( array( 'learnpress/get-courses', 'learnpress/get-quiz-details', 'learnpress/get-enrollments' ) as $id ) {
			$meta = $this->registered[ $id ]['meta'];
			$this->assertTrue( $meta['annotations']['readonly'], "$id should be readonly" );
			$this->assertFalse( $meta['annotations']['destructive'], "$id should not be destructive" );
			$this->assertSame( 'read', $meta['mcp']['required_scope'], "$id should require read" );
		}
	}

	#[Test]
	public function create_update_tools_use_write_scope_and_non_destructive(): void {
		$tools = array(
			'learnpress/create-course',
			'learnpress/update-course',
			'learnpress/create-section',
			'learnpress/update-section',
			'learnpress/create-lesson',
			'learnpress/update-lesson',
			'learnpress/create-quiz',
			'learnpress/update-quiz',
			'learnpress/add-quiz-question',
			'learnpress/update-quiz-question',
			'learnpress/enroll-student',
			'learnpress/update-enrollment',
		);
		foreach ( $tools as $id ) {
			$meta = $this->registered[ $id ]['meta'];
			$this->assertFalse( $meta['annotations']['readonly'], "$id readonly should be false" );
			$this->assertFalse( $meta['annotations']['destructive'], "$id destructive should be false" );
			$this->assertSame( 'write', $meta['mcp']['required_scope'], "$id should require write" );
		}
	}

	#[Test]
	public function delete_tools_are_destructive_and_require_write_scope(): void {
		$tools = array(
			'learnpress/delete-course',
			'learnpress/delete-section',
			'learnpress/delete-lesson',
			'learnpress/delete-quiz',
			'learnpress/delete-quiz-question',
		);
		foreach ( $tools as $id ) {
			$meta = $this->registered[ $id ]['meta'];
			$this->assertFalse( $meta['annotations']['readonly'], "$id readonly should be false" );
			$this->assertTrue( $meta['annotations']['destructive'], "$id should be destructive" );
			$this->assertSame( 'write', $meta['mcp']['required_scope'], "$id should require write" );
		}
	}

	#[Test]
	public function every_ability_has_strict_input_schema_and_callable_executor(): void {
		foreach ( $this->registered as $id => $args ) {
			$this->assertFalse(
				$args['input_schema']['additionalProperties'] ?? null,
				"$id input schema must be strict"
			);
			$this->assertTrue( is_callable( $args['execute_callback'] ), "$id executor must be callable" );
		}
	}
}
