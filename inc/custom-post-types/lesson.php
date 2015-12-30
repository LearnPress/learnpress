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
			add_action( 'save_post_' . $post_type_name, array( $this, 'update_lesson_meta' ) );

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

				wp_enqueue_style( 'lp-meta-boxes', LP()->plugin_url( 'assets/css/meta-boxes.css' ) );
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
				wp_enqueue_script( 'lp-meta-boxes', LP()->plugin_url( 'assets/js/meta-boxes.js', 'jquery', 'backbone', 'util' ) );

				wp_localize_script( 'lp-meta-boxes', 'lp_lesson_params', self::admin_params() );

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
					'public'             => false, // no access directly via lesson permalink url
					'taxonomies'         => array( 'lesson-tag' ),
					'publicly_queryable' => true,
					'show_ui'            => true,
					'has_archive'        => true,
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
						'author',
						'excerpt'
					),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'lessons', 'hierarchical' => true, 'with_front' => false )
				)
			);

			register_taxonomy( 'lesson-tag', array( LP_LESSON_CPT ),
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

		static function add_meta_boxes() {
			$prefix     = '_lp_';
			$meta_boxes = array(
				'id'     => 'lesson_settings',
				'title'  => __('Lesson Settings', 'learn_press'),
				'pages'  => array( LP_LESSON_CPT ),
				'fields' => array(
					array(
						'name' => __( 'Lesson Duration', 'learn_press' ),
						'id'   => "{$prefix}duration",
						'type' => 'number',
						'desc' => __( 'The length of the lesson (in minutes)', 'learn_press' ),
						'std'  => 30,
					),
					array(
						'name'    => __( 'Preview Lesson', 'learn_press' ),
						'id'      => "{$prefix}is_previewable",
						'type'    => 'radio',
						'desc'    => __( 'If this is a preview lesson, then student can view this lesson content without taking the course', 'learn_press' ),
						'options' => array(
							'yes'     => __( 'Yes', 'learn_press' ),
							'no' => __( 'No', 'learn_press' ),
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
			$new_columns = array(
				LP()->course_post_type => __( 'Course', 'learn_press' )
			);
			if( current_theme_supports( 'post-formats' ) ){
				$new_columns['format'] = __( 'Format', 'learn_press' );
			}
			$new_columns['is_previewable'] = __( 'Preview', 'learn_press' );
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
							echo '<div><a href="' . esc_url( add_query_arg( array('course_id' => $course->ID) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learn_press' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learn_press' ) );
							echo "&nbsp;|&nbsp;";
							if( $course_id = learn_press_get_request( 'filter_course') ) {
								printf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=lp_lesson' ), __( 'Remove Filter', 'learn_press' ) );
							} else {
								printf( '<a href="%s">%s</a>', admin_url( 'edit.php?post_type=lp_lesson&filter_course=' . $course->ID ), __( 'Filter', 'learn_press' ) );
							}
							echo '</div></div>';
						}

					} else {
						_e( 'Not assigned yet', 'learn_press' );
					}


					break;
				case 'is_previewable':
					printf(
						'<input type="checkbox" class="learn-press-checkbox learn-press-toggle-lesson-preview" %s value="%s" data-nonce="%s" />',
						get_post_meta( $post_id, '_lp_is_previewable', true ) == 'yes' ? ' checked="checked"' : '',
						$post_id,
						wp_create_nonce( 'learn-press-toggle-lesson-preview' )
					);
					break;
				case 'format':
					learn_press_item_meta_format( $post_id, __( 'Standard', 'learn_press' ) );
			}
		}

		/**
		 * Update lesson meta data
		 *
		 * @param $lesson_id
		 */
		function update_lesson_meta( $lesson_id ) {
			return;
			$course_id = get_post_meta( $lesson_id, '_lpr_course', true );
			if ( !$course_id ) {
				delete_post_meta( $lesson_id, '_lpr_course' );
				update_post_meta( $lesson_id, '_lpr_course', 0 );
			}
		}

        function posts_fields( $fields ){
            if ( !is_admin() ) {
                return $fields;
            }
            global $pagenow;
            if ( $pagenow != 'edit.php' ) {
                return $fields;
            }
            global $post_type;
            if ( LP()->lesson_post_type != $post_type ) {
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
			if ( !is_admin() ) {
				return $join;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $join;
			}
			global $post_type;
			if ( LP()->lesson_post_type != $post_type ) {
				return $join;
			}
			global $wpdb;
			if( $course_id = learn_press_get_request( 'filter_course') ) {
				$join .= " INNER JOIN {$wpdb->prefix}learnpress_section_items si ON si.item_id = {$wpdb->posts}.ID";
				$join .= " INNER JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id AND s.section_course_id = " . $course_id;
			}
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
			if ( LP()->lesson_post_type != $post_type ) {
				return $where;
			}
			global $wpdb;


			if ( isset ( $_GET['meta_course'] ) ) {
                $where .= " AND (
                    {$wpdb->postmeta}.meta_key='_lpr_course'
                )";
				$where .= " AND (
                	{$wpdb->postmeta}.meta_value='" . intval( $_GET['meta_course'] ) . "'
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
			if ( LP()->lesson_post_type != $post_type ) {
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
			$columns[LP()->course_post_type] = 'course';
			return $columns;
		}

		static function create_default_meta( $id ){
			$meta = apply_filters( 'learn_press_default_lesson_meta',
				array(
					'_lp_duration'		=> 10,
					'_lp_is_previewable' => 'no'
				)
			);
			foreach( $meta as $key => $value ){
				update_post_meta( $id, $key, $value );
			}
		}

	}// end LP_Lesson_Post_Type
}
new LP_Lesson_Post_Type();