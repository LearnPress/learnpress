<?php

class LP_Query_Course extends WP_Query {
	public function __construct( $query = '' ) {
		$query = wp_parse_args(
			$query,
			array(
				'post_type'   => LP_COURSE_CPT,
				'post_status' => 'publish'
			)
		);
		parent::__construct( $query );
	}
}