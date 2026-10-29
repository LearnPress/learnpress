<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\Question;

use Brain\Monkey\Functions;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostFIBModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;

/**
 * Unit tests for QuestionPostFIBModel reading blanks from answers config.
 * Answers come from get_answers_for_legacy_type() (stubbed), mark is fixed to 2.
 */
class QuestionPostFIBModelTest extends BrainMonkeyTestCase {

	const CONTENT = 'Capital of VN is [fib id="b1" fill="Ha Noi"], of Japan is [fib id="b2" fill="Tokyo"]';

	protected function setUp(): void {
		parent::setUp();

		// Simple shortcode stubs, enough for [fib id="" fill=""].
		Functions\when( 'get_shortcode_regex' )->justReturn( '\[fib[^\]]*\]' );
		Functions\when( 'shortcode_parse_atts' )->alias(
			function ( string $text ) {
				preg_match_all( '/(\w+)="([^"]*)"/', $text, $matches, PREG_SET_ORDER );
				$atts = [];
				foreach ( $matches as $match ) {
					$atts[ $match[1] ] = $match[2];
				}

				return $atts;
			}
		);
	}

	private function blanks(): array {
		return [
			'b1' => [
				'id'         => 'b1',
				'fill'       => 'Ha Noi',
				'match_case' => 0,
				'comparison' => 'equal',
			],
			'b2' => [
				'id'         => 'b2',
				'fill'       => 'Tokyo',
				'match_case' => 0,
				'comparison' => 'equal',
			],
		];
	}

	/**
	 * @param QuestionAnswerModel[] $answers
	 */
	private function make_question( array $answers ): QuestionPostFIBModel {
		return new class( $answers ) extends QuestionPostFIBModel {
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
		};
	}

	private function make_config_answer( $blanks ): QuestionAnswerModel {
		return new QuestionAnswerModel(
			[
				'question_answer_id'  => null,
				'title'               => self::CONTENT,
				'value'               => 'v1',
				'order'               => '1',
				'is_true'             => 'yes',
				'meta_data'           => $blanks,
				'is_get_all_metadata' => true,
			]
		);
	}

	public function test_get_answer_data_uses_given_blanks(): void {
		Functions\expect( 'learn_press_get_question_answer_meta' )->never();

		$data = $this->make_question( [] )->get_answer_data(
			self::CONTENT,
			0,
			[
				'b1' => 'ha noi',
				'b2' => 'Osaka',
			],
			$this->blanks()
		);

		$this->assertTrue( $data['b1']['is_correct'] );
		$this->assertFalse( $data['b2']['is_correct'] );
		$this->assertSame( 'Tokyo', $data['b2']['correct'] );
	}

	public function test_get_answer_data_without_blanks_reads_old_meta(): void {
		Functions\expect( 'learn_press_get_question_answer_meta' )
			->twice()
			->with( 5, '_blanks', true )
			->andReturn( $this->blanks() );

		$data = $this->make_question( [] )->get_answer_data( self::CONTENT, 5, [ 'b1' => 'Ha Noi' ] );

		$this->assertTrue( $data['b1']['is_correct'] );
	}

	/**
	 * Question read answers from old table (get_answer_option), answer has id 5 and blanks meta.
	 */
	private function make_question_old_table(): QuestionPostFIBModel {
		$answer = new QuestionAnswerModel(
			[
				'question_answer_id'  => 5,
				'question_id'         => 1,
				'title'               => self::CONTENT,
				'value'               => '',
				'order'               => '1',
				'is_true'             => '',
				'meta_data'           => $this->blanks(),
				'is_get_all_metadata' => true,
			]
		);

		return new class( $answer ) extends QuestionPostFIBModel {
			public QuestionAnswerModel $answer;

			public function __construct( QuestionAnswerModel $answer ) {
				$this->answer = $answer;
			}

			public function get_answer_option() {
				return [ $this->answer ];
			}

			public function get_answers_for_legacy_type(): array {
				return [ $this->answer ];
			}
		};
	}

