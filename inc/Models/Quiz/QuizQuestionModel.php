<?php

namespace LearnPress\Models\Quiz;

use Exception;
use LearnPress\Databases\QuizQuestionsDB;
use LearnPress\Filters\QuizQuestionsFilter;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use LP_Cache;
use stdClass;
use Throwable;

/**
 * Class QuizQuestionModel
 *
 * Handle all method about quiz question.
 *
 * @package LearnPress/Classes
 * @version 1.0.1
 * @since 4.2.9
 */
class QuizQuestionModel {
	/**
	 * Auto increment, Primary key
	 *
	 * @var int
	 */
	public $quiz_question_id = 0;
	/**
	 * Title of the section
	 *
	 * @var int
	 */
	public $quiz_id = 0;
	/**
	 * Foreign key, Course ID
	 *
	 * @var int
	 */
	public $question_id = 0;
	/**
	 * Order of the section
	 *
	 * @var int
	 */
	public $question_order = 0;

	/**
	 * If data get from database, map to object.
	 * Else create new object to save data to database.
	 *
	 * @param array|object|mixed $data
	 */
	public function __construct( $data = null ) {
		if ( $data ) {
			$this->map_to_object( $data );
		}
	}

	/**
	 * Map array, object data to QuizQuestionModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return QuizQuestionModel
	 */
	public function map_to_object( $data ): QuizQuestionModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get section id
	 *
	 * @return int
	 */
	public function get_quiz_question_id(): int {
		return $this->quiz_question_id;
	}

	/**
	 * Get course model
	 *
	 * @return false|QuizPostModel
	 */
	public function get_quiz_post_model() {
		return QuizPostModel::find( $this->quiz_id, true );
	}

	/**
	 * Get course model
	 *
	 * @return false|QuestionPostModel
	 */
	public function get_question_post_model() {
		return QuestionPostModel::find( $this->question_id, true );
	}

	/**
	 * Get quizQuestionsModel by quiz_id and question_id.
	 *
	 * @return false|QuizQuestionModel
	 */
	public static function find( int $quiz_id, int $question_id, $check_cache = true ) {
		$filter              = new QuizQuestionsFilter();
		$filter->quiz_id     = $quiz_id;
		$filter->question_id = $question_id;
		$key_cache           = "quizQuestion/find/{$quiz_id}/{$question_id}";
		$cache               = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$model = $cache->get_cache( $key_cache );
			if ( $model instanceof QuizQuestionModel ) {
				return $model;
			}
		}

		$model = static::get_item_model_from_db( $filter );

		// Set cache
		if ( $model instanceof QuizQuestionModel ) {
			$cache->set_cache( $key_cache, $model );
		}

		return $model;
	}

	/**
	 * Get post from database.
	 * If not exists, return false.
	 * If exists, return QuizQuestionModel.
	 *
	 * @param QuizQuestionsFilter $filter
	 *
	 * @return QuizQuestionModel|false|static
	 * @version 1.0.0
	 */
	public static function get_item_model_from_db( QuizQuestionsFilter $filter ) {
		$db    = QuizQuestionsDB::getInstance();
		$model = false;

		try {
			$db->get_query_single_row( $filter );
			$query_single_row = $db->get_quiz_questions( $filter );
			$rs               = $db->wpdb->get_row( $query_single_row );

			if ( $rs instanceof stdClass ) {
				$model = new static( $rs );
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $model;
	}

	/**
	 * Save data to table quiz_questions.
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public function save(): QuizQuestionModel {
		$quizPostModel = $this->get_quiz_post_model();
		if ( ! $quizPostModel ) {
			throw new Exception( __( 'Quiz not found', 'learnpress' ) );
		}

		$quizPostModel->check_capabilities_update_item_course();

		$db = QuizQuestionsDB::getInstance();

		$data = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		if ( $data['quiz_question_id'] === 0 ) { // Insert data.
			$quiz_question_id       = $db->insert_data( $data );
			$this->quiz_question_id = $quiz_question_id;
		} else { // Update data.
			$db->update_data( $data );
		}

		// Clear cache
		$this->clean_caches();

		return $this;
	}

	/**
	 * Delete row
	 *
	 * @throws Exception
	 * @since 4.2.8.6
	 * @version 1.0.1
	 */
	public function delete() {
		// Check permission
		$quizPostModel = $this->get_quiz_post_model();
		if ( ! $quizPostModel ) {
			throw new Exception( __( 'Quiz not found', 'learnpress' ) );
		}

		$quizPostModel->check_capabilities_update_item_course();

		$db                 = QuizQuestionsDB::getInstance();
		$filter             = new QuizQuestionsFilter();
		$filter->where[]    = $db->wpdb->prepare( 'AND quiz_question_id = %d', $this->quiz_question_id );
		$filter->collection = $db->tb_lp_quiz_questions;
		$db->delete_execute( $filter );

		// Clear cache
		$this->clean_caches();
	}

	/**
	 * Clean caches
	 *
	 * @return void
	 * @throws Exception
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function clean_caches() {
		$key_cache       = "quizQuestion/find/{$this->quiz_id}/{$this->question_id}";
		$lp_course_cache = new LP_Cache();
		$lp_course_cache->clear( $key_cache );
	}
}
