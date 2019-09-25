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

		// allow hook
		do_action( 'learn-press/after-create-section', $this->course_id, $section );

		return $section;
	}

	/**
	 * Update data into database.
	 *
	 * @since 3.0.0
	 *
	 * @param $args
	 *
	 * @return mixed
	 */
	public function update( &$args ) {

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
	 * Delete section data from database.
	 *
	 * @since 3.0.0
	 *
	 * @param $id string
	 *
	 * @return bool
	 */
	public function delete( &$id ) {

		global $wpdb;

		// allow hook
		do_action( 'learn-press/before-delete-section', $this->course_id, $id );

		// Remove section items.
		$wpdb->delete( $wpdb->learnpress_section_items, array( 'section_id' => $id ) );
		learn_press_reset_auto_increment( 'learnpress_section_items' );

		// Remove section
		$result = $wpdb->delete( $wpdb->learnpress_sections, array( 'section_id' => $id ) );
		learn_press_reset_auto_increment( 'learnpress_sections' );

		return ! ! $result;
	}

	public function duplicate( &$section, $args = array() ) {
		// TODO: Implement duplicate() method.
	}

	/**
	 * Remove all items from each section and delete course's sections.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function clear() {

		$sections_ids = LP_Object_Cache::get( 'course-' . $this->course_id, 'learn-press/course-sections-ids' );

		if ( ! $sections_ids ) {
			return false;
		}

		global $wpdb;

		do_action( 'learn-press/before-clear-section', $this->course_id );

		// Remove all items in course's sections
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE %d AND section_id IN(" . join( ',', $sections_ids ) . ")", 1 ) );
		learn_press_reset_auto_increment( 'learnpress_section_items' );
		// delete sections ids cache
		wp_cache_delete( 'course-' . $this->course_id, 'learn-press/course-sections-ids' );


		// delete sections in course
		$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d", $this->course_id ) );
		learn_press_reset_auto_increment( 'learnpress_sections' );
		// delete sections cache
		wp_cache_delete( 'course-' . $this->course_id, 'learn-press/course-sections' );

		return true;
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
	 * Get course sections ids and set data to cache.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function read_sections_ids() {

		// Get course's sections id data from cache
		$ids = LP_Object_Cache::get( 'course-' . $this->course_id, 'learn-press/course-sections-ids' );

		if ( ! $ids ) {
			global $wpdb;
			// get sections id
			$ids = $wpdb->get_col( $wpdb->prepare( "SELECT section_id FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d", $this->course_id ) );
			// Set cache
			LP_Object_Cache::set( 'course-' . $this->course_id, $ids, 'learn-press/course-sections-ids' );
		}

		return $ids;
	}

	/**
	 * Read all items from DB
	 *
	 * @param int $section_id
	 *
	 * @return array
	 */
	public function read_items( $section_id  ) {

		global $wpdb;

		$query = $wpdb->prepare( "
			SELECT item_id id
			FROM {$wpdb->learnpress_section_items} si 
			INNER JOIN {$wpdb->learnpress_sections} s ON si.section_id = s.section_id
			INNER JOIN {$wpdb->posts} c ON c.ID = s.section_course_id
			INNER JOIN {$wpdb->posts} it ON it.ID = si.item_id
			WHERE s.section_id = %d
			AND it.post_status = %s
			ORDER BY si.item_order, si.section_item_id ASC
		", $section_id, /*'publish',*/ 'publish' );

		return $wpdb->get_col( $query );
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

		$current_sections = LP_Object_Cache::get( 'course-' . $this->course_id, 'learn-press/course-sections' );
		$new_sections     = array();

		$orders = array();

		foreach ( $sections as $index => $section_id ) {
			$order = $index + 1;

			$orders[ $section_id ] = $order;

			$wpdb->update(
				$wpdb->learnpress_sections,
				array( 'section_order' => $order ),
				array( 'section_id' => $section_id )
			);

			foreach ( $current_sections as $current_section ) {
				if ( $current_section->section_id == $section_id ) {
					$new_sections[ $index ] = $current_section;
				}
			}

			$this->get_section_items( $section_id );
		}

		LP_Object_Cache::set( 'course-' . $this->course_id, $new_sections, 'learn-press/course-sections' );

		return $orders;
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
	public function get_section_items( $section_id ) {
		$course = learn_press_get_course( $this->course_id );

		$sections = $course->get_curriculum_raw();

		$return = array();

		if ( ! empty( $sections ) ) {
			foreach ( $sections as $section ) {
				if ( $section['id'] == $section_id ) {
					if ( isset( $section['items'] ) && is_array( $section['items'] ) ) {
						$return = $section['items'];
					}
				}
			}
		}

		LP_Object_Cache::set( 'course-' . $this->course_id . '-' . $section_id, $return, 'learn-press/course-section-items' );

		return $return;
	}

	/**
	 * Create new section item and add to course.
	 *
	 * @since 3.0.0
	 *
	 * @param int   $section_id
	 * @param array $item
	 *
	 * @return array | bool
	 */
	public function new_item( $section_id, $item ) {

		// course author, for case co-instructor add new items
		$author_id = get_post_field( 'post_author', $this->course_id ) ? get_post_field( 'post_author', $this->course_id ) : learn_press_get_current_user_id();

		//$item = wp_parse_args( $item, array( 'title' => '', 'type' => '' ) );
		$item = array_merge( array( 'title' => '', 'type' => '' ), $item );

		$args = array(
			'title'  => $item['title'],
			'author' => $author_id
		);

		if ( ! empty( $item['id'] ) ) {
			$item['old_id'] = $item ['id'];
		}
		if ( $item['type'] == LP_LESSON_CPT ) {
			$lesson_curd = new LP_Lesson_CURD();
			$item['id']  = $lesson_curd->create( $args );
		} else if ( $item['type'] == LP_QUIZ_CPT ) {
			$quiz_curd  = new LP_Quiz_CURD();
			$item['id'] = $quiz_curd->create( $args );
		} else {
			$item['id'] = apply_filters( 'learn-press/new-section-item-data', $item['id'], $item, $args, $this->course_id );
		}

		if ( is_wp_error( $item['id'] ) || ! $item['id'] ) {
			return false;
		}

		$item['preview'] = get_post_meta( $item['id'], '_lp_preview', true ) == 'yes';

		// allow hook
		do_action( 'learn-press/after-new-section-item', $item['id'], $section_id, $this->course_id );

		// add item to section
		return $this->add_items_section( $section_id, array( $item ) );
	}

	/**
	 * Add list new items to section.
	 *
	 * @param       $section_id
	 * @param array $items
	 *
	 * @return array
	 */
	public function add_items_section( $section_id, $items = array() ) {
		$order         = 1;
		$current_items = $this->get_section_items( $section_id );
		// allow hook
		do_action( 'learn-press/before-add-items-section', $items, $section_id, $this->course_id );

		global $wpdb;

		$all_items = array_merge( $current_items, $items );
		$result    = array();
		foreach ( $all_items as $item ) {

			$item  = (array) $item;
			$exist = $this->item_section_exist( $section_id, $item['id'] );

			if ( $exist ) {
				$a = $wpdb->update( $wpdb->learnpress_section_items,
					array( 'item_order' => $order ),
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id']
					)
				);
			} else {
				$a = $wpdb->insert( $wpdb->learnpress_section_items,
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id'],
						'item_order' => $order,
						'item_type'  => $item['type'],
					)
				);
			}
			// get WP Post
			$post = get_post( $item['id'] );
			$item = array_merge(
				$item,
				array(
					'id'      => $post->ID,
					'title'   => $post->post_title,
					'type'    => $post->post_type,
					'preview' => get_post_meta( $post->ID, '_lp_preview', true ) == 'yes'
				)
			);

			if ( ! $exist ) {
				do_action( 'learn-press/added-item-to-section', $item, $section_id, $this->course_id );
			} else {
				do_action( 'learn-press/updated-item-to-section', $item, $section_id, $this->course_id );
			}

			$result[] = $item;
			$order ++;
		}

		LP_Object_Cache::set( 'course-' . $this->course_id . '-' . $section_id, $all_items, 'learn-press/course-section-items' );

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

	/**
	 * Get section id of lesson, quiz id in curriculum.
	 *
	 * @param $item_id
	 *
	 * @return array|bool|null|object
	 */
	public function get_item_section( $item_id ) {
		global $wpdb;

		$query   = $wpdb->prepare( "SELECT section_id, item_order FROM {$wpdb->learnpress_section_items} WHERE item_id = %d", $item_id );
		$section = $wpdb->get_row( $query, ARRAY_A );

		return $section;
	}

	/**
	 * Update course final item.
	 *
	 * @since 3.0.0
	 *
	 * @return bool
	 */
	public function update_final_item() {

		$sections = LP_Object_Cache::get( 'course-' . $this->course_id, 'learn-press/course-sections' );

		if ( ! $sections ) {
			return false;
		}

		$last_section = end( $sections );
		$section_id   = $last_section->section_id;

		// get last section items
		$section_items = LP_Object_Cache::get( 'course-' . $this->course_id . '-' . $section_id, 'learn-press/course-section-items' );

		$types = apply_filters( 'learn-press/post-types-support-assessment-by-final-item', array( LP_QUIZ_CPT ) );

		if ( $section_items ) {
			// last item in last section
			$final = end( $section_items );

			if ( is_array( $types ) && in_array( $final['type'], $types ) ) {
				update_post_meta( $this->course_id, '_' . substr_replace( $final['type'], "_final_", 2, 1 ), $final['id'] );
				$diff = array_diff( $types, array( $final['type'] ) );
				foreach ( $diff as $type ) {
					// delete all other final meta
					delete_post_meta( $this->course_id, '_' . substr_replace( $type, "_final_", 2, 1 ) );
				}
			} else {
				// for last item is not post type need check final item
				foreach ( $types as $type ) {
					delete_post_meta( $this->course_id, '_' . substr_replace( $type, "_final_", 2, 1 ) );
				}
			}
		} else {
			// for last section does not has any item
			foreach ( $types as $type ) {
				delete_post_meta( $this->course_id, '_' . substr_replace( $type, "_final_", 2, 1 ) );
			}
		}

		return true;

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
				$wpdb->update( $wpdb->learnpress_section_items,
					array( 'item_order' => $order ),
					array(
						'section_id' => $section_id,
						'item_id'    => $item['id']
					)
				);
			} else {
				$wpdb->insert( $wpdb->learnpress_section_items,
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

		LP_Object_Cache::set( 'course-' . $this->course_id . '-' . $section_id, $items, 'learn-press/course-section-items' );

		return $items;
	}

	/**
	 * Check item exist.
	 *
	 * @since 3.0.0
	 *
	 * @param $items   array
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
	 * Update lesson, quiz title in admin course editor.
	 *
	 * @since 3.0.0
	 *
	 * @param $item
	 *
	 * @return array
	 */
	public function update_item( $item ) {
		$item = wp_parse_args( $item, array( 'id' => '', 'title' => '', ) );

		wp_update_post( array( 'ID' => $item['id'], 'post_title' => $item['title'], ) );

		if ( isset( $item['preview'] ) && $item['preview'] == 1 ) {
			update_post_meta( $item['id'], '_lp_preview', 'yes' );
		} else {
			delete_post_meta( $item['id'], '_lp_preview' );
		}

		return $item;
	}
}