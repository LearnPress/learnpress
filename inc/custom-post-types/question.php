<?php
/**
 * Class LP_Question_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();


if ( ! class_exists( 'LP_Question_Post_Type' ) ) {

	/**
	 * Class LP_Question_Post_Type
	 */
	class LP_Question_Post_Type extends LP_Abstract_Post_Type_Core {
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * @var RW_Meta_Box[]
		 */
		public static $metaboxes = array();

		/**
		 * LP_Question_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct( $post_type, $args = '' ) {

			add_action( 'admin_head', array( $this, 'init' ) );
			add_action( 'edit_form_after_editor', array( __CLASS__, 'template_question_editor' ) );
			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_question_editor' ) );

			add_filter( 'views_edit-' . LP_QUESTION_CPT, array( $this, 'views_pages' ), 10 );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );

			$this->add_map_method( 'before_delete', 'before_delete_question' )
				->add_map_method( 'save', 'save_question' );

			parent::__construct( $post_type, $args );
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
			$unassigned_items = learn_press_get_unassigned_questions();
			$text             = sprintf( __( 'Unassigned %s', 'learnpress' ), '<span class="count">(' . sizeof( $unassigned_items ) . ')</span>' );

			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				$views['unassigned'] = sprintf(
					'<a href="%s" class="current">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_QUESTION_CPT . '&unassigned=yes' ),
					$text
				);
			} else {
				$views['unassigned'] = sprintf(
					'<a href="%s">%s</a>',
					admin_url( 'edit.php?post_type=' . LP_QUESTION_CPT . '&unassigned=yes' ),
					$text
				);
			}

			return $views;
		}

		/**
		 * JS template for admin question editor.
		 *
		 * @since 3.0.0
		 */
		public static function template_question_editor() {
			if ( LP_QUESTION_CPT !== get_post_type() ) {
				return;
			}
			learn_press_admin_view( 'question/editor' );
		}

		/**
		 * Load data for question editor.
		 *
		 * @since 3.0.0
		 */
		public function data_question_editor() {

			if ( LP_QUESTION_CPT !== get_post_type() ) {
				return;
			}

			global $post, $pagenow;

			// add default answer for new question
			if ( $pagenow === 'post-new.php' ) {
				$question = LP_Question::get_question( $post->ID, array( 'type' => apply_filters( 'learn-press/default-add-new-question-type', 'true_or_false' ) ) );
				$answers  = $question->get_default_answers();
			} else {
				$question = LP_Question::get_question( $post->ID );
				$answers  = ( $question->get_data( 'answer_options' ) ? array_values( $question->get_data( 'answer_options' ) ) : array() );
			}

			if ( empty( $answers ) ) {
				$answers = array(
					array(
						'question_answer_id' => 0,
						'text'               => ''
					)
				);
			}

			wp_localize_script( 'learn-press-admin-question-editor', 'lp_question_editor',
				apply_filters(
					'learn-press/question-editor/localize-script',
					array(
						'root' => array(
							'id'                => $post->ID,
							'auto_draft'        => get_post_status( $post->ID ) == 'auto-draft',
							'open'              => false,
							'title'             => get_the_title( $post->ID ),
							'type'              => array(
								'key'   => $question->get_type(),
								'label' => $question->get_type_label()
							),
							'answers'           => apply_filters( 'learn-press/question-editor/question-answers-data', $answers, $post->ID, 0 ),
							'ajax'              => admin_url( '' ),
							'action'            => 'admin_question_editor',
							'nonce'             => wp_create_nonce( 'learnpress_admin_question_editor' ),
							'questionTypes'     => LP_Question::get_types(),
							'externalComponent' => apply_filters( 'learn-press/admin/external-js-component', array() )
						),
						'i18n' => apply_filters( 'learn-press/question-editor/i18n',
							array(
								'new_option_label' => __( 'New Option', 'learnpress' )
							)
						)
					)
				)
			);
		}

		/**
		 * Register question post type.
		 */
		public function register() {
			register_taxonomy( 'question_tag', array( LP_QUESTION_CPT ),
				array(
					'labels'            => array(
						'name'          => __( 'Question Tag', 'learnpress' ),
						'menu_name'     => __( 'Tag', 'learnpress' ),
						'singular_name' => __( 'Tag', 'learnpress' ),
						'add_new_item'  => __( 'Add New Tag', 'learnpress' ),
						'all_items'     => __( 'All Tags', 'learnpress' )
					),
					'public'            => true,
					'hierarchical'      => false,
					'show_ui'           => true,
					'show_admin_column' => 'true',
					'show_in_nav_menus' => true,
					'rewrite'           => array(
						'slug'         => 'question-tag',
						'hierarchical' => false,
						'with_front'   => false
					),
				)
			);
			add_post_type_support( 'question', 'comments' );

			return array(
				'labels'             => array(
					'name'               => __( 'Question Bank', 'learnpress' ),
					'menu_name'          => __( 'Question Bank', 'learnpress' ),
					'singular_name'      => __( 'Question', 'learnpress' ),
					'all_items'          => __( 'Questions', 'learnpress' ),
					'view_item'          => __( 'View Question', 'learnpress' ),
					'add_new_item'       => __( 'Add New Question', 'learnpress' ),
					'add_new'            => __( 'Add New', 'learnpress' ),
					'edit_item'          => __( 'Edit Question', 'learnpress' ),
					'update_item'        => __( 'Update Question', 'learnpress' ),
					'search_items'       => __( 'Search Questions', 'learnpress' ),
					'not_found'          => __( 'No questions found', 'learnpress' ),
					'not_found_in_trash' => __( 'No questions found in trash', 'learnpress' ),
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
				'supports'           => array( 'title', 'editor', 'revisions' ),
				'hierarchical'       => false,
				'rewrite'            => array( 'slug' => 'questions', 'hierarchical' => true, 'with_front' => false )
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
		 * @param $question_id
		 *
		 * @since 3.0.0
		 *
		 */
		public function before_delete_question( $question_id ) {
			// question curd
			$curd = new LP_Question_CURD();
			// remove question from course items
			$curd->delete( $question_id );
		}

		/**
		 * Add default answer when save new question action.
		 *
		 * @param $question_id
		 *
		 * @since 3.0.0
		 *
		 */
		public function save_question( $question_id ) {
			if ( get_post_status( $question_id ) == 'auto-draft' ) {
				$curd          = new LP_Question_CURD();
				$user_id       = learn_press_get_current_user_id();
				$question_type = 'true_or_false';


				update_post_meta( $question_id, '_lp_type', $question_type );
				get_user_meta( $user_id, '_learn_press_memorize_question_types', $question_type );

				$question = LP_Question::get_question( $question_id, array( 'type' => $question_type ) );
				$question->set_type( $question_type );

				$answers = $question->get_default_answers();

				// insert answers data in new question
				foreach ( $answers as $index => $answer ) {
					$insert = array(
						'question_id'  => $question_id,
						'answer_data'  => serialize( array(
								'text'    => stripslashes( $answer['text'] ),
								'value'   => isset( $answer['value'] ) ? stripslashes( $answer['value'] ) : '',
								'is_true' => ( $answer['is_true'] == 'yes' ) ? $answer['is_true'] : ''
							)
						),
						'answer_order' => $index + 1
					);
					$curd->add_answer( $question_type, $insert );
				}
			}
		}

		/**
		 * Add question meta box settings.
		 */
		public function add_meta_boxes() {
			self::$metaboxes['general_settings'] = new RW_Meta_Box( self::settings_meta_box() );
			parent::add_meta_boxes();
		}

		/**
		 * Register question meta box settings.
		 *
		 * @return mixed
		 */
		public static function settings_meta_box() {

			$des_explanation = __( 'Explain why an option is true and other is false.', 'learnpress' );
			$des_explanation .= __( 'The text will be shown when:', 'learnpress' );
			$des_explanation .= sprintf( '<br/> %s', __( '- User click on \'Check answer\' button.', 'learnpress' ) );
			$des_explanation .= sprintf( '<br/> %s', __( '- Answered question correct.', 'learnpress' ) );
			$des_explanation .= sprintf( '<br/> %s', __( '- Option \'Show Correct Answer\' is enable', 'learnpress' ) );

			$meta_box = array(
				'id'     => 'question_settings',
				'title'  => __( 'Settings', 'learnpress' ),
				'pages'  => array( LP_QUESTION_CPT ),
				'fields' => array(
					array(
						'name'  => __( 'Mark for this question', 'learnpress' ),
						'id'    => '_lp_mark',
						'type'  => 'number',
						'clone' => false,
						'desc'  => __( 'Mark for choosing the right answer.', 'learnpress' ),
						'min'   => 1,
						'std'   => 1
					),
					array(
						'name' => __( 'Question Explanation', 'learnpress' ),
						'id'   => '_lp_explanation',
						'type' => 'textarea',
						'desc' => $des_explanation,
						'std'  => null
					),
					array(
						'name' => __( 'Question Hint', 'learnpress' ),
						'id'   => '_lp_hint',
						'type' => 'textarea',
						'desc' => __( 'Instruction for user to select the right answer. The text will be shown when users click the \'Hint\' button.', 'learnpress' ),
						'std'  => null
					)
				)
			);

			return apply_filters( 'learn_press_question_meta_box_args', $meta_box );
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
				'author'    => __( 'Author', 'learnpress' ),
				LP_QUIZ_CPT => __( 'Quiz', 'learnpress' ),
				'type'      => __( 'Type', 'learnpress' )
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
				case 'lp_quiz':
					// question curd
					$curd = new LP_Question_CURD();
					// get quiz
					$quiz = $curd->get_quiz( $post_id );

					if ( $quiz ) {
						echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_quiz' => $quiz->ID ) ) ) . '">' . get_the_title( $quiz->ID ) . '</a>';
						echo '<div class="row-actions">';
						printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $quiz->ID ) ), __( 'Edit', 'learnpress' ) );
						echo "&nbsp;|&nbsp;";
						printf( '<a href="%s">%s</a>', get_the_permalink( $quiz->ID ), __( 'View', 'learnpress' ) );
						echo '</div></div>';
					} else {
						_e( 'Not assigned yet', 'learnpress' );
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
		public function posts_join_paged( $join ) {
			if ( ! $this->_is_archive() ) {
				return $join;
			}

			global $wpdb;

			if ( $quiz_id = $this->_filter_quiz() || ( $this->_get_orderby() == 'quiz-name' ) ) {
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

			if ( $posts_where_paged || ! $this->_is_archive() ) {
				return $where;
			}

			global $wpdb;

			if ( $quiz_id = $this->_filter_quiz() ) {
				$where .= $wpdb->prepare( " AND (q.ID = %d)", $quiz_id );
			}

			if ( 'yes' === LP_Request::get( 'unassigned' ) ) {
				global $wpdb;
				$where .= $wpdb->prepare( "
                    AND {$wpdb->posts}.ID NOT IN(
                        SELECT qq.question_id 
                        FROM {$wpdb->learnpress_quiz_questions} qq
                        INNER JOIN {$wpdb->posts} p ON p.ID = qq.question_id
                        WHERE p.post_type = %s
                    )
                ", LP_QUESTION_CPT );
			}

			$posts_where_paged = true;

			return $where;
		}

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
		public function posts_orderby( $order_by_statement ) {

			if ( ! $this->_is_archive() ) {
				return $order_by_statement;
			}
			$orderby = $this->_get_orderby();
			if ( $orderby && $order = $this->_get_order() ) {
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
		 * @return bool
		 */
		private function _is_archive() {
			global $pagenow, $post_type;
			if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( LP_QUESTION_CPT != $post_type ) ) {
				return false;
			}

			return true;
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

		/**
		 * @return LP_Question_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				$args            = array(
					'default_meta' => array(
						'_lp_mark' => 1,
						'_lp_type' => 'true_or_false'
					)
				);
				self::$_instance = new self( LP_QUESTION_CPT, $args );
			}

			return self::$_instance;
		}
	}

	// LP_Question_Post_Type
	$question_post_type = LP_Question_Post_Type::instance();

	// add meta box
	$question_post_type
		->add_meta_box( 'lesson_assigned', __( 'Assigned', 'learnpress' ), 'question_assigned', 'side', 'high' );
}