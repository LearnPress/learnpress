<?php

/**
 * Class LP_Object_Query
 *
 * Base class for query functionality on post type object.
 *
 * @since 3.x.x
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
	 * @since 3.x.x
	 *
	 * @return array
	 */
	public function get_query_vars() {
		return $this->query_vars;
	}

	/**
	 * Get value of a query var by key.
	 *
	 * @since 3.x.x
	 *
	 * @param string $var_name
	 * @param string $default
	 *
	 * @return mixed|string
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
	 * @since 3.x.x
	 *
	 * @param string $var_name
	 * @param mixed  $value
	 * @param bool   $overwrite
	 */
	public function set( $var_name, $value, $overwrite = false ) {

		if ( $overwrite || ! isset( $this->query_vars[ $var_name ] ) ) {
			$this->query_vars[ $var_name ] = $value;
		}
	}

	/**
	 * Get default query vars.
	 *
	 * @since 3.x.x
	 *
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array(
			'name'           => '',
			'parent'         => '',
			'parent_exclude' => '',
			'exclude'        => '',
			'limit'          => get_option( 'posts_per_page' ),
			'page'           => 1,
			'offset'         => '',
			'paginate'       => false,
			'order'          => 'DESC',
			'orderby'        => 'date',
			'return'         => 'objects'
		);
	}
}