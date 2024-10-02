<?php

/**
 * Class Quiz Post Model
 * To replace class LP_Quiz old
 *
 * @package LearnPress/Classes
 * @version 1.0.0
 * @since 4.2.7.1
 */

namespace LearnPress\Models;

use Exception;
use LP_Filter;
use LP_Quiz_DB;
use LP_Quiz_Filter;

class QuizPostModel extends PostModel {
	/**
	 * @var string Post Type
	 */
	public $post_type = LP_QUIZ_CPT;

	protected $_data = array(
		'mark'           => 0,
		'answer_options' => array(),
		'point'          => 1,
		'explanation'    => '',
		'hint'           => '',
	);

	protected $question_ids = '';

	/**
	 * Const meta key
	 */
	const META_KEY_REVIEW              = '_lp_review';
	const META_KEY_DURATION            = '_lp_duration';
	const META_KEY_PASSING_GRADE       = '_lp_passing_grade';
	const META_KEY_NEGATIVE_MARKING    = '_lp_negative_marking';
	const META_KEY_INSTANT_CHECK       = '_lp_instant_check';
	const META_KEY_RETAKE_COUNT        = '_lp_retake_count';
	const META_KEY_PAGINATION          = '_lp_pagination';
	const META_KEY_SHOW_CORRECT_REVIEW = '_lp_show_correct_review';


	/**
	 * Get question_ids
	 *
	 * @return array
	 */
	public function get_question_ids() {
		try {
			if ( empty( $this->question_ids ) ) {
				$db                       = LP_Quiz_DB::getInstance();
				$filter                   = new LP_Filter();
				$filter->only_fields      = [ 'question_id' ];
				$filter->collection_alias = 'quiz_q';
				$filter->collection       = $db->tb_lp_quiz_questions;
				$filter->join[]           = "INNER JOIN $db->tb_posts as p ON p.ID = quiz_q.question_id";
				$filter->where[]          = "AND p.post_type = '" . LP_QUESTION_CPT . "'";
				$filter->where[]          = 'AND quiz_q.quiz_id = ' . $this->ID;
				$filter->where[]          = 'AND p.post_status IN( "publish" )';
				$filter->order_by         = 'question_order';
				$filter->run_query_count  = false;
				$question_ids             = $db->execute( $filter );
				$this->question_ids       = $question_ids;
			}
		} catch ( Exception $e ) {
			error_log( $e->getMessage() );
		}

		return $this->question_ids;
	}

	public function count_questions() {
			$size      = 0;
			$questions = $this->get_question_ids();

		if ( $questions ) {
			$size = sizeof( $questions );
		}

		return (int) apply_filters( 'learn-press/quiz/count-questions', $size, $this->get_id() );
	}

	/**
	 * Get default quiz meta.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public static function get_default_meta() {
		$meta = array(
			'review'              => 'no',
			'duration'            => '10 minute',
			'passing_grade'       => 80,
			'negative_marking'    => 'no',
			'instant_check'       => 'no',
			'retake_count'        => '0',
			'pagination'          => 1,
			'show_correct_review' => 'yes',
		);

		return apply_filters( 'learn-press/quiz/default-meta', $meta );
	}

	public function get_review() {
		if ( empty( $this->meta_data->review ) ) {
			$review = $this->get_meta_value_by_key( self::META_KEY_REVIEW, '' );
			apply_filters( 'learn-press/quiz/review', $review, $this->ID );
			$this->meta_data->review = $review;
		}

		return $this->meta_data->review;
	}

	public function get_duration() {
		if ( empty( $this->meta_data->duration ) ) {
			$duration = $this->get_meta_value_by_key( self::META_KEY_DURATION, '' );
			apply_filters( 'learn-press/quiz/duration', $duration, $this->ID );
			$this->meta_data->duration = $duration;
		}

		return $this->meta_data->duration;
	}

	public function get_passing_grade() {
		if ( empty( $this->meta_data->passing_grade ) ) {
			$passing_grade = $this->get_meta_value_by_key( self::META_KEY_PASSING_GRADE, '' );
			apply_filters( 'learn-press/quiz/passing_grade', $passing_grade, $this->ID );
			$this->meta_data->passing_grade = $passing_grade;
		}

		return $this->meta_data->passing_grade;
	}

	public function get_negative_marking() {
		if ( empty( $this->meta_data->negative_marking ) ) {
			$negative_marking = $this->get_meta_value_by_key( self::META_KEY_NEGATIVE_MARKING, '' );
			apply_filters( 'learn-press/quiz/negative_marking', $negative_marking, $this->ID );
			$this->meta_data->negative_marking = $negative_marking;
		}

		return $this->meta_data->negative_marking;
	}

	public function get_instant_check() {
		if ( empty( $this->meta_data->instant_check ) ) {
			$instant_check = $this->get_meta_value_by_key( self::META_KEY_INSTANT_CHECK, '' );
			apply_filters( 'learn-press/quiz/instant_check', $instant_check, $this->ID );
			$this->meta_data->instant_check = $instant_check;
		}

		return $this->meta_data->instant_check;
	}

	public function get_retake_count() {
		if ( empty( $this->meta_data->retake_count ) ) {
			$retake_count = $this->get_meta_value_by_key( self::META_KEY_RETAKE_COUNT, '' );
			apply_filters( 'learn-press/quiz/retake_count', $retake_count, $this->ID );
			$this->meta_data->retake_count = $retake_count;
		}

		return $this->meta_data->retake_count;
	}

	public function get_pagination() {
		if ( empty( $this->meta_data->pagination ) ) {
			$pagination = $this->get_meta_value_by_key( self::META_KEY_PAGINATION, '' );
			apply_filters( 'learn-press/quiz/pagination', $pagination, $this->ID );
			$this->meta_data->pagination = $pagination;
		}

		return $this->meta_data->pagination;
	}

	public function get_show_correct_review() {
		if ( empty( $this->meta_data->show_correct_review ) ) {
			$show_correct_review = $this->get_meta_value_by_key( self::META_KEY_SHOW_CORRECT_REVIEW, '' );
			apply_filters( 'learn-press/quiz/show_correct_review', $show_correct_review, $this->ID );
			$this->meta_data->show_correct_review = $show_correct_review;
		}

		return $this->meta_data->show_correct_review;
	}

	/**
	 * Get post quiz by ID
	 *
	 * @param int $post_id
	 * @param bool $check_cache
	 *
	 * @return false|static
	 */
	public static function find( int $post_id, bool $check_cache = false ) {
		$filter     = new LP_Quiz_Filter();
		$filter->ID = $post_id;

		return self::get_item_model_from_db( $filter );
	}
}