	private function stub_get_answer_options(): void {
		Functions\when( 'wp_parse_args' )->alias( fn( $args, $defaults ) => array_merge( $defaults, $args ) );
		Functions\when( 'wp_strip_all_tags' )->returnArg();
		Functions\when( 'do_shortcode' )->returnArg();
	}

	public function test_get_answer_options_passes_blanks_of_answer_model(): void {
		// Blanks get from meta_data of answer model, not query meta again.
		Functions\expect( 'learn_press_get_question_answer_meta' )->never();
		$this->stub_get_answer_options();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options(
			[
				'exclude' => [ 'question_id', 'order' ],
				'map'     => [ 'question_answer_id' => 'uid' ],
				'answer'  => [ 'b1' => 'Ha Noi' ],
			]
		);

		$this->assertSame( 5, $options[0]['uid'] );
		$this->assertSame( [ 'b1', 'b2' ], $options[0]['ids'] );
		$this->assertTrue( $options[0]['answers']['b1']['is_correct'] );
		$this->assertFalse( $options[0]['answers']['b2']['is_correct'] );
		$this->assertSame( 'Tokyo', $options[0]['answers']['b2']['correct'] );
	}

	public function test_get_answer_options_exclude_is_true_hides_fill(): void {
		$this->stub_get_answer_options();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options(
			[
				'exclude' => [ 'question_id', 'order', 'is_true' ],
				'map'     => [ 'question_answer_id' => 'uid' ],
				'answer'  => [ 'b1' => 'Ha Noi' ],
			]
		);

		$this->assertSame( [], $options[0]['answers'] );
		$this->assertSame( [ 'b1', 'b2' ], $options[0]['ids'] );
		$this->assertStringContainsString( '{{FIB_b1}}', $options[0]['title'] );
		$this->assertStringContainsString( '{{FIB_b2}}', $options[0]['title'] );
		$this->assertStringNotContainsString( 'Ha Noi', $options[0]['title'] );
		$this->assertStringNotContainsString( 'Tokyo', $options[0]['title'] );
		$this->assertStringNotContainsString( 'Tokyo', $options[0]['title_api'] );
		$this->assertArrayNotHasKey( 'is_true', $options[0] );
	}

	public function test_get_answer_options_show_result_without_fill(): void {
		$this->stub_get_answer_options();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options(
			[
				'exclude'     => [ 'question_id', 'order', 'is_true' ],
				'map'         => [ 'question_answer_id' => 'uid' ],
				'answer'      => [
					'b1' => 'Ha Noi',
					'b2' => 'Osaka',
				],
				'show_result' => true,
			]
		);

		$answers = $options[0]['answers'];
		$this->assertTrue( $answers['b1']['is_correct'] );
		$this->assertFalse( $answers['b2']['is_correct'] );
		$this->assertSame( 'Osaka', $answers['b2']['answer'] );
		$this->assertArrayNotHasKey( 'correct', $answers['b1'] );
		$this->assertArrayNotHasKey( 'correct', $answers['b2'] );
		$this->assertStringNotContainsString( 'Tokyo', (string) json_encode( $options ) );
	}

	public function test_get_answer_options_show_result_with_is_true_has_fill(): void {
		$this->stub_get_answer_options();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options(
			[
				'exclude'     => [ 'question_id', 'order' ],
				'answer'      => [ 'b2' => 'Osaka' ],
				'show_result' => true,
			]
		);

		$this->assertSame( 'Tokyo', $options[0]['answers']['b2']['correct'] );
	}

	public function test_get_answer_options_exclude_is_true_string(): void {
		$this->stub_get_answer_options();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options( [ 'exclude' => 'question_id, order, is_true' ] );

		$this->assertSame( [], $options[0]['answers'] );
		$this->assertArrayNotHasKey( 'order', $options[0] );
	}

	public function test_get_answer_options_without_args_not_error(): void {
		$this->stub_get_answer_options();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options();

		$this->assertSame( 5, $options[0]['question_answer_id'] );
		$this->assertStringContainsString( '{{FIB_b1}}', $options[0]['title'] );
		$this->assertArrayHasKey( 'b1', $options[0]['answers'] );
	}

