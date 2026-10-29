<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\Question;

use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Question\QuestionPostMultipleChoiceModel;
use LearnPress\Models\Question\QuestionPostSingleChoiceModel;
use LearnPress\Models\Question\QuestionPostTrueFalseModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for check() of true/false, single choice and multi choice questions.
 * Answers come from get_answers_for_legacy_type() (stubbed), mark is fixed to 2.
 */
class QuestionPostChoiceCheckTest extends BrainMonkeyTestCase {

	/**
	 * Answers as read from config: value => is_true.
	 */
	private function make_answers( array $values ): array {
		$answers = [];
		$order   = 1;
		foreach ( $values as $value => $is_true ) {
			$answers[] = new QuestionAnswerModel(
				[
					'value'   => (string) $value,
					'is_true' => $is_true,
					'order'   => (string) $order++,
				]
			);
		}

		return $answers;
	}

	private function make_question( string $type, array $answers ): QuestionPostModel {
		switch ( $type ) {
			case 'true_or_false':
				return new class( $answers ) extends QuestionPostTrueFalseModel {
					use ChoiceCheckStubTrait;
				};
			case 'single_choice':
				return new class( $answers ) extends QuestionPostSingleChoiceModel {
					use ChoiceCheckStubTrait;
				};
			default:
				return new class( $answers ) extends QuestionPostMultipleChoiceModel {
					use ChoiceCheckStubTrait;
				};
		}
	}

	private function correct(): array {
		return [
			'correct' => true,
			'mark'    => 2.0,
		];
	}

	private function incorrect(): array {
		return [
			'correct' => false,
			'mark'    => 0,
		];
	}

	/**
	 * @dataProvider provider_single_answer_types
	 */
	public function test_check_single_answer_correct( string $type ): void {
		$question = $this->make_question( $type, $this->make_answers( [ 'a1' => 'yes', 'a2' => '' ] ) );

		$this->assertSame( $this->correct(), $question->check( 'a1' ) );
	}

	/**
	 * @dataProvider provider_single_answer_types
	 */
	public function test_check_single_answer_wrong( string $type ): void {
		$question = $this->make_question( $type, $this->make_answers( [ 'a1' => 'yes', 'a2' => '' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( 'a2' ) );
	}

	/**
	 * @dataProvider provider_single_answer_types
	 */
	public function test_check_single_answer_empty_is_wrong( string $type ): void {
		$question = $this->make_question( $type, $this->make_answers( [ 'a1' => 'yes', 'a2' => '' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( '' ) );
	}

	public function provider_single_answer_types(): array {
		return [
			[ 'true_or_false' ],
			[ 'single_choice' ],
		];
	}

	public function test_check_multi_choice_all_correct_selected(): void {
		$question = $this->make_question(
			'multi_choice',
			$this->make_answers( [ 'a1' => 'yes', 'a2' => '', 'a3' => 'yes' ] )
		);

		$this->assertSame( $this->correct(), $question->check( [ 'a1', 'a3' ] ) );
	}

	public function test_check_multi_choice_missing_correct_is_wrong(): void {
		$question = $this->make_question(
			'multi_choice',
			$this->make_answers( [ 'a1' => 'yes', 'a2' => '', 'a3' => 'yes' ] )
		);

		$this->assertSame( $this->incorrect(), $question->check( [ 'a1' ] ) );
	}

	public function test_check_multi_choice_extra_wrong_selected_is_wrong(): void {
		$question = $this->make_question(
			'multi_choice',
			$this->make_answers( [ 'a1' => 'yes', 'a2' => '', 'a3' => 'yes' ] )
		);

		$this->assertSame( $this->incorrect(), $question->check( [ 'a1', 'a2', 'a3' ] ) );
	}

	/**
	 * Answers as array, same as read from table question_answer_config.
	 */
	private function make_array_answers( array $values ): array {
		return array_map(
			function ( QuestionAnswerModel $answer ) {
				return [
					'value'   => $answer->value,
					'is_true' => $answer->is_true,
					'order'   => $answer->order,
				];
			},
			$this->make_answers( $values )
		);
	}

	/**
	 * @dataProvider provider_single_answer_types
	 */
	public function test_check_single_answer_array_shape( string $type ): void {
		$question = $this->make_question( $type, $this->make_array_answers( [ 'a1' => 'yes', 'a2' => '' ] ) );

		$this->assertSame( $this->correct(), $question->check( 'a1' ) );
		$this->assertSame( $this->incorrect(), $question->check( 'a2' ) );
	}

	/**
	 * @dataProvider provider_single_answer_types
	 */
	public function test_check_single_answer_array_missing_keys_is_wrong( string $type ): void {
		$question = $this->make_question( $type, [ [ 'title' => 'A' ] ] );

		$this->assertSame( $this->incorrect(), $question->check( 'a1' ) );
	}

	public function test_check_multi_choice_array_shape(): void {
		$question = $this->make_question(
			'multi_choice',
			$this->make_array_answers( [ 'a1' => 'yes', 'a2' => '', 'a3' => 'yes' ] )
		);

		$this->assertSame( $this->correct(), $question->check( [ 'a1', 'a3' ] ) );
		$this->assertSame( $this->incorrect(), $question->check( [ 'a1', 'a2', 'a3' ] ) );
	}

	public function test_check_multi_choice_no_answers_is_wrong(): void {
		$question = $this->make_question( 'multi_choice', [] );

		$this->assertSame( $this->incorrect(), $question->check( [ 'a1' ] ) );
	}
}

/**
 * Stub DB reads for check(): answers from constructor, mark fixed to 2.
 */
trait ChoiceCheckStubTrait {
	public array $config_answers;

	public function __construct( array $answers ) {
		$this->config_answers = $answers;
	}

	public function get_answers_for_legacy_type(): array {
		return $this->config_answers;
	}

	public function get_mark() {
		return 2;
	}
}
