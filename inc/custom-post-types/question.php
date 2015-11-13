<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Question_Post_Type' ) ) {
	// Base class for custom post type to extends
	learn_press_include( 'custom-post-types/abstract.php' );

	// class LP_Question_Post_Type
	class LP_Question_Post_Type extends LP_Abstract_Post_Type{
		function __construct() {

			//add_action( 'init', array( $this, 'register_post_type' ) );
			//add_action( 'admin_head', array( $this, 'enqueue_script' ) );
			//add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 5 );
			add_filter( 'manage_lpr_question_posts_columns', array( $this, 'columns_head' ) );
			parent::__construct();

		}

		/**
		 * Register question post type
		 */
		static function register_post_type() {
			register_post_type( LP_QUESTION_CPT,
				array(
					'labels'             => array(
						'name'               => __( 'Question Bank', 'learn_press' ),
						'menu_name'          => __( 'Question Bank', 'learn_press' ),
						'singular_name'      => __( 'Question', 'learn_press' ),
						'all_items'          => __( 'Questions', 'learn_press' ),
						'view_item'          => __( 'View Question', 'learn_press' ),
						'add_new_item'       => __( 'Add New Question', 'learn_press' ),
						'add_new'            => __( 'Add New', 'learn_press' ),
						'edit_item'          => __( 'Edit Question', 'learn_press' ),
						'update_item'        => __( 'Update Question', 'learn_press' ),
						'search_items'       => __( 'Search Questions', 'learn_press' ),
						'not_found'          => __( 'No question found', 'learn_press' ),
						'not_found_in_trash' => __( 'No question found in trash', 'learn_press' ),
					),
					'public'             => true,
					'publicly_queryable' => true,
					'show_ui'            => true,
					'has_archive'        => true,
					'capability_type'    => LP_LESSON_CPT,
					'map_meta_cap'       => true,
					'show_in_menu'       => 'learn_press',
					'show_in_admin_bar'  => true,
					'show_in_nav_menus'  => true,
					'supports'           => array( 'title', 'editor', 'revisions', 'author' ),
					'hierarchical'       => true,
					'rewrite'            => array( 'slug' => 'questions', 'hierarchical' => true, 'with_front' => false )
				)
			);


			register_taxonomy( 'question-tag', array( LP_QUESTION_CPT ),
				array(
					'labels'            => array(
						'name'          => __( 'Question Tag', 'learn_press' ),
						'menu_name'     => __( 'Tag', 'learn_press' ),
						'singular_name' => __( 'Tag', 'learn_press' ),
						'add_new_item'  => __( 'Add New Tag', 'learn_press' ),
						'all_items'     => __( 'All Tags', 'learn_press' )
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
					'title'  => __('Answer','learn_press'),
					'pages'  => array( LP()->question_post_type ),
					'fields' => array(
						array(
							'name' => __( '', 'learn_press' ),
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
			$prefix = '_lp_';
			$meta_box = array(
				'id'     => 'question_settings',
				'title'  => __('Settings','learn_press'),
				'pages'  => array( LP()->question_post_type ),
				'fields' => array(
					array(
						'name'  => __( 'Mark For This Question', 'learn_press' ),
						'id'    => "{$prefix}mark",
						'type'  => 'number',
						'clone' => false,
						'desc'  => __('Mark for choosing the right answer', 'learn_press'),
						'min'   => 1,
						'std'   => 1
					),
					array(
						'name'  => __( 'Question Explanation', 'learn_press' ),
						'id'    => "{$prefix}explanation",
						'type'  => 'textarea',
						'desc'  => __('', 'learn_press'),
						'std'   => null
					)
				)
			);

			return apply_filters( 'learn_press_question_meta_box_args', $meta_box );
		}

        /**
         * Enqueue scripts
         */
		static function admin_scripts() {
			if ( ! in_array( get_post_type(), array( LP()->question_post_type ) ) ) return;
			ob_start();
			?>
			<script>
				var form = $('#post');
				form.submit(function (evt) {
					var $title = $('#title'),
						is_error = false;
					if (0 == $title.val().length) {
						alert('<?php _e( 'Please enter the title of the question', 'learn_press' );?>');
						$title.focus();
						is_error = true;
					} else if ( $('.lpr-question-types').length && ( 0 == $('.lpr-question-types').val().length ) ) {
						alert('<?php _e( 'Please a type of question', 'learn_press' );?>');
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
						<?php endif;?>
					</select>
					<button class="button" data-action="add" type="button"><?php _e( 'Add [Enter]', 'learn_press' );?></button>
					<button data-action="cancel" class="button" type="button"><?php _e( 'Cancel [ESC]', 'learn_press' );?></button>
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
			$user = wp_get_current_user();
			if ( in_array( LP()->teacher_role, $user->roles ) ) {
				unset( $columns['author'] );
			}
			return $columns;
		}

		static function admin_styles(){}
		static function admin_params(){

		}

		function save(){
			$this->save_post();
		}
		function save_post( ){
			die();
		}
	} // end LP_Question_Post_Type
}

new LP_Question_Post_Type();