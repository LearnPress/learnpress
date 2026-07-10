<?php

namespace LearnPress\MCP\Schemas;

use LearnPress\MCP\Support\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Input/output schemas for quiz question write tools.
 */
class QuestionSchemas {

	/**
	 * Answer input schema (create/update).
	 *
	 * @return array
	 */
	protected static function answer_input(): array {
		return Schemas::object(
			array(
				'answer_id'  => Schemas::id(),
				'title'      => Schemas::string(),
				'value'      => Schemas::string(),
				'is_correct' => Schemas::boolean(),
			),
			array( 'title' )
		);
	}

	/**
	 * Answers array schema.
	 *
	 * @return array
	 */
	protected static function answers(): array {
		return array(
			'type'  => 'array',
			'items' => self::answer_input(),
		);
	}

	/**
	 * add-quiz-question input.
	 *
	 * @return array
	 */
	public static function add_input(): array {
		return Schemas::object(
			array(
				'quiz_id'     => Schemas::id(),
				'title'       => Schemas::string(),
				'type'        => Schemas::string(),
				'content'     => Schemas::string(),
				'mark'        => Schemas::number( 0 ),
				'order'       => Schemas::integer( 1 ),
				'answers'     => self::answers(),
				'explanation' => Schemas::string(),
				'hint'        => Schemas::string(),
			),
			array( 'quiz_id', 'title', 'type' )
		);
	}

	/**
	 * update-quiz-question input.
	 *
	 * @return array
	 */
	public static function update_input(): array {
		return Schemas::object(
			array(
				'quiz_id'     => Schemas::id(),
				'question_id' => Schemas::id(),
				'title'       => Schemas::string(),
				'content'     => Schemas::string(),
				'type'        => Schemas::string(),
				'mark'        => Schemas::number( 0 ),
				'order'       => Schemas::integer( 1 ),
				'answers'     => self::answers(),
				'explanation' => Schemas::string(),
				'hint'        => Schemas::string(),
			),
			array( 'quiz_id', 'question_id' )
		);
	}

	/**
	 * delete-quiz-question input.
	 *
	 * @return array
	 */
	public static function delete_input(): array {
		return Schemas::object(
			array(
				'quiz_id'     => Schemas::id(),
				'question_id' => Schemas::id(),
			),
			array( 'quiz_id', 'question_id' )
		);
	}

	/**
	 * add-quiz-question output.
	 *
	 * @return array
	 */
	public static function add_output(): array {
		return Schemas::object(
			array(
				'question_id'   => Schemas::integer(),
				'quiz_id'       => Schemas::integer(),
				'type'          => Schemas::string(),
				'mark'          => Schemas::number(),
				'answers_count' => Schemas::integer(),
			),
			array( 'question_id', 'quiz_id' )
		);
	}

	/**
	 * Write output wrapped under "question".
	 *
	 * @return array
	 */
	public static function write_output(): array {
		return Schemas::object_output( 'question' );
	}

	/**
	 * delete-quiz-question output.
	 *
	 * @return array
	 */
	public static function delete_output(): array {
		return Schemas::object(
			array(
				'removed'           => Schemas::boolean(),
				'removed_from_quiz' => Schemas::boolean(),
				'question_id'       => Schemas::integer(),
				'recovery'          => array( 'type' => 'object' ),
			),
			array( 'removed', 'question_id' )
		);
	}
}
