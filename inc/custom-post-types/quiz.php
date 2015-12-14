<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Quiz_Post_Type' ) ) {

	// Base class for custom post type to extends
	learn_press_include( 'custom-post-types/abstract.php' );

	// class LP_Quiz_Post_Type
	final class LP_Quiz_Post_Type extends LP_Abstract_Post_Type {
		function __construct() {

			add_action( 'admin_head', array( $this, 'enqueue_script' ) );
			add_filter( 'manage_lp_quiz_posts_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_lp_quiz_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
			add_action( 'save_post_lpr_quiz', array( $this, 'update_quiz_meta' ) );
			add_action( 'admin_head', array( $this, 'print_js_template' ) );

			//add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
			//add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			//add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			//add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			//add_filter( 'manage_edit-lpr_quiz_sortable_columns', array( $this, 'columns_sortable' ) );

			add_action( 'save_post', array( $this, 'save' ) );

			parent::__construct();

		}

		/**
		 * Print js template
		 */
		static function print_js_template() {
			learn_press_admin_view( 'meta-boxes/quiz/js-template.php' );
		}

		static function save() {

		}

		/**
		 * Register quiz post type
		 */
		static function register_post_type() {
			register_post_type( LP()->quiz_post_type,
				apply_filters( 'lpr_quiz_post_type_args',
					array(
						'labels'             => array(
							'name'          => __( 'Quizzes', 'learn_press' ),
							'menu_name'     => __( 'Quizzes', 'learn_press' ),
							'singular_name' => __( 'Quiz', 'learn_press' ),
							'add_new_item'  => __( 'Add New Quiz', 'learn_press' ),
							'edit_item'     => __( 'Edit Quiz', 'learn_press' ),
							'all_items'     => __( 'Quizzes', 'learn_press' ),
						),
						'public'             => true,
						'publicly_queryable' => true,
						'show_ui'            => true,
						'has_archive'        => false,
						'capability_type'    => LP()->lesson_post_type,
						'map_meta_cap'       => true,
						'show_in_menu'       => 'learn_press',
						'show_in_admin_bar'  => true,
						'show_in_nav_menus'  => true,
						'supports'           => array(
							'title',
							'editor',
							'revisions',
							'author'
						),
						'hierarchical'       => true,
						'rewrite'            => array( 'slug' => 'quizzes', 'hierarchical' => true, 'with_front' => false )
					)
				)
			);
		}

		static function add_meta_boxes() {

			$prefix                                        = '_lp_';
			$meta_box                                      = apply_filters(
				'learn_press_quiz_question_meta_box_args',
				array(
					'title'      => __( 'Questions', 'learn_press' ),
					'post_types' => LP()->quiz_post_type,
					'id'		=> 'questions',
					'fields'     => array(
						array(
							'name' => __( '', 'learn_press' ),
							'desc' => __( '', 'learn_press' ),
							'id'   => "{$prefix}questions",
							'type' => 'quiz_questions'
						)
					)
				)
			);
			$GLOBALS['learn_press_quiz_question_meta_box'] = new RW_Meta_Box( $meta_box );

			new RW_Meta_Box(
				array(
					'title'      => __( 'General Settings', 'learn_press' ),
					'post_types' => LP()->quiz_post_type,
					'context'    => 'normal',
					'priority'   => 'high',
					'fields'     => array(
						array(
							'name' => __( 'Duration', 'learn_press' ),
							'desc' => __( 'Duration of the quiz (in minutes). Auto submits when expire. Set 0 to disable.', 'learn_press' ),
							'id'   => "{$prefix}duration",
							'type' => 'number',
							'min'  => 0,
							'std'  => 10
						),
						array(
							'name' => __( 'Re-take', 'learn_press' ),
							'id'   => "{$prefix}retake_count",
							'type' => 'number',
							'desc' => __( 'How many times the user can re-take this quiz. Set to 0 to disable', 'learn_press' ),
							'min'  => 0
						),
						array(
							'name' => __( 'Show correct answer', 'learn_press' ),
							'id'   => "{$prefix}show_result",
							'type' => 'checkbox',
							'desc' => __( 'Show the correct answer in result of the quiz.', 'learn_press' ),
							'std'  => 0
						),
						array(
							'name' => __( 'Show question answer immediately', 'learn_press' ),
							'id'   => "{$prefix}show_question_answer",
							'type' => 'checkbox',
							'desc' => __( 'Show the correct answer and explanation (if exists) of the question right after student answered.', 'learn_press' ),
							'std'  => 0
						),
					)
				)
			);
		}

		function enqueue_script() {
			if ( LP()->quiz_post_type != get_post_type() ) return;
			ob_start();
			?>
			<script>
				var form = $('#post');

				form.submit(function (evt) {
					var $title = $('#title'),
						is_error = false;
					window.learn_press_before_update_quiz_message = [];
					if (0 == $title.val().length) {
						window.learn_press_before_update_quiz_message.push('<?php _e( 'Please enter the title of the quiz', 'learn_press' );?>');
						$title.focus();
						is_error = true;
					}

					/* hook */
					is_error = form.triggerHandler('learn_press_question_before_update') === false;

					if (window.learn_press_before_update_quiz_message.length /*true == is_error*/) {
						if (window.learn_press_before_update_quiz_message.length) {
							alert("Error: \n" + window.learn_press_before_update_quiz_message.join("\n\n"))
						}
						evt.preventDefault();
						return false;
					}
				});
			</script>
			<?php
			$script = ob_get_clean();
			$script = preg_replace( '!</?script>!', '', $script );
			learn_press_enqueue_script( $script );

			ob_start();
			?>
			<script type="text/html" id="tmpl-form-quick-add-question">
				<div id="lpr-form-quick-add-question" class="lpr-quick-add-form">
					<input type="text">
					<select class="lpr-question-types lpr-select2" name="lpr_question[type]" id="lpr-quiz-question-type">
						<?php if ( $questions = lpr_get_question_types() ): ?>
							<?php foreach ( $questions as $type => $name ): ?>
								<option value="<?php echo $type; ?>"><?php echo $name; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<button class="button" data-action="add" type="button"><?php _e( 'Add [Enter]', 'learn_press' ); ?></button>
					<button data-action="cancel" class="button" type="button"><?php _e( 'Cancel [ESC]', 'learn_press' ); ?></button>
					<span class="lpr-ajaxload">...</span>
				</div>
			</script>
			<?php
			$js_template = ob_get_clean();
			learn_press_enqueue_script( $js_template, true );
		}

		/**
		 * Add columns to admin manage quiz page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function columns_head( $columns ) {

			// append new column after title column
			$pos = array_search( 'title', array_keys( $columns ) );
			if ( false !== $pos && !array_key_exists( LP()->course_post_type, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					array( LP()->course_post_type => __( 'Course', 'learn_press' ), 'num_of_question' => __( 'Num. of questions', 'learn_press' ) ),
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
		 * @param int    $post_id
		 */
		function columns_content( $name, $post_id ) {
			switch ( $name ) {
				case LP()->course_post_type:

					$courses = learn_press_get_item_courses( $post_id );
					if ( $courses ) {
						foreach( $courses as $course ) {
							echo '<div><a href="' . esc_url( add_query_arg( array('course_id' => $course->ID) ) ) . '">' . get_the_title( $course->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $course->ID ) ), __( 'Edit', 'learn_press' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $course->ID ), __( 'View', 'learn_press' ) );
							echo '</div></div>';
						}

					} else {
						_e( 'Not assigned yet', 'learn_press' );
					}
					break;
				case 'num_of_question':
					$quiz = LP_Quiz::get_quiz( $post_id );
					$questions = $quiz->get_questions();
					echo ($n = sizeof( $questions )) ? sprintf( _nx( '%d question', '%d questions', $n, 'learn_press' ), $n ) : '_';
			}
		}

		/**
		 * Update lesson meta data
		 *
		 * @param $quiz_id
		 */
		function update_quiz_meta( $quiz_id ) {
			$course_id = get_post_meta( $quiz_id, '_lpr_course', true );
			if ( !$course_id ) {
				delete_post_meta( $quiz_id, '_lpr_course' );
				update_post_meta( $quiz_id, '_lpr_course', 0 );
			}
		}

		function posts_fields( $fields ) {
			if ( !is_admin() ) {
				return $fields;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $fields;
			}
			global $post_type;
			if ( LP()->quiz_post_type != $post_type ) {
				return $fields;
			}

			$fields = " DISTINCT " . $fields;
			return $fields;
		}

		/**
		 * @param $join
		 *
		 * @return string
		 */
		function posts_join_paged( $join ) {
			if ( !is_admin() ) {
				return $join;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $join;
			}
			global $post_type;
			if ( LP()->quiz_post_type != $post_type ) {
				return $join;
			}
			global $wpdb;
			$join .= " LEFT JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id";
			$join .= " LEFT JOIN {$wpdb->posts} AS c ON c.ID = {$wpdb->postmeta}.meta_value";

			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
		function posts_where_paged( $where ) {

			if ( !is_admin() ) {
				return $where;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $where;
			}
			global $post_type;
			if ( LP()->quiz_post_type != $post_type ) {
				return $where;
			}
			global $wpdb;

			$where .= " AND (
                {$wpdb->postmeta}.meta_key='_lpr_course'
            )";
			if ( isset ( $_GET['meta_course'] ) ) {

				$where .= " AND (
                	{$wpdb->postmeta}.meta_value=" . intval( $_GET['meta_course'] ) . ")";
			}
			if ( isset( $_GET['s'] ) ) {
				$s = $_GET['s'];
				if ( empty( $s ) ) {
					$where .= " AND ( c.post_title IS NULL)";
				} else {
					$where = preg_replace(
						"/\.post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
						" .post_content LIKE '%$s%' ) OR (c.post_title LIKE '%$s%' )", $where
					);
				}
			}

			return $where;
		}

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
		function posts_orderby( $order_by_statement ) {
			if ( !is_admin() ) {
				return $order_by_statement;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $order_by_statement;
			}
			global $post_type;
			if ( LP()->quiz_post_type != $post_type ) {
				return $order_by_statement;
			}
			if ( isset ( $_GET['orderby'] ) && isset ( $_GET['order'] ) ) {
				$order_by_statement = "c.post_title {$_GET['order']}";
				return $order_by_statement;
			}
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		function columns_sortable( $columns ) {
			$columns[LP()->course_post_type] = 'course';
			return $columns;
		}
	} // end LP_Quiz_Post_Type
}
new LP_Quiz_Post_Type();
