<?php
/**
 * Define base class of LearnPress widgets and helper functions
 */

if ( !class_exists( 'LP_Widget' ) ) {
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

			if ( $this->options ) {
				foreach ( $this->options as $id => $field ) {
					if ( is_array( $field ) && array_key_exists( 'std', $field ) ) {
						$this->defaults[$id] = $field['std'];
					} else {
						$this->defaults[$id] = null;
					}
				}
			}
			parent::__construct( $id_base, $this->_name_prefix . $name, $widget_options, $control_options );
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
				$key  = !empty( $this->map_fields[$meta_key] ) ? $this->map_fields[$meta_key] : $meta_key;
				$data = array_key_exists( $key, $this->instance ) ? $this->instance[$key] : '';
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

			if ( !apply_filters( 'learn_press_widget_display_content', true, $this ) ) {
				return;
			}

			if ( !apply_filters( 'learn_press_widget_display_content-' . $this->id_base, true, $this ) ) {
				return;
			}
			$this->before_widget();
			$this->show();
			$this->after_widget();
		}

		public function before_widget() {
			echo $this->args['before_widget'];
			if ( !empty( $this->instance['title'] ) ) {
				echo $this->args['before_title'];
				echo $this->instance['title'];
				echo $this->args['after_title'];
			}
		}

		public function after_widget() {
			echo $this->args['after_widget'];
		}

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
			if ( !$this->options ) {
				return;
			}
//			var_dump($this->options);
			global $post;
			add_filter( 'get_post_metadata', array( $this, 'field_data' ), 10, 4 );
			add_filter( 'rwmb_checkbox_begin_html', array( $this, 'before_checkbox_html' ), 10, 3 );
			//

			$post = (object) array( 'ID' => 1, 'post_type' => 'lp-post-widget' );

			setup_postdata( $post );
			if ( !class_exists( 'RW_Meta_Box' ) ) {
				require_once LP_PLUGIN_PATH . 'inc/libraries/meta-box/meta-box.php';
			}

//            var_dump($this->instance);
			$this->options = RW_Meta_Box::normalize_fields( $this->options );

			$this->options = $this->normalize_options();

			foreach ( $this->options as $key => $field ) {
				$origin_id           = $field['id'];
				$field['field_name'] = $this->get_field_name( $field['id'] );
				$field['id']         = $this->get_field_id( $field['id'] );

                # If there is old value, bind it to field as init value
				if ($this->instance[$key]) {$field['std'] = $this->instance[$key];}

				//$field['value']      = md5( $field['std'] );
				$this->map_fields[$field['id']] = $origin_id;
				$this->_show_field( $field );
			}
			wp_reset_postdata();
			remove_filter( 'get_post_metadata', array( $this, 'field_data' ) );
			remove_filter( 'rwmb_checkbox_begin_html', array( $this, 'before_checkbox_html' ), 10, 3 );
		}

		/**
		 * Find RMMB field and display it
		 *
		 * @param $field
		 */
		private function _show_field( $field ) {
			$callable = array( 'RW_Meta_Box', 'get_class_name' );

			if ( !is_callable( $callable ) ) {
				$callable = array( 'RWMB_Field', 'get_class_name' );
			}

			if ( is_callable( $callable ) ) {
				$field_class = call_user_func( $callable, $field );
			} else {
				$field_class = false;
			}

			if ( $field_class ) {
				call_user_func( array( $field_class, 'show' ), $field, false );
			}
		}

		/**
		 * @return array|bool
		 */
		public function normalize_options() {
			return !is_array( $this->options ) ? array() : $this->options;
		}

		/**
		 * Find template and display it
		 */
		public function get_template() {
			learn_press_get_widget_template( $this->get_slug(), 'default.php' );
		}

		/**
		 * Get path to template files inside widget
		 *
		 * @return string
		 */
		public function get_template_path() {
			if ( file_exists( $this->file ) ) {
				$this->template_path = dirname( $this->file ) . '/tmpl/';
			}

			return $this->template_path;
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
				self::$_widgets[$type] = $args;
				if ( !self::$_has_registered ) {
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
                    //$widget       = new $widget_class();
                    //$widget->file = $widget_file;
                    register_widget( $widget_class );
                    if ( ! empty( $wp_widget_factory->widgets[ $widget_class ] ) ) {
                        $wp_widget_factory->widgets[ $widget_class ]->file = $widget_file;
                    }
                }
            }

            return;
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
			$id_base         = !empty( $args['id_base'] ) ? $args['id_base'] : $this->_id_prefix . $type;
			$name            = !empty( $args['name'] ) ? $args['name'] : ucwords( str_replace( '-', ' ', $type ) );
			$widget_options  = !empty( $args['widget_options'] ) ? $args['widget_options'] : array();
			$control_options = !empty( $args['control_options'] ) ? $args['control_options'] : array();

			return array( $id_base, $name, $widget_options, $control_options );
		}

		private function sanitize_instance( $instance ) {
			return wp_parse_args( $instance, $this->defaults );
		}
	}
}

/**
 * Get template path of a widget
 *
 * @param $slug
 *
 * @return string
 */
function learn_press_get_widget_template_path( $slug ) {
	return LP_WIDGET_PATH . "/{$slug}/tmpl/";
}

function learn_press_get_widget_theme_template_path( $slug ) {

}

/**
 * Display a template of a widget
 *
 * @param       $slug
 * @param       $template_name
 * @param array $args
 */
function learn_press_get_widget_template( $slug, $template_name = 'default.php', $args = array() ) {
	//$template_path = learn_press_get_widget_template_path( $slug );
	$template = "widgets/{$slug}/" . ( $template_name ? $template_name : 'default.php' );
	die();
	learn_press_get_template( $template );// $template_name ? $template_name : 'default.php', $args, learn_press_template_path() . "/widgets/{$slug}", LP_PLUGIN_PATH . "/widgets/{$slug}" );
}

/**
 * Display a template of a widget
 *
 * @param string $slug
 * @param string $template_name
 *
 * @return string
 */
function learn_press_locate_widget_template( $slug, $template_name = 'default.php' ) {
	//$template_path = learn_press_get_widget_template_path( $slug );
	$template = "widgets/{$slug}/" . ( $template_name ? $template_name : 'default.php' );
	return learn_press_locate_template( $template );
}