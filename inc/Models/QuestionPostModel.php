<?php

/**
 * Class Question Post Model
 * To replace class LP_Question old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.7.1
 */

namespace LearnPress\Models;

use Exception;
use LP_Question_Answers_DB;
use LP_Question_Answers_Filter;
use LP_Question_Filter;

class QuestionPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_QUESTION_CPT;

	protected $_answer_options = array();

	/**
	 * Const meta key
	 */
	const META_KEY_TYPE        = '_lp_type';
	const META_KEY_SAMPLE_DATA = '_lp_sample_data';
	const META_KEY_MARK        = '_lp_mark';
	const META_KEY_HINT        = '_lp_hint';
	const META_KEY_EXPLANATION = '_lp_explanation';

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
	 * Get all type of questions
	 *
	 * @return array
	 */
	public static function get_types() {
		$types = apply_filters(
			'learn-press/question-types',
			array(
				'true_or_false'  => esc_html__( 'True Or False', 'learnpress' ),
				'multi_choice'   => esc_html__( 'Multi Choice', 'learnpress' ),
				'single_choice'  => esc_html__( 'Single Choice', 'learnpress' ),
				'fill_in_blanks' => esc_html__( 'Fill In Blanks', 'learnpress' ),
			)
		);

		return apply_filters( 'learn_press_question_types', $types );
	}

	/**
	 * Get type question
	 *
	 * @return string|float
	 */
	public function get_type() {
		if ( empty( $this->meta_data->type ) ) {
			$type = $this->get_meta_value_by_key( self::META_KEY_TYPE, '' );
			apply_filters( 'learn-press/question/type', $type, $this->ID );
			$this->meta_data->type = $type;
		}

		return $this->meta_data->type;
	}

	/**
	 * @return mixed
	 */
	public function get_hint() {
		if ( empty( $this->meta_data->hint ) ) {
			$hint = $this->get_meta_value_by_key( self::META_KEY_HINT, '' );
			apply_filters( 'learn-press/question/hint', $hint, $this->ID );
			$this->meta_data->hint = $hint;
		}

		return $this->meta_data->hint;
	}

	/**
	 * @return mixed
	 */
	public function get_explanation() {
		if ( empty( $this->meta_data->explanation ) ) {
			$explanation = $this->get_meta_value_by_key( self::META_KEY_EXPLANATION, '' );
			apply_filters( 'learn-press/question/explanation', $explanation, $this->ID );
			$this->meta_data->explanation = $explanation;
		}

		return $this->meta_data->explanation;
	}

	/**
	 * @return mixed
	 */
	public function get_mark() {
		if ( empty( $this->meta_data->mark ) ) {
			$mark = $this->get_meta_value_by_key( self::META_KEY_MARK, '' );
			apply_filters( 'learn-press/question/mark', $mark, $this->ID );
			$this->meta_data->mark = $mark;
		}

		return $this->meta_data->mark;
	}

	public static function get_default_meta() {
		$meta = array(
			'mark'        => 1,
			'explanation' => null,
			'hint'        => null,
		);

		return apply_filters( 'learn-press/question/default-meta', $meta );
	}

	/**
	 * Return type of question in 'readable text'.
	 *
	 * @return string
	 */
	public function get_type_label() {
		$type  = $this->get_type();
		$types = $this->get_types();
		$label = $types[ $type ];
		return $label;
	}

	/**
	 * Get default question list answers.
	 *
	 * @return array|bool
	 */
	public function get_default_answers() {
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

		return self::get_item_model_from_db( $filter_post );
	}
}
