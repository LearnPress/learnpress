<?php
/**
 * Project:     learnpress.
 * Author:      GiapNV
 * Date:        1/23/15
 *
 * Copyright 2007-2014 thimpress.com. All rights reserved.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
define( 'LPR_QUIZ_SLUG', 'quiz');
if( ! class_exists( 'LPR_Quiz_Post_Type' ) ){
    // class LPR_Quiz_Post_Type
    final class LPR_Quiz_Post_Type{
        private static $loaded = false;
        function __construct(){
            if( self::$loaded ) return;

            add_action( 'init', array( $this, 'register_post_type' ) );
            add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 0 );
			add_filter( 'manage_lpr_quiz_posts_columns', array( $this, 'columns_head' ) );
			add_action( 'manage_lpr_quiz_posts_custom_column', array( $this, 'columns_content' ), 10, 2 );
			add_action( 'save_post_lpr_quiz', array( $this, 'update_quiz_meta' ) );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_filter( 'manage_edit-lpr_quiz_sortable_columns', array( $this, 'columns_sortable' ) );
            add_action( 'save_post', array( $this, 'save' ) );

            self::$loaded = true;
        }

        static function save(){


        }

        /**
         * Register quiz post type
         */
        function register_post_type() {
            register_post_type( 'lpr_quiz',
                apply_filters('lpr_quiz_post_type_args',
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
                        'has_archive'        => true,
                        'capability_type'    => LPR_LESSON_CPT,
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

        function add_meta_boxes(){

            $prefix       = '_lpr_';
            $meta_box = apply_filters(
                'learn_press_quiz_question_meta_box_args',
                array(
                    'title'      => __( 'Questions', 'learn_press' ),
                    'post_types' => LPR_QUIZ_CPT,
                    'fields'     => array(
                        array(
                            'name' => __( '', 'learn_press' ),
                            'desc' => __( '', 'learn_press' ),
                            'id'   => "{$prefix}quiz_question",
                            'type' => 'quiz_question'
                        )
                    )
                )
            );
            $GLOBALS['learn_press_quiz_question_meta_box'] = new RW_Meta_Box( $meta_box );
            new RWMB_Quiz_Question_Field();
            new RW_Meta_Box(
                array(
                    'title'      => __( 'LearnPress Quiz Settings', 'learn_press' ),
                    'post_types' => LPR_QUIZ_CPT,
                    'context'    => 'normal',
                    'priority'   => 'high',
                    'fields'     => array(
                        array(
                            'name' => __( 'Quiz Duration', 'learn_press' ),
                            'desc' => __( 'Quiz duration (in minutes). Auto submits when expire. Set 0 to disable.', 'learn_press' ),
                            'id'   => "{$prefix}duration",
                            'type' => 'number',
                            'min' => 0,
                            'std'  => 10
                        ),
                        array(
                            'name' => __( 'Re-take quiz', 'learn_press' ),
                            'id'   => "{$prefix}retake_quiz",
                            'type' => 'number',
                            'desc' => 'How many times the user can re-take this quiz. Set to 0 to disable',
                            'min'   => 0
                        ),
                        array(
                            'name' => __( 'Show quiz result', 'learn_press' ),
                            'id'   => "{$prefix}show_quiz_result",
                            'type' => 'checkbox',
                            'desc' => __( 'Show the correct answer in result of the quiz.', 'learn_press' ),
                            'std'   => 0
                        ),
                    )
                )
            );
        }

        function enqueue_script(){
            if( 'lpr_quiz' != get_post_type() ) return;
            ob_start();
            ?>
            <script>
                var form = $('#post');
                form.submit(function(evt){
                    var $title = $('#title'),
                        is_error = false;
                    if( 0 == $title.val().length ){
                        alert( '<?php _e( 'Please enter the title of the quiz', 'learn_press' );?>' );
                        $title.focus();
                        is_error = true;
                    }

                    if( true == is_error ){
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
                        <?php if( $questions = lpr_get_question_types() ):?>
                            <?php foreach( $questions as $type ):?>
                                <option value="<?php echo $type;?>"><?php echo LPR_Question_Type::instance( $type )->get_name();?></option>
                            <?php endforeach;?>
                        <?php endif;?>
                    </select>
                    <button class="button" data-action="add" type="button"><?php _e('Add [Enter]', 'learnpress');?></button>
                    <button  data-action="cancel" class="button" type="button"><?php _e('Cancel [ESC]', 'learnpress');?></button>
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
			if ( false !== $pos && !array_key_exists( 'lpr_course', $columns ) ) {
				$columns = array_merge(
					array_slice( $columns, 0, $pos + 1 ),
					array( 'lpr_course' => __( 'Course', 'learn_press' ) ),
					array_slice( $columns, $pos + 1 )
				);
			}
			unset ( $columns['taxonomy-lesson-tag'] );
			$user = wp_get_current_user();
			if ( in_array( 'lpr_teacher', $user->roles ) ) {
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
				case 'lpr_course':
					$course_id  = get_post_meta( $post_id, '_lpr_course', true );
					$arr_params = array( 'meta_course' => $course_id );
					echo '<a href="' . esc_url( add_query_arg( $arr_params ) ) . '">' . ( $course_id ? get_the_title( $course_id ) : __( 'Not assigned yet', 'learn_press' ) ) . '</a>';
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
			if ( 'lpr_quiz' != $post_type ) {
				return $join;
			}
			global $wpdb;
			$join .= " INNER JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id";
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
			if ( 'lpr_quiz' != $post_type ) {
				return $where;
			}
			global $wpdb;

			$where .= " AND (
                {$wpdb->postmeta}.meta_key='_lpr_course'
            )";
			if ( isset ( $_GET['meta_course'] ) ) {
				$where .= " AND (
                	{$wpdb->postmeta}.meta_value='{$_GET['meta_course']}'
           		 )";
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
		function posts_orderby($order_by_statement) {
			if ( !is_admin() ) {
				return $order_by_statement;
			}
			global $pagenow;
			if ( $pagenow != 'edit.php' ) {
				return $order_by_statement;
			}
			global $post_type;
			if ( 'lpr_quiz' != $post_type ) {
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
		function columns_sortable($columns) {
			$columns['lpr_course'] = 'course';
			return $columns;
		}
    } // end LPR_Quiz_Post_Type
}
new LPR_Quiz_Post_Type();

