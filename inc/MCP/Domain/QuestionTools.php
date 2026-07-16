<?php

namespace LearnPress\MCP\Domain;

use Exception;
use LearnPress\MCP\Mappers\ResponseMapper;
use LearnPress\MCP\Support\Errors;
use LearnPress\MCP\Support\Permissions;
use LearnPress\MCP\Support\Sanitizer;
use LearnPress\MCP\Support\Validator;
use LearnPress\Models\Question\QuestionAnswerModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\QuizPostModel;
use Throwable;
use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Quiz question add/update/delete executors.
 *
 * Manages questions, answers, and quiz-question relationships through the
 * LearnPress models. Delete is relationship-only (never removes the shared
 * question post) and recoverable through returned metadata.
 */
class QuestionTools {

	/**
	 * Execute `learnpress/add-quiz-question`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function add_quiz_question( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$quiz_id = Validator::require_id( $args, 'quiz_id' );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}

		$title = Sanitizer::text( $args['title'] ?? '' );
		if ( '' === $title ) {
			return Errors::invalid( __( 'title is required.', 'learnpress' ) );
		}

		$type = Validator::question_type( $args['type'] ?? '' );
		if ( is_wp_error( $type ) ) {
			return $type;
		}

		$quiz = Validator::find_quiz( $quiz_id );
		if ( is_wp_error( $quiz ) ) {
			return $quiz;
		}

		if ( ! Permissions::can_edit_post( $quiz_id ) ) {
			return Errors::forbidden();
		}

		$has_answers = array_key_exists( 'answers', $args ) && is_array( $args['answers'] );
		if ( $has_answers ) {
			$valid = self::validate_answers( $type, $args['answers'] );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}

		try {
			$options = array();
			if ( $has_answers ) {
				foreach ( $args['answers'] as $answer ) {
					$options[] = array(
						'title'   => Sanitizer::text( $answer['title'] ?? '' ),
						'is_true' => Sanitizer::boolean( $answer['is_correct'] ?? false ) ? 'yes' : '',
					);
				}
			}

			$relation    = $quiz->create_question_and_add(
				array(
					'question_title'   => $title,
					'question_type'    => $type,
					'question_content' => Sanitizer::html( $args['content'] ?? '' ),
					'question_options' => $options,
				)
			);
			$question_id = absint( $relation->question_id );

			$question = QuestionPostModel::find( $question_id, true );
			if ( ! $question instanceof QuestionPostModel ) {
				return Errors::internal();
			}

			self::apply_meta( $question, $args );

			if ( array_key_exists( 'order', $args ) ) {
				self::reorder_question( $quiz, $question_id, absint( $args['order'] ) );
			}

			// Reload without cache so the answer count reflects newly created answers.
			$question = QuestionPostModel::find( $question_id, false );

			return array(
				'question_id'   => $question_id,
				'quiz_id'       => $quiz_id,
				'type'          => (string) $question->get_type(),
				'mark'          => (float) $question->get_mark(),
				'answers_count' => count( $question->get_answer_option() ),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/update-quiz-question`.
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function update_quiz_question( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$quiz_id = Validator::require_id( $args, 'quiz_id' );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}
		$question_id = Validator::require_id( $args, 'question_id' );
		if ( is_wp_error( $question_id ) ) {
			return $question_id;
		}

		$quiz = Validator::find_quiz( $quiz_id );
		if ( is_wp_error( $quiz ) ) {
			return $quiz;
		}
		$relation = Validator::find_quiz_question( $quiz_id, $question_id );
		if ( is_wp_error( $relation ) ) {
			return $relation;
		}
		$question = Validator::find_question( $question_id );
		if ( is_wp_error( $question ) ) {
			return $question;
		}

		if ( ! Permissions::can_edit_post( $quiz_id ) ) {
			return Errors::forbidden();
		}

		$type = null;
		if ( array_key_exists( 'type', $args ) ) {
			$type = Validator::question_type( $args['type'] );
			if ( is_wp_error( $type ) ) {
				return $type;
			}
		}

		$effective_type = is_string( $type ) ? $type : (string) $question->get_type();
		if ( array_key_exists( 'answers', $args ) && is_array( $args['answers'] ) ) {
			$valid = self::validate_answers( $effective_type, $args['answers'] );
			if ( is_wp_error( $valid ) ) {
				return $valid;
			}
		}

		try {
			if ( array_key_exists( 'title', $args ) ) {
				$question->post_title = Sanitizer::text( $args['title'] );
			}
			if ( array_key_exists( 'content', $args ) ) {
				$question->post_content = Sanitizer::html( $args['content'] );
			}
			$question->save();

			if ( is_string( $type ) ) {
				$question->save_meta_value_by_key( QuestionPostModel::META_KEY_TYPE, $type );
			}
			self::apply_meta( $question, $args );

			if ( array_key_exists( 'answers', $args ) && is_array( $args['answers'] ) ) {
				self::sync_answers( $question, $args['answers'] );
			}

			if ( array_key_exists( 'order', $args ) ) {
				self::reorder_question( $quiz, $question_id, absint( $args['order'] ) );
			}

			// Reload without cache so the answer set reflects the sync above.
			$question = QuestionPostModel::find( $question_id, false );
			$order    = self::current_order( $quiz, $question_id );

			return array( 'question' => ResponseMapper::question( $question, true, $order ) );
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Execute `learnpress/delete-quiz-question` (relationship-only).
	 *
	 * @param mixed $input Ability input.
	 *
	 * @return array|WP_Error
	 */
	public static function delete_quiz_question( $input ) {
		$args    = is_array( $input ) ? $input : array();
		$quiz_id = Validator::require_id( $args, 'quiz_id' );
		if ( is_wp_error( $quiz_id ) ) {
			return $quiz_id;
		}
		$question_id = Validator::require_id( $args, 'question_id' );
		if ( is_wp_error( $question_id ) ) {
			return $question_id;
		}

		$quiz = Validator::find_quiz( $quiz_id );
		if ( is_wp_error( $quiz ) ) {
			return $quiz;
		}
		$relation = Validator::find_quiz_question( $quiz_id, $question_id );
		if ( is_wp_error( $relation ) ) {
			return $relation;
		}

		if ( ! Permissions::can_edit_post( $quiz_id ) ) {
			return Errors::forbidden();
		}

		$previous_order = (int) $relation->question_order;

		try {
			$quiz->remove_question_from_quiz( $question_id );

			return array(
				'removed'           => true,
				'removed_from_quiz' => true,
				'question_id'       => $question_id,
				'recovery'          => array(
					'quiz_id'        => $quiz_id,
					'question_id'    => $question_id,
					'previous_order' => $previous_order,
					'note'           => __( 'Question removed from quiz only. The question post is preserved and can be re-added.', 'learnpress' ),
				),
			);
		} catch ( Throwable $e ) {
			return Errors::from_throwable( $e );
		}
	}