	public function test_get_answer_options_hook_regex_content_still_hides_fill(): void {
		$this->stub_get_answer_options();
		// Hook return raw content (shortcode remain), model must still replace shortcode.
		\Brain\Monkey\Filters\expectApplied( 'learn-press/question/fib/regex-content' )
			->once()
			->andReturnFirstArg();

		$question = $this->make_question_old_table();
		$options  = $question->get_answer_options( [ 'exclude' => [ 'is_true' ] ] );

		$this->assertStringNotContainsString( 'fill=', $options[0]['title'] );
	}

	public function test_check_all_blanks_correct(): void {
		$question = $this->make_question( [ $this->make_config_answer( $this->blanks() ) ] );

		$result = $question->check(
			[
				'b1' => 'Ha Noi',
				'b2' => 'tokyo',
			]
		);

		$this->assertTrue( $result['correct'] );
		$this->assertSame( 2, $result['mark'] );
		$this->assertSame(
			[
				'b1' => true,
				'b2' => true,
			],
			$result['blanks']
		);
	}

	public function test_check_one_blank_wrong(): void {
		$question = $this->make_question( [ $this->make_config_answer( $this->blanks() ) ] );

		$result = $question->check(
			[
				'b1' => 'Ha Noi',
				'b2' => 'Osaka',
			]
		);

		$this->assertFalse( $result['correct'] );
		$this->assertSame( 0, $result['mark'] );
		$this->assertFalse( $result['blanks']['b2'] );
	}

	public function test_check_array_answer_blanks_from_meta(): void {
		$answer   = [
			'title' => self::CONTENT,
			'value' => 'v1',
			'meta'  => [ QuestionAnswerModel::META_KEY_BLANKS => $this->blanks() ],
		];
		$question = $this->make_question( [ $answer ] );

		$result = $question->check(
			[
				'b1' => 'Ha Noi',
				'b2' => 'tokyo',
			]
		);

		$this->assertTrue( $result['correct'] );
		$this->assertSame( 2, $result['mark'] );
	}

	public function test_get_blanks_of_answer_model(): void {
		$answer = $this->make_config_answer( $this->blanks() );

		$this->assertSame( $this->blanks(), $this->make_question( [] )->get_blanks_of_answer( $answer ) );
	}

	public function test_get_blanks_of_answer_array_meta_data(): void {
		$answer = [ 'meta_data' => $this->blanks() ];

		$this->assertSame( $this->blanks(), $this->make_question( [] )->get_blanks_of_answer( $answer ) );
	}

	public function test_get_blanks_of_answer_array_without_blanks_is_null(): void {
		$this->assertNull( $this->make_question( [] )->get_blanks_of_answer( [ 'value' => 'v1' ] ) );
	}

	public function test_get_blanks_of_answer_invalid_is_null(): void {
		$this->assertNull( $this->make_question( [] )->get_blanks_of_answer( 'v1' ) );
	}

	/**
	 * @dataProvider provider_check_answer
	 */
	public function test_check_answer( array $blank, string $user_fill, bool $expected ): void {
		$this->assertSame( $expected, $this->make_question( [] )->check_answer( $blank, $user_fill ) );
	}

	public function provider_check_answer(): array {
		return [
			'equal ignore case vietnamese'      => [ [ 'fill' => 'Hà Nội' ], 'HÀ NỘI', true ],
			'equal ignore case trims'           => [ [ 'fill' => 'Hà Nội' ], '  hà nội ', true ],
			'equal ignore case different word'  => [ [ 'fill' => 'Hà Nội' ], 'Hà Nam', false ],
			'equal match case same'             => [ [ 'fill' => 'Hà Nội', 'match_case' => 1 ], 'Hà Nội', true ],
			'equal match case vietnamese upper' => [ [ 'fill' => 'Hà Nội', 'match_case' => 1 ], 'HÀ NỘI', false ],
			'any ignore case vietnamese'        => [ [ 'fill' => 'Huế, Hà Nội', 'comparison' => 'any' ], 'HÀ NỘI', true ],
			'any ignore case not in list'       => [ [ 'fill' => 'Huế, Hà Nội', 'comparison' => 'any' ], 'Đà Nẵng', false ],
			'any match case vietnamese upper'   => [ [ 'fill' => 'Huế, Hà Nội', 'comparison' => 'any', 'match_case' => 1 ], 'HUẾ', false ],
			'any match case same'               => [ [ 'fill' => 'Huế, Hà Nội', 'comparison' => 'any', 'match_case' => 1 ], 'Huế', true ],
			'equal fill named entity'           => [ [ 'fill' => 'H&agrave; nội' ], 'Hà nội', true ],
			'equal fill entity match case'      => [ [ 'fill' => 'H&agrave; nội', 'match_case' => 1 ], 'Hà nội', true ],
			'any fill named entity'             => [ [ 'fill' => 'Huế, H&agrave; nội', 'comparison' => 'any' ], 'hà nội', true ],
			'range in'                        => [ [ 'fill' => '1, 5', 'comparison' => 'range' ], '3.5', true ],
			'range out'                         => [ [ 'fill' => '1, 5', 'comparison' => 'range' ], '6', false ],
		];
	}

