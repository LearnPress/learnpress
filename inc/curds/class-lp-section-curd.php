<?php

class LP_Section_CURD implements LP_Interface_CURD {

	/**
	 * Create item and insert to database.
	 *
	 * @since 3.0.0
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function create( $args ) {
		global $wpdb;

		$section = wp_parse_args( $args, array(
			'section_name',
			'section_course_id'   => 0,
			'section_order'       => 0,
			'section_description' => ''
		) );

		$section     = stripslashes_deep( $section );
		$insert_data = array(
			'section_name'        => $section['section_name'],
			'section_course_id'   => $section['section_course_id'],
			'section_order'       => $section['section_order'],
			'section_description' => $section['section_description'],
		);

		$wpdb->insert(
			$wpdb->learnpress_sections,
			$insert_data,
			array( '%s', '%d', '%d', '%s' )
		);
		$section['section_id'] = $wpdb->insert_id;

		return $section;
	}

	/**
	 * Load data from database.
	 *
	 * @since 3.0.0
	 *
	 * @param object $object
	 *
	 * @return mixed
	 */
	public function load( &$object ) {
		// TODO: Implement load() method.
	}

	/**
	 * Update data into database.
	 *
	 * @since 3.0.0
	 *
	 * @param $args array
	 *
	 * @return mixed
	 */
	public function update( $args = array() ) {
		// TODO: Implement update() method.
	}

	/**
	 * Delete data from database.
	 *
	 * @since 3.0.0
	 *
	 * @return mixed
	 */
	public function delete() {
		// TODO: Implement delete() method.
	}
}