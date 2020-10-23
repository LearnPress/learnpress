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
	 * @var array
	 */
	protected $_remove_features = array();

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
		add_action( 'deleted_post', array( $this, '_deleted_post' ) );

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
		add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ) );

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

		add_action( 'init', array( $this, 'maybe_remove_features' ), 1000 );
	}

	public function get_post_type() {
		$post_type = get_post_type();
		if ( ! $post_type ) {
			$post_type = LP_Request::get_string( 'post_type' );
		}

		return $post_type;
	}

	public function admin_footer_scripts() {

		global $pagenow;

		if ( $this->get_post_type() !== $this->_post_type ) {
			return;
		}

		$user = learn_press_get_current_user();

		if ( ! $user->is_admin() ) {
			return;
		}

		if ( $pagenow === 'post.php' ) {
		    // TODO: move script to file js - tungnx
			?>
            <script>
                jQuery(function ($) {
                    var isAssigned = '<?php echo esc_js( $this->is_assigned() );?>',
                        $postStatus = $('#post_status'),
                        $message = $('<p class="learn-press-notice-assigned-item"></p>').html(isAssigned),
                        currentStatus = $postStatus.val();

                    (currentStatus === 'publish') && isAssigned && $postStatus.on('change', function () {
                        if (this.value !== 'publish') {
                            $message.insertBefore($('#post-status-select'));
                        } else {
                            $message.remove();
                        }
                    });

                })
            </script>
			<?php
		}
	}

	public function is_assigned() {
		global $wpdb;
		$post_type = $this->get_post_type();
		if ( learn_press_is_support_course_item_type( $post_type ) ) {
			$query = $wpdb->prepare( "
                SELECT s.section_course_id
                FROM {$wpdb->learnpress_section_items} si
                INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
                INNER JOIN {$wpdb->posts} p ON p.ID = si.item_id
                WHERE p.ID = %d
	        ", get_the_ID() );

			if ( $course_id = $wpdb->get_var( $query ) ) {
				return __( 'This item has already assigned to course. It will be removed from course if it is not published.', 'learnpress' );
			}
		} elseif ( LP_QUESTION_CPT === $post_type ) {
			$query = $wpdb->prepare( "
		        SELECT p.ID 
                FROM {$wpdb->posts} p 
                INNER JOIN {$wpdb->learnpress_quiz_questions} qq ON p.ID = qq.quiz_id
                WHERE qq.question_id = %d
		    ", get_the_ID() );

			if ( $quiz_id = $wpdb->get_var( $query ) ) {
				return __( 'This question has already assigned to quiz. It will be removed from quiz if it is not published.', 'learnpress' );
			}
		}

		return 0;
	}

	public function maybe_remove_features() {
		if ( ! $this->_remove_features ) {
			return;
		}

		foreach ( $this->_remove_features as $feature ) {
			remove_post_type_support( $this->_post_type, $feature );
		}
	}

	public function remove_feature( $feature ) {
		if ( is_array( $feature ) ) {
			foreach ( $feature as $fea ) {
				$this->remove_feature( $fea );
			}
		} else {
			$this->_remove_features[] = $feature;
		}
	}

	public function update_default_meta() {
		global $wp_query, $post;

		if ( ! $post ) {
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
			if ( ! metadata_exists( 'post', $post->ID, $k ) ) {
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
		// Maybe remove
		$this->maybe_remove_assigned( $post_id );

		if ( get_post_type( $post_id ) != $this->_post_type ) {
			return false;
		}
		// TODO: check more here
		// prevent loop action
		remove_action( 'save_post', array( $this, '_do_save' ), 10 );
		$func_args = func_get_args();
		$this->_call_method( 'save', $func_args );
		$this->_flush_cache();
		add_action( 'save_post', array( $this, '_do_save' ), 10, 2 );

		return $post_id;
	}

	public function maybe_remove_assigned( $post_id ) {
		global $wpdb;

		$post        = get_post( $post_id );
		$post_type   = $this->get_post_type();
		$post_status = $post->post_status;

		// If we are updating question
		if ( LP_QUESTION_CPT === $post_type ) {

			// If question is not published then delete it from quizzes
			if ( $post_status !== 'publish' ) {
				$query = $wpdb->prepare( "
                    DELETE FROM {$wpdb->learnpress_quiz_questions}
                    WHERE question_id = %d
                ", $post_id );
				$wpdb->query( $query );
			}

		} // Else
        elseif ( learn_press_is_support_course_item_type( $post_type ) ) {

			// If item is not published then delete it from courses
			if ( $post_status !== 'publish' ) {
				$query = $wpdb->prepare( "
                    DELETE FROM {$wpdb->learnpress_section_items}
                    WHERE item_id = %d
                ", $post_id );
				$wpdb->query( $query );
			}
		}
	}

	protected function _get_quizzes_by_question( $question_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
	        SELECT quiz_id
            FROM {$wpdb->learnpress_quiz_questions}
            WHERE question_id = %d
	    ", $question_id );

		return $wpdb->get_col( $query );
	}

	protected function _get_courses_by_item( $item_id ) {
		global $wpdb;
		$query = $wpdb->prepare( "
	        SELECT section_course_id
            FROM {$wpdb->learnpress_sections} s
            INNER JOIN {$wpdb->learnpress_section_items} si ON s.section_id = si.section_id
            WHERE si.item_id = %d
	    ", $item_id );

		return $wpdb->get_col( $query );
	}

	/**
	 * Ouput meta boxes.
	 *
	 * @param WP_Post $post
	 * @param mixed   $box
	 */
	public function _do_output_meta_box( $post, $box ) {
		$callback = $this->_meta_boxes[ $box['id'] ][2];
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

	private function _is_archive() {
		global $pagenow, $post_type;
		if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( $this->_post_type != LP_Request::get_string( 'post_type' ) ) ) {
			return false;
		}

		return true;
	}

	public function _before_delete_post( $post_id ) {
		// TODO:
		if ( ! $this->_check_post() ) {
			return;
		}
		$func_args = func_get_args();

		return $this->_call_method( 'before_delete', $func_args );
	}

	public function _deleted_post( $post_id ) {
		$this->_flush_cache();
	}

	protected function _flush_cache() {
		// Flush the Hard Cache to applies new changes
		LP_Hard_Cache::flush();
		wp_cache_flush();
	}

	public function _posts_fields( $fields ) {
		if ( ! $this->_check_post() ) {
			return $fields;
		}

		return $this->posts_fields( $fields );
	}

	public function _posts_join_paged( $join ) {
		if ( ! $this->_check_post() ) {
			return $join;
		}

		return $this->posts_join_paged( $join );
	}

	public function _posts_where_paged( $where ) {
		if ( ! $this->_check_post() ) {
			return $where;
		}

		return $this->posts_where_paged( $where );
	}

	public function _posts_orderby( $orderby ) {
		if ( ! $this->_check_post() ) {
			return $orderby;
		}

		return $this->posts_orderby( $orderby );
	}

	public function _check_post() {
		global $pagenow, $post_type;

		if ( ! is_admin() || ( ! in_array( $pagenow, array(
				'edit.php',
				'post.php'
			) ) ) || ( $this->_post_type != $post_type )
		) {
			return false;
		}

		return true;
	}

	public function add_meta_box( $id, $title, $callback = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		$this->_meta_boxes[ $id ] = func_get_args();

		return $this;
	}

	public function register() {
		return false;
	}

	public function add_meta_boxes() {
		if ( $this->_post_type != learn_press_get_requested_post_type() ) {
			return;
		}
		do_action( 'learn_press_add_meta_boxes', $this->_post_type, $this );
		do_action( "learn_press_{$this->_post_type}_add_meta_boxes", $this );
		if ( ! $this->_meta_boxes ) {
			return;
		}

		foreach ( $this->_meta_boxes as $k => $meta_box ) {
			$size = sizeof( $meta_box );
			if ( ( $size == 2 ) || ( $size == 3 && ! $meta_box[2] ) ) {
				$func        = 'output_' . preg_replace( '/[-]+/', '_', $meta_box[0] );
				$meta_box[2] = array( $this, $func );
			}
			array_splice( $meta_box, 3, 0, array( $this->_post_type ) );
			$this->_meta_boxes[ $k ] = $meta_box;

			$meta_box[2] = array( $this, '_do_output_meta_box' );
			call_user_func_array( 'add_meta_box', $meta_box );
		}
	}

	public function before_delete( $post_id ) {
		// Implement from child
	}

	/**
	 *
	 */
	public function save() {

	}

	/**
	 * Filter item by the course selected.
	 *
	 * @since 3.0.7
	 *
	 * @return bool|int
	 */
	protected function _filter_items_by_course() {
		$course_id = ! empty( $_REQUEST['course'] ) ? absint( $_REQUEST['course'] ) : false;

		if ( ! $course_id ) {
			global $post_type;
			if ( ! learn_press_is_support_course_item_type( $post_type ) ) {
				$course_id = false;
			}
		}

		return $course_id;
	}

	/**
	 * @return mixed
	 */
	protected function _get_course_column_title() {
		global $post_type;

		if ( ! learn_press_is_support_course_item_type( $post_type ) ) {
			return false;
		}

		$title = __( 'Course', 'learnpress' );

		if ( $course_id = $this->_filter_items_by_course() ) {
			if ( $course = learn_press_get_course( $course_id ) ) {
				$count       = $course->count_items( $this->_post_type );
				$post_object = get_post_type_object( $post_type );
				$title       = sprintf( _n( 'Course (%d %s)', 'Course (%d %s)', $count, 'learnpress' ), $count, $count > 1 ? $post_object->label : $post_object->labels->singular_name );
			}
		}

		return $title;
	}

	/**
	 * Get course that the items is contained.
	 *
	 * @param $post_id
	 */
	protected function _get_item_course( $post_id ) {
		$courses = learn_press_get_item_courses( $post_id );
		if ( $courses ) {
			foreach ( $courses as $course ) {
				echo '<div><a href="' . esc_url( remove_query_arg( 'orderby', add_query_arg( array( 'course' => $course->ID ) ) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
				echo '<div class="row-actions">';
				printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learnpress' ) );
				echo "&nbsp;|&nbsp;";
				printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learnpress' ) );

				if ( $this->_filter_items_by_course() ) {
					echo "&nbsp;|&nbsp;";
					printf( '<a href="%s">%s</a>', remove_query_arg( array(
						'course',
						'orderby'
					) ), __( 'Remove Filter', 'learnpress' ) );
				}
				echo '</div></div>';
			}

		} else {
			_e( 'Not assigned yet', 'learnpress' );
		}
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

	}

	/**
	 * Get string for searching
	 *
	 * @return string
	 */
	private function _get_search() {
		return LP_Request::get( 's' );
	}

	/**
	 * @return string
	 */
	private function _get_order() {
		return strtolower( LP_Request::get( 'order' ) ) === 'desc' ? 'DESC' : 'ASC';
	}

	/**
	 * @return mixed
	 */
	private function _get_orderby() {
		return LP_Request::get( 'orderby' );
	}

	public function _post_row_actions( $actions, $post ) {
		if ( ! $this->_check_post() ) {
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
			$this->_map_methods[ $origin ] = $replace;
		} else {
			if ( empty( $this->_map_methods[ $origin ] ) ) {
				$this->_map_methods[ $origin ] = array( $replace );
			} else {
				$this->_map_methods[ $origin ][] = $replace;
			}
		}

		return $this;
	}

	private function _get_map_method( $origin ) {
		if ( ! empty( $this->_map_methods[ $origin ] ) ) {
			if ( is_array( $this->_map_methods[ $origin ] ) ) {
				$callback = array();
				foreach ( $this->_map_methods[ $origin ] as $method ) {
					$callback[] = array( $this, $method );
				}
			} else {
				$callback = array( $this, $this->_map_methods[ $origin ] );
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
		$messages[ $this->_post_type ] = array(
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
			10 => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'draft updated.', 'learnpress' ) )
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
			$messages[ $this->_post_type ][1] .= $view_link;
			$messages[ $this->_post_type ][6] .= $view_link;
			$messages[ $this->_post_type ][9] .= $view_link;

			$preview_permalink = learn_press_get_preview_url( $post->ID );

			$preview_link                      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), sprintf( '%s %s', __( 'Preview', 'learnpress' ), $post_type_object->labels->singular_name ) );
			$messages[ $this->_post_type ][8]  .= $preview_link;
			$messages[ $this->_post_type ][10] .= $preview_link;
		}

		return $messages;
	}
}

class LP_Abstract_Post_Type_Core extends LP_Abstract_Post_Type {
	/**
	 * Get string for searching
	 *
	 * @return string
	 */
	protected function _get_search() {
		return LP_Request::get( 's' );
	}

	/**
	 * @return string
	 */
	protected function _get_order() {
		return strtolower( LP_Request::get( 'order' ) ) === 'desc' ? 'DESC' : 'ASC';
	}

	/**
	 * @return mixed
	 */
	protected function _get_orderby() {
		return LP_Request::get( 'orderby' );
	}
}