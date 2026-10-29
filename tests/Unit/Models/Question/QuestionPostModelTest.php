<?php

declare( strict_types=1 );

namespace LearnPress\Tests\Unit\Models\Question;

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Exception;
use LearnPress\Models\Question\QuestionAnswerConfigModel;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Tests\Helpers\BrainMonkeyTestCase;
use LP_Debug;
use stdClass;
use Throwable;

/**
 * Unit tests for QuestionPostModel answer handling.
 * The question type and answers config are set directly, so no DB access is needed.
 */
class QuestionPostModelTest extends BrainMonkeyTestCase {

	protected function setUp(): void {
		parent::setUp();

		if ( ! class_exists( 'LP_Helper', false ) ) {
			eval(
				'class LP_Helper {
					public static function json_decode( string $json, bool $assoc = false ) {
						return json_decode( $json, $assoc );
					}
				}'
			);
		}

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

		LP_Debug::$logged = [];
	}

	/**
	 * Build a question of $type without loading post/meta from DB.
	 */
	private function make_question( string $type, array $default_answers = [] ): QuestionPostModel {
		return new class( $type, $default_answers ) extends QuestionPostModel {
			public array $default_answers;
			public array $saved_configs = [];

			public function __construct( string $type, array $default_answers ) {
				$this->question_type   = $type;
				$this->default_answers = $default_answers;
			}

			public function get_type() {
				return $this->question_type;
			}

			public function get_default_answers(): array {
				return $this->default_answers;
			}

			public function save_answers_config( array $answers ): void {
				$this->saved_configs[] = $answers;
			}
		};
	}

	/**
	 * Build a question whose answers config is $config (false = no row, Throwable = DB error).
	 * get_answers_config() is overridden, so no DB access.
	 *
	 * @param QuestionAnswerConfigModel|false|Throwable $config
	 */
	private function make_question_with_config( string $type, $config ): QuestionPostModel {
		return new class( $type, $config ) extends QuestionPostModel {
			public $config;
			public int $find_calls = 0;
			public array $legacy_answers;

			public function __construct( string $type, $config ) {
				$this->ID             = 15;
				$this->question_type  = $type;
				$this->config         = $config;
				$this->legacy_answers = [ new QuestionAnswerModel( [ 'question_answer_id' => 99 ] ) ];
			}

			public function get_type() {
				return $this->question_type;
			}

			public function get_answer_option() {
				return $this->legacy_answers;
			}

			public function get_answers_config(): array {
				++$this->find_calls;
				if ( $this->config instanceof Throwable ) {
					throw $this->config;
				}

				return $this->config instanceof QuestionAnswerConfigModel ? $this->config->get_answers() : [];
			}

			public function normalize( array $answers ): array {
				return $this->normalize_config_answers_to_model_legacy( $answers );
			}
		};
	}

	/**
	 * Build a question use real get_answers_config(), config model read from cache (wp_cache_get stubbed).
	 */
	private function make_question_read_config( string $type ): QuestionPostModel {
		return new class( $type ) extends QuestionPostModel {
			public function __construct( string $type ) {
				$this->ID            = 15;
				$this->question_type = $type;
			}

			public function get_type() {
				return $this->question_type;
			}
		};
	}

	private function make_config( string $answers_json ): QuestionAnswerConfigModel {
		$config          = new QuestionAnswerConfigModel();
		$config->answers = $answers_json;

		return $config;
	}

	/**
	 * Run callback without write error_log to output (get_answers_for_legacy_type log for not legacy type).
	 */
	private function without_error_log( callable $callback ) {
		$old = ini_set( 'error_log', '/dev/null' );
		try {
			return $callback();
		} finally {
			ini_set( 'error_log', (string) $old );
		}
	}

	public function test_get_answers_config_empty_answers_returns_empty(): void {
		Functions\when( 'wp_cache_get' )->justReturn( $this->make_config( '' ) );

		$this->assertSame( [], $this->make_question_read_config( 'image_map' )->get_answers_config() );
	}

	public function test_get_answers_config_returns_json_untouched(): void {
		$json = '{"image":"map.png","points":[{"x":10,"y":20}]}';
		Functions\when( 'wp_cache_get' )->justReturn( $this->make_config( $json ) );

		$this->assertSame(
			[
				'image'  => 'map.png',
				'points' => [
					[
						'x' => 10,
						'y' => 20,
					],
				],
			],
			$this->make_question_read_config( 'image_map' )->get_answers_config()
		);
	}

	public function test_get_answers_config_error_logs_and_returns_empty(): void {
		Functions\when( 'wp_cache_get' )->alias(
			function () {
				throw new Exception( 'DB error' );
			}
		);

		$this->assertSame( [], $this->make_question_read_config( 'image_map' )->get_answers_config() );
		$this->assertSame( [ 'DB error' ], LP_Debug::$logged );
	}

	public function test_get_answers_for_legacy_type_no_row_reads_legacy(): void {
		$question = $this->make_question_with_config( 'single_choice', false );

		$this->assertSame( $question->legacy_answers, $question->get_answers_for_legacy_type() );
	}

	public function test_get_answers_for_legacy_type_empty_answers_reads_legacy(): void {
		$question = $this->make_question_with_config( 'single_choice', $this->make_config( '[]' ) );

		$this->assertSame( $question->legacy_answers, $question->get_answers_for_legacy_type() );
	}

	public function test_get_answers_for_legacy_type_error_logs_and_reads_legacy(): void {
		$question = $this->make_question_with_config( 'single_choice', new Exception( 'DB error' ) );

		$this->assertSame( $question->legacy_answers, $question->get_answers_for_legacy_type() );
		$this->assertSame( [ 'DB error' ], LP_Debug::$logged );
	}

	public function test_get_answers_for_legacy_type_new_type_returns_empty_without_reading(): void {
		$question = $this->make_question_with_config(
			'image_map',
			$this->make_config( '{"image":"map.png"}' )
		);

		$answers = $this->without_error_log( fn() => $question->get_answers_for_legacy_type() );

		$this->assertSame( [], $answers );
		$this->assertSame( 0, $question->find_calls );
	}

	public function test_get_answers_for_legacy_type_returns_config_as_stored(): void {
		$json     = '[{"title":"A","value":"v1","order":1,"is_true":"yes"},{"title":"B","value":"v2","order":2,"is_true":""}]';
		$question = $this->make_question_with_config( 'single_choice', $this->make_config( $json ) );

		$this->assertSame( json_decode( $json, true ), $question->get_answers_for_legacy_type() );
	}

	public function test_get_answers_for_legacy_type_cached_on_instance(): void {
		$json     = '[{"title":"A","value":"v1","order":1,"is_true":"yes"}]';
		$question = $this->make_question_with_config( 'single_choice', $this->make_config( $json ) );

		$first  = $question->get_answers_for_legacy_type();
		$second = $question->get_answers_for_legacy_type();

		$this->assertSame( $first, $second );
		$this->assertSame( 1, $question->find_calls );
	}

	public function test_normalize_config_answers_maps_sync_shape(): void {
		$json     = '[{"title":"A","value":"v1","order":1,"is_true":"yes"},{"title":"B","value":"v2","order":2,"is_true":""}]';
		$question = $this->make_question_with_config( 'single_choice', false );

		$answers = $question->normalize( json_decode( $json, true ) );

		$this->assertCount( 2, $answers );
		$this->assertContainsOnlyInstancesOf( QuestionAnswerModel::class, $answers );
		$this->assertNull( $answers[0]->question_answer_id );
		$this->assertSame( '15', $answers[0]->question_id );
		$this->assertSame( 'A', $answers[0]->title );
		$this->assertSame( 'v1', $answers[0]->value );
		$this->assertSame( '1', $answers[0]->order );
		$this->assertSame( 'yes', $answers[0]->is_true );
		$this->assertSame( '', $answers[1]->is_true );
		$this->assertInstanceOf( stdClass::class, $answers[0]->meta_data );
	}

	public function test_normalize_config_answers_object_shape_drops_id(): void {
		$json     = '[{"question_answer_id":"7","question_id":"3","title":"A","value":"v1","order":"1","is_true":"yes","meta_data":{},"is_get_all_metadata":false}]';
		$question = $this->make_question_with_config( 'true_or_false', false );

		$answers = $question->normalize( json_decode( $json, true ) );

		$this->assertNull( $answers[0]->question_answer_id );
		$this->assertSame( '15', $answers[0]->question_id );
		$this->assertSame( 'v1', $answers[0]->value );
	}

	public function test_normalize_config_answers_missing_order_and_is_true_use_defaults(): void {
		$json     = '[{"title":"A","value":"v1"},{"title":"B","value":"v2"}]';
		$question = $this->make_question_with_config( 'multi_choice', false );

		$answers = $question->normalize( json_decode( $json, true ) );

		$this->assertSame( [ '1', '2' ], [ $answers[0]->order, $answers[1]->order ] );
		$this->assertSame( '', $answers[0]->is_true );
	}

	public function test_normalize_config_answers_sorts_by_order(): void {
		$json     = '[{"value":"c","order":3},{"value":"a","order":1},{"value":"b1","order":2},{"value":"b2","order":2}]';
		$question = $this->make_question_with_config( 'multi_choice', false );

		$values = array_map(
			fn( $answer ) => $answer->value,
			$question->normalize( json_decode( $json, true ) )
		);

		$this->assertSame( [ 'a', 'b1', 'b2', 'c' ], $values );
	}

	public function test_normalize_config_answers_fib_blanks_from_meta(): void {
		$json     = '[{"title":"[fib id=\"x1\"]","value":"v1","order":1,"is_true":"yes","meta":{"_blanks":{"x1":{"id":"x1","fill":"Ha Noi"}}}}]';
		$question = $this->make_question_with_config( 'fill_in_blanks', false );

		$answers = $question->normalize( json_decode( $json, true ) );

		$this->assertSame( [ 'x1' => [ 'id' => 'x1', 'fill' => 'Ha Noi' ] ], $answers[0]->meta_data );
		$this->assertTrue( $answers[0]->is_get_all_metadata );
		$this->assertSame( $answers[0]->meta_data, $answers[0]->get_all_metadata() );
	}

	public function test_normalize_config_answers_fib_blanks_from_meta_data(): void {
		$json     = '[{"question_answer_id":"7","title":"t","value":"v1","order":"1","is_true":"yes","meta_data":{"x1":{"id":"x1","fill":"Hue"}},"is_get_all_metadata":true}]';
		$question = $this->make_question_with_config( 'fill_in_blanks', false );

		$answers = $question->normalize( json_decode( $json, true ) );

		$this->assertSame( [ 'x1' => [ 'id' => 'x1', 'fill' => 'Hue' ] ], $answers[0]->meta_data );
		$this->assertTrue( $answers[0]->is_get_all_metadata );
	}

	public function test_normalize_config_answers_fib_without_blanks_is_empty_string(): void {
		$json     = '[{"title":"t","value":"v1","order":1,"is_true":"yes"}]';
		$question = $this->make_question_with_config( 'fill_in_blanks', false );

		$this->assertSame( '', $question->normalize( json_decode( $json, true ) )[0]->meta_data );
	}

	public function test_get_answer_options_for_js_map_uid_from_answer_id(): void {
		Functions\when( 'wp_parse_args' )->alias( fn( $args, $defaults ) => array_merge( $defaults, $args ) );
		Functions\when( 'do_shortcode' )->returnArg();
		$question                 = $this->make_question_with_config( 'single_choice', false );
		$question->legacy_answers = [
			new QuestionAnswerModel(
				[
					'question_answer_id' => 7,
					'question_id'        => '15',
					'title'              => 'A',
					'value'              => 'v1',
					'order'              => '1',
					'is_true'            => 'yes',
				]
			),
			new QuestionAnswerModel(
				[
					'question_answer_id' => 8,
					'question_id'        => '15',
					'title'              => 'B',
					'value'              => 'v2',
					'order'              => '2',
					'is_true'            => '',
				]
			),
		];

		$options = $question->get_answer_options(
			[
				'exclude' => [ 'question_id', 'order', 'is_true' ],
				'map'     => [ 'question_answer_id' => 'uid' ],
			]
		);

		$this->assertSame(
			[
				[
					'title' => 'A',
					'value' => 'v1',
					'uid'   => 7,
				],
				[
					'title' => 'B',
					'value' => 'v2',
					'uid'   => 8,
				],
			],
			$options
		);
	}

	public function test_get_answer_options_legacy_keeps_answer_id(): void {
		Functions\when( 'wp_parse_args' )->alias( fn( $args, $defaults ) => array_merge( $defaults, $args ) );
		$question = $this->make_question_with_config( 'single_choice', false );

		$this->assertSame( 99, $question->get_answer_options()[0]['question_answer_id'] );
	}

	public function test_create_default_answers_new_type_saves_config_as_is(): void {
		$default_answers = [
			'image'  => 'map.png',
			'points' => [
				[ 'x' => 10, 'y' => 20 ],
			],
		];
		$question        = $this->make_question( 'image_map', $default_answers );

		$result = $question->create_default_answers();

		$this->assertSame( $default_answers, $result );
		$this->assertSame( [ $default_answers ], $question->saved_configs );
	}

	/**
	 * @dataProvider provider_legacy_types
	 */
	public function test_is_legacy_answer_type_legacy_types_return_true( string $type ): void {
		$this->assertTrue( $this->make_question( $type )->is_legacy_answer_type() );
	}

	public function provider_legacy_types(): array {
		return [
			[ 'true_or_false' ],
			[ 'single_choice' ],
			[ 'multi_choice' ],
			[ 'fill_in_blanks' ],
			[ 'sorting_choice' ],
		];
	}

	public function test_is_legacy_answer_type_new_type_returns_false(): void {
		$this->assertFalse( $this->make_question( 'image_map' )->is_legacy_answer_type() );
	}

	public function test_is_legacy_answer_type_empty_type_returns_false(): void {
		$this->assertFalse( $this->make_question( '' )->is_legacy_answer_type() );
	}

	/**
	 * Build a question with meta values, not read DB.
	 */
	private function make_question_with_meta( array $meta ): QuestionPostModel {
		return new class( $meta ) extends QuestionPostModel {
			public array $meta;

			public function __construct( array $meta ) {
				$this->ID   = 15;
				$this->meta = $meta;
			}

			public function get_meta_value_by_key( string $key, $default_value = false, bool $single = true ) {
				return $this->meta[ $key ] ?? $default_value;
			}
		};
	}

	private function stub_hint_explanation(): void {
		Functions\when( 'wp_kses_allowed_html' )->justReturn( [] );
		Functions\when( 'wp_kses' )->alias( fn( $content ) => strip_tags( $content, '<strong>' ) );
		Functions\when( 'do_shortcode' )->alias( fn( $content ) => str_replace( '[b]', '<strong>B</strong>', $content ) );
	}

	public function test_get_hint_do_shortcode_and_sanitize(): void {
		$this->stub_hint_explanation();

		$question = $this->make_question_with_meta( [ QuestionPostModel::META_KEY_HINT => 'Hint [b]<script>x</script>' ] );

		$this->assertSame( 'Hint <strong>B</strong>x', $question->get_hint() );
	}

	public function test_get_hint_apply_filter_with_question_id(): void {
		$this->stub_hint_explanation();
		Filters\expectApplied( 'learn-press/question/hint' )
			->once()
			->with( 'Hint <strong>B</strong>', 15 )
			->andReturn( 'Filtered <script>bad</script>' );

		$question = $this->make_question_with_meta( [ QuestionPostModel::META_KEY_HINT => 'Hint [b]' ] );

		// Filter result still sanitized.
		$this->assertSame( 'Filtered bad', $question->get_hint() );
	}

	public function test_get_hint_empty_meta_returns_empty(): void {
		$this->stub_hint_explanation();

		$this->assertSame( '', $this->make_question_with_meta( [] )->get_hint() );
	}

	public function test_get_explanation_do_shortcode_and_sanitize(): void {
		$this->stub_hint_explanation();

		$question = $this->make_question_with_meta( [ QuestionPostModel::META_KEY_EXPLANATION => 'Why [b]<script>x</script>' ] );

		$this->assertSame( 'Why <strong>B</strong>x', $question->get_explanation() );
	}

	public function test_get_explanation_apply_filter_with_question_id(): void {
		$this->stub_hint_explanation();
		Filters\expectApplied( 'learn-press/question/explanation' )
			->once()
			->with( 'Why <strong>B</strong>', 15 )
			->andReturn( 'Filtered' );

		$question = $this->make_question_with_meta( [ QuestionPostModel::META_KEY_EXPLANATION => 'Why [b]' ] );

		$this->assertSame( 'Filtered', $question->get_explanation() );
	}
}
