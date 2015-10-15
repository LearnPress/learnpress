<?php
abstract class LP_Abstract_Post_Type{
	/**
	 * Prevent duplicate loading
	 *
	 * @var bool
	 */
	protected static $loaded;

	/**
	 * Constructor
	 */
	function __construct(){
		$extended = get_class($this);

		add_action( 'init', array( $extended, 'register_post_type' ) );
		add_action( 'admin_init', array( $extended, 'add_meta_boxes' ), 0 );


		add_action( 'admin_enqueue_scripts', array( $extended, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $extended, 'admin_styles' ) );

		add_action( 'admin_footer-post.php', array( $extended, 'print_js_template' ) );
		add_action( 'admin_footer-post-new.php', array( $extended, 'print_js_template' ) );
	}

	/**
	 * Those functions should be extended from child class to override
	 *
	 * @return mixed
	 */
	static function register_post_type(){}
	static function add_meta_boxes(){}
	static function admin_params(){}
	static function admin_scripts(){}
	static function admin_styles(){}
}