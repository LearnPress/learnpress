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
	 * Prepare question data for rendering
	 *
	 * @param int   $question_id Question ID
	 * @param array $args        Arguments array with keys:
	 *                           - instant_check: bool
	 *                           - quiz_status: string
	 *                           - checked_questions: array
	 *                           - answered: array
	 *                           - show_correct_review: bool
	 *                           - status: string
	 *
	 * @return array Question data array
	 * @since 4.2.9
	 */
	public static function prepare_render_data( int $question_id, array $args = [] ): array {
		// Parse arguments with defaults
		$args = wp_parse_args(
			$args,
			[
				'instant_check'       => true,
				'quiz_status'         => '',
				'checked_questions'   => [],
				'answered'            => [],
				'show_correct_review' => true,
				'status'              => '',
			]
		);

		// Get question object
		$question = learn_press_get_question( $question_id );
		if ( ! $question ) {
			return [];
		}

		// Extract args
		$instantCheck     = $args['instant_check'];
		$quizStatus       = $args['quiz_status'];
		$checkedQuestions = $args['checked_questions'];
		$answered         = $args['answered'];
		$status           = $args['status'];

		// Initialize variables
		$checked        = false;
		$theHint        = $question->get_hint();
		$theExplanation = '';
		$hasExplanation = false;

		// Check if we should show explanation
		if ( $instantCheck || $status == 'completed' ) {
			$theExplanation = $question->get_explanation();
			$checked        = in_array( $question_id, $checkedQuestions );
			$hasExplanation = ! ! $theExplanation;
		}

		// Get mark/point
		$mark = $question->get_mark() ? $question->get_mark() : 1;

		// Build question data array
		$questionData = [
			'object'  => $question,
			'id'      => absint( $question_id ),
			'title'   => $question->get_title(),
			'type'    => $question->get_type(),
			'point'   => $mark,
			'checked' => $checked,
		];

		// Add content if available
		$content = $question->get_content();
		if ( $content ) {
			$questionData['content'] = $content;
		}

		// Add hint if available
		if ( $theHint ) {
			$questionData['hint'] = $theHint;
		}

		// Add explanation based on status
		if ( $status == 'completed' || ( $checked && $theExplanation ) ) {
			$questionData['explanation'] = $theExplanation;
		}

		// Add has_explanation flag
		if ( $hasExplanation ) {
			$questionData['has_explanation'] = $hasExplanation;

			if ( $checked ) {
				$questionData['explanation'] = $theExplanation;
			}
		}
		$questionData['answered'] = $answered[ $question_id ]['answered'] ?? array();

		// Determine if we should include correct answers
		$with_true_or_false = ( $checked || ( $quizStatus == 'completed' && $args['show_correct_review'] ) );

		$questionData['show_correct_review'] = $with_true_or_false;
		
		$questionData['disabled'] = $checked || $quizStatus === 'completed';

		// Get answer options
		$questionData['options'] = learn_press_get_question_options_for_js(
			$question,
			[
				'include_is_true' => $with_true_or_false,
				'answer'          => $answered[ $question_id ]['answered'] ?? '',
			]
		);

		return apply_filters( 'learn-press/question/prepare-render-data', $questionData, $question_id, $args );
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
