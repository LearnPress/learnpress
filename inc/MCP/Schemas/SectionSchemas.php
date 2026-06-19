<?php

namespace LearnPress\MCP\Schemas;

use LearnPress\MCP\Support\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Input/output schemas for section write tools.
 */
class SectionSchemas {

	/**
	 * create-section input.
	 *
	 * @return array
	 */
	public static function create_input(): array {
		return Schemas::object(
			array(
				'course_id'   => Schemas::id(),
				'name'        => Schemas::string(),
				'description' => Schemas::string(),
				'order'       => Schemas::integer( 1 ),
			),
			array( 'course_id', 'name' )
		);
	}

	/**
	 * update-section input.
	 *
	 * @return array
	 */
	public static function update_input(): array {
		return Schemas::object(
			array(
				'course_id'   => Schemas::id(),
				'section_id'  => Schemas::id(),
				'name'        => Schemas::string(),
				'description' => Schemas::string(),
				'order'       => Schemas::integer( 1 ),
			),
			array( 'course_id', 'section_id' )
		);
	}

	/**
	 * delete-section input.
	 *
	 * @return array
	 */
	public static function delete_input(): array {
		return Schemas::object(
			array(
				'course_id'  => Schemas::id(),
				'section_id' => Schemas::id(),
			),
			array( 'course_id', 'section_id' )
		);
	}

	/**
	 * Write output wrapped under "section".
	 *
	 * @return array
	 */
	public static function write_output(): array {
		return Schemas::object_output( 'section' );
	}

	/**
	 * delete-section output.
	 *
	 * @return array
	 */
	public static function delete_output(): array {
		return Schemas::object(
			array(
				'removed'        => Schemas::boolean(),
				'section_id'     => Schemas::integer(),
				'course_id'      => Schemas::integer(),
				'affected_items' => array(
					'type'  => 'array',
					'items' => array( 'type' => 'object' ),
				),
				'recovery'       => array( 'type' => 'object' ),
			),
			array( 'removed', 'section_id' )
		);
	}
}
