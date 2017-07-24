<?php

/**
 * Class LP_Course_Section
 *
 * @since 3.x.x
 */
class LP_Course_Section {

	/**
	 * Store section data
	 *
	 * @var null
	 */
	protected $data = null;

	/**
	 * LP_Course_Section constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {
		$data = wp_parse_args(
			$data,
			array(
				'section_id'          => 0,
				'section_name'        => '0',
				'section_course_id'   => 0,
				'section_order'       => 1,
				'section_description' => '',
				'items'               => array()
			)
		);

		// Set data
		foreach ( $data as $k => $v ) {
			$k                = str_replace( 'section_', '', $k );
			$this->data[ $k ] = $v;
		}

		// Load section items
		$this->_load_items();
	}

	/**
	 * Load items from course curriculum to it section
	 *
	 * @return bool
	 */
	protected function _load_items() {
		if ( ! $this->get_id() ) {
			return false;
		}

		// All items
		$curriculum = wp_cache_get( 'course-' . $this->get_course_id(), 'lp-course-curriculum' );

		if ( ! $curriculum ) {
			return false;
		}

		foreach ( $curriculum as $item ) {

			// Find the items with in this section only
			if ( $item->section_id != $this->get_id() ) {
				continue;
			}

			// Create item
			if ( $item_class = $this->_get_item( $item ) ) {
				$this->data['items'][ $item->item_id ] = $item_class;
			}
		}

		return true;
	}

	/**
	 * Get item class from item data
	 *
	 * @param array $item
	 *
	 * @return bool|LP_Abstract_Course_Item
	 */
	protected function _get_item( $item ) {
		$type  = str_replace( 'lp_', '', $item->item_type );
		$class = apply_filters( 'learn-press/course-item-class', 'LP_' . ucfirst( $type ), $item, $this );
		if ( class_exists( $class ) ) {
			return new $class( $item->item_id, $item );
		}

		return false;
	}

	/**
	 * Get data to array.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function to_array() {
		$data = array(
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'course_id'   => $this->get_course_id(),
			'description' => $this->get_description(),
			'items'       => $this->get_items_array(),
		);

		return $data;
	}

	/**
	 * Return section id
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->data['id'];
	}

	/**
	 * Return section title
	 *
	 * @return mixed
	 */
	public function get_title() {
		return apply_filters( 'learn-press/section-title', $this->data['name'], $this );
	}

	/**
	 * Return section course id
	 *
	 * @return mixed
	 */
	public function get_course_id() {
		return $this->data['course_id'];
	}

	/**
	 * Return section order
	 *
	 * @return mixed
	 */
	public function get_order() {
		return $this->data['order'];
	}

	/**
	 * Return section description
	 *
	 * @return mixed
	 */
	public function get_description() {
		return apply_filters( 'learn-press/section-description', $this->data['description'], $this );
	}

	/**
	 * Get items in this section.
	 *
	 * @return object[]
	 */
	public function get_items() {
		return apply_filters( 'learn-press/section-items', $this->data['items'], $this );
	}

	/**
	 * Get items in this section to array.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public function get_items_array() {
		$items = $this->get_items();

		$data = array();
		foreach ( $items as $item ) {
			$data[] = $item->to_array();
		}

		return $data;
	}

	/**
	 * Get completed items of a specific user.
	 * If user does not pass into then get from current user.
	 *
	 * @param int $user_id
	 *
	 * @return mixed
	 */
	public function get_completed_items( $user_id = 0 ) {
		$items = array();

		return $items;
	}

	/**
	 * Count number of items in section.
	 *
	 * @return int
	 */
	public function count_items() {
		$items = $this->get_items();

		return is_array( $items ) ? sizeof( $this->get_items() ) : 0;
	}

	/**
	 * Check if a section contains an item.
	 *
	 * @param $item_id
	 *
	 * @return bool
	 */
	public function has_item( $item_id ) {
		$found = false;

		if ( $items = $this->get_items() ) {
			$found = ! empty( $items[ $item_id ] );
		}

		return apply_filters( 'learn-press/section-has-item', $found, $item_id, $this->get_id(), $this->get_course_id() );
	}
}