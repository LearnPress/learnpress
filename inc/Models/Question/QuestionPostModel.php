<?php

/**
 * Class Question Post Model
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.8.8
 */

namespace LearnPress\Models\Question;

use Exception;
use LearnPress\Models\PostModel;
use LP_Cache;
use LP_Question_Answers_DB;
use LP_Question_Answers_Filter;
use LP_Question_Filter;

class QuestionPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_QUESTION_CPT;

	protected $_answer_options = array();

	public $question_type = '';

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

	public function get_answer_option() {
		try {
			if ( empty( $this->_answer_options ) ) {
				$db                    = LP_Question_Answers_DB::getInstance();
				$filter                = new LP_Question_Answers_Filter();
				$filter->where[]       = "AND question_id = ({$this->ID})";
				$this->_answer_options = $db->get_question_answers( $filter );
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $this->_answer_options;
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
		return $this->get_meta_value_by_key( self::META_KEY_TYPE, '' );
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
	 * Get class name of Template html
	 *
	 * @param string $type
	 *
	 * @return string
	 */
	public function get_template_by_type( string $type = '' ): string {
		if ( empty( $type ) ) {
			$type = $this->get_type();
		}

		switch ( $type ) {
			case 'true_or_false':
				return 'question-true-or-false';
			case 'multi_choice':
				return 'question-multi-choice';
			case 'single_choice':
				return 'question-single-choice';
			case 'fill_in_blanks':
				return 'question-fill-in-blanks';
			default:
				return '';
		}
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
	 * Create answers for question
	 * For case question does not have answers yet.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function create_default_answers() {
		global $wpdb;

		$answers = $this->get_default_answers();

		foreach ( $answers as $index => $answer ) {
			$answer = array(
				'question_id' => $this->get_id(),
				'title'       => $answer['title'],
				'value'       => $answer['value'] ?? '',
				'is_true'     => ( $answer['is_true'] == 'yes' ) ? $answer['is_true'] : '',
				'order'       => $index + 1,
			);

			$db = LP_Question_Answers_DB::getInstance();
			$db->wpdb->insert(
				$db->tb_lp_question_answers,
				$answer,
				array( '%d', '%s', '%s', '%s', '%d' )
			);

			$question_answer_id = $wpdb->insert_id;

			if ( $question_answer_id ) {
				$answer['question_answer_id'] = $question_answer_id;
			}

			$answers[ $index ] = $answer;
		}
	}

	/**
	 * Get default question list answers.
	 *
	 * @return array
	 * @move from class LP_Question old
	 */
	public function get_default_answers(): array {
		$answers = array(
			array(
				'question_answer_id' => - 1,
				'is_true'            => 'yes',
				'value'              => learn_press_random_value(),
				'title'              => esc_html__( 'First option', 'learnpress' ),
			),
			array(
				'question_answer_id' => - 2,
				'is_true'            => 'no',
				'value'              => learn_press_random_value(),
				'title'              => esc_html__( 'Second option', 'learnpress' ),
			),
			array(
				'question_answer_id' => - 3,
				'is_true'            => 'no',
				'value'              => learn_press_random_value(),
				'title'              => esc_html__( 'Third option', 'learnpress' ),
			),
		);

		return $answers;
	}
}
