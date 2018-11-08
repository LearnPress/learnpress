<?php

/**
 * Class LP_Query_List_Table
 */
class LP_Query_List_Table implements ArrayAccess {
	/**
	 * @var array|null
	 */
	protected $_data = null;

	/**
	 * LP_Query_List_Table constructor.
	 *
	 * @param $data
	 */
	public function __construct( $data ) {

		$this->_data = wp_parse_args(
			$data, array(
				'pages'      => 0,
				'total'      => 0,
				'items'      => null,
				'paged'      => 1,
				'limit'      => 10,
				'nav_format' => '%#%/',
				'nav_base'   => '',
				'single'     => __( 'item', 'learnpress' ),
				'plural'     => __( 'items', 'learnpress' ),
				'format'     => ''
			)
		);

		global $wp;
		if ( ! empty( $wp->query_vars['view_id'] ) && empty( $data['paged'] ) ) {
			$this->_data['paged'] = absint( $wp->query_vars['view_id'] );
		}

		$this->_init();
	}

	/**
	 *
	 */
	protected function _init() {

	}

	/**
	 * @return int
	 */
	public function get_pages() {
		if ( empty( $this->_data['pages'] ) && $this->_data['total'] ) {
			$this->_data['pages'] = ceil( $this->_data['total'] / $this->_data['limit'] );
		}

		return absint( $this->_data['pages'] );
	}

	/**
	 * @return int
	 */
	public function get_total() {
		return absint( $this->_data['total'] );
	}

	/**
	 * @return array
	 */
	public function get_items() {
		return $this->_data['items'];
	}

	/**
	 * @return int
	 */
	public function get_paged() {
		return max( 1, absint( $this->_data['paged'] ) );
	}

	/**
	 * @return int
	 */
	public function get_limit() {
		return absint( $this->_data['limit'] );
	}

	/**
	 * Pagination
	 *
	 * @param bool $echo
	 *
	 * @return string
	 */
	public function get_nav_numbers( $echo = true, $base_url='' ) {
		if( !$base_url ) {
			$base_url = learn_press_get_current_url();
		}
		if ( ! empty( $this->_data['nav_base'] ) ) {
			if ( is_callable( $this->_data['nav_base'] ) ) {
				$base = call_user_func_array( $this->_data['nav_base'], array( $this->_data['nav_format'] ) );
			} else {
				$base = $this->_data['nav_base'];
			}
		} else {
			$base = trailingslashit( preg_replace( '~\/[0-9]+\/?$~', '', $base_url ) );
		}

		return learn_press_paging_nav(
			array(
				'num_pages' => $this->get_pages(),
				'paged'     => $this->get_paged(),
				'echo'      => $echo,
				'format'    => $this->_data['nav_format'],
				'base'      => $base
			)
		);
	}

	/**
	 * Get range
	 *
	 * @return array
	 */
	public function get_offset() {
		$from = ( $this->get_paged() - 1 ) * $this->get_limit() + 1;
		$to   = $from + $this->get_limit() - 1;
		$to   = min( $to, $this->get_total() );
		if ( $this->get_total() < 1 ) {
			$from = 0;
		}

		return array( $from, $to );
	}

	public function get_offset_text( $format = '', $echo = false ) {
		$offset = $this->get_offset();
		$output = '';

		if ( ! $format ) {
			if ( $this->_data['single'] && $this->_data['plural'] ) {
				$format = __( 'Displaying {{from}} to {{to}} of {{total}} {{item_name}}.', 'learnpress' );
			} else {
				$format = __( 'Displaying {{from}} to {{to}} of {{total}}.', 'learnpress' );
			}
		}

		$output = str_replace(
			array( '{{from}}', '{{to}}', '{{total}}', '{{item_name}}' ),
			array(
				$offset[0],
				$offset[1],
				$this->get_total(),
				$this->get_total() < 2 ? $this->_data['single'] : $this->_data['plural']
			),
			$format
		);

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	public function get_nav( $format = '', $echo = false, $base_url = '' ) {
		$output  = '';
		$offset  = $this->get_offset_text( empty( $format ) ? $this->_data['format'] : $format, false );
		$numbers = $this->get_nav_numbers( false, $base_url );

		if ( $offset && $numbers ) {
			$output = sprintf( '<div class="learn-press-nav-items">%s%s</div>', $offset, $numbers );
		}

		if ( $echo ) {
			echo $output;
		}

		return $output;
	}

	public function offsetExists( $offset ) {
		return array_key_exists( $offset, $this->_data );
	}

	public function offsetGet( $offset ) {
		return array_key_exists( $offset, $this->_data ) ? $this->_data[ $offset ] : false;
	}

	public function offsetSet( $offset, $value ) {
		$this->_data[ $offset ] = $value;
	}

	public function offsetUnset( $offset ) {
		return false;
	}
}