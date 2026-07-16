<?php

namespace LearnPress\MCP\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Shared JSON schema builders for Phase 2 MCP tools.
 *
 * Keeps every input schema strict (`additionalProperties=false`) and reuses
 * common field shapes so the per-domain schema providers stay small.
 */
class Schemas {

	/**
	 * Allowed post statuses for Phase 2 write tools.
	 *
	 * @return string[]
	 */
	public static function allowed_statuses(): array {
		return array( 'draft', 'publish', 'pending' );
	}

	/**
	 * Build a strict object schema.
	 *
	 * @param array    $properties Property schemas keyed by name.
	 * @param string[] $required   Required property names.
	 *
	 * @return array
	 */
	public static function object( array $properties, array $required = array() ): array {
		$schema = array(
			'type'                 => 'object',
			'additionalProperties' => false,
			'properties'           => $properties,
		);

		if ( ! empty( $required ) ) {
			$schema['required'] = array_values( $required );
		}

		return $schema;
	}

	/**
	 * Positive integer ID schema.
	 *
	 * @return array
	 */
	public static function id(): array {
		return array(
			'type'    => 'integer',
			'minimum' => 1,
		);
	}

	/**
	 * Strict object schema requiring a single positive integer ID field.
	 *
	 * @param string $id Field name (e.g. "course_id").
	 *
	 * @return array
	 */
	public static function required_id( string $id ): array {
		return self::object( array( $id => self::id() ), array( $id ) );
	}

	/**
	 * Post status enum schema restricted to the Phase 2 allowlist.
	 *
	 * @return array
	 */
	public static function status(): array {
		return array(
			'type' => 'string',
			'enum' => self::allowed_statuses(),
		);
	}

	/**
	 * String schema.
	 *
	 * @return array
	 */
	public static function string(): array {
		return array( 'type' => 'string' );
	}

	/**
	 * Boolean schema.
	 *
	 * @return array
	 */
	public static function boolean(): array {
		return array( 'type' => 'boolean' );
	}

	/**
	 * Number schema with optional minimum.
	 *
	 * @param float|null $minimum Optional minimum value.
	 *
	 * @return array
	 */
	public static function number( ?float $minimum = null ): array {
		$schema = array( 'type' => 'number' );
		if ( null !== $minimum ) {
			$schema['minimum'] = $minimum;
		}

		return $schema;
	}

	/**
	 * Integer schema with optional minimum.
	 *
	 * @param int|null $minimum Optional minimum value.
	 *
	 * @return array
	 */
	public static function integer( ?int $minimum = null ): array {
		$schema = array( 'type' => 'integer' );
		if ( null !== $minimum ) {
			$schema['minimum'] = $minimum;
		}

		return $schema;
	}

	/**
	 * Array-of-positive-IDs schema.
	 *
	 * @return array
	 */
	public static function id_array(): array {
		return array(
			'type'  => 'array',
			'items' => self::id(),
		);
	}

	/**
	 * Array-of-strings schema.
	 *
	 * @return array
	 */
	public static function string_array(): array {
		return array(
			'type'  => 'array',
			'items' => self::string(),
		);
	}

	/**
	 * Strict object output wrapper schema, e.g. { "course": { ... } }.
	 *
	 * @param string $key Wrapper key.
	 *
	 * @return array
	 */
	public static function object_output( string $key ): array {
		return self::object(
			array( $key => array( 'type' => 'object' ) ),
			array( $key )
		);
	}
}
