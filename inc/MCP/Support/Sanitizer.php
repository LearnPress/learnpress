<?php

namespace LearnPress\MCP\Support;

use LP_Helper;

defined( 'ABSPATH' ) || exit;

/**
 * Boundary sanitization helpers for MCP write input.
 *
 * Reuses LearnPress sanitization conventions so MCP write tools store data the
 * same way the Course Builder and admin flows do.
 */
class Sanitizer {

	/**
	 * Sanitize a plain text field (titles, names, short labels).
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function text( $value ): string {
		return sanitize_text_field( (string) $value );
	}

	/**
	 * Sanitize rich/HTML content the LearnPress way.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function html( $value ): string {
		return LP_Helper::sanitize_params_submitted( (string) $value, 'html' );
	}

	/**
	 * Coerce a JSON boolean, "true"/"false", 1/0 into a strict boolean.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return bool
	 */
	public static function boolean( $value ): bool {
		return (bool) filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Parse a date/time string into MySQL GMT format.
	 *
	 * Returns null for empty or unparseable input so callers can reject it
	 * instead of silently persisting the 1970-01-01 epoch.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string|null 'Y-m-d H:i:s' (GMT) or null.
	 */
	public static function datetime( $value ): ?string {
		$value = trim( (string) $value );
		if ( '' === $value ) {
			return null;
		}

		$timestamp = strtotime( $value );
		if ( false === $timestamp ) {
			return null;
		}

		return gmdate( 'Y-m-d H:i:s', $timestamp );
	}

	/**
	 * Sanitize a URL for storage.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return string
	 */
	public static function url( $value ): string {
		return esc_url_raw( (string) $value );
	}

	/**
	 * Sanitize a list of positive integer IDs.
	 *
	 * @param mixed $value Raw value (array expected).
	 *
	 * @return int[]
	 */
	public static function id_list( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $item ) {
			$id = absint( $item );
			if ( $id > 0 ) {
				$ids[] = $id;
			}
		}

		return array_values( array_unique( $ids ) );
	}

	/**
	 * Normalize ability input to an array.
	 *
	 * @param mixed  $input   Raw ability input.
	 * @param string $ability Ability name for error context.
	 *
	 * @return array|\WP_Error
	 */
	public static function input_array( $input, string $ability ) {
		if ( null === $input ) {
			return array();
		}
		if ( is_array( $input ) ) {
			return $input;
		}

		return Errors::invalid(
			sprintf(
				/* translators: %s: ability name. */
				__( 'Invalid input for ability "%s". Input must be an object.', 'learnpress' ),
				$ability
			)
		);
	}

	/**
	 * Normalize status input into a unique sanitized status list.
	 *
	 * @param mixed $input String, CSV string, array, or null.
	 *
	 * @return array
	 */
	public static function status_list( $input ): array {
		if ( null === $input || '' === $input ) {
			return array();
		}

		$values = is_array( $input )
			? $input
			: ( false !== strpos( (string) $input, ',' ) ? explode( ',', (string) $input ) : array( (string) $input ) );

		$out = array();
		foreach ( $values as $value ) {
			$status = LP_Helper::sanitize_params_submitted( (string) $value, 'key' );
			if ( '' !== $status ) {
				$out[] = $status;
			}
		}

		return array_values( array_unique( $out ) );
	}
}
