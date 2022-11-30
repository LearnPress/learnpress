<?php
/**
 * Class LP_Quiz_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 4.0.0
 */

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
		 * LP_Quiz_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct() {

			//$this->add_map_method( 'before_delete', 'before_delete_quiz' );

			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_quiz_editor' ) );

			add_filter( 'views_edit-' . LP_QUIZ_CPT, array( $this, 'views_pages' ), 10 );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

			parent::__construct();
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
					'labels'             => array(
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
					'public'             => true,
					'publicly_queryable' => true,
					'show_ui'            => true,
					'has_archive'        => false,
					'capability_type'    => LP_LESSON_CPT,
					'map_meta_cap'       => true,
					'show_in_menu'       => 'learn_press',
					'show_in_rest'       => true,
					'show_in_admin_bar'  => true,
					'show_in_nav_menus'  => true,
					'supports'           => array(
						'title',
						'editor',
						'revisions',
					),
					'hierarchical'       => true,
					'rewrite'            => array(
						'slug'         => 'quizzes',
						'hierarchical' => true,
						'with_front'   => false,
					),
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
		 *
		 * @return bool|string
		 */
		public function admin_editor() {
			$quiz = LP_Quiz::get_quiz();

			echo learn_press_admin_view_content( 'quiz/editor' );
		}

		/**
		 * Add columns to admin manage quiz page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {
			$pos = array_search( 'title', array_keys( $columns ) );

			if ( false !== $pos && ! array_key_exists( LP_COURSE_CPT, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					array(
						'instructor'      => esc_html__( 'Author', 'learnpress' ),
						LP_COURSE_CPT     => esc_html__( 'Course', 'learnpress' ),
						'num_of_question' => esc_html__( 'Questions', 'learnpress' ),
						'duration'        => esc_html__( 'Duration', 'learnpress' ),
					),
					array_slice( $columns, $pos + 1 )
				);
			}

			unset( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();

			if ( in_array( 'lp_teacher', $user->roles ) ) {
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
			global $post;

			switch ( $name ) {
				case 'instructor':
					$this->column_instructor( $post_id );
					break;
				case 'lp_course':
					$this->_get_item_course( $post_id );
					break;
				case 'num_of_question':
					if ( property_exists( $post, 'question_count' ) ) {
						$count = $post->question_count;
					} else {
						$quiz  = LP_Quiz::get_quiz( $post_id );
						$count = $quiz->count_questions();
					}

					printf(
						'<span class="lp-label-counter' . ( ! $count ? ' disabled' : '' ) . '" title="%s">%s</span>',
						( $count ) ? sprintf( _n( '%d question', '%d questions', $count, 'learnpress' ), $count ) : __( 'This quiz has no questions', 'learnpress' ),
						$count
					);
					break;
				case 'duration':
					$duration_str = get_post_meta( $post_id, '_lp_duration', true );
					$duration     = (int) $duration_str;

					if ( $duration > 0 ) {
						$duration_str    .= 's';
						$duration_str_arr = explode( ' ', $duration_str );
						$type_time        = '';

						if ( is_array( $duration_str_arr ) && ! empty( $duration_str_arr ) && count( $duration_str_arr ) >= 2 ) {
							switch ( $duration_str_arr[1] ) {
								case 'hours':
									$type_time = __( 'hours', 'learnpress' );
									break;
								case 'minutes':
									$type_time = __( 'minutes', 'learnpress' );
									break;
								case 'days':
									$type_time = __( 'days', 'learnpress' );
									break;
								case 'weeks':
									$type_time = __( 'weeks', 'learnpress' );
									break;
							}

							$duration_str = sprintf( '%1$s %2$s', $duration_str_arr[0], $type_time );
						}
					} else {
						$duration_str = '--';
					}

					echo esc_html( $duration_str );
					break;
				case 'preview':
					printf(
						'<input type="checkbox" class="learn-press-checkbox learn-press-toggle-item-preview" %s value="%s" data-nonce="%s" />',
						get_post_meta( $post_id, '_lp_preview', true ) == 'yes' ? ' checked="checked"' : '',
						$post_id,
						wp_create_nonce( 'learn-press-toggle-item-preview' )
					);
					break;
				default:
					break;

			}
		}

		/**
		 * @param $fields
		 *
		 * @return string
		 */
		public function posts_fields( $fields ): string {
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

		/**
		 * @param $join
		 *
		 * @return string
		 */
		public function posts_join_paged( $join ): string {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $join;
			}

			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
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

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
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
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns['instructor']      = 'author';
			$columns[ LP_COURSE_CPT ]   = 'course-name';
			$columns['num_of_question'] = 'question-count';

			return $columns;
		}

		/**
		 * Quiz assigned view.
		 *
		 * @since 3.0.0
		 */
		// public static function quiz_assigned() {
		// 	learn_press_admin_view( 'meta-boxes/course/assigned.php' );
		// }

		public function meta_boxes() {
			return array(
				'quiz_assigned' => array(
					'title'    => esc_html__( 'Assigned', 'learnpress' ),
					'callback' => function( $post ) {
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
		 * Save Post type Quiz
		 *
		 * @author tungnx
		 * @version 1.0.0
		 * @since 4.0.0
		 */
		public function save( int $post_id, WP_Post $post ) {
			$lp_quiz_cache = LP_Quiz_Cache::instance();

			// Clear cache get question_ids of quiz
			$lp_quiz_cache->clear( "$post_id/question_ids" );
		}
	}

	// LP_Quiz_Post_Type
	$quiz_post_type = LP_Quiz_Post_Type::instance();
}
