<?php

/**
 * Class LP_Object_Query
 *
 * Base class for query functionality on post type object.
 *
 * @since 3.3.0
 */
abstract class LP_Object_Query {
	/**
	 * @var array
	 */
	protected $query_vars = array();

	/**
	 * LP_Object_Query constructor.
	 *
	 * @param array $args
	 */
	public function __construct( $args = array() ) {

		$this->query_vars = wp_parse_args( $args, $this->get_default_query_vars() );
	}

	/**
	 * Get all query vars.
	 *
	 * @return array
	 * @since 3.3.0
	 */
	public function get_query_vars() {
		return $this->query_vars;
	}

	/**
	 * Get value of a query var by key.
	 *
	 * @param string $var_name
	 * @param string $default
	 *
	 * @return mixed|string
	 * @since 3.3.0
	 */
	public function get( $var_name, $default = '' ) {
		if ( isset( $this->query_vars[ $var_name ] ) ) {
			return $this->query_vars[ $var_name ];
		}

		return $default;
	}

	/**
	 * Set new value for query var.
	 *
	 * @param string $var_name
	 * @param mixed  $value
	 * @param bool   $overwrite
	 *
	 * @since 3.3.0
	 */
	public function set( $var_name, $value, $overwrite = false ) {

		if ( $overwrite || ! isset( $this->query_vars[ $var_name ] ) ) {
			$this->query_vars[ $var_name ] = $value;
		}
	}

	/**
	 * Get default query vars.
	 *
	 * @return array
	 * @since 3.3.0
	 */
	protected function get_default_query_vars() {
		return array(
			'name'           => '',
			'parent'         => '',
			'parent_exclude' => '',
			'exclude'        => '',
			'limit'          => get_option( 'posts_per_page' ),
			'paged'          => 1,
			'offset'         => '',
			'paginate'       => false,
			'order'          => 'DESC',
			'orderby'        => 'date',
			'return'         => 'objects',
		);
	}
}
