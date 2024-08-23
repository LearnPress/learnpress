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
	// protected $_map_methods = array();

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
	public function __construct( $post_type = '', $args = '' ) {

		if ( ! empty( $post_type ) ) {
			$this->_post_type = $post_type;
		}
		add_action( 'init', array( $this, '_do_register' ) );
		add_action( 'save_post', array( $this, '_do_save_post' ), - 1, 3 );
		add_action( 'wp_after_insert_post', [ $this, 'wp_after_insert_post' ], - 1, 3 );
		add_action( 'before_delete_post', array( $this, '_before_delete_post' ) );
		add_action( 'deleted_post', array( $this, '_deleted_post' ) );
		add_action( 'wp_trash_post', array( $this, '_before_trash_post' ) );
		add_action( 'trashed_post', array( $this, '_trashed_post' ) );

		add_filter( 'manage_edit-' . $this->_post_type . '_sortable_columns', array( $this, 'sortable_columns' ) );
		add_filter( 'manage_' . $this->_post_type . '_posts_columns', array( $this, 'columns_head' ) );
		add_filter( 'manage_' . $this->_post_type . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );

		add_filter( 'posts_fields', array( $this, '_posts_fields' ) );
		add_filter( 'posts_join_paged', array( $this, '_posts_join_paged' ) );
		add_filter( 'posts_where_paged', array( $this, '_posts_where_paged' ) );
		add_filter( 'posts_orderby', array( $this, '_posts_orderby' ) );

		// Show actions link on list post admin.
		add_filter( 'post_row_actions', array( $this, '_post_row_actions' ), 10, 2 );

		// New metabox: Nhamdv
		add_action( 'add_meta_boxes', array( $this, 'render_meta_box' ), 0 );

		// After update h5p and withdraw will remove it.
		add_action( 'load-post.php', array( $this, 'add_meta_boxes' ), 0 );
		add_action( 'load-post-new.php', array( $this, 'add_meta_boxes' ), 0 );
		// End

		// Comment by tungnx
		// add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		// add_action( 'admin_enqueue_scripts', array( $this, 'admin_styles' ) );

		// Comment by tungnx
		// add_action( 'admin_footer-post.php', array( $this, 'print_js_template' ) );
		// add_action( 'admin_footer-post-new.php', array( $this, 'print_js_template' ) );

		// Comment by tungnx - not use
		// add_action( 'pre_get_posts', array( $this, 'update_default_meta' ) );
		add_action( 'admin_footer', array( $this, 'admin_footer_scripts' ) );

		//add_filter( 'post_updated_messages', array( $this, 'updated_messages' ) );

		$args = wp_parse_args(
			$args,
			array(
				'auto_save'    => 'no',
				'default_meta' => false,
			)
		);

		if ( $args['auto_save'] == 'no' ) {
			add_action( 'admin_print_scripts', array( $this, 'remove_auto_save_script' ) );
		}

		/*
		if ( $args['default_meta'] ) {
			$this->_default_metas = $args['default_meta'];
		}*/

		// Comment by tungnx
		// add_action( 'init', array( $this, 'maybe_remove_features' ), 1000 );
	}

	/**
	 * This function is invoked along with 'init' action to register
	 * new post type with WP.
	 *
	 * @editor tungnx
	 * @since modify 4.1.0
	 */
	public function _do_register() {
		$args = $this->args_register_post_type();

		/*
		 * Todo: This is function old, still has on some addons, so need replace "register" function to args_register_post_type
		 * When replace all will delete this function - long ago will delete, for some user didn't updated new addon version fix
		 */
		if ( method_exists( $this, 'register' ) ) {
			$args = $this->register();
		}

		if ( $args ) {
			register_post_type( $this->_post_type, $args );

			// Todo: tungnx review this code.
			//flush_rewrite_rules();
		}
	}

	/**
	 * Args to register custom post type.
	 *
	 * @return array
	 */
	public function args_register_post_type(): array {
		return array();
	}

	/**
	 * Hook save post of WP
	 *
	 * In child-class use function save()
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $is_update
	 *
	 * @editor tungnx
	 * @since modify 4.0.9
	 * @version 4.0.2
	 */
	final function _do_save_post( int $post_id = 0, WP_Post $post = null, bool $is_update = false ) {
		// Maybe remove
		$this->maybe_remove_assigned( $post );

		if ( ! $this->check_post( $post_id ) ) {
			return;
		}

		$this->save( $post_id, $post );
		$this->save_post( $post_id, $post, $is_update );
	}

	/**
	 * Function for child class handle when post has just saved
	 *
	 * @editor tungnx
	 * @docs Class post type extend need override this function if want to handle when save
	 */
	public function save( int $post_id, WP_Post $post ) {
		// Implement from child
	}

	/**
	 * Function for child class handle when post has just saved
	 * This function provides the argument `$update` to determine whether a post is updated or new.
	 * Replace for function save only has two args
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $is_update
	 *
	 * @since 4.2.6.9
	 * @version 1.0.0
	 */
	public function save_post( int $post_id, WP_Post $post = null, bool $is_update = false ) {
		// Implement from child
	}

	/**
	 * Callback hook 'wp_after_insert_post'
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @return void
	 * @since 4.2.6.9
	 * @version 1.0.0
	 */
	final function wp_after_insert_post( $post_id, $post, $update ) {
		if ( ! $this->check_post( $post_id ) ) {
			return;
		}

		$this->after_insert_post( $post_id, $post, $update );
	}

	/**
	 * Function for child class handle when post has just saved
	 *
	 * @param int $post_id
	 * @param WP_Post|null $post
	 * @param bool $update
	 */
	public function after_insert_post( int $post_id, WP_Post $post = null, bool $update = false ) {
		// Implement from child
	}

	/**
	 * Hook before delete post
	 * Only on receiver 1 param $post_id, can't get param $post - don't know why
	 *
	 * @param int $post_id
	 * @param WP_Post|null $post
	 *
	 * @editor tungnx
	 * @since modify 4.0.9
	 */
	final function _before_delete_post( int $post_id, WP_Post $post = null ) {
		try {
			// Todo: check is pages of LP
			if ( 'page' === get_post_type( $post_id ) ) {
				// Clear cache LP settings
				$lp_settings_cache = new LP_Settings_Cache( true );
				$lp_settings_cache->clean_lp_settings();
			}

			if ( ! $this->check_post( $post_id ) ) {
				return;
			}

			$this->before_delete( $post_id );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Function for child class handle before post deleted
	 *
	 * @param int $post_id
	 *
	 * @editor tungnx
	 * @since modify 4.0.9
	 */
	public function before_delete( int $post_id ) {
		// Implement from child
	}

	/**
	 * Hook deleted post
	 *
	 * @param int $post_id
	 */
	final function _deleted_post( int $post_id ) {
		$this->deleted_post( $post_id );
	}

	/**
	 * Function for child class handle when post has just deleted
	 *
	 * @editor tungnx
	 * @docs Class post type extend need override this function if want to handle when post deleted
	 */
	public function deleted_post( int $post_id ) {
		// Implement from child
	}

	protected $course_of_item_trashed = 0;

	/**
	 * Hook before delete post
	 *
	 * @param int $post_id
	 *
	 * @author tungnx
	 * @since 4.1.6.9
	 * @version 1.0.0
	 */
	final function _before_trash_post( int $post_id ) {
		if ( ! $this->check_post( $post_id ) ) {
			return;
		}

		$this->before_trash_post( $post_id );
	}

	/**
	 * Before trash post
	 *
	 * @param int $post_id
	 *
	 * @return void
	 * @author tungnx
	 * @since 4.1.6.9
	 * @version 1.0.0.0
	 */
	public function before_trash_post( int $post_id ) {
		// Implement from child
		// Check is item type of course
		$course_item_types = learn_press_get_course_item_types();
		if ( ! in_array( get_post_type( $post_id ), $course_item_types ) ) {
			return;
		}

		try {
			// Set course id of item when item assign on course is trashed
			$course_of_item = LP_Course_DB::getInstance()->get_course_by_item_id( $post_id );
			if ( $course_of_item ) {
				$this->course_of_item_trashed = $course_of_item;
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Hook Trashed post
	 *
	 * @param int $post_id
	 *
	 * @return void
	 * @author tungnx
	 * @since 4.1.6.9
	 * @version 1.0.0
	 */
	final function _trashed_post( int $post_id ) {
		if ( ! $this->check_post( $post_id ) ) {
			return;
		}

		$this->trashed_post( $post_id );
	}

	/**
	 * Method handle Trashed post
	 *
	 * @param int $post_id
	 *
	 * @return void
	 * @since 4.1.6.9
	 * @version 1.0.0
	 * @author tungnx
	 */
	public function trashed_post( int $post_id ) {
		// Implement from child
		// Check is item type of course
		$course_item_types = learn_press_get_course_item_types();
		if ( ! in_array( get_post_type( $post_id ), $course_item_types ) ) {
			return;
		}

		if ( $this->course_of_item_trashed ) {
			// Save course when item assign on course is trashed
			$course_id   = $this->course_of_item_trashed;
			LP_Course_Post_Type::instance()->save_post( $course_id, null, true );
			$this->course_of_item_trashed = 0;
		}
	}

	public function column_instructor( $post_id = 0 ) {
		global $post;

		$args = array(
			'post_type' => $post->post_type,
			'author'    => get_the_author_meta( 'ID' ),
		);

		$author_link = esc_url_raw( add_query_arg( $args, 'edit.php' ) );
		echo sprintf( '<span class="post-author">%s<a href="%s">%s</a></span>', get_avatar( get_the_author_meta( 'ID' ), 32 ), $author_link, get_the_author() );
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

		// Comment by tungnx - not use on here, wrote on js
		/*
		if ( $pagenow === 'edit.php' ) {
			$option = sprintf( '<option value="">%s</option>', __( 'Search by user', 'learnpress' ) );
			$user   = get_user_by( 'id', LP_Request::get_int( 'author' ) );

			if ( $user ) {
				$option = sprintf( '<option value="%d" selected="selected">%s</option>', $user->ID, $user->user_login );
			}
		}*/

		// Todo: write this code on file js
		if ( $pagenow === 'post.php' ) {
			?>
			<script>
				jQuery(function ($) {
					var isAssigned = '<?php echo esc_js( $this->is_assigned() ); ?>',
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
			$query = $wpdb->prepare(
				"
                SELECT s.section_course_id
                FROM {$wpdb->learnpress_section_items} si
                INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
                INNER JOIN {$wpdb->posts} p ON p.ID = si.item_id
                WHERE p.ID = %d
	        ",
				get_the_ID()
			);

			$course_id = $wpdb->get_var( $query );
			if ( $course_id ) {
				return __( 'This item has already been assigned to the course. It will be removed from the course if it is not published.', 'learnpress' );
			}
		} elseif ( LP_QUESTION_CPT === $post_type ) {
			$query = $wpdb->prepare(
				"
		        SELECT p.ID
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->learnpress_quiz_questions} qq ON p.ID = qq.quiz_id
                WHERE qq.question_id = %d
		    ",
				get_the_ID()
			);

			$quiz_id = $wpdb->get_var( $query );
			if ( $quiz_id ) {
				return __( 'This question has already been assigned to the quiz. It will be removed from the quiz if it is not published.', 'learnpress' );
			}
		}

		return 0;
	}

	public function remove_auto_save_script() {
		global $post;

		if ( $post && in_array( get_post_type( $post->ID ), array( $this->_post_type ) ) ) {
			wp_dequeue_script( 'autosave' );
		}
	}

	/**
	 * Maybe remove assigned item
	 *
	 * @param WP_Post $post
	 *
	 * @editor tungnx
	 * @todo Review and move to place correct
	 */
	public function maybe_remove_assigned( WP_Post $post = null ) {
		global $wpdb;

		if ( ! $post ) {
			return;
		}

		$post_type   = $post->post_type;
		$post_status = $post->post_status;

		// If we are updating question
		if ( LP_QUESTION_CPT === $post_type ) {

			// If question is not published then delete it from quizzes
			if ( $post_status !== 'publish' ) {
				$query = $wpdb->prepare(
					"
                    DELETE FROM {$wpdb->learnpress_quiz_questions}
                    WHERE question_id = %d
                	",
					$post->ID
				);
				$wpdb->query( $query );
			}
		} elseif ( learn_press_is_support_course_item_type( $post_type ) ) {

			// If item is not published then delete it from courses
			if ( $post_status !== 'publish' ) {
				$query = $wpdb->prepare(
					"
                    DELETE FROM {$wpdb->learnpress_section_items}
                    WHERE item_id = %d
                	",
					$post->ID
				);
				$wpdb->query( $query );
			}
		}
	}

	protected function _get_quizzes_by_question( $question_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"
	        SELECT quiz_id
            FROM {$wpdb->learnpress_quiz_questions}
            WHERE question_id = %d
	    ",
			$question_id
		);

		return $wpdb->get_col( $query );
	}

	protected function _get_courses_by_item( $item_id ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"
	        SELECT section_course_id
            FROM {$wpdb->learnpress_sections} s
            INNER JOIN {$wpdb->learnpress_section_items} si ON s.section_id = si.section_id
            WHERE si.item_id = %d
	    ",
			$item_id
		);

		return $wpdb->get_col( $query );
	}

	/**
	 * Ouput meta boxes.
	 *
	 * @param WP_Post $post
	 * @param mixed $box
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

	/**
	 * @editor tungnx
	 * @reason not use
	 */
	/*
	private function _is_archive() {
		global $pagenow, $post_type;
		if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( $this->_post_type != LP_Request::get_string( 'post_type' ) ) ) {
			return false;
		}

		return true;
	}*/

	protected function _flush_cache() {
		// LP_Hard_Cache::flush();
		// wp_cache_flush();
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

	public function posts_join_paged( $join ) {
		return $join;
	}

	final function _posts_where_paged( $where ) {
		if ( ! $this->_check_post() ) {
			return $where;
		}

		return $this->posts_where_paged( $where );
	}

	public function posts_where_paged( $where ) {
		return $where;
	}

	public function _posts_orderby( $orderby ) {
		if ( ! $this->_check_post() ) {
			return $orderby;
		}

		return $this->posts_orderby( $orderby );
	}

	public function posts_orderby( $orderby ) {
		return $orderby;
	}

	/**
	 * Check post valid
	 *
	 * @return bool
	 */
	public function _check_post(): bool {
		global $pagenow, $post_type;

		if ( ! is_admin() || ( ! in_array( $pagenow, array(
				'edit.php',
				'post.php'
			) ) ) || ( $this->_post_type != $post_type ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Check post is valid to handle
	 *
	 * @param int $post_id
	 *
	 * @return bool
	 * @since 4.1.6.9
	 * @version 1.0.1
	 */
	public function check_post( int $post_id = 0 ): bool {
		$can_save = true;

		try {
			$post = get_post( $post_id );
			if ( ! $post ) {
				return false;
			}

			if ( $this->_post_type !== $post->post_type ) {
				//throw new Exception( 'Post type is invalid' );
				return false;
			}

			if ( ! current_user_can( ADMIN_ROLE ) &&
				 get_current_user_id() !== (int) $post->post_author ) {
				$can_save = false;
			}

			$can_save = apply_filters( 'lp/custom-post-type/can-save', $can_save, $post );
		} catch ( Throwable $e ) {
			$can_save = false;
		}

		return $can_save;
	}

	/**
	 * Check is page list posts valid
	 *
	 * @return bool
	 */
	protected function is_page_list_posts_on_backend(): bool {
		global $pagenow, $post_type;

		if ( ! is_admin() || $pagenow != 'edit.php' || ( $this->_post_type != $post_type ) ) {
			return false;
		}

		return true;
	}

	/**
	 * New Metabox instance
	 *
	 * @return void
	 * @author Nhamdv
	 */
	public function meta_boxes() {
		return array();
	}

	/**
	 * Render Metabox.
	 *
	 * @return void
	 * @author Nhamdv
	 */
	public function render_meta_box() {
		$add_meta_box = $this->meta_boxes();
		$metaboxes    = ! empty( $add_meta_box ) && is_array( $add_meta_box ) ? $add_meta_box : array();

		$metaboxes = apply_filters( 'learnpress/custom-post-type/add-meta-box', $metaboxes, $this->_post_type );

		if ( ! empty( $metaboxes ) ) {
			foreach ( $metaboxes as $metabox_id => $metabox ) {
				if ( isset( $metabox['callback'] ) ) {
					add_meta_box( $metabox_id, $metabox['title'] ?? esc_html__( 'Unknown', 'learnpress' ), $metabox['callback'], $metabox['post_type'] ?? $this->_post_type, $metabox['context'] ?? 'normal', $metabox['priority'] ?? 'high' );
				}
			}
		}
	}

	// Todo: after update metabox in h5p and withdraw will remove this function
	public function add_meta_box( $id, $title, $callback = null, $context = 'advanced', $priority = 'default', $callback_args = null ) {
		$this->_meta_boxes[ $id ] = func_get_args();

		return $this;
	}

	// Todo: after update metabox in h5p and withdraw will remove this function
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

	/**
	 * Filter item by the course selected.
	 *
	 * @return bool|int
	 * @Todo move to course LP_Course_Post_Type
	 * @since 3.0.7
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
	 * @Todo move to course LP_Course_Post_Type
	 */
	protected function _get_course_column_title() {
		global $post_type;

		if ( ! learn_press_is_support_course_item_type( $post_type ) ) {
			return false;
		}

		$title     = esc_html__( 'Course', 'learnpress' );
		$course_id = $this->_filter_items_by_course();

		if ( $course_id ) {
			$course = learn_press_get_course( $course_id );

			if ( $course ) {
				$count       = $course->count_items( $this->_post_type );
				$post_object = get_post_type_object( $post_type );
				$title       = sprintf( _n( 'Course (%1$d %2$s)', 'Course (%1$d %2$s)', $count, 'learnpress' ), $count, $count > 1 ? $post_object->label : $post_object->labels->singular_name );
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
				echo '<div><a href="' . esc_url_raw( remove_query_arg( 'orderby', add_query_arg( array( 'course' => $course->ID ) ) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
				echo '<div class="row-actions">';
				printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learnpress' ) );
				echo '&nbsp;|&nbsp;';
				printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learnpress' ) );

				if ( $this->_filter_items_by_course() ) {
					echo '&nbsp;|&nbsp;';
					printf(
						'<a href="%s">%s</a>',
						esc_url_raw( remove_query_arg( array( 'course', 'orderby' ) ) ),
						__( 'Remove Filter', 'learnpress' )
					);
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
		// Implement from child
	}

	/**
	 * Get string for searching
	 *
	 * @return string
	 */
	protected function get_search(): string {
		return LP_Request::get( 's' );
	}

	/**
	 * @return string
	 */
	protected function get_order_sort(): string {
		return strtolower( LP_Request::get( 'order' ) ) === 'desc' ? 'DESC' : 'ASC';
	}

	/**
	 * @return mixed
	 */
	protected function get_order_by(): string {
		return LP_Request::get( 'orderby' );
	}

	/**
	 * Show actions on list post
	 *
	 * @param string[] $actions
	 * @param WP_Post $post
	 *
	 * @return array|false|mixed
	 */
	public function _post_row_actions( $actions, $post ) {
		if ( ! $this->_check_post() ) {
			return $actions;
		}

		return $this->row_actions( $actions, $post );
	}

	public function row_actions( $actions, $post ) {
		return $actions;
	}

	/**
	 * @deprecated 4.1.6.9
	 */
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
			5  => isset( $_GET['revision'] ) ? sprintf( __( 'The lesson has been restored to revision from %s', 'learnpress' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'published.', 'learnpress' ) ),
			7  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'saved.', 'learnpress' ) ),
			8  => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'submitted.', 'learnpress' ) ),
			9  => sprintf(
				sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'scheduled for: <strong>%1$s</strong>.', 'learnpress' ) ),
				// translators: Publish box date format, see http://php.net/date
				date_i18n( __( 'M j, Y @ G:i', 'learnpress' ), strtotime( $post->post_date ) )
			),
			10 => sprintf( '%s %s', $post_type_object->labels->singular_name, __( 'draft updated.', 'learnpress' ) ),
		);

		if ( $post_type_object->publicly_queryable ) {
			$permalink = get_permalink( $post->ID );

			$view_link = sprintf( ' <a href="%s">%s</a>', esc_url_raw( $permalink ), sprintf( '%s %s', __( 'View', 'learnpress' ), $post_type_object->labels->singular_name ) );
			switch ( $this->_post_type ) {
				case LP_LESSON_CPT:
				case LP_QUIZ_CPT:
					//$view_link = learn_press_get_item_course_id( $post->ID, $post->post_type ) ? $view_link : '';
					$view_link = LP_Course_DB::getInstance()->get_course_by_item_id( $post->ID ) ? $view_link : '';
					break;
				case LP_ORDER_CPT:
					$order     = learn_press_get_order( $post->ID );
					$view_link = $order->get_view_order_url();
					$view_link = sprintf( ' <a href="%s">%s</a>', esc_url_raw( $view_link ), sprintf( '%s %s', __( 'View', 'learnpress' ), $post_type_object->labels->singular_name ) );
					break;
				case LP_QUESTION_CPT:
					$view_link = '';
					break;
			}
			$messages[ $this->_post_type ][1] .= $view_link;
			$messages[ $this->_post_type ][6] .= $view_link;
			$messages[ $this->_post_type ][9] .= $view_link;

			$preview_permalink = learn_press_get_preview_url( $post->ID );

			$preview_link                      = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url_raw( $preview_permalink ), sprintf( '%s %s', __( 'Preview', 'learnpress' ), $post_type_object->labels->singular_name ) );
			$messages[ $this->_post_type ][8]  .= $preview_link;
			$messages[ $this->_post_type ][10] .= $preview_link;
		}

		return $messages;
	}
}
