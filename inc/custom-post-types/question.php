<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Question_Post_Type' ) ) {
	// Base class for custom post type to extends
	learn_press_include( 'custom-post-types/abstract.php' );

	// class LP_Question_Post_Type
	class LP_Question_Post_Type extends LP_Abstract_Post_Type {
		function __construct() {
			add_action( 'admin_head', array( $this, 'init' ) );

			//add_action( 'init', array( $this, 'register_post_type' ) );
			//add_action( 'admin_head', array( $this, 'enqueue_script' ) );
			//add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 5 );
			add_filter( 'manage_lp_question_posts_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_lp_question_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_action( 'before_delete_post', array( $this, 'delete_question_answers' ) );

			//add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_filter( 'manage_edit-lp_question_sortable_columns', array( $this, 'columns_sortable' ) );

			parent::__construct();

		}

		function init() {
			global $pagenow, $post_type;
			$hidden = get_user_meta( get_current_user_id(), 'manageedit-lp_questioncolumnshidden', true );
			if ( !is_array( $hidden ) && empty( $hidden ) ) {
				update_user_meta( get_current_user_id(), 'manageedit-lp_questioncolumnshidden', array( 'taxonomy-question-tag' ) );
			}
		}

		/**
		 * Delete all answers assign to question being deleted
		 *
		 * @param $post_id
		 */
		function delete_question_answers( $post_id ) {
			global $wpdb;
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_question_answers
				WHERE question_id = %d
			", $post_id );
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_question_answers' );

			// also, delete question from quiz
			$wpdb->query(
				$wpdb->prepare( "
					DELETE FROM {$wpdb->prefix}learnpress_quiz_questions
					WHERE question_id = %d
				", $post_id )
			);
			learn_press_reset_auto_increment( 'learnpress_quiz_questions' );
		}

		/**
		 * Register question post type
		 */
		static function register_post_type() {
			register_post_type( LP_QUESTION_CPT,
				array(
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
						'not_found'          => __( 'No question found', 'learnpress' ),
						'not_found_in_trash' => __( 'No question found in trash', 'learnpress' ),
					),
					'public'             => false, // disable access directly via permalink url
					'publicly_queryable' => false,
					'show_ui'            => true,
					'has_archive'        => false,
					'capability_type'    => LP_LESSON_CPT,
					'map_meta_cap'       => true,
					'show_in_menu'       => 'learn_press',
					'show_in_admin_bar'  => true,
					'show_in_nav_menus'  => true,
					'supports'           => array( 'title', 'editor', 'revisions' ),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'questions', 'hierarchical' => true, 'with_front' => false )
				)
			);


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


		}

		static function add_meta_boxes() {

			new RW_Meta_Box(
				array(
					'id'     => 'question_answer',
					'title'  => __( 'Answer', 'learnpress' ),
					'pages'  => array( LP()->question_post_type ),
					'fields' => array(
						array(
							'name' => __( '', 'learnpress' ),
							'id'   => "_lp_question_answer",
							'type' => 'question'
						)
					)
				)
			);

			$meta_box = self::settings_meta_box();
			new RW_Meta_Box( $meta_box );
		}

		static function settings_meta_box() {
			$prefix   = '_lp_';
			$meta_box = array(
				'id'     => 'question_settings',
				'title'  => __( 'Settings', 'learnpress' ),
				'pages'  => array( LP()->question_post_type ),
				'fields' => array(
					array(
						'name'  => __( 'Mark for this question', 'learnpress' ),
						'id'    => "{$prefix}mark",
						'type'  => 'number',
						'clone' => false,
						'desc'  => __( 'Mark for choosing the right answer', 'learnpress' ),
						'min'   => 1,
						'std'   => 1
					),
					array(
						'name' => __( 'Question explanation', 'learnpress' ),
						'id'   => "{$prefix}explanation",
						'type' => 'textarea',
						'desc' => __( 'Explanation why an option is true and other is false', 'learnpress' ),
						'std'  => null
					),
					array(
						'name' => __( 'Question hint', 'learnpress' ),
						'id'   => "{$prefix}hint",
						'type' => 'textarea',
						'desc' => __( 'Instruction for user select the right answer', 'learnpress' ),
						'std'  => null
					)
				)
			);

			return apply_filters( 'learn_press_question_meta_box_args', $meta_box );
		}

		/**
		 * Enqueue scripts
		 */
		static function admin_scripts() {
			if ( !in_array( get_post_type(), array( LP()->question_post_type ) ) ) return;
			ob_start();
			?>
			<script>
				var form = $('#post');
				form.submit(function (evt) {
					var $title = $('#title'),
						is_error = false;
					if (0 == $title.val().length) {
						alert('<?php _e( 'Please enter the title of the question', 'learnpress' );?>');
						$title.focus();
						is_error = true;
					} else if ($('.lpr-question-types').length && ( 0 == $('.lpr-question-types').val().length )) {
						alert('<?php _e( 'Please a type of question', 'learnpress' );?>');
						$('.lpr-question-types').focus();
						is_error = true;
					}
					if (is_error) {
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
						<?php if ( $questions = learn_press_question_types() ): ?>
							<?php foreach ( $questions as $type => $name ): ?>
								<option value="<?php echo $type; ?>"><?php echo $name; ?></option>
							<?php endforeach; ?>
						<?php endif; ?>
					</select>
					<button class="button" data-action="add" type="button"><?php _e( 'Add [Enter]', 'learnpress' ); ?></button>
					<button data-action="cancel" class="button" type="button"><?php _e( 'Cancel [ESC]', 'learnpress' ); ?></button>
					<span class="lpr-ajaxload">...</span>
				</div>
			</script>
			<?php
			$js_template = ob_get_clean();
			learn_press_enqueue_script( $js_template, true );
		}

		/**
		 * Add columns to admin manage question page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function columns_head( $columns ) {
			$pos         = array_search( 'title', array_keys( $columns ) );
			$new_columns = array(
				LP()->quiz_post_type => __( 'Quiz', 'learnpress' ),
				'type'               => __( 'Type', 'learnpress' )
			);

			if ( false !== $pos && !array_key_exists( LP()->quiz_post_type, $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					$new_columns,
					array_slice( $columns, $pos + 1 )
				);
			}

			$user = wp_get_current_user();
			if ( in_array( LP()->teacher_role, $user->roles ) ) {
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
		function columns_content( $name, $post_id ) {
			switch ( $name ) {
				case LP()->quiz_post_type:
					$quizzes = learn_press_get_question_quizzes( $post_id );
					if ( $quizzes ) {
						foreach ( $quizzes as $quiz ) {
							echo '<div><a href="' . esc_url( add_query_arg( array( 'filter_quiz' => $quiz->ID ) ) ) . '">' . get_the_title( $quiz->ID ) . '</a>';
							echo '<div class="row-actions">';
							printf( '<a href="%s">%s</a>', admin_url( sprintf( 'post.php?post=%d&action=edit', $quiz->ID ) ), __( 'Edit', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							printf( '<a href="%s">%s</a>', get_the_permalink( $quiz->ID ), __( 'View', 'learnpress' ) );
							echo "&nbsp;|&nbsp;";
							if ( $quiz_id = learn_press_get_request( 'filter_quiz' ) ) {
								printf( '<a href="%s">%s</a>', remove_query_arg( 'filter_quiz' ), __( 'Remove Filter', 'learnpress' ) );
							} else {
								printf( '<a href="%s">%s</a>', add_query_arg( 'filter_quiz', $quiz->ID ), __( 'Filter', 'learnpress' ) );
							}
							echo '</div></div>';
						}

					} else {
						_e( 'Not assigned yet', 'learnpress' );
					}

					break;
				case 'type':
					echo learn_press_question_name_from_slug( get_post_meta( $post_id, '_lp_type', true ) );
			}
		}

		/**
		 * @param $join
		 *
		 * @return string
		 */
		function posts_join_paged( $join ) {
			if ( !$this->_is_archive() ) {
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
		function posts_where_paged( $where ) {

			if ( !$this->_is_archive() ) {
				return $where;
			}

			global $wpdb;
			if ( $quiz_id = $this->_filter_quiz() ) {
				$where .= $wpdb->prepare( " AND (q.ID = %d)", $quiz_id );
			}
			return $where;
			if ( isset( $_GET['s'] ) ) {
				$s = $_GET['s'];
				if ( !empty( $s ) ) {
					$where = preg_replace(
						"/\.post_content\s+LIKE\s*(\'[^\']+\')\s*\)/",
						" .post_content LIKE '%$s%' ) OR ({$wpdb->posts}.post_title LIKE '%$s%' )", $where
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
			if ( !$this->_is_archive() ) {
				return $order_by_statement;
			}
			if ( isset ( $_GET['orderby'] ) && isset ( $_GET['order'] ) ) {
				switch ( $_GET['orderby'] ) {
					case 'quiz-name':
						$order_by_statement = "q.post_title {$_GET['order']}";
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
		function columns_sortable( $columns ) {
			$columns[LP()->quiz_post_type] = 'quiz-name';
			return $columns;
		}

		static function admin_styles() {
		}

		static function admin_params() {

		}

		function save() {
			$this->save_post();
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( LP()->question_post_type != $post_type ) ) {
				return false;
			}
			return true;
		}

		private function _filter_quiz() {
			return !empty( $_REQUEST['filter_quiz'] ) ? absint( $_REQUEST['filter_quiz'] ) : false;
		}

		private function _get_orderby() {
			return isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		}
	} // end LP_Question_Post_Type
}

new LP_Question_Post_Type();