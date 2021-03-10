<?php

/**
 * Class LP_Course_Section
 *
 * @since 3.0.0
 */
class LP_Course_Section extends LP_Abstract_Object_Data {

	/**
	 * Store section data
	 *
	 * @var null
	 */
	protected $data = null;

	/**
	 * @var int
	 */
	protected $course_id = 0;

	/**
	 * @var LP_Section_CURD
	 */
	protected $_curd = null;

	/**
	 * @var array
	 */
	protected $items = array();

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
				'position'            => 0,
				'items'               => array(),
			)
		);

		// Set data
		foreach ( $data as $k => $v ) {
			$k = str_replace( 'section_', '', $k );

			if ( $k === 'course_id' ) {
				$this->course_id = absint( $v );
				continue;
			}
			$this->_data[ $k ] = $v;
		}

		$this->_curd = new LP_Section_CURD( 0 );
		$this->set_id( $this->_data['id'] );
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
		$items = LP_Object_Cache::get( 'section-' . $this->get_id(), 'learn-press/section-items' );
		if ( false === $items ) {
			$items = $this->_curd->read_items( $this->get_id() );
			LP_Object_Cache::set( 'section-' . $this->get_id(), $items, 'learn-press/section-items' );
		}

		LP_Helper_CURD::cache_posts( $items );

		foreach ( $items as $item ) {
			$item_class = $this->_get_item( $item );

			if ( $item_class ) {
				if ( $item_class instanceof LP_Course_Item ) {
					$item_class->set_course( $this->get_course_id() );
					$item_class->set_section( $this );
					$this->items[ $item ] = $item_class;
				}
			}
		}

		return true;
	}

	/**
	 * Get item class from item data
	 *
	 * @param array $item
	 *
	 * @return bool|LP_Course_Item
	 */
	protected function _get_item( $item ) {
		if ( ! is_numeric( $item ) ) {
			$item_id = $item->item_id;
		} else {
			$item_id = absint( $item );
		}

		return LP_Course_Item::get_item( $item_id );
	}

	/**
	 * Get data to array.
	 *
	 * @return array
	 * @since 3.0.0
	 */
	public function to_array() {
		$data = array(
			'id'          => $this->get_id(),
			'title'       => $this->get_title(),
			'course_id'   => $this->get_course_id(),
			'description' => $this->get_description(),
			'items'       => $this->get_items_array(),
			'order'       => $this->get_order(),
		);

		return $data;
	}

	/**
	 * Return section id
	 *
	 * @return mixed
	 */
	public function get_id() {
		return $this->_data['id'];
	}

	/**
	 * Return section title
	 *
	 * @return mixed
	 */
	public function get_title() {
		return apply_filters( 'learn-press/section-title', $this->_data['name'], $this );
	}

	/**
	 * Return section course id
	 *
	 * @return mixed
	 */
	public function get_course_id() {
		return $this->course_id;
	}

	/**
	 * Return section order
	 *
	 * @return mixed
	 */
	public function get_order() {
		return $this->_data['order'];
	}

	/**
	 * Return section description
	 *
	 * @return mixed
	 */
	public function get_description() {
		return apply_filters( 'learn-press/section-description', $this->_data['description'], $this );
	}

	/**
	 * Get items in this section.
	 *
	 * @param string|array $type
	 * @param bool         $preview
	 *
	 * @return array
	 */
	public function get_items( $type = '', $preview = true ) {

		/**
		 * @var LP_Course_Item[] $items
		 */
		$items = apply_filters( 'learn-press/section-items', $this->items, $this );

		if ( ! $items ) {
			return $items;
		}

		if ( $type || ! $preview ) {
			$filtered_items = array();

			if ( $type ) {
				settype( $type, 'array' );
			}

			foreach ( $items as $item ) {

				if ( ! $preview ) {
					if ( $item->is_preview() ) {

						continue;
					}
				}
				if ( ! $type || $type && in_array( learn_press_get_post_type( $item->get_id() ), $type ) ) {
					$filtered_items[] = $item;
				}
			}

			$items = $filtered_items;
		}

		return $items;
	}

	/**
	 * Get items in this section to array.
	 *
	 * @return array
	 * @since 3.0.0
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
	 * @param string $type
	 * @param bool   $preview
	 *
	 * @return int
	 */
	public function count_items( $type = '', $preview = true ) {
		$items = $this->get_items( $type, $preview );

		return is_array( $items ) ? sizeof( $items ) : 0;
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
		$items = $this->get_items();

		if ( $items ) {
			$found = ! empty( $items[ $item_id ] );
		}

		return apply_filters( 'learn-press/section-has-item', $found, $item_id, $this->get_id(),
			$this->get_course_id() );
	}

	public function get_slug() {
		return $this->get_title() ? sanitize_title( $this->get_title() ) . '-' . $this->get_id() : $this->get_id();
	}

	public function main_class() {
		$class = array( 'section' );

		if ( ! $this->count_items() ) {
			$class[] = 'section-empty';
		}

		$closed = learn_press_cookie_get( 'closed-section-' . $this->get_course_id() );

		if ( $closed && in_array( $this->get_id(), $closed ) ) {
			$class[] = 'closed';
		}

		$output = 'class="' . implode( ' ', $class ) . '"';

		echo ' ' . $output;

		return $output;
	}

	public function set_position( $position ) {
		$this->_data['position'] = $position;
	}

	public function get_position() {
		return ! empty( $this->_data['position'] ) ? absint( $this->_data['position'] ) : 0;
	}
}
