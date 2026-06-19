<?php

namespace LearnPress\MCP\Schemas;

use LearnPress\MCP\Support\Schemas;
use LearnPress\MCP\Support\Validator;

defined( 'ABSPATH' ) || exit;

/**
 * Input/output schemas for enrollment write tools.
 */
class EnrollmentSchemas {

	/**
	 * Enrollment status enum schema.
	 *
	 * @return array
	 */
	protected static function status_enum(): array {
		return array(
			'type' => 'string',
			'enum' => Validator::enrollment_statuses(),
		);
	}

	/**
	 * Status enum allowed when creating a manual enrollment (active states only).
	 *
	 * @return array
	 */
	protected static function create_status_enum(): array {
		return array(
			'type' => 'string',
			'enum' => Validator::enroll_create_statuses(),
		);
	}

	/**
	 * Graduation enum schema.
	 *
	 * @return array
	 */
	protected static function graduation_enum(): array {
		return array(
			'type' => 'string',
			'enum' => Validator::graduations(),
		);
	}

	/**
	 * enroll-student input.
	 *
	 * @return array
	 */
	public static function enroll_input(): array {
		return Schemas::object(
			array(
				'user_id'    => Schemas::id(),
				'course_id'  => Schemas::id(),
				'status'     => self::create_status_enum(),
				'start_time' => Schemas::string(),
			),
			array( 'user_id', 'course_id' )
		);
	}

	/**
	 * update-enrollment input.
	 *
	 * @return array
	 */
	public static function update_input(): array {
		return Schemas::object(
			array(
				'enrollment_id' => Schemas::id(),
				'user_id'       => Schemas::id(),
				'course_id'     => Schemas::id(),
				'status'        => self::status_enum(),
				'graduation'    => self::graduation_enum(),
				'start_time'    => Schemas::string(),
				'end_time'      => Schemas::string(),
			),
			array( 'enrollment_id' )
		);
	}

	/**
	 * enroll-student output.
	 *
	 * @return array
	 */
	public static function enroll_output(): array {
		return Schemas::object(
			array(
				'enrollment_id'    => Schemas::integer(),
				'user_id'          => Schemas::integer(),
				'course_id'        => Schemas::integer(),
				'status'           => Schemas::string(),
				'start_time'       => Schemas::string(),
				'already_enrolled' => Schemas::boolean(),
			),
			array( 'enrollment_id', 'user_id', 'course_id' )
		);
	}

	/**
	 * Write output wrapped under "enrollment".
	 *
	 * @return array
	 */
	public static function write_output(): array {
		return Schemas::object_output( 'enrollment' );
	}

	/**
	 * get-student-progress input (read).
	 *
	 * @return array
	 */
	public static function progress_input(): array {
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
	 * get-enrollments input (read).
	 *
	 * @return array
	 */
	public static function get_enrollments_input(): array {
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
