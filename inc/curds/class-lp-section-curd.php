<?php

/**
 * Class LP_Section_CURD
 *
 * @since 3.0.0
 */
class LP_Section_CURD extends LP_Object_Data_CURD implements LP_Interface_CURD {
	/**
	 * @var int
	 *
	 * @since 3.0.0
	 */
	private $course_id = null;

	/**
	 * LP_Section_CURD constructor.
	 *
	 * @since 3.0.0
	 *
	 * @param $course_id
	 */
	public function __construct( $course_id ) {
		$this->course_id = $course_id;
	}

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
		$data = wp_parse_args( $args, array(
			'section_name'        => '',
			'section_description' => '',
			'section_course_id'   => 0,
			'section_order'       => 0,
			'items'               => array(),
		) );

		return $data;
	}

	/**
	 * @param $course_id
	 *
	 * @return int
	 */
	private function get_last_number_order( $course_id ) {
		global $wpdb;

		$query  = $wpdb->prepare( "SELECT MAX(s.section_order) FROM {$wpdb->prefix}learnpress_sections AS s WHERE s.section_course_id = %d", $course_id );
		$result = intval( $wpdb->get_var( $query ) );

		return ( $result > 0 ) ? $result : 1;
	}

	/**
	 * Update sort sections.
	 *
	 * @since 3.0.0
	 *
	 * @param $sections string[]
	 *
	 * @return array
	 */
	public function sort_sections( $sections ) {
		global $wpdb;

		$orders = array();

		foreach ( $sections as $index => $section_id ) {
			$order = $index + 1;

			$orders[ $section_id ] = $order;

			$wpdb->update(
				$wpdb->learnpress_sections,
				array( 'section_order' => $order ),
				array( 'section_id' => $section_id )
			);
		}

		return $orders;
	}

	/**
	 * Create item and insert to database.
	 *
	 * @since 3.0.0
	 *
	 * @param $args array
	 *
	 * @return mixed
	 */
	public function create( &$args ) {
		global $wpdb;

		$section                  = $this->parse( $args );
		$section                  = stripslashes_deep( $section );
		$section['section_order'] = $this->get_last_number_order( $section['section_course_id'] ) + 1;
		$insert_data              = array(
			'section_course_id'   => $this->course_id,
			'section_name'        => $section['section_name'],
			'section_order'       => $section['section_order'],
			'section_description' => $section['section_description'],
		);

		$wpdb->insert(
			$wpdb->learnpress_sections,
			$insert_data,
			array( '%d', '%s', '%d', '%s' )
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
	public function update( &$args = array() ) {
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
	 * @param $id string
	 *
	 * @return bool
	 */
	public function delete( &$id ) {
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

	/**
	 * Get list items of section.
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id
	 *
	 * @return array
	 */
	private function get_section_items( $section_id ) {
		$course = learn_press_get_course( $this->course_id );

		$sections = $course->get_curriculum_raw();
		if ( empty( $sections ) ) {
			return array();
		}
		foreach ( $sections as $section ) {
			if ( $section['id'] == $section_id ) {
				if ( isset( $section['items'] ) && is_array( $section['items'] ) ) {
					return $section['items'];
				}
			}
		}

		return array();
	}

	/**
	 * @param $items array
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id string
	 * @param $items array
	 *
	 * @return array
	 */
	public function add_items_section( $section_id, $items = array() ) {
		$order         = 1;
		$current_items = $this->get_section_items( $section_id );

		global $wpdb;

		$all_items = array_merge( $current_items, $items );
		$result    = array();

		foreach ( $all_items as $item ) {
			$exist = $this->item_section_exist( $section_id, $item['id'] );

			if ( $exist ) {
				$wpdb->update(
					$wpdb->learnpress_section_items,
					array(
						'item_order' => $order
					),
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id']
					)
				);
			} else {
				$wpdb->insert(
					$wpdb->learnpress_section_items,
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id'],
						'item_order' => $order,
						'item_type'  => $item['type'],
					)
				);
			}

			$post     = get_post( $item['id'] );
			$result[] = array(
				'id'    => $post->ID,
				'title' => $post->post_title,
				'type'  => $post->post_type,
			);

			$order ++;
		}

		return $result;
	}

	/**
	 * Check item was been added to any section.
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id
	 * @param $item_id
	 *
	 * @return bool
	 */
	private function item_section_exist( $section_id, $item_id ) {
		global $wpdb;

		$section_id = intval( $section_id );
		$item_id    = intval( $item_id );

		$query = $wpdb->prepare( "SELECT * FROM {$wpdb->learnpress_section_items} WHERE section_id = %d AND item_id = %d", $section_id, $item_id );
		$item  = $wpdb->get_row( $query, ARRAY_A );

		return ! ! $item;
	}

	public function sort_section_items( $orders ) {

	}

	/**
	 * Remove section item.
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function remove_section_item( $section_id, $item_id ) {
		global $wpdb;

		$result = $wpdb->delete(
			$wpdb->learnpress_section_items,
			array(
				'section_id' => $section_id,
				'item_id'    => $item_id
			),
			array(
				'%d',
				'%d'
			)
		);

		return ! ! $result;
	}

	/**
	 * Update section items.
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id
	 * @param $items array
	 *
	 * @return array
	 */
	public function update_section_items( $section_id, $items ) {
		$current_items = $this->get_section_items( $section_id );

		global $wpdb;
		foreach ( $items as $index => $item ) {
			$order = $index + 1;
			$exist = $this->item_section_exist( $section_id, $item['id'] );

			if ( $exist ) {
				$wpdb->update(
					$wpdb->learnpress_section_items,
					array(
						'item_order' => $order
					),
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id']
					)
				);
			} else {
				$wpdb->insert(
					$wpdb->learnpress_section_items,
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id'],
						'item_order' => $order,
						'item_type'  => $item['type'],
					)
				);
			}
		}

		/**
		 * Remove non-existent items.
		 */
		foreach ( $current_items as $item ) {
			$find = $this->check_item_exist( $items, $item['id'] );

			if ( ! $find ) {
				$wpdb->delete(
					$wpdb->learnpress_section_items,
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id'],
					)
				);
			}
		}

		return $items;
	}

	/**
	 * Check item exist.
	 *
	 * @since 3.0.0
	 *
	 * @param $items array
	 * @param $item_id string
	 *
	 * @return bool
	 */
	private function check_item_exist( $items, $item_id ) {
		foreach ( $items as $item ) {
			if ( $item['id'] == $item_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Update section item.
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id
	 * @param $item
	 *
	 * @return array
	 */
	public function update_section_item( $section_id, $item ) {
		$item = wp_parse_args( $item, array(
			'id'    => '',
			'title' => '',
			'type'  => ''
		) );

		wp_update_post( array(
			'ID'         => $item['id'],
			'post_title' => $item['title'],
		) );

		return $item;
	}

	/**
	 * Create a section item.
	 *
	 * @since 3.0.0
	 *
	 * @param $section_id
	 * @param $item
	 *
	 * @return array
	 */
	public function create_section_item( $section_id, $item ) {
		$item = wp_parse_args( $item, array(
			'title' => '',
			'type'  => ''
		) );

		$post_id = wp_insert_post( array(
			'post_title'  => $item['title'],
			'post_type'   => $item['type'],
			'post_status' => 'publish'
		) );

		$item['id'] = $post_id;

		return $this->add_items_section( $section_id, array( $item ) );
	}
}