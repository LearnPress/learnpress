<?php

namespace LearnPress\Models\Question;

use Exception;
use LearnPress\Databases\QuestionAnswersDB;
use LearnPress\Filters\QuestionAnswersFilter;
use LearnPress\Helpers\Template;
use LearnPress\Models\PostModel;
use LP_Cache;
use LP_Debug;
use LP_Question_Filter;
use ReflectionMethod;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Class Question Post Model
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.2
 * @since 4.2.9
 */

class QuestionPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_QUESTION_CPT;

	protected $answer_options = [];

	/**
	 * @var QuestionAnswerModel[] Answers read from new table question_answer_config
	 */
	protected $answer_options_config = [];

	protected $question_type = '';

	/**
	 * Const meta key
	 */
	const META_KEY_TYPE        = '_lp_type';
	const META_KEY_MARK        = '_lp_mark';
	const META_KEY_HINT        = '_lp_hint';
	const META_KEY_EXPLANATION = '_lp_explanation';

	/**
	 * Question types store answers with fixed keys: title, value, is_true, order.
	 * Also save on old tables learnpress_question_answers, learnpress_question_answermeta.
	 * Other types store free-form JSON on table learnpress_question_answer_config.
	 *
	 * @since 4.5.0
	 */
	const LEGACY_ANSWER_TYPES = [
		'true_or_false',
		'single_choice',
		'multi_choice',
		'fill_in_blanks',
		'sorting_choice',
	];

	/**
	 * Get post question by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter_post     = new LP_Question_Filter();
		$filter_post->ID = $post_id;

		$key_cache   = "questionPostModel/find/{$post_id}";
		$lpQuizCache = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$questionPostModel = $lpQuizCache->get_cache( $key_cache );
			if ( $questionPostModel instanceof QuestionPostModel ) {
				return $questionPostModel;
			}
		}

		$questionPostModel = self::get_item_model_from_db( $filter_post );

		if ( $questionPostModel instanceof QuestionPostModel ) {
			$type        = $questionPostModel->get_type();
			$model_class = self::get_question_obj_by_type( $type );

			if ( $model_class && class_exists( $model_class )
				&& is_subclass_of( $model_class, self::class ) ) {
				$questionPostModel = new $model_class( $questionPostModel );
			}

			$lpQuizCache->set_cache( $key_cache, $questionPostModel );
		}

		return $questionPostModel;
	}

	/**
	 * Get answers config (new table) as stored, not map keys.
	 * Use for all types, new types read their JSON by this method.
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_answers_config(): array {
		try {
			$answerConfigModel = QuestionAnswerConfigModel::find_by_question_id(
				$this->get_id(),
				true
			);
			if ( $answerConfigModel instanceof QuestionAnswerConfigModel ) {
				return $answerConfigModel->get_answers();
			}
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return [];
	}

	/**
	 * Get answer options for question
	 * From old table learnpress_question_answers
	 * If code new use get_answers_for_legacy_type() to instead
	 */
	public function get_answer_option() {
		try {
			if ( ! $this->is_legacy_answer_type() ) {
				return $this->answer_options;
			}

			if ( empty( $this->answer_options ) ) {
				$db                  = QuestionAnswersDB::getInstance();
				$filter              = new QuestionAnswersFilter();
				$filter->question_id = $this->get_id();
				$filter->limit       = -1; // Get all answers for question
				$answers_rs          = $db->get_question_answers( $filter );
				$answer_options      = [];

				foreach ( $answers_rs as $answer ) {
					$questionAnswerModel = new QuestionAnswerModel( $answer );
					$questionAnswerModel->get_all_metadata();
					$answer_options[] = $questionAnswerModel;
				}

				$this->answer_options = $answer_options;
			}
		} catch ( Exception $e ) {
			LP_Debug::error_log( $e );
		}

		return $this->answer_options;
	}

	/**
	 * Get answer options for legacy types, read from new table question_answer_config.
	 * If question has not config, read from old tables via get_answer_option().
	 *
	 * @return QuestionAnswerModel[]|array[] array[] if read from config, QuestionAnswerModel[] if read from old tables
	 * @throws Exception
	 * @version 1.0.0
	 * @since 4.5.0
	 */
	public function get_answers_for_legacy_type(): array {
		if ( ! empty( $this->answer_options_config ) ) {
			return $this->answer_options_config;
		}

		// Only show log for DEV
		if ( ! $this->is_legacy_answer_type() ) {
			error_log( 'Question type is not legacy' );
			return $this->answer_options_config;
		}

		try {
			$answers = $this->get_answers_config();
			if ( empty( $answers ) ) {
				return $this->get_answer_option();
			}

			// Return answers config as stored (array), each type check both QuestionAnswerModel and array.
			$this->answer_options_config = $answers;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );

			return $this->get_answer_option();
		}

		return $this->answer_options_config;
	}

	/**
	 * Map answers config (JSON) of legacy types to QuestionAnswerModel, same as read from old tables.
	 * Answer config not store question_answer_id, so it is always null.
	 *
	 * @param array $answers
	 *
	 * @return QuestionAnswerModel[]
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	protected function normalize_config_answers_to_model_legacy( array $answers ): array {
		$is_fib         = $this->get_type() === 'fill_in_blanks';
		$answer_options = [];

		foreach ( array_values( $answers ) as $index => $answer ) {
			$answer = (array) $answer;
			// Values as string, same as read from old tables.
			$data = [
				'question_answer_id' => null,
				'question_id'        => (string) $this->get_id(),
				'title'              => (string) ( $answer['title'] ?? '' ),
				'value'              => (string) ( $answer['value'] ?? '' ),
				'order'              => (string) ( $answer['order'] ?? $index + 1 ),
				'is_true'            => (string) ( $answer['is_true'] ?? '' ),
			];

			if ( $is_fib ) {
				$meta                        = (array) ( $answer['meta'] ?? [] );
				$data['meta_data']           = $meta[ QuestionAnswerModel::META_KEY_BLANKS ] ?? $answer['meta_data'] ?? '';
				$data['is_get_all_metadata'] = true;
			}

			$answer_options[] = [
				'index' => $index,
				'model' => new QuestionAnswerModel( $data ),
			];
		}

		// Sort by order ASC as old tables, keep position when same order.
		usort(
			$answer_options,
			function ( $a, $b ) {
				return [ (int) $a['model']->order, $a['index'] ] <=> [ (int) $b['model']->order, $b['index'] ];
			}
		);

		return array_column( $answer_options, 'model' );
	}

	/**
	 * Get answer options as array.
	 * Compatible with learn_press_get_question_options_for_js().
	 *
	 * @param array $args
	 *
	 * @return array
	 */
	public function get_answer_options( $args = [] ): array {
		$args = wp_parse_args(
			$args,
			[
				'exclude' => '',
				'map'     => '',
				'answer'  => '',
			]
		);

		if ( $args['exclude'] && is_string( $args['exclude'] ) ) {
			$exclude = array_map( 'trim', explode( ',', $args['exclude'] ) );
		} else {
			$exclude = $args['exclude'];
		}

		$map = $args['map'];

		$answer_models = $this->get_answer_option();
		$options       = [];

		foreach ( $answer_models as $answer_model ) {
			$options[] = [
				'question_answer_id' => $answer_model->question_answer_id,
				'question_id'        => $answer_model->question_id,
				'title'              => $answer_model->title,
				'value'              => $answer_model->value,
				'order'              => $answer_model->order,
				'is_true'            => $answer_model->is_true,
			];
		}

		if ( $options && ( $exclude || $map ) ) {
			$exclude = array_flip( $exclude );

			foreach ( $options as $k => $option ) {
				$option['title'] = do_shortcode( $option['title'] );

				foreach ( $map as $k_map => $v_map ) {
					if ( array_key_exists( $k_map, $option ) ) {
						$option[ $v_map ]  = $option[ $k_map ];
						$exclude[ $k_map ] = 1;
					}
				}

				$options[ $k ] = array_diff_key( $option, $exclude );
			}
		}

		return apply_filters( 'learn-press/question/answer-options', $options, $this->get_id() );
	}

	/**
	 * Check user answer.
	 * Override in type-specific subclasses.
	 *
	 * @param mixed $user_answer
	 *
	 * @return array
	 */
	public function check( $user_answer = null ): array {
		return [
			'correct' => false,
			'mark'    => 0,
		];
	}

	/**
	 * Get all types of question
	 *
	 * @return array
	 */
	public static function get_types(): array {
		$types = [
			'true_or_false'  => esc_html__( 'True Or False', 'learnpress' ),
			'multi_choice'   => esc_html__( 'Multi Choice', 'learnpress' ),
			'single_choice'  => esc_html__( 'Single Choice', 'learnpress' ),
			'fill_in_blanks' => esc_html__( 'Fill In Blanks', 'learnpress' ),
		];

		return apply_filters( 'learn-press/question-types', $types );
	}

	/**
	 * Get question object by type
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public static function get_question_obj_by_type( string $type = '' ): string {
		$types = self::get_types();

		if ( ! array_key_exists( $type, $types ) ) {
			return '';
		}

		switch ( $type ) {
			case 'true_or_false':
				return QuestionPostTrueFalseModel::class;
			case 'multi_choice':
				return QuestionPostMultipleChoiceModel::class;
			case 'single_choice':
				return QuestionPostSingleChoiceModel::class;
			case 'fill_in_blanks':
				return QuestionPostFIBModel::class;
			case 'sorting_choice':
				// Addon sorting choice old (model not declare check), use model of LP to check answer.
				$model_class = apply_filters( 'learn-press/question-object-by-type', '', $type );
				if ( ! self::is_class_declare_check( $model_class ) ) {
					$model_class = QuestionSortingChoiceModel::class;
				}

				return $model_class;
			default:
				return apply_filters( 'learn-press/question-object-by-type', '', $type );
		}
	}

	/**
	 * Check class is model question and declare method check, not inherit from QuestionPostModel.
	 *
	 * @param mixed $model_class
	 *
	 * @return bool
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public static function is_class_declare_check( $model_class ): bool {
		if ( ! is_string( $model_class ) || ! class_exists( $model_class )
			|| ! is_subclass_of( $model_class, self::class ) ) {
			return false;
		}

		try {
			$method = new ReflectionMethod( $model_class, 'check' );

			return $method->getDeclaringClass()->getName() !== self::class;
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return false;
	}

	/**
	 * Get type question
	 *
	 * @return string|float
	 */
	public function get_type() {
		if ( empty( $this->question_type ) ) {
			$this->question_type = $this->get_meta_value_by_key( self::META_KEY_TYPE, '' );
		}

		return $this->question_type;
	}

	/**
	 * Check type question is legacy answer type.
	 * Legacy type: answers have fixed keys (title, value, is_true, order) and save on old tables.
	 *
	 * @return bool
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function is_legacy_answer_type(): bool {
		return in_array( $this->get_type(), self::LEGACY_ANSWER_TYPES, true );
	}

	/**
	 * Check type question is valid
	 *
	 * @param string $type
	 *
	 * @return void
	 */
	public static function check_type_valid( string $type ): bool {
		$types = self::get_types();

		if ( ! array_key_exists( $type, $types ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Get type label by type
	 *
	 * @return string
	 */
	public function get_type_label(): string {
		$type  = $this->get_type();
		$types = self::get_types();

		if ( ! array_key_exists( $type, $types ) ) {
			return '';
		}

		return $types[ $type ];
	}

	/**
	 * @return mixed
	 */
	public function get_hint() {
		return Template::sanitize_html_content(
			(string) $this->get_meta_value_by_key( self::META_KEY_HINT, '' )
		);
	}

	/**
	 * @return mixed
	 */
	public function get_explanation() {
		return Template::sanitize_html_content(
			(string) $this->get_meta_value_by_key( self::META_KEY_EXPLANATION, '' )
		);
	}

	/**
	 * @return mixed
	 */
	public function get_mark() {
		return (float) $this->get_meta_value_by_key( self::META_KEY_MARK, 1 );
	}

	/**
	 * Create default answers for question
	 * For case question does not have answers yet.
	 *
	 * @return array
	 * @throws Exception
	 */
	public function create_default_answers(): array {
		$answers = $this->get_default_answers();

		if ( $this->is_legacy_answer_type() ) {
			foreach ( $answers as $index => $answer ) {
				$answer = array(
					'question_id' => $this->get_id(),
					'title' => $answer['title'] ?? '',
					'value' => $answer['value'] ?? '',
					'is_true' => $answer['is_true'] ?? '',
					'order' => $index + 1,
				);

				$questionAnswerModel = new QuestionAnswerModel( $answer );
				$questionAnswerModel->save();
				$answers[ $index ]['question_answer_id'] = $questionAnswerModel->question_answer_id;
			}
		}

		// For save new table question_answer_config - @since 4.5.0
		$this->save_answers_config( $answers );

		return $answers;
	}

	/**
	 * Save answers config (new table)
	 * Save array answers as-is, not prepare keys.
	 *
	 * @param array $answers
	 *
	 * @throws Exception
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function save_answers_config( array $answers ): void {
		$answerConfigModel = QuestionAnswerConfigModel::find_by_question_id(
			$this->get_id(),
			true
		);
		if ( ! $answerConfigModel instanceof QuestionAnswerConfigModel ) {
			$answerConfigModel              = new QuestionAnswerConfigModel();
			$answerConfigModel->question_id = $this->get_id();
		}

		$check_answers_valid = $this->check_answers_valid_before_save( $answers );
		if ( $check_answers_valid instanceof WP_Error ) {
			throw new Exception( $check_answers_valid->get_error_message() );
		}

		$answerConfigModel->set_answers( $answers );
		$answerConfigModel->save();
	}

	/**
	 * Get default question list answers.
	 *
	 * @return array
	 * @move from class LP_Question old
	 */
	public function get_default_answers(): array {
		return [];
	}

	/**
	 * Generate random value
	 *
	 * @param int $length
	 *
	 * @return string
	 */
	public function random_value( int $length = 10 ): string {
		return substr( md5( uniqid( mt_rand(), true ) ), 0, $length );
	}

	/**
	 * Check capabilities create question
	 *
	 * @return bool
	 */
	public function check_capabilities_create(): bool {
		return current_user_can( 'edit_' . LP_LESSON_CPT . 's' );
	}

	/**
	 * Check capabilities update question
	 *
	 * @return bool
	 */
	public function check_capabilities_update(): bool {
		return current_user_can( 'edit_' . LP_LESSON_CPT, $this->ID );
	}

	/**
	 * Check format answers valid before save
	 *
	 * @param $answers
	 * @return true|WP_Error
	 */
	public function check_answers_valid_before_save( $answers ) {
		return true;
	}
}
