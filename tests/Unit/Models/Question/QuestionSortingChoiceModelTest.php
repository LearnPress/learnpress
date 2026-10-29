<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\Question;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\Question\QuestionSortingChoiceModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use RuntimeException;

/**
 * Unit tests for check() of sorting choice question
 * and get_question_obj_by_type() of type sorting_choice.
 * Answers come from get_answers_for_legacy_type() (stubbed), mark is fixed to 2.
 */
class QuestionSortingChoiceModelTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'LP_Debug', false ) ) {
			eval(
				'class LP_Debug {
					public static array $logged = array();
					public static function error_log( $error ) {
						self::$logged[] = $error instanceof \\Throwable ? $error->getMessage() : (string) $error;
					}
				}'
			);
		}

		\LP_Debug::$logged = [];
	}

	/**
	 * @param array|RuntimeException $answers
	 */
	private function make_question( $answers ): QuestionSortingChoiceModel {
		return new class( $answers ) extends QuestionSortingChoiceModel {
			public $config_answers;

			public function __construct( $answers ) {
				$this->config_answers = $answers;
			}

			public function get_answers_for_legacy_type(): array {
				if ( $this->config_answers instanceof RuntimeException ) {
					throw $this->config_answers;
				}

				return $this->config_answers;
			}

			public function get_mark() {
				return 2;
			}
		};
	}

	/**
	 * Answers as read from old tables (sorted by order ASC): list values.
	 */
	private function make_model_answers( array $values ): array {
		$answers = [];
		foreach ( array_values( $values ) as $index => $value ) {
			$answers[] = new QuestionAnswerModel(
				[
					'value'   => $value,
					'is_true' => 'yes',
					'order'   => (string) ( $index + 1 ),
				]
			);
		}

		return $answers;
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

	public function test_check_correct_order(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2', 'a3' ] ) );

		$this->assertSame( $this->correct(), $question->check( [ 'a1', 'a2', 'a3' ] ) );
	}

	public function test_check_wrong_order(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2', 'a3' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( [ 'a2', 'a1', 'a3' ] ) );
	}

	public function test_check_missing_option_is_wrong(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2', 'a3' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( [ 'a1', 'a2' ] ) );
	}

	public function test_check_extra_option_is_wrong(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( [ 'a1', 'a2', 'a3' ] ) );
	}

	public function test_check_empty_answer_is_wrong(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( '' ) );
		$this->assertSame( $this->incorrect(), $question->check( null ) );
	}

	public function test_check_no_answers_is_wrong(): void {
		$question = $this->make_question( [] );

		$this->assertSame( $this->incorrect(), $question->check( [] ) );
	}

	public function test_check_user_answer_keys_not_sequence(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2' ] ) );

		$this->assertSame( $this->correct(), $question->check( [ 3 => 'a1', 7 => 'a2' ] ) );
	}

	public function test_check_non_scalar_answer_is_wrong(): void {
		$question = $this->make_question( $this->make_model_answers( [ 'a1', 'a2' ] ) );

		$this->assertSame( $this->incorrect(), $question->check( [ [ 'a1' ], 'a2' ] ) );
	}

	public function test_check_numeric_value_compare_as_string(): void {
		$question = $this->make_question( $this->make_model_answers( [ '10', '2' ] ) );

		$this->assertSame( $this->correct(), $question->check( [ 10, 2 ] ) );
	}

	public function test_check_config_answers_sorted_by_order(): void {
		// Config stored not by order, correct order is: a1, a2, a3.
		$question = $this->make_question(
			[
				[
					'value'   => 'a3',
					'is_true' => 'yes',
					'order'   => 3,
				],
				[
					'value'   => 'a1',
					'is_true' => 'yes',
					'order'   => 1,
				],
				[
					'value'   => 'a2',
					'is_true' => 'yes',
					'order'   => 2,
				],
			]
		);

		$this->assertSame( $this->correct(), $question->check( [ 'a1', 'a2', 'a3' ] ) );
		$this->assertSame( $this->incorrect(), $question->check( [ 'a3', 'a1', 'a2' ] ) );
	}

	public function test_check_config_answers_without_order_keep_position(): void {
		$question = $this->make_question(
			[
				[ 'value' => 'a1' ],
				[ 'value' => 'a2' ],
			]
		);

		$this->assertSame( $this->correct(), $question->check( [ 'a1', 'a2' ] ) );
	}

	public function test_check_error_is_logged_and_wrong(): void {
		$question = $this->make_question( new RuntimeException( 'db error' ) );

		$this->assertSame( $this->incorrect(), $question->check( [ 'a1' ] ) );
		$this->assertSame( [ 'db error' ], \LP_Debug::$logged );
	}

	/**
	 * Question with answers read from old tables (get_answer_option()), ID 11, 12, 13... by order.
	 */
	private function make_question_options( array $values ): QuestionSortingChoiceModel {
		Functions\when( 'do_shortcode' )->returnArg();
		Functions\when( 'wp_parse_args' )->alias(
			function ( $args, $defaults ) {
				return array_merge( $defaults, $args );
			}
		);

		$answers = [];
		foreach ( array_values( $values ) as $index => $value ) {
			$answers[] = new QuestionAnswerModel(
				[
					'question_answer_id' => (string) ( 11 + $index ),
					'question_id'        => '5',
					'title'              => 'Title ' . $value,
					'value'              => $value,
					'is_true'            => 'yes',
					'order'              => (string) ( $index + 1 ),
				]
			);
		}

		return new class( $answers ) extends QuestionSortingChoiceModel {
			public array $answer_models;

			public function __construct( array $answers ) {
				$this->answer_models = $answers;
			}

			public function get_answer_option() {
				return $this->answer_models;
			}
		};
	}

	/**
	 * Args same as learn_press_get_question_options_for_js().
	 */
	private function options_for_js_args( bool $include_is_true ): array {
		$exclude = [ 'question_id', 'order' ];
		if ( ! $include_is_true ) {
			$exclude[] = 'is_true';
		}

		return [
			'exclude' => $exclude,
			'map'     => [ 'question_answer_id' => 'uid' ],
		];
	}

	public function test_get_answer_options_include_is_true_add_sorting_by_correct_position(): void {
		$question = $this->make_question_options( [ 'a1', 'a2', 'a3' ] );

		$options = $question->get_answer_options( $this->options_for_js_args( true ) );

		$sorting = array_column( $options, 'sorting', 'value' );
		ksort( $sorting );
		$this->assertSame(
			[
				'a1' => 0,
				'a2' => 1,
				'a3' => 2,
			],
			$sorting
		);
	}

	public function test_get_answer_options_exclude_is_true_no_sorting(): void {
		$question = $this->make_question_options( [ 'a1', 'a2', 'a3' ] );

		$options = $question->get_answer_options( $this->options_for_js_args( false ) );

		$this->assertCount( 3, $options );
		foreach ( $options as $option ) {
			$this->assertArrayNotHasKey( 'sorting', $option );
			$this->assertArrayNotHasKey( 'is_true', $option );
		}
	}

	public function test_get_answer_options_exclude_as_string(): void {
		$question = $this->make_question_options( [ 'a1', 'a2' ] );

		$options = $question->get_answer_options(
			[
				'exclude' => 'question_id, is_true',
				'map'     => [],
			]
		);

		foreach ( $options as $option ) {
			$this->assertArrayNotHasKey( 'sorting', $option );
		}
	}

	/**
	 * @dataProvider provider_include_is_true
	 */
	public function test_get_answer_options_remove_uid_and_shuffle( bool $include_is_true ): void {
		$question = $this->make_question_options( [ 'a1', 'a2', 'a3' ] );

		$options = $question->get_answer_options( $this->options_for_js_args( $include_is_true ) );

		foreach ( $options as $option ) {
			$this->assertArrayNotHasKey( 'uid', $option );
			$this->assertArrayNotHasKey( 'question_answer_id', $option );
		}
		$this->assertSame( [ 0, 1, 2 ], array_keys( $options ) );
		$this->assertNotSame( [ 'a1', 'a2', 'a3' ], array_column( $options, 'value' ) );
		$values = array_column( $options, 'value' );
		sort( $values );
		$this->assertSame( [ 'a1', 'a2', 'a3' ], $values );
	}

	public function provider_include_is_true(): array {
		return [
			[ true ],
			[ false ],
		];
	}

	public function test_get_answer_options_one_option(): void {
		$question = $this->make_question_options( [ 'a1' ] );

		$options = $question->get_answer_options( $this->options_for_js_args( true ) );

		$this->assertCount( 1, $options );
		$this->assertSame( 'a1', $options[0]['value'] );
		$this->assertSame( 0, $options[0]['sorting'] );
	}

	public function test_get_answer_options_no_answers(): void {
		$question = $this->make_question_options( [] );

		$this->assertSame( [], $question->get_answer_options( $this->options_for_js_args( true ) ) );
	}

	public function test_get_question_obj_by_type_addon_model_not_declare_check_use_lp_model(): void {
		$this->add_sorting_choice_type( SortingChoiceAddonModelNoCheck::class );

		$this->assertSame(
			QuestionSortingChoiceModel::class,
			QuestionPostModel::get_question_obj_by_type( 'sorting_choice' )
		);
	}

	public function test_get_question_obj_by_type_addon_model_declare_check_keep_addon_model(): void {
		$this->add_sorting_choice_type( SortingChoiceAddonModelHasCheck::class );

		$this->assertSame(
			SortingChoiceAddonModelHasCheck::class,
			QuestionPostModel::get_question_obj_by_type( 'sorting_choice' )
		);
	}

	public function test_get_question_obj_by_type_no_addon_model_use_lp_model(): void {
		$this->add_sorting_choice_type( '' );

		$this->assertSame(
			QuestionSortingChoiceModel::class,
			QuestionPostModel::get_question_obj_by_type( 'sorting_choice' )
		);
	}

	public function test_get_question_obj_by_type_type_not_registered(): void {
		Filters\expectApplied( 'learn-press/question-types' )->andReturnFirstArg();

		$this->assertSame( '', QuestionPostModel::get_question_obj_by_type( 'sorting_choice' ) );
	}

	public function test_is_class_declare_check(): void {
		$this->assertTrue( QuestionPostModel::is_class_declare_check( QuestionSortingChoiceModel::class ) );
		$this->assertTrue( QuestionPostModel::is_class_declare_check( SortingChoiceAddonModelHasCheck::class ) );
		$this->assertFalse( QuestionPostModel::is_class_declare_check( SortingChoiceAddonModelNoCheck::class ) );
		$this->assertFalse( QuestionPostModel::is_class_declare_check( QuestionPostModel::class ) );
		$this->assertFalse( QuestionPostModel::is_class_declare_check( RuntimeException::class ) );
		$this->assertFalse( QuestionPostModel::is_class_declare_check( 'Not_Exists_Class' ) );
		$this->assertFalse( QuestionPostModel::is_class_declare_check( '' ) );
		$this->assertFalse( QuestionPostModel::is_class_declare_check( null ) );
	}

	/**
	 * Register type sorting_choice and model class of addon via filters.
	 */
	private function add_sorting_choice_type( string $addon_model_class ): void {
		Filters\expectApplied( 'learn-press/question-types' )->andReturnUsing(
			function ( $types ) {
				$types['sorting_choice'] = 'Sorting Choice';

				return $types;
			}
		);
		Filters\expectApplied( 'learn-press/question-object-by-type' )
			->with( '', 'sorting_choice' )
			->andReturn( $addon_model_class );
	}
}

/**
 * Same as model of addon sorting choice <= 4.0.4: not declare check().
 */
class SortingChoiceAddonModelNoCheck extends QuestionPostModel {
}

/**
 * Model of addon sorting choice declare check().
 */
class SortingChoiceAddonModelHasCheck extends QuestionPostModel {
	public function check( $user_answer = null ): array {
		return [
			'correct' => true,
			'mark'    => 1,
		];
	}
}
