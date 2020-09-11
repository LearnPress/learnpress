<?php
/**
 * Class LP_Lesson_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Lesson_Post_Type' ) ) {

	/**
	 * Class LP_Lesson_Post_Type
	 */
	final class LP_Lesson_Post_Type extends LP_Abstract_Post_Type_Core {
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

			$this->add_map_method( 'before_delete', 'before_delete_lesson' );
			// hide View Lesson link if not assigned to course

			/**
			 * @editor tungnx
			 * Comment code hide_view_lesson_link
			 * @since  3.2.7.7
			 */
			//add_action( 'admin_footer', array( $this, 'hide_view_lesson_link' ) );
			add_filter( 'views_edit-' . LP_LESSON_CPT, array( $this, 'views_pages' ), 10 );

			parent::__construct( $post_type );
		}

		/**
		 * Filter items unassigned.
		 *
		 * @param string $where
		 *
		 * @return string
		 */
		public function posts_where_paged( $where ) {

			if ( ! $this->_is_archive() ) {
				return $where;
			}

			global $wpdb;

			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				$where .= $wpdb->prepare( "
                    AND {$wpdb->posts}.ID NOT IN(
                        SELECT si.item_id 
                        FROM {$wpdb->learnpress_section_items} si
                        INNER JOIN {$wpdb->posts} p ON p.ID = si.item_id
                        WHERE p.post_type = %s
                    )
                ", LP_LESSON_CPT );
			}

			if ( $preview = LP_Request::get( 'preview' ) ) {
				$clause = $wpdb->prepare( "
                    SELECT ID
                    FROM {$wpdb->posts} p 
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                    WHERE pm.meta_value = %s
                    AND p.post_type = %s",
					'_lp_preview', 'yes', LP_LESSON_CPT );

				$in = '';
				if ( 'no' === $preview ) {
					$in = 'NOT';
				}

				$where .= " AND {$wpdb->posts}.ID {$in} IN({$clause})";
			}

			return $where;
		}

		/**
		 * Add filters to lesson view.
		 *
		 * @param array $views
		 *
		 * @return mixed
		 * @since 3.0.0
		 *
		 */
		public function views_pages( $views ) {
			$unassigned_items = learn_press_get_unassigned_items( LP_LESSON_CPT );
			$unassigned_text  = sprintf( __( 'Unassigned %s', 'learnpress' ), '<span class="count">(' . sizeof( $unassigned_items ) . ')</span>' );
			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				$views['lesson-unassigned'] = sprintf(
					'<a href="%s" class="current">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&unassigned=yes' ),
					$unassigned_text
				);
			} else {
				$views['lesson-unassigned'] = sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&unassigned=yes' ),
					$unassigned_text
				);
			}

			$total_preview_items = LP_Lesson_DB::getInstance()->get_total_preview_items();
			$preview_text        = sprintf( __( 'Preview %s', 'learnpress' ), '<span class="count">(' . $total_preview_items . ')</span>' );

			if ( 'yes' === LP_Request::get( 'preview' ) ) {
				$views['lesson-preview'] = sprintf(
					'<a href="%s" class="current">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&preview=yes' ),
					$preview_text
				);
			} else {
				$views['lesson-preview'] = sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&preview=yes' ),
					$preview_text
				);
			}

			$total_no_preview_items = LP_Lesson_DB::getInstance()->get_total_no_preview_items( $total_preview_items );
			$no_preview_text        = sprintf( __( 'No Preview %s', 'learnpress' ), '<span class="count">(' . $total_no_preview_items . ')</span>' );

			if ( 'no' === LP_Request::get( 'preview' ) ) {
				$views['lesson-no-preview'] = sprintf(
					'<a href="%s" class="current">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&preview=no' ),
					$no_preview_text
				);
			} else {
				$views['lesson-no-preview'] = sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&preview=no' ),
					$no_preview_text
				);
			}

			return $views;
		}

		/**
		 * @author tungnx
		 * @reason move query to get_total_preview_items method on LP_Lesson_DB class
		 * @since  3.2.7.7
		 */
		/*public function get_preview_items() {
			global $wpdb;
			$query = $wpdb->prepare( "
		        SELECT COUNT(ID)
		        FROM {$wpdb->posts} p 
		        INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
		        WHERE pm.meta_value = %s
		        AND p.post_type = %s
		    ", '_lp_preview', 'yes', LP_LESSON_CPT );

			return $wpdb->get_var( $query );
		}*/

		/**
		 * @author tungnx
		 * @reason move query to get_total_no_preview_items method on LP_Lesson_DB class
		 * @since  3.2.7.7
		 */
		/*public function get_no_preview_items() {
			global $wpdb;
			$query = $wpdb->prepare( "
		        SELECT COUNT(ID)
		        FROM {$wpdb->posts} p 
		        WHERE p.post_type = %s AND p.post_status NOT LIKE 'auto-draft'
		    ", LP_LESSON_CPT );

			return $wpdb->get_var( $query ) - $this->get_preview_items();
		}*/

		/**
		 * Register lesson post type.
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
						'post-formats',
						'revisions',
						'comments'
					),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'lessons', 'hierarchical' => true, 'with_front' => false )
				);

		}

		/**
		 * Meta boxes.
		 */
		public function add_meta_boxes() {

			$meta_boxes = apply_filters( 'learn_press_lesson_meta_box_args',
				array(
					'id'     => 'lesson_settings',
					'title'  => __( 'Lesson Settings', 'learnpress' ),
					'pages'  => array( LP_LESSON_CPT ),
					'fields' => array(
						array(
							'name'         => __( 'Lesson Duration', 'learnpress' ),
							'id'           => '_lp_duration',
							'type'         => 'duration',
							'default_time' => 'minute',
							'desc'         => __( 'Duration of the lesson. Set 0 to disable.', 'learnpress' ),
							'std'          => 30,
						),
						array(
							'name' => __( 'Preview Lesson', 'learnpress' ),
							'id'   => '_lp_preview',
							'type' => 'yes-no',
							'desc' => __( 'If this is a preview lesson, then student can view this lesson content without taking the course.', 'learnpress' ),
							'std'  => 'no'
						)
					)
				)
			);

			new RW_Meta_Box( $meta_boxes );
			parent::add_meta_boxes();
		}

		/**
		 * Remove lesson form course items.
		 *
		 * @param int $post_id
		 *
		 * @since 3.0.0
		 *
		 */
		public function before_delete_lesson( $post_id = 0 ) {
			// lesson curd
			$curd = new LP_Lesson_CURD();
			// remove lesson from course items
			$curd->delete( $post_id );
		}

		/**
		 * hide View Lesson link if not assigned to course
		 *
		 * @editor     tungnx
		 * @deprecated 3.2.7.7
		 * @todo       remove method after 3.2.7.7
		 */
		public function hide_view_lesson_link() {
			$current_screen = get_current_screen();
			global $post;
			if ( ! $post ) {
				return;
			}
			if ( $current_screen->id === LP_LESSON_CPT && ! learn_press_get_item_course_id( $post->ID, $post->post_type ) ) {
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

		/**
		 * Add columns to admin manage lesson page
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {

			// append new column after title column
			$pos         = array_search( 'title', array_keys( $columns ) );
			$new_columns = array(
				'author'      => __( 'Author', 'learnpress' ),
				LP_COURSE_CPT => $this->_get_course_column_title()
			);

			if ( current_theme_supports( 'post-formats' ) ) {
				$new_columns['format']   = __( 'Format', 'learnpress' );
				$new_columns['duration'] = __( 'Duration', 'learnpress' );
			}

			$new_columns['preview'] = __( 'Preview', 'learnpress' );

			if ( false !== $pos && ! array_key_exists( LP_COURSE_CPT, $columns ) ) {
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
					$this->_get_item_course( $post_id );
					break;
				case 'preview':
					printf(
						'<input type="checkbox" class="learn-press-checkbox learn-press-toggle-item-preview" %s value="%s" data-nonce="%s" />',
						get_post_meta( $post_id, '_lp_preview', true ) == 'yes' ? ' checked="checked"' : '',
						$post_id,
						wp_create_nonce( 'learn-press-toggle-item-preview' )
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

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns[ LP_COURSE_CPT ] = 'course-name';
			$columns['author']        = 'author';

			return $columns;
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( LP_LESSON_CPT != LP_Request::get_string( 'post_type' ) ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Admin scripts.
		 */
		public function admin_scripts() {
			if ( in_array( get_post_type(), array( LP_LESSON_CPT ) ) ) {
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/vendor/jquery.caret.js' ) );
			}
		}

		/**
		 * Add admin params.
		 *
		 * @return array
		 */
		public function admin_params() {
			return array( 'notice_empty_lesson' => '' );
		}

		/**
		 * Enqueue script.
		 */
		public function enqueue_script() {
			if ( LP_LESSON_CPT != get_post_type() ) {
				return;
			}
			LP_Assets::enqueue_script( 'select2', LP_PLUGIN_URL . '/lib/meta-box/js/select2/select2.min.js' );
			LP_Assets::enqueue_style( 'select2', LP_PLUGIN_URL . '/lib/meta-box/css/select2/select2.css' );
			ob_start();
			?>
			<script>
				var form = $('#post');
				form.submit(function (evt) {
					var $title = $('#title'),
						is_error = false;
					if (0 === $title.val().length) {
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
		 * Lesson assigned view.
		 *
		 * @since 3.0.0
		 */
		public static function lesson_assigned() {
			learn_press_admin_view( 'meta-boxes/course/assigned.php' );
		}

		/**
		 * @return LP_Lesson_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self( LP_LESSON_CPT );
			}

			return self::$_instance;
		}
	}

	// LP_Lesson_Post_Type
	$lesson_post_type = LP_Lesson_Post_Type::instance();

	// add meta box
	$lesson_post_type
		->add_meta_box( 'lesson_assigned', __( 'Assigned', 'learnpress' ), 'lesson_assigned', 'side', 'high' );
}
