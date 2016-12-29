<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( !class_exists( 'LP_Lesson_Post_Type' ) ) {
	// class LP_Lesson_Post_Type
	final class LP_Lesson_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * LP_Lesson_Post_Type constructor.
		 *
		 * @param $post_type
		 */
		public function __construct( $post_type ) {

			$this->add_map_method( 'before_delete', 'delete_course_item' );

            /**
             * Hide View Quiz link if not assigned to Course
             */
            add_action( 'admin_footer', array( $this, 'hide_view_lesson_link_if_not_assigned' ) );

			parent::__construct( $post_type );
		}

		public function hide_view_lesson_link_if_not_assigned() {
            $current_screen = get_current_screen();
            global $post;
            if ( !$post ) {
                return;
            }
            if ( $current_screen->id === LP_LESSON_CPT && !learn_press_get_item_course_id( $post->ID, $post->post_type ) ) {
                ?>
                <style type="text/css">
                    #wp-admin-bar-view {
                        display: none;
                    }
                    #sample-permalink a {
                        pointer-events: none;
                        cursor: default;
                        text-decoration: none;
                        color: #666;
                    }
                    #preview-action {
                        display: none;
                    }
                </style>
                <?php
            }
        }

		public function delete_course_item( $post_id ) {
			global $wpdb;
			// delete lesson from course's section
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_section_items
				WHERE item_id = %d
			", $post_id );
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_section_items' );
		}

		public function admin_scripts() {
			if ( in_array( get_post_type(), array( LP_COURSE_CPT, LP_LESSON_CPT ) ) ) {
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
			}
		}

		public function admin_styles() {

		}

		public function admin_params() {
			return array(
				'notice_empty_lesson' => ''
			);
		}

		/**
		 * Register lesson post type
		 */
		public function register() {
			return
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
					'public'             => true, // no access directly via lesson permalink url
					'query_var'          => true,
					'taxonomies'         => array( 'lesson_tag' ),
					'publicly_queryable' => true,
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
						'comments'
						//'excerpt'
					),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'lessons', 'hierarchical' => true, 'with_front' => false )
				);


		}

		public function add_meta_boxes() {
			$prefix     = '_lp_';
			$meta_boxes = apply_filters( 'learn_press_lesson_meta_box_args',
				array(
					'id'     => 'lesson_settings',
					'title'  => __( 'Lesson Settings', 'learnpress' ),
					'pages'  => array( LP_LESSON_CPT ),
					'fields' => array(
						array(
							'name'         => __( 'Lesson Duration', 'learnpress' ),
							'id'           => "{$prefix}duration",
							'type'         => 'number',
							'type'         => 'duration',//'number',
							'default_time' => 'minute',
							'desc'         => __( 'Duration of the lesson. Set 0 to disable', 'learnpress' ),
							'std'          => 30,
						),
						array(
							'name'    => __( 'Preview Lesson', 'learnpress' ),
							'id'      => "{$prefix}preview",
							'type'    => 'yes_no',
							'desc'    => __( 'If this is a preview lesson, then student can view this lesson content without taking the course', 'learnpress' ),
							'std' => 'no'
						)
					)
				)
			);

			new RW_Meta_Box( $meta_boxes );
			parent::add_meta_boxes();
		}

		public function enqueue_script() {
			if ( LP_LESSON_CPT != get_post_type() ) return;
			LP_Assets::enqueue_script( 'select2', LP_PLUGIN_URL . '/lib/meta-box/js/select2/select2.min.js' );
			LP_Assets::enqueue_style( 'select2', LP_PLUGIN_URL . '/lib/meta-box/css/select2/select2.css' );
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
		public function columns_head( $columns ) {

			// append new column after title column
			$pos         = array_search( 'title', array_keys( $columns ) );
			$new_columns = array(
				'author'      => __( 'Author', 'learnpress' ),
				LP_COURSE_CPT => __( 'Course', 'learnpress' )
			);
			if ( current_theme_supports( 'post-formats' ) ) {
				$new_columns['format']   = __( 'Format', 'learnpress' );
				$new_columns['duration'] = __( 'Duration', 'learnpress' );
			}
			$new_columns['preview'] = __( 'Preview', 'learnpress' );
			if ( false !== $pos && !array_key_exists( LP_COURSE_CPT, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					$new_columns,
					array_slice( $columns, $pos + 1 )
				);

			}

			unset ( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();
			if ( in_array( LP_TEACHER_ROLE, $user->roles ) ) {
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
		public function columns_content( $name, $post_id = 0 ) {
			switch ( $name ) {
				case LP_COURSE_CPT:
					$courses = learn_press_get_item_courses( $post_id );
					if ( $courses ) {
						foreach ( $courses as $course ) {
							echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_course' => $course->ID ) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							if ( $course_id = learn_press_get_request( 'filter_course' ) ) {
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
					break;
				case 'duration':
					$duration = absint( get_post_meta( $post_id, '_lp_duration', true ) ) * 60;
					if ( $duration >= 600 ) {
						echo date( 'H:i:s', $duration );
					} elseif ( $duration > 0 ) {
						echo date( 'i:s', $duration );
					} else {
						echo '-';
					}
			}
		}

		function posts_fields( $fields ) {
			if ( !$this->_is_archive() ) {
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
		public function posts_join_paged( $join ) {
			if ( !$this->_is_archive() ) {
				return $join;
			}
			global $wpdb;
			if ( $this->_filter_course() || ( $this->_get_orderby() == 'course-name' ) || ( $this->_get_search() ) ) {
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
		public function posts_where_paged( $where ) {

			if ( !$this->_is_archive() ) {
				return $where;
			}
			global $wpdb;
			if ( $course_id = $this->_filter_course() ) {
				$where .= $wpdb->prepare( " AND (c.ID = %d)", $course_id );
			}
			if ( isset( $_GET['s'] ) ) {
				$s     = $_GET['s'];
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
		public function posts_orderby( $order_by_statement ) {
			global $wpdb;
			if ( !$this->_is_archive() ) {
				return $order_by_statement;
			}
			if ( isset ( $_GET['orderby'] ) && isset ( $_GET['order'] ) ) {
				$order_by_statement = "{$wpdb->posts}.post_title {$_GET['order']}";
			}
			return $order_by_statement;
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns[LP_COURSE_CPT] = 'course-name';
			$columns['author']      = 'author';
			return $columns;
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( LP_LESSON_CPT != $post_type ) ) {
				return false;
			}
			return true;
		}

		private function _get_orderby() {
			return isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		}

		private function _get_search() {
			return isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : false;
		}

		private function _filter_course() {
			return !empty( $_REQUEST['filter_course'] ) ? absint( $_REQUEST['filter_course'] ) : false;
		}

		public static function create_default_meta( $id ) {
			$meta = apply_filters( 'learn_press_default_lesson_meta',
				array(
					'_lp_duration' => 10,
					'_lp_preview'  => 'no'
				)
			);
			foreach ( $meta as $key => $value ) {
				update_post_meta( $id, $key, $value );
			}
		}

		public static function instance() {
			if ( !self::$_instance ) {
				self::$_instance = new self( LP_LESSON_CPT );
			}
			return self::$_instance;
		}
	}// end LP_Lesson_Post_Type
	$lesson_post_type = LP_Lesson_Post_Type::instance();
}
