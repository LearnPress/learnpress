<?php

/**
 * Class LP_Abstract_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

abstract class LP_Abstract_Post_Type {
	/**
	 * Prevent duplicate loading
	 *
	 * @var bool
	 */
	protected static $loaded;

	/**
	 * Constructor
	 */
	public function __construct() {
		$extended = get_class( $this );

		if ( is_callable( array( $extended, 'register_post_type' ) ) ) {
			add_action( 'init', array( $extended, 'register_post_type' ) );
		}
		if ( is_callable( array( $extended, 'add_meta_boxes' ) ) ) {
			add_action( 'admin_init', array( $extended, 'add_meta_boxes' ), 0 );
		}
		if ( is_callable( array( $extended, 'admin_scripts' ) ) ) {
			add_action( 'admin_enqueue_scripts', array( $extended, 'admin_scripts' ) );
		}
		if ( is_callable( array( $extended, 'admin_styles' ) ) ) {
			add_action( 'admin_enqueue_scripts', array( $extended, 'admin_styles' ) );
		}
		if ( is_callable( array( $extended, 'print_js_template' ) ) ) {
			add_action( 'admin_footer-post.php', array( $extended, 'print_js_template' ) );
		}
		if ( is_callable( array( $extended, 'print_js_template' ) ) ) {
			add_action( 'admin_footer-post-new.php', array( $extended, 'print_js_template' ) );
		}
	}

	/**
	 * Those functions should be extended from child class to override
	 *
	 * @return mixed
	 */
	public static function register_post_type() {
	}

	public static function add_meta_boxes() {
	}

	public static function admin_params() {
	}

	public static function admin_scripts() {
	}

	public static function admin_styles() {
	}
}