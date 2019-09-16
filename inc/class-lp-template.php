<?php

/**
 * Class LP_Template
 *
 * @since 4.x.x
 */
class LP_Template {

	/**
	 * @var LP_Template
	 */
	protected static $instance = null;

	/**
	 * LP_Template constructor.
	 */
	protected function __construct() {
	}

	public function course_button() {
		echo "[COURSE BUTTON]";
	}

	public function course_title() {
		echo "[COURSE TITLE]";
	}

	public function cb( $callback ) {
		return array( $this, $callback );
	}

	public function hook( $name, $callback, $priority = 10, $number_args = 1 ) {
		add_action( $name, $this->cb( $callback ), $priority, $number_args );
	}

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
					remove_action( $tag, $this->cb( $function_to_remove ), $priority );
				}
			}

			return;
		}
		remove_action( $tag, $this->cb( $function_to_remove ), $priority );
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