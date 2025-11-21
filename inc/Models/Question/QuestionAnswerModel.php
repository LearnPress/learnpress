<?php

namespace LearnPress\Models\Question;

use Exception;
use LearnPress\Databases\QuestionAnswersDB;
use LearnPress\Filters\QuestionAnswersFilter;
use LP_Cache;
use stdClass;

/**
 * Class QuestionAnswerModel
 * Answers for questions in LearnPress.
 *
 * @version 1.0.0
 * @since 4.2.9
 */
class QuestionAnswerModel {
	public $question_answer_id;
	public $question_id;
	public $title;
	public $value;
	public $order;
	public $is_true;

	/********** Fields not on table **********/
	public $meta_data;
	public $is_get_all_metadata = false;

	/** Constant */
	const META_KEY_BLANKS = '_blanks';

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

		if ( is_null( $this->meta_data ) ) {
			$this->meta_data = new stdClass();
		}
	}

	/**
	 * Map array, object data to QuestionAnswerModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return QuestionAnswerModel
	 */
	public function map_to_object( $data ): QuestionAnswerModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Get question post model.
	 *
	 * @return QuestionPostModel|false
	 */
	public function get_question_post_model() {
		return QuestionPostModel::find( $this->question_id, true );
	}

	/**
	 * Find question answer by question_answer_id.
	 *
	 * @param int $question_answer_id
	 * @param bool $check_cache
	 *
	 * @return QuestionAnswerModel|false
	 * @throws Exception
	 */
	public static function find( int $question_answer_id, bool $check_cache = false ) {
		$model                      = null;
		$filter                     = new QuestionAnswersFilter();
		$filter->question_answer_id = $question_answer_id;

		$key_cache = "questionAnswerModel/find/{$question_answer_id}";
		$cache     = new LP_Cache();

		// Check cache
		if ( $check_cache ) {
			$model = $cache->get_cache( $key_cache );
			if ( $model instanceof QuestionAnswerModel ) {
				return $model;
			}
		}

		$db = QuestionAnswersDB::getInstance();
		$db->get_query_single_row( $filter );
		$query_single_row = $db->get_question_answers( $filter );
		$rs               = $db->wpdb->get_row( $query_single_row );

		if ( $rs instanceof stdClass ) {
			$model = new static( $rs );
		}

		// Set cache
		if ( $model instanceof QuestionPostModel ) {
			$cache->set_cache( $key_cache, $model );
		}

		return $model;
	}

	/**
	 * Get all meta_data
	 *
	 * @return mixed|null
	 */
	public function get_all_metadata() {
		if ( $this->is_get_all_metadata ) {
			return $this->meta_data;
		}

		$questionPostModel = $this->get_question_post_model();
		if ( $questionPostModel && $questionPostModel->get_type() === 'fill_in_blanks' ) {
			$blanks                    = learn_press_get_question_answer_meta( $this->question_answer_id, '_blanks' );
			$this->meta_data           = $blanks;
			$this->is_get_all_metadata = true;
		}

		return $this->meta_data;
	}

	/**
	 * Get meta value by key.
	 *
	 * @param string $key
	 * @param mixed $value
	 *
	 * @return void
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function save_meta_value_by_key( string $key, $value ) {
		if ( ! $this->meta_data instanceof stdClass ) {
			$this->meta_data = new stdClass();
		}

		$this->meta_data->{$key} = $value;
		update_metadata( 'learnpress_question_answer', $this->question_answer_id, $key, $value );
	}

	/**
	 * Check capabilities of current user to create question answer.
	 *
	 * @throws Exception
	 */
	public function check_capabilities_create() {
		$user = wp_get_current_user();
		if ( ! user_can( $user, 'edit_' . LP_LESSON_CPT . 's' ) ) {
			throw new Exception( __( 'You do not have permission to create answer.', 'learnpress' ) );
		}
	}

	/**
	 * Check capabilities of current user to update question answer.
	 *
	 * @throws Exception
	 */
	public function check_capabilities_update() {
		$user = wp_get_current_user();
		if ( ! user_can( $user, 'edit_' . LP_LESSON_CPT, $this->question_id ) ) {
			throw new Exception( __( 'You do not have permission to edit this item.', 'learnpress' ) );
		}
	}

	/**
	 * Update data to database.
	 *
	 * If question_answer_id is empty, insert new data, else update data.
	 *
	 * @throws Exception
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function save() {
		$db   = QuestionAnswersDB::getInstance();
		$data = [];
		foreach ( get_object_vars( $this ) as $property => $value ) {
			$data[ $property ] = $value;
		}

		$args = [
			'data'       => $data,
			'filter'     => new QuestionAnswersFilter(),
			'table_name' => $db->tb_lp_question_answers,
		];

		// Check if exists course id.
		if ( empty( $this->question_answer_id ) ) { // Insert data.
			$this->check_capabilities_create();
			$args['key_auto_increment'] = 'question_answer_id';
			$this->question_answer_id   = $db->insert_data( $args );
		} else { // Update data.
			$this->check_capabilities_update();
			$args['where_key'] = 'question_answer_id';
			$db->update_data( $args );
		}

		$this->clean_caches();
	}

	/**
	 * @throws Exception
	 */
	public function check_valid_before_delete() {
		$questionPostModel = $this->get_question_post_model();
		if ( ! $questionPostModel ) {
			throw new Exception( __( 'Question not found', 'learnpress' ) );
		}

		if ( $questionPostModel->get_type() === 'single_choice' || $questionPostModel->get_type() === 'multi_choice' ) {
			// For single choice and multiple choice, at least two answer is required.
			$filter              = new QuestionAnswersFilter();
			$filter->question_id = $this->question_id;
			$total_rows          = 0;
			$filter->query_count = true;
			$answers             = (int) QuestionAnswersDB::getInstance()->get_question_answers( $filter, $total_rows );
			if ( $answers <= 2 ) {
				throw new Exception( __( 'At least two answer is required.', 'learnpress' ) );
			}
		}
	}

	/**
	 * Delete row
	 *
	 * @throws Exception
	 */
	public function delete() {
		$this->check_valid_before_delete();

		$db                 = QuestionAnswersDB::getInstance();
		$filter             = new QuestionAnswersFilter();
		$filter->where[]    = $db->wpdb->prepare( 'AND question_answer_id = %d', $this->question_answer_id );
		$filter->collection = $db->tb_lp_question_answers;
		$db->delete_execute( $filter );

		// Clear cache
		$this->clean_caches();
	}

	/**
	 * Clean caches.
	 */
	public function clean_caches() {
		$lpQuizCache = new LP_Cache();
		$key_cache   = "questionAnswerModel/find/{$this->question_answer_id}";
		$lpQuizCache->clear( $key_cache );
	}
}
