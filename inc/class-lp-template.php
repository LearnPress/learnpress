<?php

/**
 * Class LP_Template
 *
 * @since 3.3.0
 */
class LP_Template {
	/**
	 * @var LP_Template
	 */
	protected static $instance = null;

	public $templates = array();

	/**
	 * LP_Template constructor.
	 */
	protected function __construct() {
		include_once 'templates/class-lp-template-general.php';
		include_once 'templates/class-lp-template-course.php';
		include_once 'templates/class-lp-template-checkout.php';
		include_once 'templates/class-lp-template-profile.php';

		$this->templates = apply_filters(
			'learn-press/templates-classes',
			array(
				'general'  => new LP_Template_General(),
				'course'   => new LP_Template_Course(),
				'checkout' => new LP_Template_Checkout(),
				'profile'  => new LP_Template_Profile(),
			)
		);
	}

	public function get_templates() {
		return $this->templates;
	}

	public function get_template( $name ) {
		return $this->templates[ $name ] ?? '';
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
