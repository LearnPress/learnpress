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
	 * Type of post
	 *
	 * @var string
	 */
	protected $_post_type = '';

	/**
	 * Metaboxes registered
	 *
	 * @var array
	 */
	protected $_meta_boxes = array();

	/**
	 * @var null
	 */
	protected $_current_meta_box = null;

	/**
	 * Columns display on list table
	 *
	 * @var array
	 */
	protected $_columns = array();

	/**
	 * Sortable columns
	 *
	 * @var array
	 */
	protected $_sortable_columns = array();

	/**
	 * Map default method to a new method
	 *
	 * @var array
	 */
	protected $_map_methods = array();

	/**
	 * @var array
	 */
	protected $_default_metas = array();

	/**
	 * Constructor
	 *
	 * @param string
	 * @param mixed
	 */
	public function __construct( $post_type, $args = '' ) {

		$this->_post_type = $post_type;
		add_action( 'init', array( $this, '_do_register' ) );
		add_action( 'save_post', array( $this, '_do_save' ), 10, 2 );
		add_action( 'before_delete_post', array( $this, '_before_delete_post' ) );

		add_filter( 'manage_edit-' . $this->_post_type . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'manage_' . $this->_post_type . '_posts_columns', array( $this, 'columns_head' ) );
		add_filter( 'manage_' . $this->_post_type . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

		add_filter( 'posts_fields', array( $this, '_posts_fields' ) );
		add_filter( 'posts_join_paged', array( $this, '_posts_join_paged' ) );
		add_filter( 'posts_where_paged', array( $this, '_posts_where_paged' ) );
		add_filter( 'posts_orderby', array( $this, '_posts_orderby' ) );

		add_filter( 'page_row_actions', array( $this, '_post_row_actions' ), 10, 2 );
		add_filter( 'post_row_actions', array( $this, '_post_row_actions' ), 10, 2 );

		add_action( 'load-post.php', array( $this, 'add_meta_boxes' ), 0 );
		add_action( 'load-post-new.php', array( $this, 'add_meta_boxes' ), 0 );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );
		add_action( 'admin_footer-post.php', array( $this, 'print_js_template' ) );
		add_action( 'admin_footer-post-new.php', array( $this, 'print_js_template' ) );
		add_action( 'pre_get_posts', array( $this, 'update_default_meta' ) );

		add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		$args = wp_parse_args(
			$args,
			array(
				'auto_save'    => 'no',
				'default_meta' => false
			)
		);

		if ( $args['auto_save'] == 'no' ) {
			add_action( 'admin_print_scripts', array( $this, 'remove_auto_save_script' ) );
		}

		if ( $args['default_meta'] ) {
			$this->_default_metas = $args['default_meta'];
		}
	}

	public function update_default_meta() {
		global $wp_query, $post;
		if ( !$post ) {
			return;
		}
		if ( empty( $post->post_type ) ) {
			return;
		}
		if ( $post->post_type != $this->_post_type ) {
			return;
		}
		if ( empty( $this->_default_metas ) ) {
			return;
		}
		foreach ( $this->_default_metas as $k => $v ) {
			if ( !metadata_exists( 'post', $post->ID, $k ) ) {
				update_post_meta( $post->ID, $k, $v );
			}
		}
	}

	public function remove_auto_save_script() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), array( $this->_post_type ) ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * This function is invoked along with 'init' action to register
	 * new post type with WP.
	 */
	public function _do_register() {
		if ( $args = $this->register() ) {
			register_post_type( $this->_post_type, $args );
		}
	}

	/**
	 * This function is invoked along with 'save_post' action to save
	 * post data if needed.
	 *
	 * In child-class should use function save() to update data instead
	 * of _do_save() directly. This helper function is a pre-process to
	 * checks some security in basic level or prevent loop 'save_post'
	 * action in our application.
	 *
	 * @param $post_id
	 * @param $post
	 *
	 * @return bool
	 */
	public function _do_save( $post_id, $post = null ) {
		if ( get_post_type( $post_id ) != $this->_post_type ) {
			return false;
		}
		// TODO: check more here
		// prevent loop action
		remove_action( 'save_post', array( $this, '_do_save' ), 10, 2 );
		$func_args = func_get_args();
		$this->_call_method( 'save', $func_args );
		add_action( 'save_post', array( $this, '_do_save' ), 10, 2 );
	}

	public function _do_output_meta_box( $post, $box ) {
		$callback = $this->_meta_boxes[$box['id']][2];
		if ( is_array( $callback ) ) {
			if ( $callback[0] instanceof LP_Abstract_Post_Type ) {
				if ( $callback[1] != __FUNCTION__ ) {
					call_user_func_array( $callback, array( $post, $box ) );
				}
			} else {
				call_user_func_array( $callback, array( $post, $box ) );
			}
		} else {
			if ( is_callable( array( $this, $callback ) ) ) {
				call_user_func_array( array( $this, $callback ), array( $post, $box ) );
			} else {
				call_user_func_array( $callback, array( $post, $box ) );
			}
		}
	}

	public function _before_delete_post( $post_id ) {
		// TODO:
		if ( !$this->_check_post() ) {
			return;
		}
		$func_args = func_get_args();
		return $this->_call_method( 'before_delete', $func_args );
	}

	public function _posts_fields( $fields ) {
		if ( !$this->_check_post() ) {
			return $fields;
		}
		return $this->posts_fields( $fields );
	}

	public function _posts_join_paged( $join ) {
		if ( !$this->_check_post() ) {
			return $join;
		}
		return $this->posts_join_paged( $join );
	}

	public function _posts_where_paged( $where ) {
		if ( !$this->_check_post() ) {
			return $where;
		}
		return $this->posts_where_paged( $where );
	}

	public function _posts_orderby( $orderby ) {
		if ( !$this->_check_post() ) {
			return $orderby;
		}
		return $this->posts_orderby( $orderby );
	}

	public function _check_post() {
		global $pagenow, $post_type;
		if ( !is_admin() || ( $this->_post_type != $post_type ) ) {
			return false;
		}
		return true;
	}

	public function add_meta_box( $id, $title, $callback = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		$this->_meta_boxes[$id] = func_get_args();
		return $this;
	}

	public function register() {
		return false;
	}

	public function add_meta_boxes() {
		do_action( 'learn_press_add_meta_boxes', $this->_post_type, $this );
		do_action( "learn_press_{$this->_post_type}_add_meta_boxes", $this );
		if ( !$this->_meta_boxes ) {
			return;
		}

		foreach ( $this->_meta_boxes as $k => $meta_box ) {
			$size = sizeof( $meta_box );
			if ( ( $size == 2 ) || ( $size == 3 && !$meta_box[2] ) ) {
				$func        = 'output_' . preg_replace( '/[-]+/', '_', $meta_box[0] );
				$meta_box[2] = array( $this, $func );
			}
			array_splice( $meta_box, 3, 0, array( $this->_post_type ) );
			$this->_meta_boxes[$k] = $meta_box;

			$meta_box[2] = array( $this, '_do_output_meta_box' );
			call_user_func_array( 'add_meta_box', $meta_box );
		}
	}

	public function before_delete( $post_id ) {

	}

	/**
	 *
	 */
	public function save() {

	}

	/**
	 * @param string $fields
	 *
	 * @return mixed
	 */
	public function posts_fields( $fields ) {
		return $fields;
	}

	public function posts_join_paged( $join ) {
		return $join;
	}

	public function posts_where_paged( $where ) {
		return $where;
	}

	public function posts_orderby( $orderby ) {
		return $orderby;
	}

	/**
	 * Get sortable columns for list table
	 *
	 * @return mixed
	 */
	public function sortable_columns( $columns ) {
		return $columns;
	}

	public function columns_head( $columns ) {
		return $columns;
	}

	public function columns_content( $column, $post_id = 0 ) {
		return;
		$callback = array( $this, "column_{$column}" );
		if ( is_callable( $callback ) ) {
			$func_args = func_get_args();
			call_user_func_array( $callback, $func_args );
		}
	}

	public function _post_row_actions( $actions, $post ) {
		if ( !$this->_check_post() ) {
			return $actions;
		}
		$func_args = func_get_args();
		return $this->_call_method( 'row_actions', $func_args );
	}

	public function row_actions( $actions, $post ) {
		return $actions;
	}

	/**
	 * Those functions should be extended from child class to override
	 *
	 * @return mixed
	 */

	public function register_post_type() {
		return $this;
	}

	public function admin_params() {
		return $this;
	}

	public function admin_scripts() {
		return $this;
	}

	public function admin_styles() {
		return $this;
	}

	public function print_js_template() {
		return $this;
	}

	public function add_map_method( $origin, $replace, $single = false ) {
		if ( $single ) {
			$this->_map_methods[$origin] = $replace;
		} else {
			if ( empty( $this->_map_methods[$origin] ) ) {
				$this->_map_methods[$origin] = array( $replace );
			} else {
				$this->_map_methods[$origin][] = $replace;
			}
		}
		return $this;
	}

	private function _get_map_method( $origin ) {
		if ( !empty( $this->_map_methods[$origin] ) ) {
			if ( is_array( $this->_map_methods[$origin] ) ) {
				$callback = array();
				foreach ( $this->_map_methods[$origin] as $method ) {
					$callback[] = array( $this, $method );
				}
			} else {
				$callback = array( $this, $this->_map_methods[$origin] );
			}
		} else {
			$callback = array( $this, $origin );
		}
		return $callback;
	}

	private function _call_method( $name, $args = false ) {
		$callbacks = $this->_get_map_method( $name );
		if ( is_array( $callbacks[0] ) ) {
			$return = array();
			foreach ( $callbacks as $callback ) {
				$_return = call_user_func_array( $callback, $args );
				$return  = array_merge( $return, (array) $_return );
			}
		} else {
			$return = call_user_func_array( $callbacks, $args );
		}
		return $return;
	}

	public function updated_messages( $messages ) {
		$post             = get_post();
		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $this->_post_type );
		if ( $this->_post_type !== $post_type ) {
			return $messages;
		}
		$messages[$this->_post_type] = array(
			0  => '', // Unused. Messages start at index 1.
			1  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'updated.', 'learnpress' ) ),
			2  => __( 'Custom field updated.', 'learnpress' ),
			3  => __( 'Custom field deleted.', 'learnpress' ),
			4  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'updated.', 'learnpress' ) ),
			/* translators: %s: date and time of the revision */
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'Lesson restored to revision from %s', 'learnpress' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'published.', 'learnpress' ) ),
			7  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'saved.', 'learnpress' ) ),
			8  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'submitted.', 'learnpress' ) ),
			9  => sprintf(
				sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'scheduled for: <strong>%1$s</strong>.', 'learnpress' ) ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'learnpress' ), strtotime( $post->post_date ) )
			),
			10 => sprintf( '% %s', $post_type_object->labels->singular_name, __( 'draft updated.', 'learnpress' ) )
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), sprintf( '%s %s', __( 'View', 'learnpress' ), $post_type_object->labels->singular_name ) );
			switch ( $this->_post_type ) {
				case LP_LESSON_CPT:
				case LP_QUIZ_CPT:
					$view_link = learn_press_get_item_course_id( $post->ID, $post->post_type ) ? $view_link : '';
					break;
				case LP_ORDER_CPT:
					$order     = learn_press_get_order( $post->ID );
					$view_link = $order->get_view_order_url();
					$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $view_link ), sprintf( '%s %s', __( 'View', 'learnpress' ), $post_type_object->labels->singular_name ) );
					break;
				case LP_QUESTION_CPT:
					$view_link = '';
					break;
			}
			$messages[$this->_post_type][1] .= $view_link;
			$messages[$this->_post_type][6] .= $view_link;
			$messages[$this->_post_type][9] .= $view_link;

			$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
			$preview_link      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), sprintf( '%s %s', __( 'Preview', 'learnpress' ), $post_type_object->labels->singular_name ) );
			$messages[$this->_post_type][8] .= $preview_link;
			$messages[$this->_post_type][10] .= $preview_link;
		}

		return $messages;
	}
}