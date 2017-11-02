<?php
/**
 * Class LP_Quiz_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
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
		 * LP_Quiz_Post_Type constructor.
		 *
		 * @param $post_type
		 * @param mixed
		 */
		public function __construct( $post_type, $args = '' ) {

			$this->add_map_method( 'before_delete', 'delete_quiz_questions' );

			// hide View Quiz link on Quiz table action
			add_filter( 'page_row_actions', array( $this, 'remove_view_link' ), 10, 2 );

			// hide View Quiz link if not assigned to Course
			add_action( 'admin_footer', array( $this, 'hide_view_quiz_link_if_not_assigned' ) );

			add_action( 'edit_form_after_editor', array( $this, 'template_quiz_editor' ) );
			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_quiz_editor' ) );

			parent::__construct( $post_type, $args );
		}

		/**
		 * Template quiz editor v2.
		 *
		 * @since 3.0.0
		 */
		public function template_quiz_editor() {
			if ( LP_QUIZ_CPT !== get_post_type() ) {
				return;
			}
			learn_press_admin_view( 'quiz/editor' );
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

			$hidden_questions = get_post_meta( $post->ID, '_lp_hidden_questions', true );

			wp_localize_script( 'admin-quiz-editor', 'lp_quiz_editor', array(
				'root'          => array(
					'quiz_id' => $quiz->get_id(),
					'ajax'    => admin_url( '' ),
					'action'  => 'update_list_quiz_questions',
					'nonce'   => wp_create_nonce( 'learnpress_update_list_quiz_questions' ),
					'types'   => LP_Question_Factory::get_types()
				),
				'chooseItems'   => array(
					'open'       => false,
					'addedItems' => array(),
					'items'      => array()
				),
				'i18n'          => array(
					'option'         => __( 'Option', 'learnpress' ),
					'unique'         => learn_press_uniqid(),
					'back'           => __( 'Back', 'learnpress' ),
					'selected_items' => __( 'Selected items', 'learnpress' ),
				),
				'listQuestions' => array(
					'questions'        => $quiz->quiz_editor_get_questions(),
					'hidden_questions' => ! empty( $hidden_questions ) ? $hidden_questions : array()
				)
			) );
		}

		/**
		 * Delete all questions assign to quiz being deleted
		 *
		 * @param $post_id
		 */
		public function delete_quiz_questions( $post_id ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_quiz_questions
				WHERE quiz_id = %d
			", $post_id );
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_quiz_questions' );

			// delete quiz from course's section
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_section_items
				WHERE item_id = %d
			", $post_id );
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_section_items' );
		}

		/**
		 * Register quiz post type
		 */
		public function register() {
			register_post_type( LP_QUIZ_CPT,
				apply_filters( 'lp_quiz_post_type_args',
					array(
						'labels'             => array(
							'name'               => __( 'Quizzes', 'learnpress' ),
							'menu_name'          => __( 'Quizzes', 'learnpress' ),
							'singular_name'      => __( 'Quiz', 'learnpress' ),
							'add_new_item'       => __( 'Add New Quiz', 'learnpress' ),
							'edit_item'          => __( 'Edit Quiz', 'learnpress' ),
							'all_items'          => __( 'Quizzes', 'learnpress' ),
							'view_item'          => __( 'View Quiz', 'learnpress' ),
							'add_new'            => __( 'New Quiz', 'learnpress' ),
							'update_item'        => __( 'Update Quiz', 'learnpress' ),
							'search_items'       => __( 'Search Quizzes', 'learnpress' ),
							'not_found'          => sprintf( __( 'You have not got any quizzes yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_quiz' ) ),
							'not_found_in_trash' => __( 'No quiz found in Trash', 'learnpress' )
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
						'supports'           => array(
							'title',
							'editor',
							'revisions',
						),
						'hierarchical'       => true,
						'rewrite'            => array(
							'slug'         => 'quizzes',
							'hierarchical' => true,
							'with_front'   => false
						)
					)
				)
			);
		}

		/**
		 * Add question meta box settings.
		 */
		public function add_meta_boxes() {
			self::$metaboxes['quiz_settings'] = new RW_Meta_Box( self::settings_meta_box() );
			parent::add_meta_boxes();
		}

		/**
		 * @return mixed
		 */
		public static function settings_meta_box() {
			$prefix   = '_lp_';
			$meta_box = array(
				'title'      => __( 'General Settings', 'learnpress' ),
				'post_types' => LP_QUIZ_CPT,
				'context'    => 'normal',
				'priority'   => 'high',
				'fields'     => array(
					array(
						'name'    => __( 'Show questions', 'learnpress' ),
						'desc'    => __( 'Show list of questions while doing quiz as ordered numbers (1, 2, 3, etc).', 'learnpress' ),
						'id'      => "{$prefix}show_hide_question",
						'type'    => 'radio',
						'options' => array(
							'show' => __( 'Show', 'learnpress' ),
							'hide' => __( 'Hide', 'learnpress' )
						),
						'std'     => 'hide'
					),
					array(
						'name' => __( 'Review questions', 'learnpress' ),
						'id'   => "{$prefix}review_questions",
						'type' => 'yes-no',
						'desc' => __( 'Allow re-viewing questions after completing quiz.', 'learnpress' ),
						'std'  => 'no'
					),
					array(
						'name'       => __( 'Show correct answer', 'learnpress' ),
						'id'         => "{$prefix}show_result",
						'type'       => 'yes_no',
						'desc'       => __( 'Show correct answer when reviewing questions.', 'learnpress' ),
						'std'        => 'no',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => "{$prefix}review_questions",
									'compare' => '=',
									'value'   => 'yes'
								)
							)
						)
					),
					array(
						'name'         => __( 'Duration', 'learnpress' ),
						'desc'         => __( 'Duration of the quiz. Set 0 to disable.', 'learnpress' ),
						'id'           => "{$prefix}duration",
						'type'         => 'duration',//'number',
						'default_time' => 'minute',
						'min'          => 0,
						'std'          => 10,
					),
					array(
						'name'    => __( 'Passing Grade Type', 'learnpress' ),
						'desc'    => __( 'Requires user reached this point to pass the quiz.', 'learnpress' ),
						'id'      => "{$prefix}passing_grade_type",
						'type'    => 'radio',
						'options' => array(
							'no'         => __( 'No', 'learnpress' ),
							'percentage' => __( 'Percentage', 'learnpress' ),
							'point'      => __( 'Point', 'learnpress' )
						),
						'std'     => 'percentage',
					),
					array(
						'name' => __( 'Passing Grade (<span>%</span>)', 'learnpress' ),
						'desc' => __( 'Requires user reached this point to pass the quiz.', 'learnpress' ),
						'id'   => "{$prefix}passing_grade",
						'type' => 'number',
						'min'  => 0,
						'max'  => 100,
						'std'  => 80
					),
					array(
						'name' => __( 'Re-take', 'learnpress' ),
						'id'   => "{$prefix}retake_count",
						'type' => 'number',
						'desc' => __( 'How many times the user can re-take this quiz. Set to 0 to disable', 'learnpress' ),
						'min'  => 0,
						'std'  => 0
					),
					array(
						'name'       => __( 'Archive history', 'learnpress' ),
						'id'         => "{$prefix}archive_history",
						'type'       => 'yes_no',
						'desc'       => __( 'Archive quiz results for each time.', 'learnpress' ),
						'std'        => 'no',
						'visibility' => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => "{$prefix}retake_count",
									'compare' => '>',
									'value'   => '1'
								)
							)
						)
					),
