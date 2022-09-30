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
		return array( new LP_Template_Callback( $template, $args ), 'callback' );
	}

	public function text( $text, $id = '' ) {
		return array( new LP_Template_Callback( $text, $id ), 'text' );
	}

	public function __call( $name, $arguments ) {
		$log = sprintf( 'Template %s::%s doesn\'t exists.', get_class( $this ), $name );
		error_log( $log );

		if ( LP_Debug::is_debug() ) {
			echo sprintf( '<span title="%s" class="learn-press-template-warning"></span>', $log );
		}
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
	 *
	 * Ex: Remove for text: LearnPress::instance()->template( 'course' )->remove( 'learn-press/course-content-summary', array( '<div class="course-detail-info"> <div class="lp-content-area"> <div class="course-info-left">', 'course-info-left-open' ), 10 );
	 */
	public function remove( $tag, $function_to_remove, $priority = 10 ) {
		global $wp_filter;

		if ( is_array( $function_to_remove ) ) {
			if ( empty( $wp_filter[ $tag ] ) ) {
				return;
			}

			$callbacks = $wp_filter[ $tag ]->callbacks;
			if ( ! $callbacks ) {
				return;
			}

			$priorities = array_keys( $callbacks );

			foreach ( $priorities as $priority1 ) {

				if ( $priority !== '*' && $priority !== $priority1 ) {
					continue;
				}

				if ( empty( $callbacks[ $priority1 ] ) ) {
					continue;
				}

				foreach ( $callbacks[ $priority1 ] as $callback ) {

					if ( ! $callback['function'][0] instanceof LP_Template_Callback ) {
						continue;
					}

					if ( $callback['function'][0]->get_args() !== $function_to_remove[1] ) {
						continue;
					}

					remove_action( $tag, $callback['function'], $priority1 );
				}
			}

			return;

		}

		/**
		 * $function_to_remove === '*' will remove all functions hooked into a hook
		 * in all priorities
		 */
		if ( $function_to_remove === '*' ) {
			if ( ! empty( $wp_filter[ $tag ] ) ) {
				unset( $wp_filter[ $tag ] );
			}

			return;
		}

		/**
		 * $priority === '*' will remove all functions hooked into a hook
		 * in
		 */
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
	 * Remove callback function.
	 *
	 * @param [type] $tag
	 * @param [type] $function
	 * @param [type] $priority
	 * @return void
	 *
	 * @author Nhamdv <email@email.com>
	 *
	 * Ex: LearnPress::instance()->template( 'course' )->remove_callback( 'learn-press/course-content-summary', 'single-course/title', 10 );
	 *
	 */
	public static function remove_callback( $tag, $function, $priority ) {
		global $wp_filter;

		if ( empty( $wp_filter[ $tag ] ) ) {
			return;
		}

		$callbacks = $wp_filter[ $tag ]->callbacks;

		if ( ! $callbacks ) {
			return;
		}

		$priorities = array_keys( $callbacks );

		foreach ( $priorities as $priority1 ) {

			if ( $priority !== '*' && $priority !== $priority1 ) {
				continue;
			}

			if ( empty( $callbacks[ $priority1 ] ) ) {
				continue;
			}

			foreach ( $callbacks[ $priority1 ] as $callback ) {

				if ( ! $callback['function'][0] instanceof LP_Template_Callback ) {
					continue;
				}

				if ( $callback['function'][1] !== 'callback' ) {
					continue;
				}

				if ( $callback['function'][0]->get_template() !== $function ) {
					continue;
				}

				remove_action( $tag, $callback['function'], $priority1 );
			}
		}

		return;
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
	 * @param array    $args
	 */
	public function __construct( $template, $args = array() ) {
		$this->template = $template;
		$this->args     = $args;
	}

	/**
	 *
	 */
	public function callback() {
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

	public function text() {
		learn_press_echo_vuejs_write_on_php( $this->template );
	}

	public function get_args() {
		return $this->args;
	}

	public function get_template() {
		return $this->template;
	}
}
