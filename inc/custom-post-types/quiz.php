<?php
/**
 * Class LP_Quiz_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

use LearnPress\Databases\PostDB;
use LearnPress\Filters\QuizPostFilter;
use LearnPress\Models\PostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\WPTables\QuizzesTable;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Quiz_Post_Type' ) ) {

	/**
	 * Class LP_Quiz_Post_Type
	 */
	final class LP_Quiz_Post_Type extends LP_Abstract_Post_Type {

		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * @var array
		 */
		public static $metaboxes = array();

		/**
		 * @var string
		 */
		protected $_post_type = LP_QUIZ_CPT;

		/**
		 * @var string
		 */
		protected $_screen_list = 'edit-' . LP_QUIZ_CPT;

		/**
		 * LP_Quiz_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct() {

			//$this->add_map_method( 'before_delete', 'before_delete_quiz' );

			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_quiz_editor' ) );

			add_filter( 'views_edit-' . LP_QUIZ_CPT, array( $this, 'views_pages' ), 10 );
			add_action( 'posts_pre_query', array( $this, 'posts_pre_query' ), 999, 2 );
			// add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

			parent::__construct();
		}

		/**
		 * Declare class name of table list quizzes.
		 *
		 * @param string $class_name
		 * @param array  $args
		 *
		 * @return string
		 * @since 4.2.9.5
		 */
		public function wp_list_table_class_name( $class_name, $args ) {
			if ( $this->check_class_name_handle_table( $args['screen'] ) ) {
				$class_name = QuizzesTable::class;
			}

			return $class_name;
		}

		/**
		 * Add filters to lesson view.
		 *
		 * @since 3.0.0
		 *
		 * @param array $views
		 *
		 * @return array
		 */
		public function views_pages( array $views ): array {
			$count_unassigned_quiz = LP_Course_DB::getInstance()->get_total_item_unassigned( LP_QUIZ_CPT );

			if ( $count_unassigned_quiz > 0 ) {
				$views['unassigned'] = sprintf(
					'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
					admin_url( 'edit.php?post_type=' . LP_QUIZ_CPT . '&unassigned=yes' ),
					isset( $_GET['unassigned'] ) ? 'current' : '',
					__( 'Unassigned', 'learnpress' ),
					$count_unassigned_quiz
				);
			}

			return $views;
		}

		/**
		 * Register quiz post type.
		 */
		public function args_register_post_type(): array {
			$args = apply_filters(
				'lp_quiz_post_type_args',
				array(
					'labels'              => array(
						'name'               => esc_html__( 'Quizzes', 'learnpress' ),
						'menu_name'          => esc_html__( 'Quizzes', 'learnpress' ),
						'singular_name'      => esc_html__( 'Quiz', 'learnpress' ),
						'add_new_item'       => esc_html__( 'Add A New Quiz', 'learnpress' ),
						'edit_item'          => esc_html__( 'Edit Quiz', 'learnpress' ),
						'all_items'          => esc_html__( 'Quizzes', 'learnpress' ),
						'view_item'          => esc_html__( 'View Quiz', 'learnpress' ),
						'add_new'            => esc_html__( 'New Quiz', 'learnpress' ),
						'update_item'        => esc_html__( 'Update Quiz', 'learnpress' ),
						'search_items'       => esc_html__( 'Search Quizzes', 'learnpress' ),
						'not_found'          => sprintf( __( 'You haven\'t had any quizzes yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_quiz' ) ),
						'not_found_in_trash' => esc_html__( 'There was no quiz found in the trash', 'learnpress' ),
					),
					'public'              => true,
					'publicly_queryable'  => true,
					'show_ui'             => true,
					'has_archive'         => false,
					'capability_type'     => LP_LESSON_CPT,
					'map_meta_cap'        => true,
					'show_in_menu'        => 'learn_press',
					'show_in_rest'        => true,
					'show_in_admin_bar'   => true,
					'show_in_nav_menus'   => true,
					'supports'            => array(
						'title',
						'editor',
						'revisions',
					),
					'hierarchical'        => false, // Quizzes have no parent-child hierarchy.
					'rewrite'             => array(
						'slug'         => 'quizzes',
						'hierarchical' => true,
						'with_front'   => false,
					),
					'exclude_from_search' => true,
				)
			);

			return $args;
		}

		/**
		 * Load data for quiz editor.
		 *
		 * @since 3.0.0
		 */
		public function data_quiz_editor() {
			if ( LP_QUIZ_CPT !== get_post_type() ) {
				return;
			}

			global $post;

			$quiz = LP_Quiz::get_quiz( $post->ID );

			$user_id                   = get_current_user_id();
			$default_new_question_type = get_user_meta( $user_id, '_learn_press_memorize_question_types', true ) ? get_user_meta( $user_id, '_learn_press_memorize_question_types', true ) : 'true_or_false';

			$hidden_questions          = get_post_meta( $post->ID, '_lp_hidden_questions', true );
			$hidden_questions_settings = get_post_meta( $post->ID, '_hidden_questions_settings', true );

			wp_localize_script(
				'learn-press-admin-quiz-editor',
				'lp_quiz_editor',
				apply_filters(
					'learn-press/admin-localize-quiz-editor',
					array(
						'root'          => array(
							'quiz_id'     => $post->ID,
							'ajax'        => admin_url( '' ),
							'action'      => 'admin_quiz_editor',
							'nonce'       => wp_create_nonce( 'learnpress_admin_quiz_editor' ),
							'types'       => LP_Question::get_types(),
							'default_new' => $default_new_question_type,
						),
						'chooseItems'   => array(
							'open'       => false,
							'addedItems' => array(),
							'items'      => array(),
						),
						'i18n'          => apply_filters(
							'learn-press/quiz-editor/i18n',
							array(
								'option'                 => esc_html__( 'Option', 'learnpress' ),
								'unique'                 => learn_press_uniqid(),
								'back'                   => esc_html__( 'Back', 'learnpress' ),
								'selected_items'         => esc_html__( 'Selected items', 'learnpress' ),
								'new_option'             => esc_html__( 'New Option', 'learnpress' ),
								'confirm_trash_question' => esc_html__( 'Do you want to move the "{{QUESTION_NAME}}" question to the trash?', 'learnpress' ),
								'question_labels'        => array(
									'singular' => esc_html__( 'Question', 'learnpress' ),
									'plural'   => esc_html__( 'Questions', 'learnpress' ),
								),
								'confirm_remove_blanks'  => esc_html__( 'Are you sure to remove all the blanks?', 'learnpress' ),
							)
						),
						'listQuestions' => array(
							'questions'                 => $quiz->quiz_editor_get_questions(),
							'hidden_questions'          => ! empty( $hidden_questions ) ? $hidden_questions : array(),
							'hidden_questions_settings' => $hidden_questions_settings ? $hidden_questions_settings : array(),
							'disableUpdateList'         => false,
							'supportAnswerOptions'      => learn_press_get_question_support_answer_options(),
						),
					)
				)
			);
		}

		/**
		 * Delete all questions assign to quiz.
		 *
		 * @since 3.0.0
		 *
		 * @param $post_id
		 */
		public function before_delete_quiz( $post_id ) {
			if ( get_post_type( $post_id ) !== LP_QUIZ_CPT ) {
				return;
			}

			$curd = new LP_Quiz_CURD();
			// remove question from course items
			$curd->delete( $post_id );
		}

		/**
		 * Admin editor
		 *
		 * @since 3.3.0
		 * @version 1.0.1
		 * @return void
		 */
		public function admin_editor() {
			global $post;

			$quiz_id       = $post->ID;
			$quizPostModel = QuizPostModel::find( $quiz_id, true );
			if ( ! $quizPostModel instanceof QuizPostModel ) {
				return;
			}

			do_action( 'learn-press/admin/edit-quiz/layout', $quizPostModel );
		}

		/**
		 * Query lp quizzes in admin via Post DB
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
				if ( ! $curren_screen || $curren_screen->id !== 'edit-' . LP_QUIZ_CPT ) {
					return $posts;
				}

				$post_type = $wp_query->get( 'post_type' );

				if ( empty( $post_type ) || $post_type !== LP_QUIZ_CPT ) {
					return $posts;
				}

				$posts_per_page = get_user_option( "edit_{$post_type}_per_page", get_current_user_id() );
				if ( empty( $posts_per_page ) ) {
					$posts_per_page = 20;
				}

				$paged   = max( 1, get_query_var( 'paged' ) );
				$author  = $wp_query->get( 'author' );
				$status  = $wp_query->get( 'post_status' );
				$search  = $wp_query->get( 's' );
				$month   = $wp_query->get( 'm' );
				$orderby = LP_Request::get_param( 'orderby', '', 'key' );
				$order   = LP_Request::get_param( 'order', '', 'key' );

				$filter              = new QuizPostFilter();
				$filter->only_fields = [ 'p.ID', 'p.post_title', 'p.post_author', 'p.post_date', 'p.post_date_gmt' ];
				$filter->field_count = 'p.ID';
				$filter->page        = $paged;
				$filter->limit       = $posts_per_page;
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

				if ( ! empty( $month ) && is_numeric( $month ) && strlen( (string) $month ) === 6 ) {
					$filter->where[] = $post_db->wpdb->prepare(
						'AND YEAR(p.post_date) = %d AND MONTH(p.post_date) = %d',
						substr( $month, 0, 4 ),
						substr( $month, 4, 2 )
					);
				}

				// Exclude status auto-draft
				$filter->where[] = $post_db->wpdb->prepare( 'AND p.post_status != %s', PostModel::STATUS_AUTO_DRAFT );

				// Add question count field
				$filter->only_fields[] = "(SELECT COUNT(*) FROM {$post_db->tb_lp_quiz_questions} qq_count
					WHERE qq_count.quiz_id = p.ID) AS question_count";

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

				if ( $orderby === 'course-name' ) {
					$filter->order_by = 'c.post_title';
				} elseif ( $orderby === 'question-count' ) {
					$filter->order_by = 'question_count';
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

				// Get lp quizzes
				$total_rows = 0;
				$lp_quizzes = $post_db->get_posts( $filter, $total_rows );

				$wp_query->post_count    = count( $lp_quizzes );
				$wp_query->found_posts   = $total_rows;
				$wp_query->max_num_pages = (int) ceil( $total_rows / $posts_per_page );
				$posts                   = $lp_quizzes;
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}

			return $posts;
		}

		/*public function posts_fields( $fields ): string {
			global $wpdb;

			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $fields;
			}

			$fields = ' DISTINCT ' . $fields;

			if ( $this->get_order_by() == 'question-count' ) {
				$fields .= ", (SELECT count(*) FROM {$wpdb->prefix}learnpress_quiz_questions qq WHERE {$wpdb->posts}.ID = qq.quiz_id ) as question_count";
			}

			return $fields;
		}

		public function posts_join_paged( $join ): string {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $join;
			}

			return $join;
		}

		public function posts_where_paged( $where ) {
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
					LP_QUIZ_CPT
				);
			}

			return $where;
		}

		public function posts_orderby( $order_by_statement ) {
			global $wpdb;

			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $order_by_statement;
			}

			$orderby = $this->get_order_by();
			$order   = $this->get_order_sort();

			if ( $order && $orderby ) {
				switch ( $orderby ) {
					case 'course-name':
						$order_by_statement = "post_title {$order}";
						break;
					case 'question-count':
						$order_by_statement = "question_count {$order}";
						break;
					default:
						$order_by_statement = "{$wpdb->posts}.post_title {$order}";
				}
			}

			return $order_by_statement;
		}*/

		/**
		 * Quiz assigned view.
		 *
		 * @since 3.0.0
		 */
		// public static function quiz_assigned() {
		//  learn_press_admin_view( 'meta-boxes/course/assigned.php' );
		// }

		public function meta_boxes() {
			return array(
				'quiz_assigned' => array(
					'title'    => esc_html__( 'Assigned', 'learnpress' ),
					'callback' => function ( $post ) {
						learn_press_admin_view( 'meta-boxes/course/assigned.php' );
					},
					'context'  => 'side',
					'priority' => 'high',
				),
				'quiz-editor'   => array(
					'title'    => esc_html__( 'Questions', 'learnpress' ),
					'callback' => array( $this, 'admin_editor' ),
					'context'  => 'normal',
					'priority' => 'high',
				),
			);
		}

		/**
		 * @return LP_Quiz_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
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
		public function save_post( int $post_id = 0, ?WP_Post $post = null, bool $is_update = false ) {
			// Clear cache get quiz by id
			$lpCache = new LP_Cache();
			$lpCache->clear( "quizPostModel/find/{$post_id}" );
			$lpCache->clear( "quizModel/find/{$post_id}" );

			// Clear cache get question_ids of quiz
			$lp_quiz_cache = LP_Quiz_Cache::instance();
			$lp_quiz_cache->clear( "$post_id/question_ids" );
		}
	}

	// LP_Quiz_Post_Type
	$quiz_post_type = LP_Quiz_Post_Type::instance();
}
