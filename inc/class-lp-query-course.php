<?php

/**
 * Class LP_Course_Query
 *
 * @version 3.x.x
 */
class LP_Course_Query extends LP_Object_Query {

	/**
	 * LP_Course_Query constructor.
	 *
	 * @param string $query
	 */
	public function __construct( $query = '' ) {
		parent::__construct( $query );
	}

	/**
	 * @return array
	 */
	protected function get_default_query_vars() {
		return array_merge(
			parent::get_default_query_vars(),
			array(
				'post_type'   => LP_COURSE_CPT,
				'post_status' => 'publish'
			)
		);
	}

	public function get_courses(){

	}
}

// Backward compatibility
class LP_Query_Course extends LP_Course_Query {
}