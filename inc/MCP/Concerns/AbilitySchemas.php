<?php

namespace LearnPress\MCP\Concerns;

defined( 'ABSPATH' ) || exit;

/**
 * JSON schema builders for LearnPress abilities.
 */
trait AbilitySchemas {
	/**
	 * Build schema for required integer identifier.
	 *
	 * @param string $id Field name.
	 *
	 * @return array
	 */
	protected static function schema_required_id( string $id ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( $id ),
			'properties'           => array(
				$id => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		);
	}

	/**
	 * Build list response schema with pagination.
	 *
	 * @param array $item_schema Schema for each list item.
	 *
	 * @return array
	 */
	protected static function schema_list_output( array $item_schema ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'items', 'pagination' ),
			'properties'           => array(
				'items'      => array(
					'type'  => 'array',
					'items' => $item_schema,
				),
				'pagination' => self::schema_pagination(),
			),
		);
	}

	/**
	 * Build wrapper schema for a named object.
	 *
	 * @param string $key Object key.
	 *
	 * @return array
	 */
	protected static function schema_object_output( string $key ): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( $key ),
			'properties'           => array( $key => array( 'type' => 'object' ) ),
		);
	}

	/**
	 * Pagination schema used by list abilities.
	 *
	 * @return array
	 */
	protected static function schema_pagination(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'page', 'per_page', 'total_items', 'total_pages' ),
			'properties'           => array(
				'page'        => array( 'type' => 'integer' ),
				'per_page'    => array( 'type' => 'integer' ),
				'total_items' => array( 'type' => 'integer' ),
				'total_pages' => array( 'type' => 'integer' ),
			),
		);
	}

	/**
	 * Course summary schema.
	 *
	 * @return array
	 */
	protected static function schema_course_summary(): array {
		return array(
			'type'       => 'object',
			'properties' => array(
				'course_id'  => array( 'type' => 'integer' ),
				'title'      => array( 'type' => 'string' ),
				'status'     => array( 'type' => 'string' ),
				'price'      => array( 'type' => 'number' ),
				'duration'   => array( 'type' => 'string' ),
				'permalink'  => array( 'type' => 'string' ),
				'instructor' => array( 'type' => 'object' ),
				'categories' => array( 'type' => 'array' ),
			),
		);
	}

	/**
	 * Lesson summary schema.
	 *
	 * @return array
	 */
	protected static function schema_lesson_summary(): array {
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

	/**
	 * Quiz summary schema.
	 *
	 * @return array
	 */
	protected static function schema_quiz_summary(): array {
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

	/**
	 * Input schema for course listing.
	 *
	 * @return array
	 */
	protected static function schema_get_courses_input(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'status'     => array( 'type' => array( 'string', 'array', 'null' ) ),
				'category'   => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'instructor' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'price_min'  => array(
					'type'    => 'number',
					'minimum' => 0,
				),
				'price_max'  => array(
					'type'    => 'number',
					'minimum' => 0,
				),
				'search'     => array( 'type' => 'string' ),
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
			'default'              => array(),
		);
	}

	/**
	 * Output schema for course detail.
	 *
	 * @return array
	 */
	protected static function schema_course_detail_output(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'course' ),
			'properties'           => array( 'course' => array( 'type' => 'object' ) ),
		);
	}

	/**
	 * Input schema for lesson listing.
	 *
	 * @return array
	 */
	protected static function schema_list_lessons_input(): array {
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
	 * Output schema for lesson detail.
	 *
	 * @return array
	 */
	protected static function schema_lesson_detail_output(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'lesson' ),
			'properties'           => array( 'lesson' => array( 'type' => 'object' ) ),
		);
	}

	/**
	 * Input schema for quiz listing.
	 *
	 * @return array
	 */
	protected static function schema_list_quizzes_input(): array {
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
	 * Output schema for quiz detail.
	 *
	 * @return array
	 */
	protected static function schema_quiz_detail_output(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'quiz' ),
			'properties'           => array( 'quiz' => array( 'type' => 'object' ) ),
		);
	}

	/**
	 * Input schema for student progress.
	 *
	 * @return array
	 */
	protected static function schema_progress_input(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'required'             => array( 'user_id', 'course_id' ),
			'properties'           => array(
				'user_id'   => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'course_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
			),
		);
	}

	/**
	 * Input schema for enrollment listing.
	 *
	 * @return array
	 */
	protected static function schema_get_enrollments_input(): array {
		return array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => array(
				'course_id' => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'user_id'   => array(
					'type'    => 'integer',
					'minimum' => 1,
				),
				'status'    => array( 'type' => array( 'string', 'array', 'null' ) ),
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
			'default'              => array(),
		);
	}
}
