<?php

/**
 * Class LP_Template
 *
 * @since 3.3.0
 */
class LP_Template implements ArrayAccess {
	/**
	 * @var LP_Template
	 */
	protected static $instance = null;

	public $templates = array();

	/**
	 * LP_Template constructor.
	 */
	protected function __construct() {
		$this->templates = apply_filters(
			'learn-press/templates-classes',
			array(
				'general'  => include_once 'templates/class-lp-template-general.php',
				'course'   => include_once 'templates/class-lp-template-course.php',
				'checkout' => include_once 'templates/class-lp-template-checkout.php',
				'profile'  => include_once 'templates/class-lp-template-profile.php',
			)
		);
	}

	public function has_content( $where ) {
		return has_action( $where );
	}

	public function offsetGet( $offset ) {
		return ! empty( $this->templates[ $offset ] ) ? $this->templates[ $offset ] : false;
	}

	public function offsetSet( $offset, $value ) {
		return false;
	}

	public function offsetExists( $offset ) {
		return ! empty( $this->templates[ $offset ] );
	}

	public function offsetUnset( $offset ) {
		return false;
	}

	/**
	 * Instance lp template
	 *
	 * @return LP_Template
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}
