<?php
/**
 * Class LP_Question_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

use LearnPress\Databases\PostDB;
use LearnPress\Filters\QuestionPostFilter;
use LearnPress\Models\PostModel;
use LearnPress\Models\Question\QuestionPostModel;
use LearnPress\Models\WPTables\QuestionsTable;

defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Question_Post_Type' ) ) {

	/**
	 * Class LP_Question_Post_Type
	 */
	class LP_Question_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * @var string
		 */
		protected $_post_type = LP_QUESTION_CPT;

		/**
		 * @var string
		 */
		protected $_screen_list = 'edit-' . LP_QUESTION_CPT;

		/**
		 * LP_Question_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct() {
			//add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
			add_action( 'admin_head', array( $this, 'init' ) );
			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_question_editor' ) );

			add_filter( 'views_edit-' . LP_QUESTION_CPT, array( $this, 'views_pages' ), 11 );
			add_action( 'posts_pre_query', array( $this, 'posts_pre_query' ), 999, 2 );
			// add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

			// $this->add_map_method( 'before_delete', 'before_delete_question' );

			parent::__construct();
		}

		/**
		 * Declare class name of table list questions.
		 *
		 * @param string $class_name
		 * @param array  $args
		 *
		 * @return string
		 * @since 4.2.9.5
		 */
		public function wp_list_table_class_name( $class_name, $args ) {
			if ( $this->check_class_name_handle_table( $args['screen'] ) ) {
				$class_name = QuestionsTable::class;
			}

			return $class_name;
		}

		/**
		 * Add question types support answer options
		 *
		 * @since 3.3.0
		 */
		public function wp_loaded() {
			$default_support_options = apply_filters(
				'learn-press/default-question-types-support-answer-options',
				array(
					'true_or_false',
					'single_choice',
					'multi_choice',
					'fill_in_blanks',
				)
			);

			foreach ( $default_support_options as $type ) {
				LP_Global::add_object_feature( 'question.' . $type, 'answer-options', 'yes' );
			}
		}

		/**
		 * Add filters to lesson view.
		 *
		 * @param array $views
		 *
		 * @return array
		 * @since 3.0.1
		 * @version 1.0.1
		 * @editor tungnx
		 */
		public function views_pages( array $views ): array {
			$lp_question_db = LP_Question_DB::getInstance();

			try {
				$filter = new LP_Question_Filter();
				/*if ( ! current_user_can( 'administrator' ) ) {
					$filter->where[] = $lp_question_db->wpdb->prepare( 'AND post_author = %d', get_current_user_id() );
				}*/

				$count_unassigned_questions = $lp_question_db->get_total_question_unassigned( $filter );

				if ( $count_unassigned_questions > 0 ) {
					$views['unassigned'] = sprintf(
						'<a href="%s" class="%s">%s <span class="count">(%d)</span></a>',
						admin_url( 'edit.php?post_type=' . LP_QUESTION_CPT . '&unassigned=yes' ),
						isset( $_GET['unassigned'] ) ? 'current' : '',
						__( 'Unassigned', 'learnpress' ),
						$count_unassigned_questions
					);
				}
			} catch ( Throwable $e ) {

			}

			return $views;
		}

		/**
		 * Load data for question editor.
		 *
		 * @since 3.0.0
		 */
		public function data_question_editor() {
			global $post;

			if ( LP_QUESTION_CPT !== get_post_type() ) {
				return;
			}

			$question = LP_Question::get_question( $post->ID );
			$type     = $question->get_type();
			$answers  = ( $question->get_data( 'answer_options' ) ? array_values( $question->get_data( 'answer_options' ) ) : array() );

			if ( empty( $answers ) ) {
				$answers = array(
					array(
						'order'              => 1,
						'question_answer_id' => 0,
						'is_true'            => 'yes',
						'title'              => esc_html__( 'Correct', 'learnpress' ),
					),
					array(
						'order'              => 2,
						'question_answer_id' => 0,
						'is_true'            => '',
						'title'              => esc_html__( 'Incorrect', 'learnpress' ),
					),
				);

				$type = 'true_or_false';
			}

			wp_localize_script(
				'learn-press-admin-question-editor',
				'lp_question_editor',
				apply_filters(
					'learn-press/question-editor/localize-script',
					array(
						'root' => array(
							'id'                   => $post->ID,
							'auto_draft'           => get_post_status( $post->ID ) == 'auto-draft',
							'open'                 => false,
							'title'                => get_the_title( $post->ID ),
							'type'                 => array(
								'key'   => $type,
								'label' => learn_press_question_types()[ $type ],
							),
							'answers'              => apply_filters( 'learn-press/question-editor/question-answers-data', $answers, $post->ID, 0 ),
							'ajax'                 => admin_url( '' ),
							'action'               => 'admin_question_editor',
							'nonce'                => wp_create_nonce( 'learnpress_admin_question_editor' ),
							'questionTypes'        => LP_Question::get_types(),
							'supportAnswerOptions' => learn_press_get_question_support_answer_options(),
						),
						'i18n' => apply_filters(
							'learn-press/question-editor/i18n',
							array(
								'new_option_label'      => esc_html__( 'New Option', 'learnpress' ),
								'confirm_remove_blanks' => esc_html__( 'Are you sure to remove all the blanks?', 'learnpress' ),
							)
						),
					)
				)
			);
		}

		/**
		 * Register question post type.
		 */
		public function args_register_post_type(): array {
			register_taxonomy(
				'question_tag',
				array( LP_QUESTION_CPT ),
				array(
					'labels'            => array(
						'name'          => esc_html__( 'Question Tag', 'learnpress' ),
						'menu_name'     => esc_html__( 'Tag', 'learnpress' ),
						'singular_name' => esc_html__( 'Tag', 'learnpress' ),
						'add_new_item'  => esc_html__( 'Add A New Tag', 'learnpress' ),
						'all_items'     => esc_html__( 'All Tags', 'learnpress' ),
					),
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => false,
					'show_in_nav_menus' => true,
					'rewrite'           => array(
						'slug'         => 'question-tag',
						'hierarchical' => false,
						'with_front'   => false,
					),
				)
			);
			add_post_type_support( 'question', 'comments' );

			return array(
				'labels'              => array(
					'name'               => esc_html__( 'Question Bank', 'learnpress' ),
					'menu_name'          => esc_html__( 'Question Bank', 'learnpress' ),
					'singular_name'      => esc_html__( 'Question', 'learnpress' ),
					'all_items'          => esc_html__( 'Questions', 'learnpress' ),
					'view_item'          => esc_html__( 'View Question', 'learnpress' ),
					'add_new_item'       => esc_html__( 'Add A New Question', 'learnpress' ),
					'add_new'            => esc_html__( 'Add New', 'learnpress' ),
					'edit_item'          => esc_html__( 'Edit Question', 'learnpress' ),
					'update_item'        => esc_html__( 'Update Question', 'learnpress' ),
					'search_items'       => esc_html__( 'Search Questions', 'learnpress' ),
					'not_found'          => esc_html__( 'No questions found', 'learnpress' ),
					'not_found_in_trash' => esc_html__( 'There was no questions found in the trash', 'learnpress' ),
				),
				'public'              => true,
				'publicly_queryable'  => true,
				'show_ui'             => true,
				'has_archive'         => false,
				'capability_type'     => LP_LESSON_CPT,
				'map_meta_cap'        => true,
				'show_in_menu'        => 'learn_press',
				'show_in_admin_bar'   => true,
				'show_in_nav_menus'   => true,
				'show_in_rest'        => learn_press_user_maybe_is_a_teacher(),
				'supports'            => array( 'title', 'editor', 'revisions' ),
				'hierarchical'        => false,
				'rewrite'             => array(
					'slug'         => 'questions',
					'hierarchical' => true,
					'with_front'   => false,
				),
				'exclude_from_search' => true,
			);
		}

		/**
		 * Init question.
		 *
		 * @since 3.0.0
		 */
		public function init() {
			$hidden = get_user_meta( get_current_user_id(), 'manageedit-lp_questioncolumnshidden', true );

			if ( ! is_array( $hidden ) && empty( $hidden ) ) {
				update_user_meta( get_current_user_id(), 'manageedit-lp_questioncolumnshidden', array( 'taxonomy-question-tag' ) );
			}
		}

		/**
		 * Remove question from quiz items.
		 *
		 * @param  $question_id
		 *
		 * @since 3.0.0
		 */
		public function before_delete_question( int $question_id = 0 ) {
			$curd = new LP_Question_CURD();

			$curd->delete( $question_id );
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
		public function save_post( int $post_id, ?WP_Post $post = null, bool $is_update = false ) {
			// Clear cache get question by id
			$lpCache = new LP_Cache();
			$lpCache->clear( "questionPostModel/find/{$post_id}" );
			$lpCache->clear( "questionModel/find/{$post_id}" );
		}

		/**
		 * Admin editor
		 *
		 * @since 3.3.0
		 *
		 * @return void
		 */
		public function admin_editor() {
			global $post;

			$question_id       = $post->ID;
			$questionPostModel = QuestionPostModel::find( $question_id, true );
			if ( ! $questionPostModel instanceof QuestionPostModel ) {
				return;
			}

			do_action( 'learn-press/admin/edit-question/layout', $questionPostModel );
		}

		/**
		 * Query lp questions in admin via Post DB
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
				if ( ! $curren_screen || $curren_screen->id !== 'edit-' . LP_QUESTION_CPT ) {
					return $posts;
				}

				$post_type = $wp_query->get( 'post_type' );

				if ( empty( $post_type ) || $post_type !== LP_QUESTION_CPT ) {
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
				$quiz_id = LP_Request::get_param( 'filter_quiz' );

				$filter              = new QuestionPostFilter();
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

				if ( ! empty( $month ) && is_numeric( $month ) && strlen( (string) $month ) === 6 ) {
					$filter->where[] = $post_db->wpdb->prepare(
						'AND YEAR(p.post_date) = %d AND MONTH(p.post_date) = %d',
						substr( $month, 0, 4 ),
						substr( $month, 4, 2 )
					);
				}

				// Exclude status auto-draft
				$filter->where[] = $post_db->wpdb->prepare( 'AND p.post_status != %s', PostModel::STATUS_AUTO_DRAFT );

				// Join quiz questions when needed
				if ( $quiz_id || $orderby === 'quiz-name' ) {
					$filter->join[] = "LEFT JOIN {$post_db->wpdb->prefix}learnpress_quiz_questions qq ON p.ID = qq.question_id";
					$filter->join[] = "LEFT JOIN {$post_db->wpdb->posts} qz ON qz.ID = qq.quiz_id";
				}

				// Filter by quiz
				if ( $quiz_id ) {
					$filter->where[] = $post_db->wpdb->prepare( 'AND qz.ID = %d', $quiz_id );
				}

				// Filter unassigned
				if ( 'yes' === LP_Request::get_param( 'unassigned', '', 'key' ) ) {
					$filter->where[] = "AND p.ID NOT IN(
						SELECT qq_un.question_id
						FROM {$post_db->wpdb->learnpress_quiz_questions} qq_un
					)";
				}

				if ( $orderby === 'quiz-name' ) {
					$filter->order_by = 'qz.post_title';
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

				// Get lp questions
				$total_rows   = 0;
				$lp_questions = $post_db->get_posts( $filter, $total_rows );

				$wp_query->post_count  = count( $lp_questions );
				$wp_query->found_posts = $total_rows;
				$posts                 = $lp_questions;
			} catch ( Throwable $e ) {
				LP_Debug::error_log( $e );
			}

			return $posts;
		}

		/*public function posts_join_paged( $join ): string {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $join;
			}

			global $wpdb;

			$quiz_id = $this->_filter_quiz();
			if ( $quiz_id || $this->get_order_by() == 'quiz-name' ) {
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_quiz_questions qq ON {$wpdb->posts}.ID = qq.question_id";
				$join .= " LEFT JOIN {$wpdb->posts} q ON q.ID = qq.quiz_id";
			}

			return $join;
		}

		public function posts_where_paged( $where ) {
			static $posts_where_paged = false;

			if ( $posts_where_paged || ! $this->is_page_list_posts_on_backend() ) {
				return $where;
			}

			global $wpdb;
			$quiz_id = $this->_filter_quiz();

			if ( $quiz_id ) {
				$where .= $wpdb->prepare( ' AND (q.ID = %d)', $quiz_id );
			}

			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				global $wpdb;
				$where .= " AND {$wpdb->posts}.ID NOT IN(
						SELECT qq.question_id
						FROM {$wpdb->learnpress_quiz_questions} qq
					)
				";
			}

			return $where;
		}

		public function posts_orderby( $order_by_statement ): string {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $order_by_statement;
			}

			$orderby = $this->get_order_by();
			$order   = $this->get_order_sort();

			if ( $orderby && $order ) {
				switch ( $orderby ) {
					case 'quiz-name':
						$order_by_statement = "q.post_title {$order}";
						break;
					case 'date':
						$order_by_statement = "post_date {$order}";
						break;
				}
			}

			return $order_by_statement;
		}*/

		/**
		 * @return bool|int
		 */
		private function _filter_quiz() {
			return LP_Request::get_int( 'filter_quiz' );
		}

		/**
		 * Quiz assigned view.
		 *
		 * @since 3.0.0
		 */
		public static function question_assigned() {
			learn_press_admin_view( 'meta-boxes/quiz/assigned.php' );
		}

		public function meta_boxes() {
			return array(
				'question_assigned' => array(
					'title'    => esc_html__( 'Assigned', 'learnpress' ),
					'callback' => function ( $post ) {
						learn_press_admin_view( 'meta-boxes/quiz/assigned.php' );
					},
					'context'  => 'side',
					'priority' => 'high',
				),
				'question-editor'   => array(
					'title'    => esc_html__( 'Questions Options', 'learnpress' ),
					'callback' => array( $this, 'admin_editor' ),
					'context'  => 'normal',
					'priority' => 'high',
				),
			);
		}

		/**
		 * @return LP_Question_Post_Type|null
		 *
		 * @editor tungnx
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				/*
				$args            = array(
					'default_meta' => array(
						'_lp_mark' => 1,
						'_lp_type' => 'true_or_false',
					),
				);*/
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}

	$question_post_type = LP_Question_Post_Type::instance();
}
