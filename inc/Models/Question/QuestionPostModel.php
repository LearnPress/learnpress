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
	 * Get question instance with caching.
	 * Similar to LP_Question::get_question().
	 *
	 * @param mixed $the_question Question ID, WP_Post object, or QuestionPostModel instance.
	 * @param array $args {
	 *     Optional. Arguments for question retrieval.
	 *
	 *     @type bool   $force Force refresh cache.
	 *     @type string $type  Question type override.
	 * }
	 *
	 * @return QuestionPostModel|false Question instance or false on failure.
	 * @since 4.2.9
	 */
	public static function get_question( $the_question = false, $args = array() ) {
		// Get WP_Post object
		$post_object = self::get_question_post_object( $the_question );
		if ( ! $post_object ) {
			return false;
		}

		// Handle force parameter
		$force = ! empty( $args['force'] );
		if ( isset( $args['force'] ) ) {
			unset( $args['force'] );
		}

		// Create cache key
		$key_args = wp_parse_args(
			$args,
			array(
				'id'   => $post_object->ID,
				'type' => $post_object->post_type,
			)
		);

		$cache_key = md5( serialize( $key_args ) );

		// Clear cache if force is true
		if ( $force ) {
			wp_cache_delete( $cache_key, 'lp_questions' );
		}

		// Try to get from cache
		$cached_question = wp_cache_get( $cache_key, 'lp_questions' );
		if ( $cached_question instanceof QuestionPostModel ) {
			return $cached_question;
		}

		// Get appropriate question class
		$class_name = self::get_question_class_name( $post_object, $args );

		// Instantiate question object
		if ( is_string( $class_name ) && class_exists( $class_name ) ) {
			$question = new $class_name();
			$question = $question::find( $post_object->ID );
		} else {
			$question = self::find( $post_object->ID );
		}

		// Cache the question instance
		if ( $question instanceof QuestionPostModel ) {
			wp_cache_set( $cache_key, $question, 'lp_questions' );
		}

		return $question;
	}

	/**
	 * Get WP_Post object from various input types.
	 *
	 * @param mixed $the_question Question ID, WP_Post, or QuestionPostModel.
	 *
	 * @return \WP_Post|false WP_Post object or false on failure.
	 */
	private static function get_question_post_object( $the_question ) {
		if ( false === $the_question ) {
			// Get current post if in question context
			$the_question = get_post_type() === LP_QUESTION_CPT ? $GLOBALS['post'] : false;
		} elseif ( is_numeric( $the_question ) ) {
			// Get post by ID
			$the_question = get_post( $the_question );
		} elseif ( $the_question instanceof QuestionPostModel ) {
			// Get post from model
			$the_question = get_post( $the_question->get_id() );
		} elseif ( $the_question instanceof \LP_Course_Item ) {
			// Get post from LP_Course_Item
			$the_question = get_post( $the_question->get_id() );
		} elseif ( ! ( $the_question instanceof \WP_Post ) ) {
			// Invalid type
			$the_question = false;
		}

		return apply_filters( 'learn-press/question-post-model/post-object', $the_question );
	}

	/**
	 * Get the appropriate question class name based on question type.
	 *
	 * @param \WP_Post $post_object WP_Post object.
	 * @param array    $args        Optional arguments.
	 *
	 * @return string Class name for the question type.
	 */
	private static function get_question_class_name( $post_object, $args = array() ) {
		$question_id = absint( $post_object->ID );

		// Get question type from args or post meta
		if ( ! empty( $args['type'] ) ) {
			$question_type = $args['type'];
		} else {
			$question_type = get_post_meta( $question_id, self::META_KEY_TYPE, true );
		}

		// Get class name from type
		$class_name = self::get_question_obj_by_type( $question_type );

		// Filter class name for extensibility
		return apply_filters(
			'learn-press/question-post-model/class-name',
			$class_name,
			$question_type,
			$question_id
		);
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
	 * Get answer options of the question with optional filtering and mapping.
	 * Returns stdClass objects (converted from QuestionAnswerModel) with mapping and exclusion applied.
	 *
	 * @param array $args {
	 *     Optional. Arguments for filtering and mapping answer options.
	 *
	 *     @type string|array $exclude Keys to exclude from each option object.
	 *     @type array        $map     Key mapping array (old_key => new_key).
	 *     @type mixed        $answer  Answer data (currently unused).
	 * }
	 *
	 * @return array Array of stdClass objects with modifications applied.
	 * @since 4.2.9
	 */
	public function get_answer_options( $args = array() ) {
		// Parse arguments with defaults
		$args = wp_parse_args(
			$args,
			array(
				'exclude' => '',
				'map'     => '',
				'answer'  => '',
			)
		);

		// Parse exclude parameter
		$exclude = $this->parse_exclude_param( $args['exclude'] );
		$map     = $args['map'];

		// Get raw answer options
		$options = $this->get_answer_option();

		// Return empty array if no options
		if ( empty( $options ) || ! is_array( $options ) ) {
			return array();
		}

		// Convert to stdClass objects and apply shortcodes
		$processed_options = $this->convert_to_stdclass_objects( $options );

		// Apply mapping and exclusion if needed
		if ( ! empty( $exclude ) || ! empty( $map ) ) {
			$processed_options = $this->apply_mapping_and_exclusion_to_objects( $processed_options, $map, $exclude );
		}

		return apply_filters( 'learn-press/question/answer-options', $processed_options, $this->get_id() );
	}

	/**
	 * Parse exclude parameter into array.
	 *
	 * @param string|array $exclude Exclude parameter.
	 *
	 * @return array Array of keys to exclude.
	 */
	private function parse_exclude_param( $exclude ) {
		if ( empty( $exclude ) ) {
			return array();
		}

		if ( is_string( $exclude ) ) {
			return array_map( 'trim', explode( ',', $exclude ) );
		}

		return is_array( $exclude ) ? $exclude : array();
	}

	/**
	 * Convert QuestionAnswerModel objects to stdClass and apply shortcodes.
	 * Using stdClass avoids PHP 8.2+ dynamic property deprecation warnings.
	 *
	 * @param array $options Array of QuestionAnswerModel objects.
	 *
	 * @return array Array of stdClass objects with shortcodes applied.
	 */
	private function convert_to_stdclass_objects( $options ) {
		$processed = array();

		foreach ( $options as $option ) {
			if ( ! $option instanceof QuestionAnswerModel ) {
				continue;
			}

			// Convert to stdClass to allow dynamic properties
			$std_option = new \stdClass();
			foreach ( get_object_vars( $option ) as $key => $value ) {
				$std_option->$key = $value;
			}

			// Apply shortcode to title
			if ( ! empty( $std_option->title ) ) {
				$std_option->title = do_shortcode( $std_option->title );
			}

			$processed[] = $std_option;
		}

		return $processed;
	}

	/**
	 * Apply key mapping and exclusion to stdClass objects.
	 * Mapping: Creates new property with mapped name and copies value.
	 * Exclusion: Unsets specified properties from objects.
	 *
	 * @param array $options Array of stdClass objects.
	 * @param array $map     Key mapping array (old_key => new_key).
	 * @param array $exclude Array of keys to exclude.
	 *
	 * @return array Objects with mapping and exclusion applied.
	 */
	private function apply_mapping_and_exclusion_to_objects( $options, $map, $exclude ) {
		foreach ( $options as $option ) {
			if ( ! $option instanceof \stdClass ) {
				continue;
			}

			// Apply key mapping - create new property with mapped name
			if ( ! empty( $map ) && is_array( $map ) ) {
				foreach ( $map as $old_key => $new_key ) {
					if ( property_exists( $option, $old_key ) || isset( $option->$old_key ) ) {
						// Copy value to new property name
						$option->$new_key = $option->$old_key;
						// Add old key to exclusion list
						if ( ! in_array( $old_key, $exclude, true ) ) {
							$exclude[] = $old_key;
						}
					}
				}
			}

			// Remove excluded properties
			if ( ! empty( $exclude ) ) {
				foreach ( $exclude as $key ) {
					if ( property_exists( $option, $key ) || isset( $option->$key ) ) {
						unset( $option->$key );
					}
				}
			}
		}

		return $options;
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

		$exclude_option_key = array( 'question_id', 'order' );

		if ( ! $with_true_or_false ) {
			$exclude_option_key[] = 'is_true';
		}
		
		$option_args = array(
			'exclude' => $exclude_option_key,
			'map'     => array( 'question_answer_id' => 'uid' ),
			'answer'  => $answered[ $question_id ]['answered'] ?? '',
		);

		// Get answer options
		$questionData['options'] = $this->get_answer_options( $option_args );

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
