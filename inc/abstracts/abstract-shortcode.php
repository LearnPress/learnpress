<?php

/**
 * Class LP_Abstract_Shortcode
 *
 * Abstract class for shortcodes
 *
 * @since 3.x.x
 */
abstract class LP_Abstract_Shortcode {
	/**
	 * Shortcode attributes.
	 *
	 * @var array|null
	 */
	protected $_atts = null;

	/**
	 * @var string
	 */
	protected $_name = '';

	/**
	 * LP_Abstract_Shortcode constructor.
	 *
	 * @param mixed $atts
	 */
	public function __construct( $atts = '' ) {
		$this->_atts = (array) $atts;
	}

	/**
	 * Return content of the shortcode.
	 *
	 * @return mixed
	 */
	abstract function output();

	/**
	 * Get shortcode attributes.
	 *
	 * @return mixed
	 */
	public function get_atts() {
		return apply_filters( 'learn-press/shortcode-' . $this->get_name() . '-atts', $this->_atts );
	}

	/**
	 * Get default name of shortcode (without prefix learn_press_) if it is not set.
	 *
	 * @return mixed|string
	 */
	public function get_name() {
		if ( ! $this->_name ) {
			if ( preg_match( '~^lp_(.*)_shortcode$~i', get_class( $this ), $m ) ) {
				$this->_name = preg_replace( '~_~', '-', strtolower( $m[1] ) );
			}
		}

		return $this->_name;
	}

	/**
	 * Convert to string
	 *
	 * @return mixed
	 */
	public function __toString() {
		return $this->output();
	}
}