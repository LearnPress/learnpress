<?php

namespace LearnPress\Models\Question;

use Exception;
use LearnPress\Cache\LPCache;
use LearnPress\Databases\QuestionAnswerConfigDB;
use LearnPress\Filters\QuestionAnswerConfigFilter;
use LP_Helper;
use Throwable;

defined( 'ABSPATH' ) || exit();

/**
 * Class QuestionAnswerConfigModel
 * Answers config of a question, stored as JSON in learnpress_question_answer_config.
 *
 * @version 1.0.0
 * @since 4.5.0
 */
class QuestionAnswerConfigModel {
	public int $question_answer_id = 0;
	public int $question_id        = 0;

	/**
	 * Raw `answers` JSON string: [{title, value, order, is_true, meta?}]
	 *
	 * @var string
	 */
	public string $answers = '';

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
	 * Map array, object data to QuestionAnswerConfigModel.
	 * Use for data get from database.
	 *
	 * @param array|object|mixed $data
	 *
	 * @return QuestionAnswerConfigModel
	 */
	public function map_to_object( $data ): QuestionAnswerConfigModel {
		foreach ( $data as $key => $value ) {
			if ( property_exists( $this, $key ) ) {
				$this->{$key} = $value;
			}
		}

		return $this;
	}

	/**
	 * Decoded `answers` blob. Always an array, even for rows that
	 * stored NULL or invalid JSON.
	 *
	 * @return array
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function get_answers(): array {
		try {
			if ( empty( $this->answers ) ) {
				return array();
			}

			return LP_Helper::json_decode( $this->answers, true );
		} catch ( Throwable $e ) {
			return array();
		}
	}

	/**
	 * Write one key into `answers`, preserving the others. Does not save().
	 *
	 * @param int|string $key
	 * @param mixed      $value
	 *
	 * @return void
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function set_answer_by_key_value( $key, $value ) {
		$data         = $this->get_answers();
		$data[ $key ] = $value;

		$this->set_answers( $data );
	}

	/**
	 * Set answers data
	 *
	 * @param array $data
	 * @return void
	 * @since 4.5.0
	 */
	public function set_answers( array $data = [] ) {
		$this->answers = (string) wp_json_encode( $data, JSON_UNESCAPED_UNICODE );
	}

	/**
	 * Check db version supports answers config table.
	 *
	 * @return bool
	 */
	public static function is_support(): bool {
		return (int) get_option( LP_KEY_DB_VERSION, 0 ) >= 6;
	}

	/**
	 * Find answers config by question_id.
	 *
	 * @param int $question_id
	 * @param bool $check_cache
	 *
	 * @return QuestionAnswerConfigModel|false
	 * @throws Exception
	 */
	public static function find_by_question_id( int $question_id, bool $check_cache = false ) {
		$model     = null;
		$key_cache = "questionAnswerConfigModel/find/{$question_id}";
		$cache     = new LPCache();

		if ( $check_cache ) {
			$model = $cache->get_cache( $key_cache );
			if ( $model instanceof QuestionAnswerConfigModel ) {
				return $model;
			}
		}

		$db                  = QuestionAnswerConfigDB::getInstance();
		$filter              = new QuestionAnswerConfigFilter();
		$filter->question_id = $question_id;

		$db->get_query_single_row( $filter );
		$query = $db->get_question_answer_configs( $filter );
		$row   = $db->wpdb->get_row( $query );
		$db->check_execute_has_error();
		if ( $row ) {
			$model = new static( $row );
		}

		if ( $model instanceof QuestionAnswerConfigModel ) {
			$cache->set_cache( $key_cache, $model );
		}

		return $model;
	}

	/**
	 * Save answers config of question to database.
	 *
	 * @throws Exception
	 * @since 4.5.0
	 * @version 1.0.0
	 */
	public function save() {
		$this->check_capabilities();

		$db   = QuestionAnswerConfigDB::getInstance();
		$args = [
			'data'               => get_object_vars( $this ),
			'filter'             => new QuestionAnswerConfigFilter(),
			'table_name'         => $db->tb_lp_question_answer_config,
			'key_auto_increment' => QuestionAnswerConfigFilter::COL_QUESTION_ANSWER_ID,
			'where_key'          => QuestionAnswerConfigFilter::COL_QUESTION_ANSWER_ID,
		];

		if ( empty( $this->question_answer_id ) ) {
			$question_answer_id = $db->insert_data( $args );
			if ( empty( $question_answer_id ) ) {
				throw new Exception( 'Cannot insert data to database.' );
			}

			$this->question_answer_id = $question_answer_id;
		} else {
			$db->update_data( $args );
		}

		$this->clean_caches();
	}

	/**
	 * Check permission
	 *
	 * @throws Exception
	 */
	public function check_capabilities() {
		if ( ! current_user_can( 'edit_' . LP_LESSON_CPT, $this->question_id ) ) {
			throw new Exception( __( 'You do not have permission to create answer.', 'learnpress' ) );
		}
	}

	/**
	 * Delete answers config of question.
	 *
	 * @throws Exception
	 */
	public function delete() {
		$this->check_capabilities();

		$db                 = QuestionAnswerConfigDB::getInstance();
		$filter             = new QuestionAnswerConfigFilter();
		$filter->where[]    = $db->wpdb->prepare(
			'AND question_id = %d',
			$this->question_id
		);
		$filter->collection = $db->tb_lp_question_answer_config;
		$db->delete_execute( $filter );

		$this->clean_caches();
	}

	/**
	 * Clean caches.
	 */
	public function clean_caches() {
		$lpQuizCache = new LPCache();
		$key_cache   = "questionAnswerConfigModel/find/{$this->question_id}";
		$lpQuizCache->clear( $key_cache );
	}
}
