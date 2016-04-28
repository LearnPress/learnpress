<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( !class_exists( 'LP_Lesson_Post_Type' ) ) {

	// Base class for custom post type to extends
	learn_press_include( 'custom-post-types/abstract.php' );

	// class LP_Lesson_Post_Type
	final class LP_Lesson_Post_Type extends LP_Abstract_Post_Type{

		function __construct() {
			$post_type_name = 'lp_lesson';
			add_filter( 'manage_' . $post_type_name . '_posts_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_' . $post_type_name . '_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
			////add_action( 'save_post_' . $post_type_name, array( $this, 'update_lesson_meta' ) );

			// filter
			add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_filter( 'manage_edit-' . $post_type_name . '_sortable_columns', array( $this, 'columns_sortable' ) );
			add_action( 'before_delete_post', array( $this, 'delete_course_item' ) );

			parent::__construct();

		}

		function delete_course_item( $post_id ) {
			global $wpdb;
			// delete lesson from course's section
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_section_items
				WHERE item_id = %d
			", $post_id );
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_section_items' );
		}

		static function admin_scripts(){
			if ( in_array( get_post_type(), array( LP()->course_post_type, LP()->lesson_post_type ) ) ) {
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
			}
		}

		static function admin_styles(){

		}

		static function admin_params(){
			return array(
				'notice_empty_lesson' => ''
			);
		}
		/**
		 * Register lesson post type
		 */
		static function register_post_type() {
			register_post_type( LP_LESSON_CPT,
				array(
					'labels'             => array(
						'name'               => __( 'Lessons', 'learnpress' ),
						'menu_name'          => __( 'Lessons', 'learnpress' ),
						'singular_name'      => __( 'Lesson', 'learnpress' ),
						'add_new_item'       => __( 'Add New Lesson', 'learnpress' ),
						'all_items'          => __( 'Lessons', 'learnpress' ),
						'view_item'          => __( 'View Lesson', 'learnpress' ),
						'add_new'            => __( 'Add New', 'learnpress' ),
						'edit_item'          => __( 'Edit Lesson', 'learnpress' ),
						'update_item'        => __( 'Update Lesson', 'learnpress' ),
						'search_items'       => __( 'Search Lessons', 'learnpress' ),
						'not_found'          => __( 'No lesson found', 'learnpress' ),
						'not_found_in_trash' => __( 'No lesson found in Trash', 'learnpress' ),
					),
					'public'             => false, // no access directly via lesson permalink url
					'taxonomies'         => array( 'lesson_tag' ),
					'publicly_queryable' => false,
					'show_ui'            => true,
					'has_archive'        => false,
					'capability_type'    => LP_LESSON_CPT,
					'map_meta_cap'       => true,
					'show_in_menu'       => 'learn_press',
					'show_in_admin_bar'  => true,
					'show_in_nav_menus'  => true,
					'supports'           => array(
						'title',
						'editor',
						'thumbnail',
						'post-formats',
						'revisions',
						//'excerpt'
					),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'lessons', 'hierarchical' => true, 'with_front' => false )
				)
			);

			register_taxonomy( 'lesson_tag', array( LP_LESSON_CPT ),
				array(
					'labels'            => array(
						'name'          => __( 'Tag', 'learnpress' ),
						'menu_name'     => __( 'Tag', 'learnpress' ),
						'singular_name' => __( 'Tag', 'learnpress' ),
						'add_new_item'  => __( 'Add New Tag', 'learnpress' ),
						'all_items'     => __( 'All Tags', 'learnpress' )
					),
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => 'true',
					'show_in_nav_menus' => true,
					'rewrite'           => array( 'slug' => 'lesson_tag', 'hierarchical' => true, 'with_front' => false ),
				)
			);
		}

		static function add_meta_boxes() {
			$prefix     = '_lp_';
			$meta_boxes = array(
				'id'     => 'lesson_settings',
				'title'  => __( 'Lesson Settings', 'learnpress' ),
				'pages'  => array( LP_LESSON_CPT ),
				'fields' => array(
					array(
						'name' => __( 'Lesson Duration', 'learnpress' ),
						'id'   => "{$prefix}duration",
						'type' => 'number',
						'desc' => __( 'The length of the lesson (in minutes)', 'learnpress' ),
						'std'  => 30,
					),
					array(
						'name'    => __( 'Preview Lesson', 'learnpress' ),
						'id'      => "{$prefix}preview",
						'type'    => 'radio',
						'desc'    => __( 'If this is a preview lesson, then student can view this lesson content without taking the course', 'learnpress' ),
						'options' => array(
							'yes'     => __( 'Yes', 'learnpress' ),
							'no' => __( 'No', 'learnpress' ),
						),
						'std'     => 'no'
					)
				)
			);

			new RW_Meta_Box( $meta_boxes );

		}

		function enqueue_script() {
			if ( LP()->lesson_post_type != get_post_type() ) return;
			LP_Admin_Assets::enqueue_script( 'select2', LP_PLUGIN_URL . '/lib/meta-box/js/select2/select2.min.js' );
			LP_Admin_Assets::enqueue_style( 'select2', LP_PLUGIN_URL . '/lib/meta-box/css/select2/select2.css' );
			ob_start();
			?>
			<script>
				var form = $('#post');
				form.submit(function (evt) {
					var $title = $('#title'),
						is_error = false;
					if (0 == $title.val().length) {
						alert('<?php _e( 'Please enter the title of the lesson', 'learnpress' );?>');
						$title.focus();
						is_error = true;
					}
					if (is_error) {
						evt.preventDefault();
						return false;
					}
				});
			</script>
			<?php
			$script = ob_get_clean();
			$script = preg_replace( '!</?script>!', '', $script );
			learn_press_enqueue_script( $script );
		}


		/**
		 * Add columns to admin manage lesson page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function columns_head( $columns ) {

			// append new column after title column
			$pos = array_search( 'title', array_keys( $columns ) );
			$new_columns = array(
				LP()->course_post_type => __( 'Course', 'learnpress' )
			);
			if( current_theme_supports( 'post-formats' ) ){
				$new_columns['format'] = __( 'Format', 'learnpress' );
			}
			$new_columns['preview'] = __( 'Preview', 'learnpress' );
			if ( false !== $pos && !array_key_exists( LP()->course_post_type, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					$new_columns,
					array_slice( $columns, $pos + 1 )
				);

			}

			unset ( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();
			if ( in_array( LP()->teacher_role, $user->roles ) ) {
				unset( $columns['author'] );
			}

			return $columns;
		}

		/**
		 * Display content for custom column
		 *
		 * @param string $name
		 * @param int    $post_id
		 */
		function columns_content( $name, $post_id ) {
			switch ( $name ) {
				case LP()->course_post_type:
					$courses = learn_press_get_item_courses( $post_id );
					if ( $courses ) {
						foreach( $courses as $course ) {
							echo '<div><a href="' . esc_url( add_query_arg( array('filter_course' => $course->ID) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							if( $course_id = learn_press_get_request( 'filter_course') ) {
								printf( '<a href="%s">%s</a>', remove_query_arg( 'filter_course' ), __( 'Remove Filter', 'learnpress' ) );
							} else {
								printf( '<a href="%s">%s</a>', add_query_arg( 'filter_course', $course->ID ), __( 'Filter', 'learnpress' ) );
							}
							echo '</div></div>';
						}

					} else {
						_e( 'Not assigned yet', 'learnpress' );
					}


					break;
				case 'preview':
					printf(
						'<input type="checkbox" class="learn-press-checkbox learn-press-toggle-lesson-preview" %s value="%s" data-nonce="%s" />',
						get_post_meta( $post_id, '_lp_preview', true ) == 'yes' ? ' checked="checked"' : '',
						$post_id,
						wp_create_nonce( 'learn-press-toggle-lesson-preview' )
					);
					break;
				case 'format':
					learn_press_item_meta_format( $post_id, __( 'Standard', 'learnpress' ) );
			}
		}

        function posts_fields( $fields ){
			if( !$this->_is_archive() ){
				return $fields;
			}

            $fields = " DISTINCT " . $fields;
            return $fields;
        }

		/**
		 * @param $join
		 *
		 * @return string
		 */
		function posts_join_paged( $join ) {
			if( !$this->_is_archive() ){
				return $join;
			}
			global $wpdb;
			if( $this->_filter_course() || ( $this->_get_orderby() == 'course-name' ) || ($this->_get_search()) ) {
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = {$wpdb->posts}.ID";
				$join .= " LEfT JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id";
				$join .= " LEFT JOIN {$wpdb->posts} c ON c.ID = s.section_course_id";
			}
			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
		function posts_where_paged( $where ) {

			if( !$this->_is_archive() ){
				return $where;
			}
			global $wpdb;
			if ( $course_id = $this->_filter_course() ) {
				$where .= $wpdb->prepare( " AND (c.ID = %d)", $course_id );
			}
			if ( isset( $_GET['s'] ) ) {
				$s = $_GET['s'];
				$where = preg_replace(
					"/\.post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
					" .post_content LIKE '%$s%' ) OR (c.post_title LIKE '%$s%' )", $where
				);
			}

			return $where;
		}

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
		function posts_orderby( $order_by_statement ) {
			if( !$this->_is_archive() ){
				return $order_by_statement;
			}
			if ( isset ( $_GET['orderby'] ) && isset ( $_GET['order'] ) ) {
				$order_by_statement = "c.post_title {$_GET['order']}";
				return $order_by_statement;
			}
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		function columns_sortable( $columns ) {
			$columns[LP()->course_post_type] = 'course-name';
			return $columns;
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( LP()->lesson_post_type != $post_type ) ) {
				return false;
			}
			return true;
		}

		private function _get_orderby(){
			return isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		}

		private function _get_search(){
			return isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : false;
		}

		private function _filter_course() {
			return !empty( $_REQUEST['filter_course'] ) ? absint( $_REQUEST['filter_course'] ) : false;
		}

		static function create_default_meta( $id ){
			$meta = apply_filters( 'learn_press_default_lesson_meta',
				array(
					'_lp_duration'		=> 10,
					'_lp_preview' => 'no'
				)
			);
			foreach( $meta as $key => $value ){
				update_post_meta( $id, $key, $value );
			}
		}

	}// end LP_Lesson_Post_Type
}
new LP_Lesson_Post_Type();