<?php

namespace LearnPress\MCP\Schemas;

use LearnPress\MCP\Support\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Input/output schemas for lesson write tools.
 *
 * Material attachment writes are intentionally deferred in Phase 2 and are not
 * part of the input contract.
 */
class LessonSchemas {

	/**
	 * create-lesson input.
	 *
	 * @return array
	 */
	public static function create_input(): array {
		return Schemas::object(
			array(
				'course_id'   => Schemas::id(),
				'section_id'  => Schemas::id(),
				'title'       => Schemas::string(),
				'content'     => Schemas::string(),
				'excerpt'     => Schemas::string(),
				'status'      => Schemas::status(),
				'duration'    => Schemas::string(),
				'preview'     => Schemas::boolean(),
				'video_intro' => Schemas::string(),
				'order'       => Schemas::integer( 1 ),
			),
			array( 'course_id', 'section_id', 'title' )
		);
	}

	/**
	 * update-lesson input.
	 *
	 * @return array
	 */
	public static function update_input(): array {
		return Schemas::object(
			array(
				'lesson_id'   => Schemas::id(),
				'title'       => Schemas::string(),
				'content'     => Schemas::string(),
				'excerpt'     => Schemas::string(),
				'status'      => Schemas::status(),
				'duration'    => Schemas::string(),
				'preview'     => Schemas::boolean(),
				'video_intro' => Schemas::string(),
				'section_id'  => Schemas::id(),
				'order'       => Schemas::integer( 1 ),
			),
			array( 'lesson_id' )
		);
	}

	/**
	 * delete-lesson input.
	 *
	 * @return array
	 */
	public static function delete_input(): array {
		return Schemas::object(
			array(
				'lesson_id'  => Schemas::id(),
				'course_id'  => Schemas::id(),
				'section_id' => Schemas::id(),
			),
			array( 'lesson_id' )
		);
	}

	/**
	 * Write output wrapped under "lesson".
	 *
	 * @return array
	 */
	public static function write_output(): array {
		return Schemas::object_output( 'lesson' );
	}

	/**
	 * delete-lesson output.
	 *
	 * @return array
	 */
	public static function delete_output(): array {
		return Schemas::object(
			array(
				'trashed'                 => Schemas::boolean(),
				'lesson_id'               => Schemas::integer(),
				'removed_from_curriculum' => Schemas::boolean(),
				'recovery'                => array( 'type' => 'object' ),
			),
			array( 'trashed', 'lesson_id' )
		);
	}

	/**
	 * list-lessons input (read).
	 *
	 * @return array
	 */
	public static function list_lessons_input(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'course_id' ),
			'properties'           => array(
				'course_id'  => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'section_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status'     => array( 'type' => array( 'string', 'array', 'null' ) ),
				'page'       => array(
					'type'    => 'integer',
					'minimum' => 1,
					'default' => 1,
				),
				'per_page'   => array(
					'type'    => 'integer',
					'minimum' => 1,
					'maximum' => 100,
					'default' => 10,
				),
			),
		);
	}

	/**
	 * Lesson list-item summary schema (read).
	 *
	 * @return array
	 */
	public static function lesson_summary(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'lesson_id'    => array( 'type' => 'integer' ),
				'course_id'    => array( 'type' => 'integer' ),
				'section_id'   => array( 'type' => 'integer' ),
				'section_name' => array( 'type' => 'string' ),
				'title'        => array( 'type' => 'string' ),
				'excerpt'      => array( 'type' => 'string' ),
				'duration'     => array( 'type' => 'string' ),
				'preview'      => array( 'type' => 'boolean' ),
				'status'       => array( 'type' => 'string' ),
				'permalink'    => array( 'type' => 'string' ),
			),
		);
	}
}
