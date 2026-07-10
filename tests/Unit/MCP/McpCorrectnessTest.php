<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\MCP;

use Brain\Monkey\Functions;
use LearnPress\MCP\Domain\QuestionTools;
use LearnPress\MCP\Support\Sanitizer;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use WP_Error;

/**
 * Tests for the Phase 2 MCP correctness fixes (C1 date parsing, C5 answer validation).
 */
class McpCorrectnessTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();
		// Sanitizer::text() -> sanitize_text_field() identity for these tests.
		Functions\when( 'sanitize_text_field' )->returnArg();
	}

	// ── C1: Sanitizer::datetime ────────────────────────────────────────────────

	#[Test]
	public function datetime_parses_a_valid_string_to_gmt(): void {
		$this->assertSame( '2026-06-18 10:30:00', Sanitizer::datetime( '2026-06-18 10:30:00' ) );
	}

	#[Test]
	public function datetime_returns_null_for_unparseable_input(): void {
		$this->assertNull( Sanitizer::datetime( 'definitely not a date' ) );
	}

	#[Test]
	public function datetime_returns_null_for_empty_or_whitespace(): void {
		$this->assertNull( Sanitizer::datetime( '' ) );
		$this->assertNull( Sanitizer::datetime( '   ' ) );
	}

	#[Test]
	public function datetime_never_returns_the_1970_epoch_for_bad_input(): void {
		// The original bug: gmdate('Y-m-d H:i:s', strtotime('bad')) === '1970-01-01 00:00:00'.
		$this->assertNotSame( '1970-01-01 00:00:00', (string) Sanitizer::datetime( 'bad input' ) );
	}

	// ── C5: QuestionTools::validate_answers ────────────────────────────────────

	/**
	 * Invoke the protected validator.
	 *
	 * @param string $type    Question type.
	 * @param array  $answers Answers payload.
	 *
	 * @return true|WP_Error
	 */
	private function validate( string $type, array $answers ) {
		$method = new ReflectionMethod( QuestionTools::class, 'validate_answers' );
		$method->setAccessible( true );

		return $method->invoke( null, $type, $answers );
	}

	/**
	 * Build an answer row.
	 *
	 * @param string $title   Answer title.
	 * @param bool   $correct Whether it is correct.
	 *
	 * @return array
	 */
	private function answer( string $title, bool $correct ): array {
		return array(
			'title'      => $title,
			'is_correct' => $correct,
		);
	}

	#[Test]
	public function true_or_false_requires_exactly_two_answers_and_one_correct(): void {
		$ok = $this->validate( 'true_or_false', array( $this->answer( 'True', true ), $this->answer( 'False', false ) ) );
		$this->assertTrue( $ok );

		$this->assertInstanceOf( WP_Error::class, $this->validate( 'true_or_false', array( $this->answer( 'Only one', true ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'true_or_false', array( $this->answer( 'True', false ), $this->answer( 'False', false ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'true_or_false', array( $this->answer( 'True', true ), $this->answer( 'False', true ) ) ) );
	}

	#[Test]
	public function single_choice_requires_exactly_one_correct(): void {
		$this->assertTrue( $this->validate( 'single_choice', array( $this->answer( 'A', true ), $this->answer( 'B', false ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'single_choice', array( $this->answer( 'A', true ), $this->answer( 'B', true ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'single_choice', array( $this->answer( 'A', false ), $this->answer( 'B', false ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'single_choice', array( $this->answer( 'A', true ) ) ) );
	}

	#[Test]
	public function multi_choice_requires_two_answers_and_at_least_one_correct(): void {
		$this->assertTrue( $this->validate( 'multi_choice', array( $this->answer( 'A', true ), $this->answer( 'B', true ), $this->answer( 'C', false ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'multi_choice', array( $this->answer( 'A', false ), $this->answer( 'B', false ) ) ) );
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'multi_choice', array( $this->answer( 'A', true ) ) ) );
	}

	#[Test]
	public function any_answer_with_empty_title_is_rejected(): void {
		$this->assertInstanceOf( WP_Error::class, $this->validate( 'multi_choice', array( $this->answer( 'A', true ), $this->answer( '', false ) ) ) );
	}

	#[Test]
	public function fill_in_blanks_skips_choice_count_and_correctness_rules(): void {
		// Not a choice/true-false type: only the non-empty title rule applies.
		$this->assertTrue( $this->validate( 'fill_in_blanks', array( $this->answer( 'answer', false ) ) ) );
	}
}