	/**
	 * Persist optional mark/hint/explanation metadata.
	 *
	 * @param QuestionPostModel $question Question model.
	 * @param array             $args     Input args.
	 *
	 * @return void
	 */
	protected static function apply_meta( QuestionPostModel $question, array $args ): void {
		if ( array_key_exists( 'mark', $args ) ) {
			$question->save_meta_value_by_key( QuestionPostModel::META_KEY_MARK, (float) $args['mark'] );
		}
		if ( array_key_exists( 'hint', $args ) ) {
			$question->save_meta_value_by_key( QuestionPostModel::META_KEY_HINT, Sanitizer::html( $args['hint'] ) );
		}
		if ( array_key_exists( 'explanation', $args ) ) {
			$question->save_meta_value_by_key( QuestionPostModel::META_KEY_EXPLANATION, Sanitizer::html( $args['explanation'] ) );
		}
	}

	/**
	 * Validate an answer payload for the selected question type.
	 *
	 * @param string $type    Question type.
	 * @param array  $answers Answers payload.
	 *
	 * @return true|WP_Error
	 */
	protected static function validate_answers( string $type, array $answers ) {
		foreach ( $answers as $answer ) {
			if ( ! is_array( $answer ) || '' === Sanitizer::text( $answer['title'] ?? '' ) ) {
				return Errors::invalid( __( 'Each answer requires a non-empty title.', 'learnpress' ) );
			}
		}

		$correct = 0;
		foreach ( $answers as $answer ) {
			if ( Sanitizer::boolean( $answer['is_correct'] ?? false ) ) {
				++$correct;
			}
		}

		switch ( $type ) {
			case 'single_choice':
				if ( count( $answers ) < 2 ) {
					return Errors::invalid( __( 'Choice questions require at least two answers.', 'learnpress' ) );
				}
				if ( 1 !== $correct ) {
					return Errors::invalid( __( 'Single choice questions require exactly one correct answer.', 'learnpress' ) );
				}
				break;
			case 'multi_choice':
				if ( count( $answers ) < 2 ) {
					return Errors::invalid( __( 'Choice questions require at least two answers.', 'learnpress' ) );
				}
				if ( $correct < 1 ) {
					return Errors::invalid( __( 'Choice questions require at least one correct answer.', 'learnpress' ) );
				}
				break;
			case 'true_or_false':
				if ( 2 !== count( $answers ) ) {
					return Errors::invalid( __( 'True/false questions require exactly two answers.', 'learnpress' ) );
				}
				if ( 1 !== $correct ) {
					return Errors::invalid( __( 'True/false questions require exactly one correct answer.', 'learnpress' ) );
				}
				break;
		}

		return true;
	}

