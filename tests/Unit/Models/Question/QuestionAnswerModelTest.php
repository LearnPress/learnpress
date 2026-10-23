<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\Question;

use Brain\Monkey\Functions;
use Exception;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for QuestionAnswerModel::check_capabilities_update().
 * The stored question_id is stubbed, so no DB access is needed.
 */
class QuestionAnswerModelTest extends BrainMonkeyTestCase {

	/**
	 * Question IDs the current user is allowed to edit.
	 *
	 * @var int[]
	 */
	private array $editable_question_ids = [];

	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_get_current_user' )->justReturn( (object) [ 'ID' => 1 ] );
		Functions\when( 'user_can' )->alias(
			fn( $user, string $cap, $object_id = 0 ) => 'edit_lp_lesson' === $cap
				&& in_array( (int) $object_id, $this->editable_question_ids, true )
		);
	}

	/**
	 * Build an answer whose stored question_id is $stored_question_id (null = not in DB).
	 */
	private function make_answer( array $request, ?int $stored_question_id ): QuestionAnswerModel {
		$answer = new class( $request ) extends QuestionAnswerModel {
			public ?int $stored_question_id = null;

			protected function get_stored_question_id(): ?int {
				return $this->stored_question_id;
			}
		};

		$answer->stored_question_id = $stored_question_id;

		return $answer;
	}

	public function test_check_capabilities_update_owner_of_stored_question_passes(): void {
		$this->editable_question_ids = [ 15 ];

		$answer = $this->make_answer(
			[ 'question_answer_id' => 5, 'question_id' => 15 ],
			15
		);

		$answer->check_capabilities_update();
		$this->addToAssertionCount( 1 );
	}

	public function test_check_capabilities_update_ignores_request_question_id(): void {
		// Attacker owns question 26 and sends it, but answer 5 is stored under question 15.
		$this->editable_question_ids = [ 26 ];

		$answer = $this->make_answer(
			[ 'question_answer_id' => 5, 'question_id' => 26 ],
			15
		);

		$this->expectException( Exception::class );
		$answer->check_capabilities_update();
	}

	public function test_check_capabilities_update_answer_not_found_throws(): void {
		$this->editable_question_ids = [ 26 ];

		$answer = $this->make_answer(
			[ 'question_answer_id' => 999, 'question_id' => 26 ],
			null
		);

		$this->expectException( Exception::class );
		$answer->check_capabilities_update();
	}

	public function test_check_capabilities_update_user_cannot_edit_stored_question_throws(): void {
		$this->editable_question_ids = [];

		$answer = $this->make_answer(
			[ 'question_answer_id' => 5, 'question_id' => 15 ],
			15
		);

		$this->expectException( Exception::class );
		$answer->check_capabilities_update();
	}
}
