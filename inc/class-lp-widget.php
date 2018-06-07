<?php
/**
 * Base class of LearnPress widgets and helper function.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Shortcodes
 * @version  3.0.0
 * @extends  LP_Widget
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Widget' ) ) {
	/**
	 * Class LP_Widget
	 *
	 * @extend WP_Widget
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

		/**
		 * @var bool
		 */
		private static $_has_registered = false;

		/**
		 * Widget prefix
		 *
		 * @var string
		 */
		private $_id_prefix = 'lp-widget-';

		/**
		 * Widget name prefix
		 *
		 * @var string
		 */
		private $_name_prefix = 'LearnPress - ';

		/**
		 * @var string
		 */
		protected $_option_prefix = '';

		/**
		 * @var array
		 */
		private $map_fields = array();

		/**
		 * Widget file name
		 *
		 * @var string
		 */
		public $file = '';

		/**
		 * Widget template path
		 *
		 * @var string
		 */
		public $template_path = '';

		/**
		 * Widget arguments
		 *
		 * @var array
		 */
		public $args = array();

		/**
		 * Widget options
		 *
		 * @var array
		 */
		public $instance = array();

		/**
		 *
		 * @var array
		 */
		private $defaults = array();

		/**
		 * @var bool
		 */
		public $options = array();

		/**
		 * LP_Widget constructor.
		 *
		 * @param array
		 */
		public function __construct( $args = array() ) {
			$defaults = array( 'id_base' => '', 'name' => '', 'widget_options' => '', 'control_options' => '' );
			$args     = wp_parse_args( $args, $defaults );
			$args     = $this->_parse_widget_args(
				$args,
				strtolower( str_replace( array( 'LP_Widget_', '_' ), array( '', '-' ), get_class( $this ) ) )
			);
			list( $id_base, $name, $widget_options, $control_options ) = $args;

			// filter widget option prefix
			$this->_option_prefix = apply_filters( 'learn-press/widget/option_prefix', '' );
			// set prefix to widget option id
			$this->options = array_map( array( $this, 'set_option_id' ), $this->options );

			if ( is_array( $this->options ) ) {
				// set default value for options
				foreach ( $this->options as $id => $field ) {
					if ( is_array( $field ) && array_key_exists( 'std', $field ) ) {
						$this->defaults[ $id ] = $field['std'];
					} else {
						$this->defaults[ $id ] = null;
					}
				}
			}
			parent::__construct( $id_base, $this->_name_prefix . $name, $widget_options, $control_options );
		}

		/**
		 * Set prefix to widget option id
		 *
		 * @param $options
		 *
		 * @return mixed
		 */
		public function set_option_id( $options ) {
			$options['id'] = $this->_option_prefix . $options['id'];

			return $options;
		}

		public function before_checkbox_html( $begin, $field, $meta ) {
			$begin .= sprintf(
				'<input type="hidden" name="%s" value="0">',
				$field['field_name']
			);

			return $begin;
		}

		public function field_data( $data, $object_id, $meta_key, $single ) {
			global $post;
			if ( $post->post_type == 'lp-post-widget' ) {
				$key  = ! empty( $this->map_fields[ $meta_key ] ) ? $this->map_fields[ $meta_key ] : $meta_key;
				$data = array_key_exists( $key, $this->instance ) ? $this->instance[ $key ] : '';
			}

			return $data;
		}

		public function update( $new_instance = array(), $old_instance = array() ) {
			$new_instance = $this->sanitize_instance( $new_instance );

			return $new_instance;
		}

		/**
		 * Display widget content
		 *
		 * @param array $args
		 * @param array $instance
		 */
		public function widget( $args, $instance ) {
			$this->args     = $args;
			$this->instance = $this->sanitize_instance( $instance );

			if ( ! apply_filters( 'learn-press/widget/display', true, $this ) ) {
				return;
			}

			if ( ! apply_filters( 'learn-press/widget/display-' . $this->id_base, true, $this ) ) {
				return;
			}

			$this->before_widget();
			$this->show();
			$this->after_widget();
		}

		public function before_widget() {
			echo $this->args['before_widget'];
			if ( ! empty( $this->instance['title'] ) ) {
				echo $this->args['before_title'];
				echo $this->instance['title'];
				echo $this->args['after_title'];
			}
		}

		public function after_widget() {
			echo $this->args['after_widget'];
		}

		/**
		 * Show widget.
		 */
		public function show() {
			printf( __( 'Function %s should be overwritten in child class', 'learnpress' ), __FUNCTION__ );
		}

		/**
		 * Display widget settings with meta-box fields
		 *
		 * @param mixed $instance
		 *
		 * @return mixed
		 */
		public function form( $instance ) {
			$this->instance = $this->sanitize_instance( $instance );

			if ( ! $this->options ) {
				return false;
			}

			global $post;

			add_filter( 'get_post_metadata', array( $this, 'field_data' ), 10, 4 );
			add_filter( 'rwmb_checkbox_begin_html', array( $this, 'before_checkbox_html' ), 10, 3 );
			//

			$post = (object) array( 'ID' => 1, 'post_type' => 'lp-post-widget' );

			setup_postdata( $post );

			if ( ! class_exists( 'RW_Meta_Box' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/libraries/meta-box/meta-box.php';
			}

			$this->options = RW_Meta_Box::normalize_fields( $this->options );

			$this->options = $this->normalize_options();

			foreach ( $this->options as $key => $field ) {
				$origin_id           = $field['id'];
				$field['field_name'] = $this->get_field_name( $field['id'] );
				$field['id']         = $this->get_field_id( $field['id'] );

				# If there is old value, bind it to field as init value
				if ( $this->instance[ $key ] ) {
					$field['saved'] = $this->instance[ $key ];
				}
				//$field['value']      = md5( $field['std'] );
				$this->map_fields[ $field['id'] ] = $origin_id;
				$this->_show_field( $field );
			}
			wp_reset_postdata();
			remove_filter( 'get_post_metadata', array( $this, 'field_data' ) );
			remove_filter( 'rwmb_checkbox_begin_html', array( $this, 'before_checkbox_html' ), 10 );

			return true;
		}

		/**
		 * Find RMMB field and display it
		 *
		 * @param $field
		 */
		private function _show_field( $field ) {
			$callable = array( 'RW_Meta_Box', 'get_class_name' );
			if ( ! is_callable( $callable ) ) {
				$callable = array( 'RWMB_Field', 'get_class_name' );
			}
			if ( is_callable( $callable ) ) {
				$field_class = call_user_func( $callable, $field );
			} else {
				$field_class = false;
			}
			if ( $field_class ) {
				call_user_func( array( $field_class, 'show' ), $field, true );
			}
		}

		/**
		 * @return array|bool
		 */
		public function normalize_options() {
			return ! is_array( $this->options ) ? array() : $this->options;
		}

		/**
		 * Get slug of this widget from file
		 *
		 * @return mixed
		 */
		public function get_slug() {
			$class = get_class( $this );

			return str_replace( '_', '-', strtolower( str_replace( 'LP_Widget_', '', $class ) ) );
		}

		/**
		 * Register new widget
		 *
		 * @param string|array
		 * @param mixed
		 */
		public static function register( $type, $args = '' ) {
			if ( is_array( $type ) ) {
				foreach ( $type as $k => $t ) {
					if ( is_array( $t ) ) {
						self::register( $k, $t );
					} else {
						self::register( $t );
					}
				}
			} else {
				self::$_widgets[ $type ] = $args;
				if ( ! self::$_has_registered ) {
					add_action( 'widgets_init', array( __CLASS__, 'do_register' ) );
					self::$_has_registered = true;
				}
			}
		}

		/**
		 * Tell WP register our widgets
		 */
		public static function do_register() {

			if ( ! self::$_widgets ) {
				return;
			}
			global $wp_widget_factory;


			foreach ( self::$_widgets as $type => $args ) {
				$widget_file = LP_PLUGIN_PATH . "inc/widgets/{$type}/{$type}.php";

				if ( ! file_exists( $widget_file ) ) {
					continue;
				}

				include_once $widget_file;
				$widget_class = self::get_widget_class( $type );

				if ( class_exists( $widget_class ) ) {
					register_widget( $widget_class );
					if ( ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ) {
						$wp_widget_factory->widgets[ $widget_class ]->file = $widget_file;
					}
				}
			}

			return;
		}

		/**
		 * Display a template of a widget.
		 *
		 * @since 3.0.0
		 *
		 * @param string $slug
		 * @param string $template_name
		 *
		 * @return string
		 */
		public function get_locate_template( $slug, $template_name = '' ) {
			// get widget template
			$template = "widgets/{$slug}/" . ( $template_name ? $template_name : 'default.php' );

			return learn_press_locate_template( $template );
		}

		/**
		 * Get class name of widget without LP_Widget prefix
		 *
		 * @param $slug
		 *
		 * @return string
		 */
		private static function get_widget_class( $slug ) {
			return 'LP_Widget_' . preg_replace( '~\s+~', '_', ucwords( str_replace( '-', ' ', $slug ) ) );
		}

		/**
		 * Parse some default options
		 *
		 * @param $args
		 * @param $type
		 *
		 * @return array
		 */
		private function _parse_widget_args( $args, $type ) {
			$id_base         = ! empty( $args['id_base'] ) ? $args['id_base'] : $this->_id_prefix . $type;
			$name            = ! empty( $args['name'] ) ? $args['name'] : ucwords( str_replace( '-', ' ', $type ) );
			$widget_options  = ! empty( $args['widget_options'] ) ? $args['widget_options'] : array();
			$control_options = ! empty( $args['control_options'] ) ? $args['control_options'] : array();

			return array( $id_base, $name, $widget_options, $control_options );
		}

		/**
		 * @param string $instance
		 * @param string $more
		 *
		 * @return array|mixed
		 */
		public function get_class( $instance = '', $more = '' ) {
			$classes = array( 'lp-widget' );
			if ( is_array( $instance ) && ! empty( $instance['css_class'] ) ) {
				$classes[] = $instance['css_class'];
			}
			$classes = LP_Helper::merge_class( $classes, $more );

			if ( $classes ) {
				echo ' class="' . join( ' ', $classes ) . '"';
			}

			return $classes;
		}

		private function sanitize_instance( $instance ) {
			return wp_parse_args( $instance, $this->defaults );
		}
	}
}

// Register core widgets
LP_Widget::register( array(
	'featured-courses',
	'popular-courses',
	'recent-courses',
	'course-progress',
	'course-info'
) );