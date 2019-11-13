<?php

/**
 * Class LP_Template
 *
 * @since 3.3.0
 */
class LP_Abstract_Template {

	/**
	 * @var LP_Template
	 */
	protected static $instance = null;

	/**
	 * LP_Template constructor.
	 */
	public function __construct() {
	}

	public function clearfix() {
		learn_press_get_template( 'global/clearfix' );
	}

	public function callback( $template, $args = array() ) {
		return array( new LP_Template_Callback( $template, $args ), 'display' );
	}

	/**
	 * Return is callable method of self class.
	 *
	 * @since 3.3.0
	 *
	 * @param string $callback
	 *
	 * @return array
	 */
	public function func( $callback ) {
		return array( $this, $callback );
	}

	/**
	 * Add callable method of self class to a hook of template.
	 *
	 * @param string $name
	 * @param string $callback
	 * @param int    $priority
	 * @param int    $number_args
	 */
	public function hook( $name, $callback, $priority = 10, $number_args = 1 ) {
		add_action( $name, $this->func( $callback ), $priority, $number_args );
	}

	/**
	 * Remove hooked callable method.
	 *
	 * @param string $tag
	 * @param string $function_to_remove - '*' will remove all methods.
	 * @param int    $priority
	 */
	public function remove( $tag, $function_to_remove, $priority = 10 ) {
		global $wp_filter;

		if ( $function_to_remove === '*' ) {
			if ( ! empty( $wp_filter[ $tag ] ) ) {
				unset( $wp_filter[ $tag ] );
			}

			return;
		}

		if ( $priority === '*' ) {

			if ( ! empty( $wp_filter[ $tag ]->callbacks ) ) {
				$priorities = array_keys( $wp_filter[ $tag ]->callbacks );

				foreach ( $priorities as $priority ) {
					remove_action( $tag, $this->func( $function_to_remove ), $priority );
				}
			}

			return;
		}
		remove_action( $tag, $this->func( $function_to_remove ), $priority );
	}

	/**
	 * @return LP_Template
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


class LP_Template_Callback {
	/**
	 * @var string
	 */
	protected $template = '';

	/**
	 * @var array
	 */
	protected $args = array();

	/**
	 * LP_Template_Caller constructor.
	 *
	 * @param       $template
	 * @param array $args
	 */
	public function __construct( $template, $args = array() ) {
		$this->template = $template;
		$this->args     = $args;
	}

	/**
	 *
	 */
	public function display() {
		$template_args = array();

		if ( $this->args ) {
			foreach ( $this->args as $k => $v ) {
				if ( is_numeric( $k ) && is_string( $v ) ) {
					$template_args[ $v ] = func_get_arg( $k );
				}
			}
		}
		learn_press_get_template( $this->template, $template_args );
	}
}