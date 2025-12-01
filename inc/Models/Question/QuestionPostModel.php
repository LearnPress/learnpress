<?php

/**
 * Class Question Post Model
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.9
 */

namespace LearnPress\Models\Question;

use ClassPress\Helpers\Debug;
use Exception;
use LearnPress\Databases\QuestionAnswersDB;
use LearnPress\Filters\QuestionAnswersFilter;
use LearnPress\Models\PostModel;
use LP_Cache;
use LP_Question_Filter;

class QuestionPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_QUESTION_CPT;

	protected $answer_options = [];

	private $question_type = '';

	/**
	 * Const meta key
	 */
	const META_KEY_TYPE        = '_lp_type';
	const META_KEY_MARK        = '_lp_mark';
	const META_KEY_HINT        = '_lp_hint';
	const META_KEY_EXPLANATION = '_lp_explanation';

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
		// Set cache
		if ( $questionPostModel instanceof QuestionPostModel ) {
			$lpQuizCache->set_cache( $key_cache, $questionPostModel );
		}

		return $questionPostModel;
	}

	/**
	 * Get answer options for question
	 */
	public function get_answer_option() {
		try {
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
			Debug::error_log( $e );
		}

		return $this->answer_options;
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

		// For addon sorting choice old <= v4.0.1
		if ( class_exists( 'LP_Addon_Sorting_Choice_Preload' ) && $type === 'sorting_choice' ) {
			if ( version_compare( LP_ADDON_SORTING_CHOICE_VER, '4.0.1', '<=' ) ) {
				return QuestionSortingChoiceModel::class;
			}
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
			default:
				return apply_filters( 'learn-press/question-object-by-type', '', $type );
		}
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
		return $this->get_meta_value_by_key( self::META_KEY_HINT, '' );
	}

	/**
	 * @return mixed
	 */
	public function get_explanation() {
		return $this->get_meta_value_by_key( self::META_KEY_EXPLANATION, '' );
	}

	/**
	 * @return mixed
	 */
	public function get_mark() {
		return $this->get_meta_value_by_key( self::META_KEY_MARK, 1 );
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

		foreach ( $answers as $index => $answer ) {
			$answer = array(
				'question_id' => $this->get_id(),
				'title'       => $answer['title'],
				'value'       => $answer['value'] ?? '',
				'is_true'     => $answer['is_true'] ?? '',
				'order'       => $index + 1,
			);

			$questionAnswerModel = new QuestionAnswerModel( $answer );
			$questionAnswerModel->save();
			$answers[ $index ]['question_answer_id'] = $questionAnswerModel->question_answer_id;
		}

		return $answers;
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
}