	/**
	 * Synchronize the question answer set to the desired payload.
	 *
	 * @param QuestionPostModel $question Question model.
	 * @param array             $answers  Desired answers.
	 *
	 * @return void
	 * @throws Exception When answer persistence fails.
	 */
	protected static function sync_answers( QuestionPostModel $question, array $answers ): void {
		$existing = array();
		foreach ( $question->get_answer_option() as $model ) {
			if ( $model instanceof QuestionAnswerModel ) {
				$existing[ (int) $model->question_answer_id ] = $model;
			}
		}

		$kept = array();
		foreach ( array_values( $answers ) as $index => $answer ) {
			$answer_id = absint( $answer['answer_id'] ?? 0 );
			$title     = Sanitizer::text( $answer['title'] ?? '' );
			$is_true   = Sanitizer::boolean( $answer['is_correct'] ?? false ) ? 'yes' : '';
			$value     = isset( $answer['value'] ) ? Sanitizer::text( $answer['value'] ) : '';

			if ( $answer_id > 0 && isset( $existing[ $answer_id ] ) ) {
				$model          = $existing[ $answer_id ];
				$model->title   = $title;
				$model->is_true = $is_true;
				$model->order   = $index + 1;
				if ( '' !== $value ) {
					$model->value = $value;
				}
				$model->save();
				$kept[ $answer_id ] = true;
			} else {
				$model = new QuestionAnswerModel(
					array(
						'question_id' => $question->get_id(),
						'title'       => $title,
						'value'       => '' !== $value ? $value : $question->random_value(),
						'is_true'     => $is_true,
						'order'       => $index + 1,
					)
				);
				$model->save();
				$kept[ (int) $model->question_answer_id ] = true;
			}
		}

		// Remove answers no longer in the payload. validate_answers guarantees the
		// kept set still satisfies the type's minimum, so the model's
		// minimum-answer guard never legitimately fires here; any failure
		// (capability, DB) must surface rather than be swallowed.
		foreach ( $existing as $answer_id => $model ) {
			if ( isset( $kept[ $answer_id ] ) ) {
				continue;
			}
			$model->delete();
		}
	}

	/**
	 * Reorder a question to a 1-based position within the quiz.
	 *
	 * @param QuizPostModel $quiz        Quiz model.
	 * @param int           $question_id Question ID.
	 * @param int           $position    Desired 1-based position; <= 0 appends.
	 *
	 * @return void
	 */
	protected static function reorder_question( QuizPostModel $quiz, int $question_id, int $position ): void {
		// Editor context: reorder across all question statuses, not just published.
		$ids = array_map( 'absint', $quiz->get_question_ids( array( 'publish', 'pending', 'draft' ) ) );
		$ids = array_values( array_diff( $ids, array( $question_id ) ) );

		if ( $position > 0 ) {
			$index = max( 0, min( count( $ids ), $position - 1 ) );
			array_splice( $ids, $index, 0, array( $question_id ) );
		} else {
			$ids[] = $question_id;
		}

		$quiz->update_question_position( array( 'question_ids' => $ids ) );
	}

	/**
	 * Resolve a question's 1-based order within a quiz.
	 *
	 * @param QuizPostModel $quiz        Quiz model.
	 * @param int           $question_id Question ID.
	 *
	 * @return int
	 */
	protected static function current_order( QuizPostModel $quiz, int $question_id ): int {
		// Editor context: include all statuses so non-published questions report a real order.
		$ids = array_values( array_map( 'absint', $quiz->get_question_ids( array( 'publish', 'pending', 'draft' ) ) ) );
		$pos = array_search( $question_id, $ids, true );

		return false === $pos ? 0 : ( (int) $pos + 1 );
	}
}
