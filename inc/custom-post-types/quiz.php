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
	final class LP_Quiz_Post_Type extends LP_Abstract_Post_Type_Core {

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
		public function __construct( $post_type, $args = '' ) {

			$this->add_map_method( 'before_delete', 'before_delete_quiz' );

			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_quiz_editor' ) );

			add_filter( 'views_edit-' . LP_QUIZ_CPT, array( $this, 'views_pages' ), 10 );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

			parent::__construct( $post_type, $args );
		}

		/**
		 * Add filters to lesson view.
		 *
		 * @since 3.0.0
		 *
		 * @param array $views
		 *
		 * @return mixed
		 */
		public function views_pages( $views ) {
			$unassigned_items = learn_press_get_unassigned_items( LP_QUIZ_CPT );
			$text             = sprintf( __( 'Unassigned %s', 'learnpress' ), '<span class="count">(' . sizeof( $unassigned_items ) . ')</span>' );

			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				$views['unassigned'] = sprintf(
					'<a href="%s" class="current">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_QUIZ_CPT . '&unassigned=yes' ),
					$text
				);
			} else {
				$views['unassigned'] = sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_QUIZ_CPT . '&unassigned=yes' ),
					$text
				);
			}

			return $views;
		}

		/**
		 * Register quiz post type.
		 */
		public function register() {
			register_post_type(
				LP_QUIZ_CPT,
				apply_filters(
					'lp_quiz_post_type_args',
					array(
						'labels'             => array(
							'name'               => esc_html__( 'Quizzes', 'learnpress' ),
							'menu_name'          => esc_html__( 'Quizzes', 'learnpress' ),
							'singular_name'      => esc_html__( 'Quiz', 'learnpress' ),
							'add_new_item'       => esc_html__( 'Add New Quiz', 'learnpress' ),
							'edit_item'          => esc_html__( 'Edit Quiz', 'learnpress' ),
							'all_items'          => esc_html__( 'Quizzes', 'learnpress' ),
							'view_item'          => esc_html__( 'View Quiz', 'learnpress' ),
							'add_new'            => esc_html__( 'New Quiz', 'learnpress' ),
							'update_item'        => esc_html__( 'Update Quiz', 'learnpress' ),
							'search_items'       => esc_html__( 'Search Quizzes', 'learnpress' ),
							'not_found'          => sprintf( __( 'You haven\'t had any quizzes yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_quiz' ) ),
							'not_found_in_trash' => esc_html__( 'No quiz found in Trash', 'learnpress' ),
						),
						'public'             => true,
						'publicly_queryable' => true,
						'show_ui'            => true,
						'has_archive'        => false,
						'capability_type'    => LP_LESSON_CPT,
						'map_meta_cap'       => true,
						'show_in_menu'       => 'learn_press',
						'show_in_rest'       => $this->is_support_gutenberg(),
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
				)
			);
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
								'confirm_trash_question' => esc_html__( 'Do you want to move question "{{QUESTION_NAME}}" to trash?', 'learnpress' ),
								'question_labels'        => array(
									'singular' => esc_html__( 'Question', 'learnpress' ),
									'plural'   => esc_html__( 'Questions', 'learnpress' ),
								),
								'confirm_remove_blanks'  => esc_html__( 'Are you sure to remove all blanks?', 'learnpress' ),
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
						$quiz      = LP_Quiz::get_quiz( $post_id );
						$questions = $quiz->get_questions();
						$count     = sizeof( $questions );
					}

					printf(
						'<span class="lp-label-counter' . ( ! $count ? ' disabled' : '' ) . '" title="%s">%s</span>',
						( $count ) ? sprintf( _n( '%d question', '%d questions', $count, 'learnpress' ), $count ) : __( 'This quiz has no questions', 'learnpress' ),
						$count
					);
					break;
				case 'duration':
					$duration = learn_press_human_time_to_seconds( get_post_meta( $post_id, '_lp_duration', true ) );
					if ( $duration > 0 ) {
						echo gmdate( 'H:i:s', $duration );
					} else {
						echo '-';
					}
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
		public function posts_fields( $fields ) {
			global $wpdb;

			if ( ! $this->_is_archive() ) {
				return $fields;
			}

			$fields = ' DISTINCT ' . $fields;

			if ( $this->_get_orderby() == 'question-count' ) {
				$fields .= ", (SELECT count(*) FROM {$wpdb->prefix}learnpress_quiz_questions qq WHERE {$wpdb->posts}.ID = qq.quiz_id ) as question_count";
			}

			return $fields;
		}

		/**
		 * @param $join
		 *
		 * @return string
		 */
		public function posts_join_paged( $join ) {
			if ( ! $this->_is_archive() ) {
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
			if ( ! $this->_is_archive() ) {
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

			if ( ! $this->_is_archive() ) {
				return $order_by_statement;
			}

			if ( $orderby = $this->_get_orderby() && $order = $this->_get_order() ) {
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
		 * @return bool
		 */
		private function _is_archive() {
			global $pagenow, $post_type;

			if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( LP_QUIZ_CPT != $post_type ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Quiz assigned view.
		 *
		 * @since 3.0.0
		 */
		public static function quiz_assigned() {
			learn_press_admin_view( 'meta-boxes/course/assigned.php' );
		}

		/**
		 * @return LP_Quiz_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self( LP_QUIZ_CPT, '' );
			}

			return self::$_instance;
		}

		/**
		 * Save Post type Quiz
		 * @author tungnx
		 * @version 1.0.0
		 * @since 4.0.0
		 */
		public function save() {
			if ( empty( $_REQUEST['post_type'] ) || $_REQUEST['post_type'] !== LP_QUIZ_CPT || empty( $_REQUEST['post_ID'] ) ) {
				return;
			}

			$post_quiz_id = LP_Helper::sanitize_params_submitted( $_REQUEST['post_ID'] );

			wp_cache_delete( "lp/quiz/$post_quiz_id/question_ids", 'lp/quiz' );
		}
	}

	// LP_Quiz_Post_Type
	$quiz_post_type = LP_Quiz_Post_Type::instance();

	// add meta box
	$quiz_post_type
		->add_meta_box( 'lesson_assigned', esc_html__( 'Assigned', 'learnpress' ), 'quiz_assigned', 'side', 'high' )
		->add_meta_box( 'quiz-editor', esc_html__( 'Questions', 'learnpress' ), 'admin_editor', 'normal', 'high' );
}
