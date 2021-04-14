<?php
abstract class LP_REST_Jwt_Controller extends WP_REST_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = '';

	private $_fields = null;

	private $_request = null;

	protected function add_additional_fields_schema( $schema ) {
		if ( empty( $schema['title'] ) ) {
			return $schema;
		}

		/**
		 * Can't use $this->get_object_type otherwise we cause an inf loop.
		 */
		$object_type = $schema['title'];

		$additional_fields = $this->get_additional_fields( $object_type );

		foreach ( $additional_fields as $field_name => $field_options ) {
			if ( ! $field_options['schema'] ) {
				continue;
			}

			$schema['properties'][ $field_name ] = $field_options['schema'];
		}

		$schema['properties'] = apply_filters( 'lp_jwt_rest_' . $object_type . '_schema', $schema['properties'] );

		return $schema;
	}
}
