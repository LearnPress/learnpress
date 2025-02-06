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
	final class LP_Lesson_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * @var string
		 */
		protected $_post_type = LP_LESSON_CPT;

		/**
		 * LP_Lesson_Post_Type constructor.
		 *
		 * @param $post_type
		 */
		public function __construct() {

			// $this->add_map_method( 'before_delete', 'before_delete_lesson' );
			// hide View Lesson link if not assigned to course

			add_filter( 'views_edit-' . LP_LESSON_CPT, array( $this, 'views_pages' ), 10 );

			parent::__construct();
		}

		/**
		 * Handle when save post.
		 *
		 * @param int $post_id
		 * @param WP_Post|null $post
		 * @param bool $is_update
		 *
		 * @return void
		 * @since 4.2.7.6
		 * @version 1.0.0
		 */
		public function save_post( int $post_id, WP_Post $post = null, bool $is_update = false ) {
			// Clear cache
			$lpCache = new LP_Cache();
			$lpCache->clear( "lessonPostModel/find/{$post_id}" );
			$lpCache->clear( "lessonModel/find/{$post_id}" );
		}

		/**
		 * Filter items unassigned.
		 *
		 * @param string $where
		 *
		 * @return string
		 */
		public function posts_where_paged( $where ): string {

			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $where;
			}

			global $wpdb;

			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				$where .= $wpdb->prepare(
					"
                    AND {$wpdb->posts}.ID NOT IN(
                        SELECT si.item_id
                        FROM {$wpdb->learnpress_section_items} si
                        INNER JOIN {$wpdb->posts} p ON p.ID = si.item_id
                        WHERE p.post_type = %s
                    )
                	",
					LP_LESSON_CPT
				);
			}

			$preview = LP_Request::get( 'preview' );

			if ( $preview ) {
				$clause = $wpdb->prepare(
					"
                    SELECT ID
                    FROM {$wpdb->posts} p
                    INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                    WHERE pm.meta_value = %s
                    AND p.post_type = %s",
					'_lp_preview',
					'yes',
					LP_LESSON_CPT
				);

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
		 * @return array
		 * @since 3.0.0
		 * @editor tungnx
		 * @modify 4.1.4.1
		 */
		public function views_pages( array $views ): array {
			$count_unassigned_lesson = LP_Course_DB::getInstance()->get_total_item_unassigned( LP_LESSON_CPT );

			if ( $count_unassigned_lesson > 0 ) {
				$views['unassigned'] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&unassigned=yes' ),
					isset( $_GET['unassigned'] ) ? 'current' : '',
					__( 'Unassigned', 'learnpress' ),
					$count_unassigned_lesson
				);
			}

			$total_preview_items = LP_Lesson_DB::getInstance()->get_total_preview_items();
			if ( $total_preview_items > 0 ) {
				$views['lesson-preview'] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&preview=yes' ),
					isset( $_GET['preview'] ) && $_GET['preview'] === 'yes' ? 'current' : '',
					__( 'Preview', 'learnpress' ),
					$total_preview_items
				);
			}

			$total_no_preview_items = LP_Lesson_DB::getInstance()->get_total_no_preview_items( $total_preview_items );
			if ( $total_no_preview_items > 0 ) {
				$views['lesson-no-preview'] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					admin_url( 'edit.php?post_type=' . LP_LESSON_CPT . '&preview=no' ),
					isset( $_GET['preview'] ) && $_GET['preview'] === 'no' ? 'current' : '',
					__( 'No Preview', 'learnpress' ),
					$total_no_preview_items
				);
			}

			return $views;
		}

		/**
		 * Register lesson post type.
		 */
		public function args_register_post_type(): array {

			return array(
				'labels'              => array(
					'name'               => esc_html__( 'Lessons', 'learnpress' ),
					'menu_name'          => esc_html__( 'Lessons', 'learnpress' ),
					'singular_name'      => esc_html__( 'Lesson', 'learnpress' ),
					'add_new_item'       => esc_html__( 'Add A New Lesson', 'learnpress' ),
					'all_items'          => esc_html__( 'Lessons', 'learnpress' ),
					'view_item'          => esc_html__( 'View Lesson', 'learnpress' ),
					'add_new'            => esc_html__( 'Add New', 'learnpress' ),
					'edit_item'          => esc_html__( 'Edit Lesson', 'learnpress' ),
					'update_item'        => esc_html__( 'Update Lesson', 'learnpress' ),
					'search_items'       => esc_html__( 'Search Lessons', 'learnpress' ),
					'not_found'          => esc_html__( 'No lesson found', 'learnpress' ),
					'not_found_in_trash' => esc_html__( 'There was no lesson found in the trash', 'learnpress' ),
				),
				'public'              => true,
				'query_var'           => true,
				'taxonomies'          => array( 'lesson_tag' ),
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'has_archive'         => false,
				'capability_type'     => LP_LESSON_CPT,
				'map_meta_cap'        => true,
				'show_in_menu'        => 'learn_press',
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => learn_press_user_maybe_is_a_teacher(),
				'supports'            => array(
					'title',
					'editor',
					'revisions',
					'comments',
				),
				'hierarchical'        => true,
				'rewrite'             => array(
					'slug'         => 'lessons',
					'hierarchical' => true,
					'with_front'   => false,
				),
				'exclude_from_search' => true,
			);
		}

		/**
		 * Remove lesson form course items.
		 *
		 * @param int $post_id
		 *
		 * @since 3.0.0
		 */
		/*public function before_delete( int $post_id = 0 ) {
			$curd = new LP_Lesson_CURD();
			$curd->delete( $post_id );
		}*/

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
				'instructor'  => esc_html__( 'Author', 'learnpress' ),
				LP_COURSE_CPT => $this->_get_course_column_title(),
			);

			if ( current_theme_supports( 'post-formats' ) ) {
				$new_columns['format']   = esc_html__( 'Format', 'learnpress' );
				$new_columns['duration'] = esc_html__( 'Duration', 'learnpress' );
			}

			$new_columns['preview'] = esc_html__( 'Preview', 'learnpress' );

			if ( false !== $pos && ! array_key_exists( LP_COURSE_CPT, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					$new_columns,
					array_slice( $columns, $pos + 1 )
				);

			}

			unset( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();

			if ( in_array( LP_TEACHER_ROLE, $user->roles ) ) {
				unset( $columns['instructor'] );
			}

			if ( ! empty( $columns['author'] ) ) {
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
				case 'instructor':
					$this->column_instructor( $post_id );
					break;
				case LP_COURSE_CPT:
					$this->_get_item_course( $post_id );
					break;
				case 'preview':
					$lesson_is_preview = 'yes' === get_post_meta( $post_id, '_lp_preview', true );
					echo $lesson_is_preview ? '<span class="dashicons dashicons-saved" style="color: #00c700"></span>' : '';
					break;
				case 'format':
					learn_press_item_meta_format( $post_id, __( 'Standard', 'learnpress' ) );
					break;
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

		/**
		 * Lesson assigned view.
		 *
		 * @since 3.0.0
		 */
		public function lesson_assigned() {
			learn_press_admin_view( 'meta-boxes/course/assigned.php' );
		}

		public function meta_boxes() {
			return array(
				'lesson_assigned' => array(
					'title'    => esc_html__( 'Assigned', 'learnpress' ),
					'callback' => array( $this, 'lesson_assigned' ),
					'context'  => 'side',
					'priority' => 'high',
				),
			);
		}

		/**
		 * @return LP_Lesson_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}

	$lesson_post_type = LP_Lesson_Post_Type::instance();
}