	private function editor_blank( string $id, string $fill ): string {
		return '<span class="lp-question-fib-input" data-id="' . $id . '">' . $fill . '</span>';
	}

	public function test_get_blanks_from_editor_content_fill_from_content_not_client(): void {
		$content = 'Capital of VN is ' . $this->editor_blank( 'b1', 'Hà Nội' );
		$client  = [
			'b1' => [
				'id'         => 'b1',
				'fill'       => 'Ha Noi',
				'match_case' => 1,
				'comparison' => 'any',
				'index'      => 1,
			],
		];

		$blanks = $this->make_question( [] )->get_blanks_from_editor_content( $content, $client );

		$this->assertSame(
			[
				'b1' => [
					'id'         => 'b1',
					'fill'       => 'Hà Nội',
					'match_case' => 1,
					'comparison' => 'any',
					'index'      => 1,
				],
			],
			$blanks
		);
	}

	public function test_get_blanks_from_editor_content_decodes_entities(): void {
		$content = $this->editor_blank( 'b1', 'H&agrave; n&#7897;i &amp; Hu&#7871;' );

		$blanks = $this->make_question( [] )->get_blanks_from_editor_content( $content );

		$this->assertSame( 'Hà nội & Huế', $blanks['b1']['fill'] );
	}

	public function test_get_blanks_from_editor_content_new_blank_gets_defaults(): void {
		$content = $this->editor_blank( 'b1', 'Hà Nội' ) . ' ' . $this->editor_blank( 'b2', 'Tokyo' );
		$client  = [
			'b1' => [
				'match_case' => 1,
				'comparison' => '',
			],
		];

		$blanks = $this->make_question( [] )->get_blanks_from_editor_content( $content, $client );

		$this->assertSame( 'equal', $blanks['b1']['comparison'] );
		$this->assertSame( 1, $blanks['b1']['match_case'] );
		$this->assertSame(
			[
				'id'         => 'b2',
				'fill'       => 'Tokyo',
				'match_case' => 0,
				'comparison' => 'equal',
			],
			$blanks['b2']
		);
	}

	public function test_get_blanks_from_editor_content_removes_blank_not_in_content(): void {
		$content = $this->editor_blank( 'b1', 'Hà Nội' );
		$client  = [
			'b1' => [ 'fill' => 'Hà Nội' ],
			'b2' => [ 'fill' => 'Tokyo' ],
		];

		$blanks = $this->make_question( [] )->get_blanks_from_editor_content( $content, $client );

		$this->assertSame( [ 'b1' ], array_keys( $blanks ) );
	}

	public function test_get_blanks_from_editor_content_no_blank_is_empty(): void {
		$blanks = $this->make_question( [] )->get_blanks_from_editor_content( 'No blank', [ 'b1' => [ 'fill' => 'x' ] ] );

		$this->assertSame( [], $blanks );
	}

	public function test_check_no_blanks_is_wrong(): void {
		$question = $this->make_question( [ $this->make_config_answer( '' ) ] );

		$result = $question->check( [ 'b1' => 'Ha Noi' ] );

		$this->assertFalse( $result['correct'] );
		$this->assertSame( [], $result['blanks'] );
	}
}
