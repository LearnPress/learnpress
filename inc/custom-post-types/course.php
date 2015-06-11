<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LPR_Course_Post_Type' ) ) {

	// class LPR_Course_Post_Type
	final class LPR_Course_Post_Type {
		protected static $loaded;

		function __construct() {
			if ( self::$loaded ) return;

			add_action( 'init', array( $this, 'register_post_type' ) );

			add_action( 'admin_head', array( $this, 'enqueue_script' ) );
			add_action( 'admin_init', array( $this, 'add_meta_boxes' ), 0 );
			add_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );
			add_filter( 'manage_lpr_course_posts_columns', array( $this, 'columns_head' ) );

			self::$loaded = true;
		}

		/**
		 * Register course post type
		 */
		function register_post_type() {
			$labels = array(
				'name'               => _x( 'Courses', 'Post Type General Name', 'learn_press' ),
				'singular_name'      => _x( 'Course', 'Post Type Singular Name', 'learn_press' ),
				'menu_name'          => __( 'Courses', 'learn_press' ),
				'parent_item_colon'  => __( 'Parent Item:', 'learn_press' ),
				'all_items'          => __( 'Courses', 'learn_press' ),
				'view_item'          => __( 'View Course', 'learn_press' ),
				'add_new_item'       => __( 'Add New Course', 'learn_press' ),
				'add_new'            => __( 'Add New', 'learn_press' ),
				'edit_item'          => __( 'Edit Course', 'learn_press' ),
				'update_item'        => __( 'Update Course', 'learn_press' ),
				'search_items'       => __( 'Search Course', 'learn_press' ),
				'not_found'          => __( 'No course found', 'learn_press' ),
				'not_found_in_trash' => __( 'No course found in Trash', 'learn_press' ),
			);

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => ( $page_id = learn_press_get_page_id( 'courses' ) ) && get_post( $page_id ) ? get_page_uri( $page_id ) : 'courses',
				'capability_type'    => LPR_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'taxonomies'         => array( 'course_category', 'course_tag' ),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'author' ),
				'hierarchical'       => true,
				'rewrite'            => array( 'slug' => 'courses', 'hierarchical' => true, 'with_front' => false )
			);
			register_post_type( LPR_COURSE_CPT, $args );


			register_taxonomy( 'course_category', array( LPR_COURSE_CPT ),
				array(
					'label'             => __( 'Course Categories', 'learn_press' ),
					'labels'            => array(
						'name'          => __( 'Course Categories', 'learn_press' ),
						'menu_name'     => __( 'Category', 'learn_press' ),
						'singular_name' => __( 'Category', 'learn_press' ),
						'add_new_item'  => __( 'Add New Course Category', 'learn_press' ),
						'all_items'     => __( 'All Categories', 'learn_press' )
					),
					'query_var'         => true,
					'public'            => true,
					'hierarchical'      => true,
					'show_ui'           => true,
					'show_in_menu'      => 'learn_press',
					'show_admin_column' => true,
					'show_in_admin_bar' => true,
					'show_in_nav_menus' => true,
					'rewrite'           => array(
						'slug'         => 'course_category',
						'hierarchical' => true,
						'with_front'   => false
					),
				)
			);
			register_taxonomy( 'course_tag', array( LPR_COURSE_CPT ),
				array(
					'labels'                => array(
						'name'                       => __( 'Course Tags', 'learn_press' ),
						'singular_name'              => __( 'Tag', 'learn_press' ),
						'search_items'               => __( 'Search Course Tags' ),
						'popular_items'              => __( 'Popular Course Tags' ),
						'all_items'                  => __( 'All Course Tags' ),
						'parent_item'                => null,
						'parent_item_colon'          => null,
						'edit_item'                  => __( 'Edit Course Tag' ),
						'update_item'                => __( 'Update Course Tag' ),
						'add_new_item'               => __( 'Add New Course Tag' ),
						'new_item_name'              => __( 'New Course Tag Name' ),
						'separate_items_with_commas' => __( 'Separate tags with commas' ),
						'add_or_remove_items'        => __( 'Add or remove tags' ),
						'choose_from_most_used'      => __( 'Choose from the most used tags' ),
						'menu_name'                  => __( 'Tags' ),
					),
					'public'                => true,
					'hierarchical'          => false,
					'show_ui'               => true,
					'show_in_menu'          => 'learn_press',
					'update_count_callback' => '_update_post_term_count',
					'query_var'             => true,
				)
			);
            if( ! is_admin() ){
                LPR_Assets::enqueue_script( 'tipsy', LPR_PLUGIN_URL . '/assets/js/jquery.tipsy.js' );
                LPR_Assets::enqueue_style( 'tipsy', LPR_PLUGIN_URL . '/assets/css/tipsy.css' );
            }

		}

		/**
		 * Add meta boxes to course post type page
		 */
		function add_meta_boxes() {

			new RW_Meta_Box( $this->curriculum_meta_box() );
			new RW_Meta_Box( $this->settings_meta_box() );
			new RW_Meta_Box( $this->assessment_meta_box() );
			new RW_Meta_Box( $this->payment_meta_box() );

		}

		function curriculum_meta_box() {
			$prefix = '_lpr_';

			$meta_box = array(
				'id'     	=> 'course_curriculum',
				'title'  	=> 'Course Curriculum',
				'priority'	=> 'high',
				'pages'  	=> array( LPR_COURSE_CPT ),
				'fields' 	=> array(
					array(
						'name' => __( 'Course Curriculum', 'learn_press' ),
						'id'   => "{$prefix}course_lesson_quiz",
						'type' => 'course_lesson_quiz',
						'desc' => '',
					),
				)
			);

			return apply_filters( 'learn_press_course_curriculum_meta_box_args', $meta_box );
		}

		function settings_meta_box() {
			$prefix = '_lpr_';

			$meta_box = array(
				'id'     	=> 'course_settings',
				'title'  	=> 'Course Settings',
				'pages'  	=> array( LPR_COURSE_CPT ),
				'priority' 	=> 'high',
				'fields' 	=> array(
					array(
						'name' => __( 'Course Duration', 'learn_press' ),
						'id'   => "{$prefix}course_duration",
						'type' => 'number',
						'desc' => 'The length of the course (by weeks)',
						'std'  => 10,
					),
					// array(
					// 	'name' => __( 'Course Time', 'learn_press' ),
					// 	'id'   => "{$prefix}course_time",
					// 	'type' => 'text',
					// 	'desc' => 'Course start and end time. Example:2-4pm',
					// ),
					array(
						'name' => __( 'Number of Students Enrolled', 'learn_press' ),
						'id'   => "{$prefix}course_number_student",
						'type' => 'number',
						'desc' => 'The number of students took this course',
						'std'  => 0,
					),
					array(
						'name' => __( 'Maximum students can take the course', 'learn_press' ),
						'id'   => "{$prefix}max_course_number_student",
						'type' => 'number',
						'desc' => 'Maximum Number Student of the Course',
						'std'  => 1000,
					),
                    array(
                        'name' => __( 'Re-take course', 'learn_press' ),
                        'id'   => "{$prefix}retake_course",
                        'type' => 'number',
                        'desc' => 'How many times the user can re-take this course. Set to 0 to disable',
                        'std'  => '0',
                    ),

				)
			);

			return apply_filters( 'learn_press_course_settings_meta_box_args', $meta_box );
		}

		function assessment_meta_box() {
			$prefix = '_lpr_';

			$meta_box = array(
				'id'     	=> 'course_assessment',
				'title'  	=> 'Course Assessment Settings',
				'priority' 	=> 'high',
				'pages'  	=> array( LPR_COURSE_CPT ),
				'fields' 	=> array(
					array(
						'name'    => __( 'Course Final Quiz', 'learn_press' ),
						'id'      => "{$prefix}course_final",
						'type'    => 'radio',
						'desc'    => 'If Final Quiz option is checked, then the course will be assessed by result of the last quiz, else the course
                                      will be assessed by the progress of learning lessons',
						'options' => array(
							'no'  => __( 'No Final Quiz', 'learn_press' ),
                            'yes' => __( 'Using Final Quiz', 'learn_press' )
						),
						'std'     => 'no'
					),
					array(
						'name' => __( 'Passing Condition', 'learn_press' ),
						'id'   => "{$prefix}course_condition",
						'type' => 'number',
						'min'  => 1,
						'max'  => 100,
						'desc' => 'The percentage of quiz result to finish the course',
						'std'  => 50
					)
				)
			);
			return apply_filters( 'learn_press_course_assessment_metabox', $meta_box );
		}

		function payment_meta_box() {

			$prefix = '_lpr_';

			$meta_box = array(
				'id'     	=> 'course_payment',
				'title'  	=> 'Course Payment Settings',
				'priority' 	=> 'high',
				'pages'  	=> array( LPR_COURSE_CPT ),
				'fields' 	=> array(
					array(
						'name'    => __( 'Course Payment', 'learn_press' ),
						'id'      => "{$prefix}course_payment",
						'type'    => 'radio',
						'desc'    => 'If Enrolled Require be checked, An administrator will review then set course price and commission',
						'options' => array(
							'free'     => __( 'Free', 'learn_press' ),
							'not_free' => __( 'Enrolled Require', 'learn_press' ),
						),
						'std'     => 'free'
					)
				)
			);

			if ( current_user_can( 'manage_options' ) ) {
				$message = 'If free, this field is empty or set 0. (Only admin can edit this field)';
				$price = 0;

				if( isset($_GET['post']) ) {
					$course_id = $_GET['post'];
					$type = get_post_meta( $course_id, '_lpr_course_payment', true );
					if( $type != 'free' ) {
						$suggest_price = get_post_meta( $course_id, '_lpr_course_suggestion_price', true );
						if( isset( $suggest_price ) ) {
							$message = 'This course is enrolled require and the suggestion price is ' . '<span>' . learn_press_get_currency_symbol() . $suggest_price . '</span>';
							$price = $suggest_price;
						}
					} else {
						$message = 'This course is free.';
					};
				}
				array_push(
					$meta_box['fields'],
					array(
						'name' => __( 'Course Price', 'learn_press' ),
						'id'   => "{$prefix}course_price",
						'type' => 'number',
						'min'  => 0,
						'step' => 0.01,
						'desc' => $message,
						'std'  => $price,
					)
				);
			} else {
				array_push(
					$meta_box['fields'],
					array(
						'name' 	=> __( 'Course Suggestion Price', 'learn_press'),
						'id'	=> "{$prefix}course_suggestion_price",
						'type'	=> 'number',
						'min'	=> 0,
						'step'	=> 0.01,
						'desc'	=> 'The course price you want to suggest for admin to set.'
					)
					);
			}
			return apply_filters( 'learn_press_course_payment_meta_box_args', $meta_box );
		}

		function before_save_curriculum() {
			if ( $sections = $_POST['_lpr_course_lesson_quiz'] ) foreach ( $sections as $k => $section ) {
				if ( empty( $section['name'] ) ) {
					unset( $_POST['_lpr_course_lesson_quiz'][$k] );
				}
				$_POST['_lpr_course_lesson_quiz'] = array_values( $_POST['_lpr_course_lesson_quiz'] );
			}

		}

		function enqueue_script() {
			if ( 'lpr_course' != get_post_type() ) return;
			ob_start();
			global $post;
			?>
			<script>
				window.course_id = <?php echo $post->ID;?>, form = $('#post');
				form.submit(function (evt) {
					var $title = $('#title'),
						$curriculum = $('.lpr-curriculum-section:not(.lpr-empty)'),
						is_error = false;
					if (0 == $title.val().length) {
						alert('<?php _e( 'Please enter the title of the course', 'learn_press' );?>');
						$title.focus();
						is_error = true;
					} else if (0 == $curriculum.length) {
						alert('<?php _e( 'Please add at least one section for the course', 'learn_press' );?>');
						$('.lpr-curriculum-section .lpr-section-name').focus();
						is_error = true;
					} else {
						$curriculum.each(function () {
							var $section = $('.lpr-section-name', this);
							if (0 == $section.val().length) {
								alert('<?php _e( 'Please enter the title of the section', 'learn_press' );?>');
								$section.focus();
								is_error = true;
								return false;
							}
						});
					}
					if (true == is_error) {
						evt.preventDefault();
						return false;
					}
				});
                $('input[name="_lpr_course_final"]').bind('click change', function(){
                    if( $(this).val() == 'yes' ){
                        $(this).closest('.rwmb-field').next().show();
                    }else{
                        $(this).closest('.rwmb-field').next().hide();
                    }
                }).filter(":checked").trigger('change')
			</script>
			<?php
			$script = ob_get_clean();
			$script = preg_replace( '!</?script>!', '', $script );
			learn_press_enqueue_script( $script );
		}

		/**
		 * Add columns to admin manage course page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		function columns_head( $columns ) {
			$user = wp_get_current_user();
			if ( in_array( 'lpr_teacher', $user->roles ) ) {
				unset( $columns['author'] );
			}
			return $columns;
		}
	} // end LPR_Course_Post_Type
	new LPR_Course_Post_Type();
}