//							array(
//								'name' => __( 'Show check answer', 'learnpress' ),
//								'id'   => "{$prefix}show_check_answer",
//								'type' => 'yes_no',
//								'desc' => __( 'Show button to check answer while doing quiz.', 'learnpress' ),
//								'std'  => 'no'
//							),
					array(
						'name' => __( 'Show check answer', 'learnpress' ),
						'id'   => "{$prefix}show_check_answer",
						'type' => 'text',
						'desc' => __( 'Show button to check answer while doing quiz ( 0 = Disabled, -1 = Unlimited, N = Number of check ).', 'learnpress' ),
						'std'  => '0'
					),
					array(
						'name' => __( 'Show hint', 'learnpress' ),
						'id'   => "{$prefix}show_hint",
						'type' => 'text',
						'desc' => __( 'Show button to hint answer while doing quiz ( 0 = Disabled, -1 = Unlimited, N = Number of check ).', 'learnpress' ),
						'std'  => '0'
					)
				)
			);

			return apply_filters( 'learn_press_quiz_general_meta_box', $meta_box );
		}


		/**
		 * Add columns to admin manage quiz page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {

			// append new column after title column
			$pos = array_search( 'title', array_keys( $columns ) );
			if ( false !== $pos && ! array_key_exists( 'lp_course', $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					array(
						'author'          => __( 'Author', 'learnpress' ),
						'lp_course'       => __( 'Course', 'learnpress' ),
						'num_of_question' => __( 'Questions', 'learnpress' ),
						'duration'        => __( 'Duration', 'learnpress' )
					),
					array_slice( $columns, $pos + 1 )
				);
			}
			unset ( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();
			if ( in_array( 'lp_teacher', $user->roles ) ) {
				unset( $columns['author'] );
			}

			return $columns;
		}

		/**
		 * Display content for custom column
		 *
		 * @param string $name
		 * @param int $post_id
		 */
		public function columns_content( $name, $post_id = 0 ) {
			global $post;
			switch ( $name ) {
				case 'lp_course':
					$courses = learn_press_get_item_courses( $post_id );
					if ( $courses ) {
						foreach ( $courses as $course ) {
							echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_course' => $course->ID ) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							if ( $this->_filter_course() ) {
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
				case 'num_of_question':
					if ( property_exists( $post, 'question_count' ) ) {
						$count = $post->question_count;
					} else {
						$quiz      = LP_Quiz::get_quiz( $post_id );
						$questions = $quiz->get_questions();
						$count     = sizeof( $questions );
					}

					printf(
						'<span class="lp-label-counter" title="%s">%s</span>',
						( $count ) ? sprintf( _nx( '%d question', '%d questions', $count, 'learnpress' ), $count ) : __( 'This quiz has no questions', 'learnpress' ),
						$count
					);
					break;
				case 'duration':
					$duration = learn_press_human_time_to_seconds( get_post_meta( $post_id, '_lp_duration', true ) );
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
		 * @param $fields
		 *
		 * @return string
		 */
		public function posts_fields( $fields ) {
			global $wpdb;
			if ( ! $this->_is_archive() ) {
				return $fields;
			}
			$fields = " DISTINCT " . $fields;
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
			global $wpdb;
			if ( $this->_filter_course() || ( $this->_get_orderby() == 'course-name' ) || $this->_get_search() ) {
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_section_items si ON {$wpdb->posts}.ID = si.item_id";
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id";
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

			if ( ! $this->_is_archive() ) {
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
			if ( ! $this->_is_archive() ) {
				return $order_by_statement;
			}
			global $wpdb;
			if ( isset ( $_GET['orderby'] ) && isset ( $_GET['order'] ) ) {
				switch ( $_GET['orderby'] ) {
					case 'course-name':
						$order_by_statement = "c.post_title {$_GET['order']}";
						break;
					case 'question-count':
						$order_by_statement = "question_count {$_GET['order']}";
						break;
					default:
						$order_by_statement = "{$wpdb->posts}.post_title {$_GET['order']}";
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
			$columns['author']          = 'author';
			$columns['lp_course']       = 'course-name';
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
		 * @return bool|int
		 */
		private function _filter_course() {
			return ! empty( $_REQUEST['filter_course'] ) ? absint( $_REQUEST['filter_course'] ) : false;
		}

		/**
		 * @return string
		 */
		private function _get_orderby() {
			return isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		}

		/**
		 * @return bool
		 */
		private function _get_search() {
			return isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : false;
		}

		/**
		 * Remove View Quiz link on dashboard Quiz list
		 *
		 * @param array $actions
		 *
		 * @return array $actions
		 */
		public function remove_view_link( $actions, $post ) {
			$post_id = $post->ID;
			if ( $post->post_type === LP_QUIZ_CPT && ! learn_press_get_item_course_id( $post->ID, $post->post_type ) ) {
				unset( $actions['view'] );
			}

			return $actions;
		}

		/**
		 * Hide view Quiz link
		 */
		public function hide_view_quiz_link_if_not_assigned() {
			$current_screen = get_current_screen();
			global $post;
			if ( ! $post ) {
				return;
			}
			if ( $current_screen->id === LP_QUIZ_CPT && ! learn_press_get_item_course_id( $post->ID, $post->post_type ) ) {
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
		 * @param $return
		 * @param $post_id
		 * @param $new_title
		 * @param $new_slug
		 * @param $post
		 *
		 * @return mixed
		 */
		public function get_sample_permalink_html( $return, $post_id, $new_title, $new_slug, $post ) {
			return $return;
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

	}

	$quiz_post_type = LP_Quiz_Post_Type::instance();
}
