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

use LearnPress\Databases\PostDB;
use LearnPress\Filters\LessonPostFilter;
use LearnPress\Filters\PostFilter;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\CourseSectionItemModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\WPTables\LessonsTable;

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
		 * @var string
		 */
		protected $_screen_list = 'edit-' . LP_LESSON_CPT;

		/**
		 * LP_Lesson_Post_Type constructor.
		 *
		 * @param $post_type
		 */
		public function __construct() {

			// $this->add_map_method( 'before_delete', 'before_delete_lesson' );
			// hide View Lesson link if not assigned to course

			add_filter( 'views_edit-' . LP_LESSON_CPT, array( $this, 'views_pages' ), 10 );
			add_filter( 'posts_pre_query', array( $this, 'posts_pre_query' ), 999, 2 );
			// add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

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
		 * @throws Exception
		 * @version 1.0.1
		 * @since 4.2.7.6
		 */
		public function save_post( int $post_id, ?WP_Post $post = null, bool $is_update = false ) {
			// Clear cache
			$lpCache = new LP_Cache();
			$lpCache->clear( "lessonPostModel/find/{$post_id}" );
			$lpCache->clear( "lessonModel/find/{$post_id}" );

			// Find courses have this lesson
			$obj_course_ids = CourseSectionItemModel::get_courses_from_item_id( $post_id, LP_LESSON_CPT );
			if ( ! empty( $obj_course_ids ) ) {
				foreach ( $obj_course_ids as $obj_course_id ) {
					$course_id       = $obj_course_id->section_course_id;
					$coursePostModel = CoursePostModel::find( $course_id, true );
					if ( $coursePostModel ) {
						$coursePostModel->save();
					}
				}
			}
		}

		/**
		 * Declare class name of table list lessons.
		 *
		 * @param string $class_name
		 * @param array  $args
		 *
		 * @return string
		 * @since 4.2.9.5
		 */
		public function wp_list_table_class_name( $class_name, $args ) {
			if ( $this->check_class_name_handle_table( $args['screen'] ) ) {
				$class_name = LessonsTable::class;
			}

			return $class_name;
		}

		/**
		 * Query lp lessons in admin via Post DB
		 *
		 * @param array    $posts
		 * @param WP_Query $wp_query
		 *
		 * @return array|WP_Query
		 * @since 4.4.8
		 * @version 1.0.0
		 */
		public function posts_pre_query( $posts, $wp_query ) {
			try {
				if ( ! is_admin() ) {
					return $posts;
				}

				// Check screen
				$curren_screen = get_current_screen();
				if ( ! $curren_screen || $curren_screen->id !== 'edit-' . LP_LESSON_CPT ) {
					return $posts;
				}

				$post_type = $wp_query->get( 'post_type' );
				if ( empty( $post_type ) || $post_type !== LP_LESSON_CPT ) {
					return $posts;
				}

				$posts_per_page = get_user_option( "edit_{$post_type}_per_page", get_current_user_id() );
				if ( empty( $posts_per_page ) ) {
					$posts_per_page = 20;
				}

				$paged     = max( 1, get_query_var( 'paged' ) );
				$author    = $wp_query->get( 'author' );
				$status    = $wp_query->get( 'post_status' );
				$search    = $wp_query->get( 's' );
				$month     = $wp_query->get( 'm' );
				$orderby   = LP_Request::get_param( 'orderby', '', 'key' );
				$order     = LP_Request::get_param( 'order', '', 'key' );
				$preview   = LP_Request::get_param( 'preview', '', 'key' );
				$course_id = LP_Request::get_param( 'course', '', 'int' );

				$filter              = new LessonPostFilter();
				$filter->page        = $paged;
				$filter->limit       = $posts_per_page;
				$filter->only_fields = [ 'p.ID', 'p.post_title', 'p.post_author', 'p.post_date', 'p.post_date_gmt' ];
				$post_db             = PostDB::getInstance();

				if ( ! empty( $status ) ) {
					$filter->post_status = array( $status );
				}

				if ( ! empty( $author ) ) {
					$filter->post_author = absint( $author );
				}

				if ( ! empty( $search ) ) {
					$filter->post_title = sanitize_text_field( wp_unslash( $search ) );
				}

				// Find lesson assign by course_id
				if ( ! empty( $course_id ) ) {
					$filter->join[]  = "INNER JOIN {$post_db->tb_lp_section_items} si ON p.ID = si.item_id";
					$filter->join[]  = "INNER JOIN {$post_db->tb_lp_sections} st ON st.section_id = si.section_id";
					$filter->where[] = $post_db->wpdb->prepare( 'AND st.section_course_id = %d', $course_id );
				}

				if ( ! empty( $month ) && is_numeric( $month ) && strlen( (string) $month ) === 6 ) {
					$filter->where[] = $post_db->wpdb->prepare(
						'AND YEAR(p.post_date) = %d AND MONTH(p.post_date) = %d',
						substr( $month, 0, 4 ),
						substr( $month, 4, 2 )
					);
				}

				// Exclude status auto-draft
				$filter->where[] = $post_db->wpdb->prepare( 'AND p.post_status != %s', PostModel::STATUS_AUTO_DRAFT );

				// Join sections/courses when sorting by course-name
				if ( $orderby === 'course-name' ) {
					$filter->join[] = "LEFT JOIN {$post_db->wpdb->prefix}learnpress_section_items si ON p.ID = si.item_id";
					$filter->join[] = "LEFT JOIN {$post_db->wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id";
					$filter->join[] = "LEFT JOIN {$post_db->wpdb->posts} c ON c.ID = s.section_course_id";
				}

				// Filter unassigned
				if ( 'yes' === LP_Request::get_param( 'unassigned', '', 'key' ) ) {
					$filter->where[] = "AND p.ID NOT IN(
						SELECT si.item_id
						FROM {$post_db->wpdb->learnpress_section_items} si
					)";
				}

				// Filter preview.
				// Non-preview lessons include those that do not have `_lp_preview` meta key.
				if ( $preview ) {
					$filter_preview                      = new LessonPostFilter();
					$filter_preview->limit               = -1;
					$filter_preview->only_fields         = [ PostFilter::COL_ID ];
					$filter_preview->join[]              = "INNER JOIN {$post_db->wpdb->postmeta} pm ON p.ID = pm.post_id";
					$filter_preview->where[]             = $post_db->wpdb->prepare(
						'AND pm.meta_key = %s AND pm.meta_value = %s',
						'_lp_preview',
						'yes'
					);
					$filter_preview->return_string_query = true;

					$preview_rows = $post_db->get_posts( $filter_preview );

					$in              = 'no' === $preview ? 'NOT' : '';
					$filter->where[] = "AND p.ID {$in} IN({$preview_rows})";
				}

				if ( $orderby === 'course-name' ) {
					$filter->order_by = 'c.post_title';
				} elseif ( $orderby === 'title' ) {
					$filter->order_by = 'p.post_title';
				} elseif ( $orderby === 'author' ) {
					$filter->order_by = 'p.post_author';
				} elseif ( $orderby === 'date' ) {
					$filter->order_by = 'p.post_date';
				} else {
					$filter->order_by = 'p.menu_order';
				}

				$filter->order = strtolower( $order ) === 'asc' ? 'ASC' : 'DESC';

				// Get lp lessons
				$total_rows = 0;
				$lp_lessons = $post_db->get_posts( $filter, $total_rows );

				$wp_query->post_count  = count( $lp_lessons );
				$wp_query->found_posts = $total_rows;
				$posts                 = $lp_lessons;
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}

			return $posts;
		}

		/*public function posts_where_paged( $where ): string {
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
		}*/

		/**
		 * Add filters to lesson view.
		 *
		 * @param array $views
		 *
		 * @return array
		 * @throws Exception
		 * @since 3.0.0
		 * @editor tungnx
		 * @version 1.0.2
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
				'hierarchical'        => false, // Lessons have no parent-child hierarchy.
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
