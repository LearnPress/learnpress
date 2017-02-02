<?php

/**
 * Class LP_Widget
 */
class LP_Widget extends WP_Widget {

	/**
	 * @var array
	 */
	private static $_widgets = array();

	/**
	 * @var bool
	 */
	private static $_has_widget = false;

	private static $_has_registered = false;

	private $_id_prefix = 'lp-widget-';

	private $_name_prefix = 'LearnPress - ';

	/**
	 * LP_Widget constructor.
	 */
	public function __construct() {
		$func_args = func_get_args();
		$args      = array( 'id_base' => '', 'name' => '', 'widget_options' => '', 'control_options' => '' );
		$pos       = 0;
		foreach ( $args as $name => $value ) {
			$args[$name] = array_key_exists( $pos, $func_args ) ? $func_args[$pos] : '';
		}
		$args = self::parse_widget_args(
			$args,
			strtolower( str_replace( array( 'LP_Widget_', '_' ), array( '', '-' ), get_class( $this ) ) )
		);
		list( $id_base, $name, $widget_options, $control_options ) = $args;
		parent::__construct( $this->_id_prefix . $id_base, $this->_name_prefix . $name, $widget_options, $control_options );
	}

	public function widget( $args, $instance ) {

	}

	/**
	 *
	 */
	public static function register( $type, $args = '' ) {
		self::$_widgets[$type] = $args;
		if ( !self::$_has_registered ) {
			add_action( 'widgets_init', array( __CLASS__, 'do_register' ) );
			self::$_has_registered = true;
		}
	}

	public static function do_register() {
		if ( !self::$_widgets ) {
			return;
		}
		foreach ( self::$_widgets as $type => $args ) {
			$widget_file = LP_PLUGIN_PATH . "/inc/widgets/{$type}/{$type}.php";
			if ( !file_exists( $widget_file ) ) {
				continue;
			}
			include_once $widget_file;
			$widget_class = self::get_widget_class( $type );
			if ( class_exists( $widget_class ) ) {
				register_widget( $widget_class );
			}
		}
	}

	private static function get_widget_class( $slug ) {
		return 'LP_Widget_' . preg_replace( '~\s+~', '_', ucwords( str_replace( '-', ' ', $slug ) ) );
	}

	private static function parse_widget_args( $args, $type ) {
		$id_base         = !empty( $args['id_base'] ) ? $args['id_base'] : 'lp-widget-' . $type;
		$name            = !empty( $args['name'] ) ? $args['name'] : ucwords( str_replace( '-', ' ', $type ) );
		$widget_options  = !empty( $args['widget_options'] ) ? $args['widget_options'] : array();
		$control_options = !empty( $args['control_options'] ) ? $args['control_options'] : array();
		return array( $id_base, $name, $widget_options, $control_options );
	}
}