<?php

namespace LearnPress\MCP\Schemas;

use LearnPress\MCP\Support\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Input/output schemas for course write tools.
 */
class CourseSchemas {

	/**
	 * Shared course writable field schemas.
	 *
	 * @return array
	 */
	protected static function fields(): array {
		return array(
			'description'       => Schemas::string(),
			'excerpt'           => Schemas::string(),
			'status'            => Schemas::status(),
			'instructor_id'     => Schemas::id(),
			'category_ids'      => Schemas::id_array(),
			'tag_ids'           => Schemas::id_array(),
			'price'             => Schemas::number( 0 ),
			'sale_price'        => Schemas::number( 0 ),
			'duration'          => Schemas::string(),
			'level'             => Schemas::string(),
			'featured_image_id' => Schemas::id(),
			'requirements'      => Schemas::string_array(),
			'target_audiences'  => Schemas::string_array(),
			'features'          => Schemas::string_array(),
			'faqs'              => array(
				'type'  => 'array',
				'items' => Schemas::object(
					array(
						'question' => Schemas::string(),
						'answer'   => Schemas::string(),
					)
				),
			),
		);
	}

	/**
	 * create-course input.
	 *
	 * @return array
	 */
	public static function create_input(): array {
		$properties = array_merge(
			array( 'title' => Schemas::string() ),
			self::fields()
		);

		return Schemas::object( $properties, array( 'title' ) );
	}

	/**
	 * update-course input.
	 *
	 * @return array
	 */
	public static function update_input(): array {
		$properties = array_merge(
			array(
				'course_id' => Schemas::id(),
				'title'     => Schemas::string(),
			),
			self::fields()
		);

		return Schemas::object( $properties, array( 'course_id' ) );
	}

	/**
	 * delete-course input.
	 *
	 * @return array
	 */
	public static function delete_input(): array {
		return Schemas::object(
			array( 'course_id' => Schemas::id() ),
			array( 'course_id' )
		);
	}

	/**
	 * Write output wrapped under "course".
	 *
	 * @return array
	 */
	public static function write_output(): array {
		return Schemas::object_output( 'course' );
	}

	/**
	 * delete-course output.
	 *
	 * @return array
	 */
	public static function delete_output(): array {
		return Schemas::object(
			array(
				'trashed'         => Schemas::boolean(),
				'course_id'       => Schemas::integer(),
				'previous_status' => Schemas::string(),
				'recovery'        => array( 'type' => 'object' ),
			),
			array( 'trashed', 'course_id' )
		);
	}

	/**
	 * get-courses input (read).
	 *
	 * @return array
	 */
	public static function get_courses_input(): array {
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
	 * Course list-item summary schema (read).
	 *
	 * @return array
	 */
	public static function course_summary(): array {
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
}
