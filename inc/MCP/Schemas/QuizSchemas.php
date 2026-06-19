<?php

namespace LearnPress\MCP\Schemas;

use LearnPress\MCP\Support\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Input/output schemas for quiz write tools.
 */
class QuizSchemas {

	/**
	 * Shared quiz writable setting schemas.
	 *
	 * @return array
	 */
	protected static function settings(): array {
		return array(
			'content'             => Schemas::string(),
			'status'              => Schemas::status(),
			'duration'            => Schemas::string(),
			'passing_grade'       => array(
				'type'    => 'number',
				'minimum' => 0,
				'maximum' => 100,
			),
			'retake_count'        => Schemas::integer( 0 ),
			'instant_check'       => Schemas::boolean(),
			'negative_marking'    => Schemas::boolean(),
			'show_correct_review' => Schemas::boolean(),
			'order'               => Schemas::integer( 1 ),
		);
	}

	/**
	 * create-quiz input.
	 *
	 * @return array
	 */
	public static function create_input(): array {
		$properties = array_merge(
			array(
				'course_id'  => Schemas::id(),
				'section_id' => Schemas::id(),
				'title'      => Schemas::string(),
			),
			self::settings()
		);

		return Schemas::object( $properties, array( 'course_id', 'section_id', 'title' ) );
	}

	/**
	 * update-quiz input.
	 *
	 * @return array
	 */
	public static function update_input(): array {
		$properties = array_merge(
			array(
				'quiz_id'    => Schemas::id(),
				'title'      => Schemas::string(),
				'section_id' => Schemas::id(),
			),
			self::settings()
		);

		return Schemas::object( $properties, array( 'quiz_id' ) );
	}

	/**
	 * delete-quiz input.
	 *
	 * @return array
	 */
	public static function delete_input(): array {
		return Schemas::object(
			array(
				'quiz_id'    => Schemas::id(),
				'course_id'  => Schemas::id(),
				'section_id' => Schemas::id(),
			),
			array( 'quiz_id' )
		);
	}

	/**
	 * Write output wrapped under "quiz".
	 *
	 * @return array
	 */
	public static function write_output(): array {
		return Schemas::object_output( 'quiz' );
	}

	/**
	 * delete-quiz output.
	 *
	 * @return array
	 */
	public static function delete_output(): array {
		return Schemas::object(
			array(
				'trashed'                 => Schemas::boolean(),
				'quiz_id'                 => Schemas::integer(),
				'removed_from_curriculum' => Schemas::boolean(),
				'recovery'                => array( 'type' => 'object' ),
			),
			array( 'trashed', 'quiz_id' )
		);
	}

	/**
	 * list-quizzes input (read).
	 *
	 * @return array
	 */
	public static function list_quizzes_input(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'course_id' ),
			'properties'           => array(
				'course_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'page'      => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page'  => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 10,
				),
			),
		);
	}

	/**
	 * Quiz list-item summary schema (read).
	 *
	 * @return array
	 */
	public static function quiz_summary(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'quiz_id'         => array( 'type' => 'integer' ),
				'course_id'       => array( 'type' => 'integer' ),
				'section_id'      => array( 'type' => 'integer' ),
				'section_name'    => array( 'type' => 'string' ),
				'title'           => array( 'type' => 'string' ),
				'duration'        => array( 'type' => 'string' ),
				'passing_grade'   => array( 'type' => 'number' ),
				'questions_count' => array( 'type' => 'integer' ),
				'status'          => array( 'type' => 'string' ),
				'permalink'       => array( 'type' => 'string' ),
			),
		);
	}
}
