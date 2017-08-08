<?php

/**
 * Class LP_Section_CURD
 *
 * @since 3.0.0
 */
class LP_Section_CURD implements LP_Interface_CURD {

	/**
	 * Parse input data.
	 *
	 * @since 3.0.0
	 *
	 * @param $args
	 *
	 * @return array
	 */
	private function parse( $args ) {
		return wp_parse_args( $args, array(
			'section_name',
			'section_course_id'   => 0,
			'section_order'       => 0,
			'section_description' => ''
		) );
	}

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

		$section     = $this->parse( $args );
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
		$section = $this->parse( $args );

		if ( empty( $section['section_id'] ) ) {
			return $this->create( $args );
		}

		$section_id  = $section['section_id'];
		$update_data = array(
			'section_name'        => $section['section_name'],
			'section_course_id'   => $section['section_course_id'],
			'section_order'       => $section['section_order'],
			'section_description' => $section['section_description'],
		);

		global $wpdb;

		$wpdb->update(
			$wpdb->learnpress_sections,
			$update_data,
			array( 'section_id' => $section_id )
		);
		$section['section_id'] = $section_id;

		return $section;
	}

	/**
	 * Delete data from database.
	 *
	 * @since 3.0.0
	 *
	 * @param $id
	 *
	 * @return bool
	 */
	public function delete( $id ) {
		global $wpdb;

		/**
		 * Remove section items.
		 */
		$wpdb->delete(
			$wpdb->learnpress_section_items,
			array( 'section_id' => $id )
		);

		$result = $wpdb->delete(
			$wpdb->learnpress_sections,
			array( 'section_id' => $id )
		);

		return ! ! $result;
	}
}