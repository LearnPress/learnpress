<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LPR_Lesson_Post_Type' ) ) {
	// class LPR_Lesson_Post_Type
	final class LPR_Lesson_Post_Type {
		private static $loaded = false;

		function __construct() {
			if ( self::$loaded ) return;

			add_action( 'init', array( $this, 'register_post_type' ) );

			add_action( 'admin_head', array( $this, 'enqueue_script' ) );
			add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 0 );

			add_filter( 'manage_lpr_lesson_posts_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_lpr_lesson_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
			add_action( 'save_post_lpr_lesson', array( $this, 'update_lesson_meta' ) );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_filter( 'manage_edit-lpr_lesson_sortable_columns', array( $this, 'columns_sortable' ) );

			self::$loaded = true;
		}

		/**
		 * Register lesson post type
		 */
		function register_post_type() {

			register_post_type( LPR_LESSON_CPT,
				array(
					'labels'             => array(
						'name'               => __( 'Lessons', 'learn_press' ),
						'menu_name'          => __( 'Lessons', 'learn_press' ),
						'singular_name'      => __( 'Lesson', 'learn_press' ),
						'add_new_item'       => __( 'Add New Lesson', 'learn_press' ),
						'all_items'          => __( 'Lessons', 'learn_press' ),
						'view_item'          => __( 'View Lesson', 'learn_press' ),
						'add_new'            => __( 'Add New', 'learn_press' ),
						'edit_item'          => __( 'Edit Lesson', 'learn_press' ),
						'update_item'        => __( 'Update Lesson', 'learn_press' ),
						'search_items'       => __( 'Search Lesson', 'learn_press' ),
						'not_found'          => __( 'No lesson found', 'learn_press' ),
						'not_found_in_trash' => __( 'No lesson found in Trash', 'learn_press' ),
					),
					'public'             => true,
					'taxonomies'         => array( 'lesson-tag' ),
					'publicly_queryable' => true,
					'show_ui'            => true,
					'has_archive'        => true,
					'capability_type'    => LPR_LESSON_CPT,
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
						'author',
						'excerpt'
					),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'lessons', 'hierarchical' => true, 'with_front' => false )
				)
			);

			register_taxonomy( 'lesson-tag', array( LPR_LESSON_CPT ),
				array(
					'labels'            => array(
						'name'          => __( 'Tag', 'learn_press' ),
						'menu_name'     => __( 'Tag', 'learn_press' ),
						'singular_name' => __( 'Tag', 'learn_press' ),
						'add_new_item'  => __( 'Add New Tag', 'learn_press' ),
						'all_items'     => __( 'All Tags', 'learn_press' )
					),
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => 'true',
					'show_in_nav_menus' => true,
					'rewrite'           => array( 'slug' => 'lesson-tag', 'hierarchical' => true, 'with_front' => false ),
				)
			);
		}

		function add_meta_boxes() {
			$prefix     = '_lpr_';
			$meta_boxes = array(
				'id'     => 'lesson_settings',
				'title'  => 'Lesson Settings',
				'pages'  => array( LPR_LESSON_CPT ),
				'fields' => array(
					array(
						'name' => __( 'Lesson Duration', 'learn_press' ),
						'id'   => "{$prefix}lesson_duration",
						'type' => 'number',
						'desc' => __( 'The length of the lesson (in minutes)', 'learn_press' ),
						'std'  => 30,
					),
					array(
						'name'    => __( 'Preview Lesson', 'learn_press' ),
						'id'      => "{$prefix}lesson_preview",
						'type'    => 'radio',
						'desc'    => __( 'If this is a preview lesson, then student can view this lesson content without taking the course', 'learn_press' ),
						'options' => array(
							'preview'     => __( 'Yes', 'learn_press' ),
							'not_preview' => __( 'No', 'learn_press' ),
						),
						'std'     => 'not_preview'
					)
				)
			);

			new RW_Meta_Box( $meta_boxes );

		}

		function enqueue_script() {
			if ( 'lpr_lesson' != get_post_type() ) return;
			LPR_Admin_Assets::enqueue_script( 'select2', LPR_PLUGIN_URL . '/lib/meta-box/js/select2/select2.min.js' );
			LPR_Admin_Assets::enqueue_style( 'select2', LPR_PLUGIN_URL . '/lib/meta-box/css/select2/select2.css' );
			ob_start();
			?>
			<script>
				var form = $('#post');
				form.submit(function (evt) {
					var $title = $('#title'),
						is_error = false;
					if (0 == $title.val().length) {
						alert('<?php _e( 'Please enter the title of the lesson', 'learn_press' );?>');
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
			if ( false !== $pos && !array_key_exists( 'lpr_course', $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					array( 'lpr_course' => __( 'Course', 'learn_press' ) ),
					array_slice( $columns, $pos + 1 )
				);
			}
			unset ( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();
			if ( in_array( 'lpr_teacher', $user->roles ) ) {
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
				case 'lpr_course':
					$course_id  = get_post_meta( $post_id, '_lpr_course', true );
					$arr_params = array( 'meta_course' => $course_id );
					echo '<a href="' . esc_url( add_query_arg( $arr_params ) ) . '">' . ( $course_id ? get_the_title( $course_id ) : __( 'Not assigned yet', 'learn_press' ) ) . '</a>';
			}
		}

		/**
		 * Update lesson meta data
		 *
		 * @param $lesson_id
		 */
		function update_lesson_meta( $lesson_id ) {
			$course_id = get_post_meta( $lesson_id, '_lpr_course', true );
			if ( !$course_id ) {
				delete_post_meta( $lesson_id, '_lpr_course' );
				update_post_meta( $lesson_id, '_lpr_course', 0 );
			}
		}

		/**
		 * @param $join
		 *
		 * @return string
		 */
		function posts_join_paged( $join ) {
			if ( !is_admin() ) {
				return $join;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $join;
			}
			global $post_type;
			if ( 'lpr_lesson' != $post_type ) {
				return $join;
			}
			global $wpdb;
			$join .= " INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id";
			$join .= " LEFT JOIN {$wpdb->posts} AS c ON c.ID = {$wpdb->postmeta}.meta_value";
			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
		function posts_where_paged( $where ) {

			if ( !is_admin() ) {
				return $where;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $where;
			}
			global $post_type;
			if ( 'lpr_lesson' != $post_type ) {
				return $where;
			}
			global $wpdb;

			$where .= " AND (
                {$wpdb->postmeta}.meta_key='_lpr_course'
            )";
			if ( isset ( $_GET['meta_course'] ) ) {
				$where .= " AND (
                	{$wpdb->postmeta}.meta_value='{$_GET['meta_course']}'
           		 )";
			}
			if ( isset( $_GET['s'] ) ) {
				$s = $_GET['s'];
				if ( empty( $s ) ) {
					$where .= " AND ( c.post_title IS NULL)";
				} else {
					$where = preg_replace(
						"/\.post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
						" .post_content LIKE '%$s%' ) OR (c.post_title LIKE '%$s%' )", $where
					);
				}
			}

			return $where;
		}

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
		function posts_orderby( $order_by_statement ) {
			if ( !is_admin() ) {
				return $order_by_statement;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $order_by_statement;
			}
			global $post_type;
			if ( 'lpr_lesson' != $post_type ) {
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
			$columns['lpr_course'] = 'course';
			return $columns;
		}

	}// end LPR_Lesson_Post_Type
}
new LPR_Lesson_Post_Type();
