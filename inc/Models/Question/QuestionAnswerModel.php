<?php

namespace LearnPress\Models\Question;

use Exception;
use LearnPress\Databases\QuestionAnswersDB;
use LearnPress\Filters\QuestionAnswersFilter;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use stdClass;

/**
 * Class QuestionAnswerModel
 * Answers for questions in LearnPress.
 *
 * @version 1.0.0
 * @since 4.2.8.8
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
	 * @since 4.2.8.8
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
	 * Clean caches.
	 */
	public function clean_caches() {
	}
}
