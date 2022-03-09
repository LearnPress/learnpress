<?php
/**
 * Class LP_Question_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

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
		 * LP_Question_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct( $post_type, $args = '' ) {
			add_action( 'wp_loaded', array( $this, 'wp_loaded' ) );
			add_action( 'admin_head', array( $this, 'init' ) );
			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_question_editor' ) );

			add_filter( 'views_edit-' . LP_QUESTION_CPT, array( $this, 'views_pages' ), 11 );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

			// $this->add_map_method( 'before_delete', 'before_delete_question' );

			parent::__construct( $post_type, $args );
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
			$type = $question->get_type();
			$answers = ( $question->get_data( 'answer_options' ) ? array_values( $question->get_data( 'answer_options' ) ) : array() );

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
								'confirm_remove_blanks' => esc_html__( 'Are you sure to remove all blanks?', 'learnpress' ),
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
						'add_new_item'  => esc_html__( 'Add New Tag', 'learnpress' ),
						'all_items'     => esc_html__( 'All Tags', 'learnpress' ),
					),
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => 'true',
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
				'labels'             => array(
					'name'               => esc_html__( 'Question Bank', 'learnpress' ),
					'menu_name'          => esc_html__( 'Question Bank', 'learnpress' ),
					'singular_name'      => esc_html__( 'Question', 'learnpress' ),
					'all_items'          => esc_html__( 'Questions', 'learnpress' ),
					'view_item'          => esc_html__( 'View Question', 'learnpress' ),
					'add_new_item'       => esc_html__( 'Add New Question', 'learnpress' ),
					'add_new'            => esc_html__( 'Add New', 'learnpress' ),
					'edit_item'          => esc_html__( 'Edit Question', 'learnpress' ),
					'update_item'        => esc_html__( 'Update Question', 'learnpress' ),
					'search_items'       => esc_html__( 'Search Questions', 'learnpress' ),
					'not_found'          => esc_html__( 'No questions found', 'learnpress' ),
					'not_found_in_trash' => esc_html__( 'No questions found in trash', 'learnpress' ),
				),
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => false,
				'capability_type'    => LP_LESSON_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => learn_press_user_maybe_is_a_teacher(),
				'supports'           => array( 'title', 'editor', 'revisions' ),
				'hierarchical'       => false,
				'rewrite'            => array(
					'slug'         => 'questions',
					'hierarchical' => true,
					'with_front'   => false,
				),
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
		 * Add default answer when save new question action.
		 *
		 * @param int     $post_id
		 * @param WP_Post $post
		 * @since 3.0.0
		 */
		public function save( int $post_id, WP_Post $post ) {
			/*$question_id = $post_id;

			$question_type = LP_Helper::sanitize_params_submitted( $_REQUEST['question-type'] ?? '' );

			if ( empty( $question_type ) ) {
				$types         = array_keys( LP_Question::get_types() );
				$question_type = reset( $types );
			}

			update_post_meta( $question_id, '_lp_type', $question_type );

			$question = LP_Question::get_question( $question_id );

			if ( $question->is_support( 'answer-options' ) ) {
				$question->create_default_answers();
			}*/
		}

		/**
		 * Admin editor
		 *
		 * @since 3.3.0
		 *
		 * @return bool|string
		 */
		public function admin_editor() {
			$question = LP_Question::get_question();

			if ( $question->is_support( 'answer-options' ) ) {
				echo learn_press_admin_view_content( 'question/editor' );
			}

			ob_start();
			do_action( 'learn-press/question-admin-editor', $question );
			echo ob_get_clean();
		}

		/**
		 * Add columns to admin manage question page
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {
			$pos         = array_search( 'title', array_keys( $columns ) );
			$new_columns = array(
				'instructor' => esc_html__( 'Author', 'learnpress' ),
				LP_QUIZ_CPT  => esc_html__( 'Quiz', 'learnpress' ),
				'type'       => esc_html__( 'Type', 'learnpress' ),
			);

			if ( false !== $pos && ! array_key_exists( LP_QUIZ_CPT, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					$new_columns,
					array_slice( $columns, $pos + 1 )
				);
			}

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
		 * Displaying the content of extra columns
		 *
		 * @param $name
		 * @param $post_id
		 */
		public function columns_content( $name, $post_id = 0 ) {
			switch ( $name ) {
				case 'instructor':
					$this->column_instructor( $post_id );
					break;
				case 'lp_quiz':
					$curd = new LP_Question_CURD();
					// get quiz
					$quiz = $curd->get_quiz( $post_id );

					if ( $quiz ) {
						echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_quiz' => $quiz->ID ) ) ) . '">' . get_the_title( $quiz->ID ) . '</a>';
						echo '<div class="row-actions">';
						printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $quiz->ID ) ), esc_html__( 'Edit', 'learnpress' ) );
						echo '&nbsp;|&nbsp;';
						printf( '<a href="%s">%s</a>', get_the_permalink( $quiz->ID ), esc_html__( 'View', 'learnpress' ) );
						echo '</div></div>';
					} else {
						esc_html_e( 'Not assigned yet', 'learnpress' );
					}
					break;
				case 'type':
					echo learn_press_question_name_from_slug( get_post_meta( $post_id, '_lp_type', true ) );
					break;
			}
		}

		/**
		 * Posts_join_paged.
		 *
		 * @param $join
		 *
		 * @return string
		 */
		public function posts_join_paged( $join ): string {
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

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
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

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
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
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns['author']      = 'author';
			$columns[ LP_QUIZ_CPT ] = 'quiz-name';

			return $columns;
		}

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
					'callback' => function( $post ) {
						learn_press_admin_view( 'meta-boxes/quiz/assigned.php' );
					},
					'context'  => 'side',
					'priority' => 'high',
				),
				'question-editor'   => array(
					'title'    => esc_html__( 'Answer Options', 'learnpress' ),
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
				self::$_instance = new self( LP_QUESTION_CPT );
			}

			return self::$_instance;
		}
	}

	$question_post_type = LP_Question_Post_Type::instance();

	// Todo: Nhamdv see to rewrite
	// $question_post_type
	// ->add_meta_box( 'lesson_assigned', esc_html__( 'Assigned', 'learnpress' ), 'question_assigned', 'side', 'high' )
	// ->add_meta_box( 'question-editor', esc_html__( 'Answer Options', 'learnpress' ), 'admin_editor', 'normal', 'high', 1 );
}
