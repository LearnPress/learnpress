<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
if ( !class_exists( 'LP_Course_Post_Type' ) ) {
	// class LP_Course_Post_Type
	final class LP_Course_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * New version of course editor
		 *
		 * @var bool
		 */
		protected static $_VER2 = false;

		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * Constructor
		 *
		 * @param string
		 */
		public function __construct( $post_type ) {
			parent::__construct( $post_type );

			// Map origin methods to another method
			$this
				->add_map_method( 'save', 'update_course', false )
				->add_map_method( 'save', 'before_save_curriculum', false )
				->add_map_method( 'before_delete', 'delete_course_sections' );

			add_action( 'edit_form_after_editor', array( $this, 'toggle_editor_button' ), - 10 );
			add_action( 'load-post.php', array( $this, 'post_actions' ) );
			add_action( 'init', array( $this, 'register_taxonomy' ) );
			add_action( 'init', array( $this, 'init_course' ) );

			// filter
			add_filter( "rwmb__lpr_course_price_html", array( $this, 'currency_symbol' ), 5, 3 );

			if ( self::$_VER2 ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'admin_script' ) );
				add_action( 'admin_print_scripts', array( $this, 'course_editor' ) );
			}
		}

		public function init_course() {

		}

		public function register_taxonomy() {
			$settings      = LP()->settings;
			$category_base = $settings->get( 'course_category_base' );
			register_taxonomy( 'course_category', array( LP_COURSE_CPT ),
				array(
					'label'             => __( 'Course Categories', 'learnpress' ),
					'labels'            => array(
						'name'          => __( 'Course Categories', 'learnpress' ),
						'menu_name'     => __( 'Category', 'learnpress' ),
						'singular_name' => __( 'Category', 'learnpress' ),
						'add_new_item'  => __( 'Add New Course Category', 'learnpress' ),
						'all_items'     => __( 'All Categories', 'learnpress' )
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
						'slug'         => empty( $category_base ) ? _x( 'course-category', 'slug', 'learnpress' ) : $category_base,
						'hierarchical' => true,
						'with_front'   => false
					),
				)
			);

			$tag_base = $settings->get( 'course_tag_base' );
			register_taxonomy( 'course_tag', array( LP_COURSE_CPT ),
				array(
					'labels'                => array(
						'name'                       => __( 'Course Tags', 'learnpress' ),
						'singular_name'              => __( 'Tag', 'learnpress' ),
						'search_items'               => __( 'Search Course Tags', 'learnpress' ),
						'popular_items'              => __( 'Popular Course Tags', 'learnpress' ),
						'all_items'                  => __( 'All Course Tags', 'learnpress' ),
						'parent_item'                => null,
						'parent_item_colon'          => null,
						'edit_item'                  => __( 'Edit Course Tag', 'learnpress' ),
						'update_item'                => __( 'Update Course Tag', 'learnpress' ),
						'add_new_item'               => __( 'Add New Course Tag', 'learnpress' ),
						'new_item_name'              => __( 'New Course Tag Name', 'learnpress' ),
						'separate_items_with_commas' => __( 'Separate tags with commas', 'learnpress' ),
						'add_or_remove_items'        => __( 'Add or remove tags', 'learnpress' ),
						'choose_from_most_used'      => __( 'Choose from the most used tags', 'learnpress' ),
						'menu_name'                  => __( 'Tags', 'learnpress' ),
					),
					'public'                => true,
					'hierarchical'          => false,
					'show_ui'               => true,
					'show_in_menu'          => 'learn_press',
					'update_count_callback' => '_update_post_term_count',
					'query_var'             => true,
					'rewrite'               => array(
						'slug'       => empty( $tag_base ) ? _x( 'course-tag', 'slug', 'learnpress' ) : $tag_base,
						'with_front' => false
					),
				)
			);
		}

		public function update_course( $course_id ) {
			/*learn_press_debug( $_REQUEST );
			die();*/
		}


		function admin_script() {
			global $post_type;
			if ( $post_type != 'lp_course' ) {
				return;
			}
			wp_enqueue_script( 'course-editor', LP()->js( 'admin/course-editor' ), array( 'jquery', 'backbone', 'wp-util', 'jquery-ui-sortable' ) );
			wp_enqueue_style( 'course-editor', LP()->css( 'admin/course-editor' ) );

		}

		function course_editor() {
			global $post_type;
			if ( $post_type != 'lp_course' ) {
				return;
			}
			learn_press_admin_view( 'meta-boxes/course/editor' );
		}

		/**
		 * Delete all questions assign to quiz being deleted
		 *
		 * @param $post_id
		 */
		public function delete_course_sections( $post_id ) {
			global $wpdb;
			// delete all items in section first
			$section_ids = $wpdb->get_col( $wpdb->prepare( "SELECT section_id FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d", $post_id ) );
			if ( $section_ids ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE %d AND section_id IN(" . join( ',', $section_ids ) . ")", 1 ) );
				learn_press_reset_auto_increment( 'learnpress_section_items' );

			}

			// delete all sections
			$query = $wpdb->prepare( "
				DELETE FROM {$wpdb->prefix}learnpress_sections
				WHERE section_course_id = %d
			", $post_id );
			$wpdb->query( $query );
			learn_press_reset_auto_increment( 'learnpress_sections' );
		}


		/**
		 * Process request actions on post.php loaded
		 */
		public function post_actions() {
			$delete_log = learn_press_get_request( 'delete_log' );
			// ensure that user can do this
			if ( $delete_log && current_user_can( 'delete_others_lp_courses' ) ) {
				$nonce   = learn_press_get_request( '_wpnonce' );
				$post_id = learn_press_get_request( 'post' );
				if ( wp_verify_nonce( $nonce, 'delete_log_' . $post_id . '_' . $delete_log ) ) {
					global $wpdb;
					$table = $wpdb->prefix . 'learnpress_review_logs';
					if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
						$wpdb->query(
							$wpdb->prepare( "
								DELETE FROM {$table}
								WHERE review_log_id = %d
							", $delete_log )
						);
					}
					wp_redirect( admin_url( 'post.php?post=' . learn_press_get_request( 'post' ) . '&action=edit' ) );
				}
			}
		}

		/**
		 * Toggle course description editor
		 *
		 * @param $post
		 */
		public function toggle_editor_button( $post ) {
			if ( $post->post_type == LP_COURSE_CPT ) {
				?>
				<button class="button button-primary"
						data-hidden="<?php echo get_post_meta( $post->ID, '_lp_editor_hidden', true ); ?>" type="button"
						id="learn-press-button-toggle-editor"><?php _e( 'Toggle Course Content', 'learnpress' ); ?></button>
				<?php
			}
		}

		/**
		 * Generate params for course used in admin
		 *
		 * @static
		 * @return mixed
		 */
		public function admin_params() {
			global $post;
			return apply_filters( 'learn_press_admin_course_params',
				array(
					'id'                        => absint( $post->ID ),
					'notice_empty_title'        => __( 'Please enter the title of the course', 'learnpress' ),
					'notice_empty_section'      => __( 'Please add at least one section for the course', 'learnpress' ),
					'notice_empty_section_name' => __( 'Please enter the title of the section', 'learnpress' ),
					'notice_empty_price'        => __( 'Please set a price for this course', 'learnpress' )
				)
			);
		}

		/**
		 * Enqueue scripts
		 *
		 * @static
		 */
		public function admin_scripts() {
			LP_Assets::add_localize(
				array(
					'notice_remove_section_item' => __( 'Are you sure you want to remove this item?', 'learnpress' )
				),
				null,
				'learn-press-mb-course'
			);
			if ( in_array( get_post_type(), array( LP_COURSE_CPT, LP_LESSON_CPT ) ) ) {
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
				wp_localize_script( 'lp-meta-boxes', 'lp_course_params', self::admin_params() );
			}
		}

		/**
		 * Print js template
		 */
		public function print_js_template() {
			if ( get_post_type() != LP_COURSE_CPT ) return;
			learn_press_admin_view( 'meta-boxes/course/js-template.php' );
		}

		public function currency_symbol( $input_html, $field, $sub_meta ) {
			return $input_html . '<span class="lpr-course-price-symbol">' . learn_press_get_currency_symbol() . '</span>';
		}

		/**
		 * Register course post type
		 */
		public function register() {
			$settings         = LP_Settings::instance();
			$labels           = array(
				'name'               => _x( 'Courses', 'Post Type General Name', 'learnpress' ),
				'singular_name'      => _x( 'Course', 'Post Type Singular Name', 'learnpress' ),
				'menu_name'          => __( 'Courses', 'learnpress' ),
				'parent_item_colon'  => __( 'Parent Item:', 'learnpress' ),
				'all_items'          => __( 'Courses', 'learnpress' ),
				'view_item'          => __( 'View Course', 'learnpress' ),
				'add_new_item'       => __( 'Add New Course', 'learnpress' ),
				'add_new'            => __( 'Add New', 'learnpress' ),
				'edit_item'          => __( 'Edit Course', 'learnpress' ),
				'update_item'        => __( 'Update Course', 'learnpress' ),
				'search_items'       => __( 'Search Courses', 'learnpress' ),
				'not_found'          => sprintf( __( 'You have not got any courses yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_course' ) ),
				'not_found_in_trash' => __( 'No course found in Trash', 'learnpress' )
			);
			$course_base      = $settings->get( 'course_base' );
			$course_permalink = empty( $course_base ) ? _x( 'courses', 'slug', 'learnpress' ) : $course_base;

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'query_var'          => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => 'courses',//( $page_id = learn_press_get_page_id( 'courses' ) ) && get_post( $page_id ) ? get_page_uri( $page_id ) : 'courses',
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'taxonomies'         => array( 'course_category', 'course_tag' ),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'excerpt' ),
				'hierarchical'       => false,
				'rewrite'            => $course_permalink ? array(
					'slug'         => untrailingslashit( $course_permalink ),
					'with_front'   => false
				) : false
			);
			return $args;
		}

		/**
		 * Add meta boxes to course post type page
		 */
		public function add_meta_boxes() {
			if ( !self::$_VER2 ) {
				new RW_Meta_Box( self::curriculum_meta_box() );
			}

			new RW_Meta_Box( self::settings_meta_box() );
			new RW_Meta_Box( self::assessment_meta_box() );
			new RW_Meta_Box( self::payment_meta_box() );
			parent::add_meta_boxes();
		}

		/**
		 * Course curriculum
		 *
		 * @return mixed|null|void
		 */
		public static function curriculum_meta_box() {
			$prefix = '_lp_';

			$meta_box = array(
				'id'       => 'course_curriculum',
				'title'    => __( 'Curriculum', 'learnpress' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name' => __( 'Course Curriculum', 'learnpress' ),
						'id'   => "{$prefix}curriculum",
						'type' => 'curriculum',
						'desc' => '',
					),
				)
			);

			return apply_filters( 'learn_press_course_curriculum_meta_box_args', $meta_box );
		}

		/**
		 * Course settings
		 *
		 * @return mixed|null|void
		 */

		public static function settings_meta_box() {
			$prefix = '_lp_';

			$meta_box = array(
				'id'       => 'course_settings',
				'title'    => __( 'General Settings', 'learnpress' ),
				'pages'    => array( LP_COURSE_CPT ),
				'priority' => 'high',
				'fields'   => array(
					array(
						'name' => __( 'Duration', 'learnpress' ),
						'id'   => "{$prefix}duration",
						'type' => 'duration',
						'desc' => __( 'The duration of the course (by weeks)', 'learnpress' ),
						'std'  => '10 weeks'
					),
					array(
						'name' => __( 'Maximum students', 'learnpress' ),
						'id'   => "{$prefix}max_students",
						'type' => 'number',
						'desc' => __( 'Maximum number of students who can enroll in this course.', 'learnpress' ),
						'std'  => 1000,
					),
					array(
						'name' => __( 'Students enrolled', 'learnpress' ),
						'id'   => "{$prefix}students",
						'type' => 'number',
						'desc' => __( 'How many students has taken this course.', 'learnpress' ),
						'std'  => 0,
					),
					array(
						'name' => __( 'Re-take course', 'learnpress' ),
						'id'   => "{$prefix}retake_count",
						'type' => 'number',
						'desc' => __( 'How many times the user can re-take this course. Set to 0 to disable', 'learnpress' ),
						'std'  => 0,
					),
					array(
						'name'    => __( 'Load media libraries', 'learnpress' ),
						'id'      => "{$prefix}load_media",
						'type'    => 'radio',
						'desc'    =>
							__( wp_kses( 'Load media assets for shortcode. Only use this option if you use shortcode <code>[audio]</code> in your course or lesson content',
								array( 'code' => array() ) ), 'learnpress' ),
						'std'     => 'no',
						'options' => array(
							'no'  => __( 'No', 'learnpress' ),
							'yes' => __( 'Yes', 'learnpress' )
						)
					),
				)
			);

			return apply_filters( 'learn_press_course_settings_meta_box_args', $meta_box );
		}

		/**
		 * Course assessment
		 *
		 * @return mixed|null|void
		 */
		public static function assessment_meta_box() {
			$post_id            = learn_press_get_request( 'post' );
			$prefix             = '_lp_';
			$course_result_desc = __( 'The method to assess the result of a student for a course.', 'learnpress' );
			if ( $post_id && get_post_meta( $post_id, '_lp_course_result', true ) == 'evaluate_final_quiz' && !get_post_meta( $post_id, '_lp_final_quiz', true ) ) {
				$course_result_desc .= __( '<br /><strong>Note! </strong>No final quiz in course, please add a final quiz', 'learnpress' );
			}
			$meta_box = array(
				'id'       => 'course_assessment',
				'title'    => __( 'Assessment', 'learnpress' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name'    => __( 'Course result', 'learnpress' ),
						'id'      => "{$prefix}course_result",
						'type'    => 'radio',
						'desc'    => $course_result_desc,
						'options' => array(
							'evaluate_lesson'     => __( 'Evaluate lessons', 'learnpress' ),
							'evaluate_quizzes'    => __( 'Evaluate result of quizzes', 'learnpress' ),
							'evaluate_final_quiz' => __( 'Evaluate the result of the final quiz', 'learnpress' )
						),
						'std'     => 'evaluate_lesson',
					),
					array(
						'name' => __( 'Passing condition value', 'learnpress' ),
						'id'   => "{$prefix}passing_condition",
						'type' => 'number',
						'min'  => 1,
						'max'  => 100,
						'desc' => __( 'The percentage of quiz result or lessons completed to finish the course', 'learnpress' ),
						'std'  => 80,
					)
				)
			);
			return apply_filters( 'learn_press_course_assessment_metabox', $meta_box );
		}

		/**
		 * Course payment
		 *
		 * @return mixed|null|void
		 */
		public static function payment_meta_box() {

			$course_id = !empty( $_GET['post'] ) ? $_GET['post'] : 0;
			$prefix    = '_lp_';

			$meta_box = array(
				'id'       => 'course_payment',
				'title'    => __( 'Payment Settings', 'learnpress' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name'    => __( 'Course payment', 'learnpress' ),
						'id'      => "{$prefix}payment",
						'type'    => 'radio',
						'desc'    => __( 'If Paid is checked, An administrator will review then set course price and commission.', 'learnpress' ),
						'options' => array(
							'no'  => __( 'Free', 'learnpress' ),
							'yes' => __( 'Paid', 'learnpress' ),
						),
						'std'     => 'no',
						'class'   => 'lp-course-payment-field'
					)
				)
			);

			$payment = get_post_meta( $course_id, '_lp_payment', true );

			if ( current_user_can( 'manage_options' ) ) {
				$message    = __( 'If free, this field is empty or set 0. (Only admin can edit this field)', 'learnpress' );
				$price      = 0;
				$sale_price = 0;
				$start_date = '';
				$end_date   = '';

				if ( isset( $_GET['post'] ) ) {
					$course_id = $_GET['post'];
					$type      = get_post_meta( $course_id, '_lp_payment', true );
					if ( $type != 'free' ) {
						$suggest_price = get_post_meta( $course_id, '_lp_suggestion_price', true );
						if ( isset( $suggest_price ) ) {
							$message = __( 'This course is required enrollment and the suggested price is ', 'learnpress' ) . '<span>' . learn_press_get_currency_symbol() . $suggest_price . '</span>';
							$price   = $suggest_price;
						}

						$sale_price = get_post_meta( $course_id, '_lp_sale_price', true );
						$start_date = get_post_meta( $course_id, '_lp_sale_start', true );
						$end_date   = get_post_meta( $course_id, '_lp_sale_end', true );

					} else {
						$message = __( 'This course is free.', 'learnpress' );
					};
				}
				array_push(
					$meta_box['fields'],
					array(
						'name'  => __( 'Price', 'learnpress' ),
						'id'    => "{$prefix}price",
						'type'  => 'number',
						'min'   => 0,
						'step'  => 0.01,
						'desc'  => $message,
						'std'   => $price,
						'class' => 'lp-course-price-field' . ( $payment != 'yes' ? ' hide-if-js' : '' )
					),
					array(
						'name'  => __( 'Sale Price', 'learnpress' ),
						'id'    => "{$prefix}sale_price",
						'type'  => 'number',
						'min'   => 0,
						'step'  => 0.01,
						'desc'  => '<a href="#" id="' . $prefix . 'sale_price_schedule">' . __( 'Schedule', 'learnpress' ) . '</a>',
						'std'   => $sale_price,
						'class' => 'lp-course-price-field lp-course-sale_price-field' . ( $payment != 'yes' ? ' hide-if-js' : '' )
					),
					array(
						'name'  => __( 'Sale start date', 'learnpress' ),
						'id'    => "{$prefix}sale_start",
						'type'  => 'datetime',
						'std'   => $start_date,
						'class' => 'lp-course-sale_start-field hide'
					),
					array(
						'name'  => __( 'Sale end date', 'learnpress' ),
						'id'    => "{$prefix}sale_end",
						'type'  => 'datetime',
						'desc'  => '<a href="#" id="' . $prefix . 'sale_price_schedule_cancel">' . __( 'Cancel', 'learnpress' ) . '</a>',
						'std'   => $end_date,
						'class' => 'lp-course-sale_end-field hide'
					)
				);
			} else {
				array_push(
					$meta_box['fields'],
					array(
						'name'  => __( 'Course Suggestion Price', 'learnpress' ),
						'id'    => "{$prefix}course_suggestion_price",
						'type'  => 'number',
						'min'   => 0,
						'step'  => 0.01,
						'desc'  => __( 'The course price you want to suggest for admin to set.', 'learnpress' ),
						'class' => 'lp-course-price-field' . ( $payment != 'yes' ? ' hide-if-js' : '' ),
						'std'   => 0
					)
				);
			}
			$meta_box['fields'] = array_merge(
				$meta_box['fields'],
				array(
					array(
						'name'    => __( 'Requires enroll', 'learnpress' ),
						'id'      => "{$prefix}required_enroll",
						'type'    => 'radio',
						'desc'    => __( 'Require users logged in to study or public to all', 'learnpress' ),
						'options' => array(
							'yes' => __( 'Yes, enroll is required', 'learnpress' ),
							'no'  => __( 'No', 'learnpress' ),
						),
						'std'     => 'yes',
						'class'   => 'lp-course-required-enroll' . ( $payment == 'yes' ? ' hide-if-js' : '' )
					)
				)
			);
			return apply_filters( 'learn_press_course_payment_meta_box_args', $meta_box );
		}

		/**
		 * Course review logs
		 *
		 */
		public function review_logs_meta_box() {
			add_meta_box(
				'review_logs',
				__( 'Review Logs', 'learnpress' ),
				array( $this, 'review_logs_content' ),
				LP_COURSE_CPT,
				'normal',
				'default'
			);
		}

		/**
		 * Display view for listing logs
		 *
		 * @param $post
		 */
		public function review_logs_content( $post ) {
			global $wpdb;
			$view_all = learn_press_get_request( 'view_all_review' );
			$table    = $wpdb->prefix . 'learnpress_review_logs';
			if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
				$query         = $wpdb->prepare( "
                                    SELECT SQL_CALC_FOUND_ROWS *
                                    FROM {$wpdb->learnpress_review_logs}
                                    WHERE course_id = %d
                                    ORDER BY `date` DESC"
					. ( $view_all ? "" : " LIMIT 0, 10" ) . "
                            ", $post->ID );
				$reviews       = $wpdb->get_results( $query );
				$total_reviews = $wpdb->get_var( "SELECT FOUND_ROWS()" );
				$count_reviews = sizeof( $reviews );

				$view = learn_press_get_admin_view( 'meta-boxes/course/review-logs.php' );
				include $view;
			}
		}

		/**
		 * Insert new section into database
		 *
		 * @param array $section
		 *
		 * @return array|mixed|string
		 */
		private function _insert_section( $section = array() ) {
			global $wpdb;
			$section = wp_parse_args(
				$section,
				array(
					'section_name'        => '',
					'section_course_id'   => 0,
					'section_order'       => 0,
					'section_description' => ''
				)
			);
			$section = stripslashes_deep( $section );
			extract( $section );

			$insert_data = compact( 'section_name', 'section_course_id', 'section_order', 'section_description' );
			$wpdb->insert(
				$wpdb->learnpress_sections,
				$insert_data,
				array( '%s', '%d', '%d' )
			);
			$section['section_id'] = $wpdb->insert_id;
			return $section;
		}

		/**
		 * @param array $item
		 *
		 * @return array
		 */
		private function _insert_item( $item = array() ) {
			$_post = $this->_cleanPostData();

			$item_id    = wp_insert_post(
				array(
					'post_title'  => $item['post_title'],
					'post_type'   => $item['post_type'],
					'post_status' => 'publish'
				)
			);
			$item['ID'] = $item_id;

			$this->_resetPostData( $_post );

			return $item;
		}

		private function _cleanPostData() {
			$_post = $_POST;
			if ( $_POST ) {
				foreach ( $_POST as $k => $v ) {
					unset( $_POST[$k] );
				}
			}
			return $_post;
		}

		private function _resetPostData( $_post ) {
			if ( $_post ) {
				foreach ( $_post as $k => $v ) {
					$_POST[$k] = $v;
				}
			}
			return $_POST;
		}

		/*
		 * Delete all sections in a course and reset auto increment
		 */
		private function _reset_sections() {
			global $wpdb, $post;

			$wpdb->query(
				$wpdb->prepare( "
					DELETE FROM si
					USING {$wpdb->learnpress_section_items} si
					INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
					INNER JOIN {$wpdb->posts} p ON p.ID = s.section_course_id
					WHERE p.ID = %d
				", $post->ID )
			);
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_section_items} AUTO_INCREMENT = 1
			" );

			$wpdb->query(
				$wpdb->prepare( "
					DELETE FROM {$wpdb->learnpress_sections}
					WHERE section_course_id = %d
				", $post->ID )
			);
			$wpdb->query( "
				ALTER TABLE {$wpdb->learnpress_sections} AUTO_INCREMENT = 1
			" );
		}

		public function _save() {
			global $wpdb, $post;

			$this->_reset_sections();
			if ( !empty( $_REQUEST['_lp_curriculum'] ) ) {
				$section_order = 0;
				$query_update  = array();
				$update_ids    = array();
				$query_insert  = array();
				foreach ( $_REQUEST['_lp_curriculum'] as $section_id => $_section ) {
					$section_id = 0;
					// section items
					$items             = $_section['items'];
					$item_order        = 0;
					$insert            = false;
					$sql_section_items = array();
					if ( !empty( $items ) ) {
						foreach ( $items as $section_item_id => $_item ) {

							// abort the item has not got a name
							if ( !$_item['name'] )
								continue;
							$insert = true;

							$item_id = $_item['item_id'];

							// if item has not got the ID then insert a new one
							if ( !$item_id ) {
								$item    = $this->_insert_item(
									array(
										'post_title' => $_item['name'],
										'post_type'  => $_item['post_type'],
									)
								);
								$item_id = $item['ID'];
							} else { // Otherwise, update existing
								if ( strcmp( $_item['name'], $_item['old_name'] ) !== 0 ) {
									$query_update[] = 'WHEN ' . $_item['item_id'] . ' THEN \'' . $_item['name'] . '\'';
									$update_ids[]   = $_item['item_id'];
									$update_data    = array(
										'ID'         => $_item['item_id'],
										'post_title' => $_item['name']
									);
									if ( LP()->settings->get( 'auto_update_post_name' ) == 'yes' ) {
										$update_data['post_name'] = sanitize_title( $_item['name'] );
									}
									// prevent update the meta of course for the items when update items
									$_post = $this->_cleanPostData();
									wp_update_post( $update_data );
									$this->_resetPostData( $_post );
								}
								$item_id = $_item['item_id'];
							}
							$sql_section_items[] = $wpdb->prepare( "(%d, %d, %d, %s)", - 9999999, $item_id, ++ $item_order, $_item['post_type'] );
						}
					}

					if ( $insert || ( !$insert && ( !empty( $_section['name'] ) || empty( $items ) ) ) ) {
						$section = array(
							'section_name'        => !empty( $_section['name'] ) ? $_section['name'] : '',
							'section_course_id'   => $post->ID,
							'section_order'       => ++ $section_order,
							'section_description' => $_section['description'],
							'items'               => array()
						);

						if ( !$section_id ) {
							$section    = $this->_insert_section( $section );
							$section_id = $section['section_id'];
						}
						$sections[$section_id] = $section;
						foreach ( $sql_section_items as $section_item ) {
							$query_insert[] = str_replace( - 9999999, $section_id, $section_item );
						}
					}
				}
				if ( $query_insert ) {
					$query_insert = "
						INSERT INTO {$wpdb->learnpress_section_items}(`section_id`, `item_id`, `item_order`, `item_type`)
						VALUES " . join( ', ', $query_insert ) . "
                    ";
					$wpdb->query( $query_insert );
				}
				if ( $query_update ) {
					$query_update = "
						UPDATE {$wpdb->posts}
						SET
								`post_title` = CASE `ID` " . join( ' ', $query_update ) . " END
						WHERE
								ID IN (" . join( ',', $update_ids ) . ")
                                                    ";
					$wpdb->query( $query_update );
				}
			}
		}

		private function _update_final_quiz() {
			global $post;
			$final_quiz = false;

			if ( learn_press_get_request( '_lp_course_result' ) == 'evaluate_final_quiz' ) {
				if ( $final_quiz = learn_press_get_final_quiz( $post->ID ) ) {
					update_post_meta( $post->ID, '_lp_final_quiz', $final_quiz );
				} else {
					delete_post_meta( $post->ID, '_lp_final_quiz' );
				}
			} else {
				delete_post_meta( $post->ID, '_lp_final_quiz' );
			}
			do_action( 'learn_press_update_final_quiz', $final_quiz, $post->ID );
			return $final_quiz;
		}

		private function _send_mail() {
			if ( !LP()->user->is_instructor() ) return;
			$mail = LP()->mail;

		}

		private function _review_log() {
			global $wpdb, $post;
			$user   = learn_press_get_current_user();
			$action = '';

			// Course is submitted by instructor
			if ( learn_press_get_request( 'learn_press_submit_course_notice_reviewer' ) == 'yes' ) {
				$action = 'learn_press_course_submit_for_reviewer';
			} // Course is submitted by admin
			elseif ( learn_press_get_request( 'learn_press_submit_course_notice_instructor' ) == 'yes' ) {
				$action = 'learn_press_course_submit_for_instructor';
			}

			if ( $user->is_admin() ) {
				if ( $post->post_status != 'publish' && get_post_status( $post->ID ) == 'publish' ) {
					do_action( 'learn_press_course_submit_approved', $post->ID );
					delete_post_meta( $post->ID, '_lp_submit_for_reviewer', 'yes' );
				} elseif ( get_post_status( $post->ID ) != 'publish' ) {
					do_action( 'learn_press_course_submit_rejected', $post->ID );
					delete_post_meta( $post->ID, '_lp_submit_for_reviewer', 'yes' );
				}
			} elseif ( $user->is_instructor() ) {
				if ( $post->post_status != 'publish' && get_post_meta( $post->ID, '_lp_submit_for_reviewer', true ) != 'yes' ) {
					do_action( 'learn_press_course_submit_for_reviewer', $post->ID );
					update_post_meta( $post->ID, '_lp_submit_for_reviewer', 'yes' );
				}
			}

			if ( !$action ) {
				return;
			}

			/*if ( !$required_review || ( $required_review && $enable_edit_published ) ) {
				return;
			}*/

			$user    = LP()->user;
			$message = learn_press_get_request( 'review_message' );

			if ( !$message && !$user->is_instructor() && get_post_status( $post->ID ) == 'publish' ) {
				$message = __( 'Your course has been published', 'learnpress' );
			}
			if ( apply_filters( 'learn_press_review_log_message', $message, $post->ID, $user->id ) ) {
				$table = $wpdb->prefix . 'learnpress_review_logs';
				if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
					$query = $wpdb->prepare( "
                                            INSERT INTO {$wpdb->learnpress_review_logs}(`course_id`, `user_id`, `message`, `date`, `status`, `user_type`)
                                            VALUES(%d, %d, %s, %s, %s, %s)
                                    ", $post->ID, $user->id, $message, current_time( 'mysql' ), get_post_status( $post->ID ), $user->is_instructor() ? 'instructor' : 'reviewer' );
					$wpdb->query( $query );
					do_action( 'learn_press_update_review_log', $wpdb->insert_id, $post->ID, $user->id );
				}
			}
			if ( $action == 'learn_press_course_submit_for_instructor' ) {
				if ( get_post_status( $post->ID ) == 'publish' ) {
					do_action( 'learn_press_course_submit_approved', $post->ID, $user );
				} else {
					do_action( 'learn_press_course_submit_rejected', $post->ID, $user );
				}
			} else {
				do_action( $action, $post->ID, $user );
			}
		}

		public function before_save_curriculum() {

			global $post, $pagenow;

			// Ensure that we are editing course in admin side
			if ( ( $pagenow != 'post.php' ) || ( get_post_type() != LP_COURSE_CPT ) ) {
				return;
			}

			remove_action( 'save_post', array( $this, 'before_save_curriculum' ), 1000 );
			//remove_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );

			$user                  = LP()->user;
			$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
			$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';

			if ( $user->is_instructor() && $required_review && !$enable_edit_published ) {
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => 'pending'
					),
					array( '%d', '%s' )
				);
			}

			$new_status = get_post_status( $post->ID );
			$old_status = get_post_meta( $post->ID, '_lp_course_status', true );

			$this->_save();
			$this->_update_final_quiz();

			if ( $new_status != $old_status ) {
				do_action( 'learn_press_transition_course_status', $new_status, $old_status, $post->ID );
				update_post_meta( $post->ID, '_lp_course_status', $new_status );
			}

			$this->_review_log();
			delete_post_meta( $post->ID, '_lp_curriculum' );
			//add_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );
		}

		public static function enqueue_scripts() {

		}

		/**
		 * Add columns to admin manage course page
		 *
		 * @param  array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {
			$user = wp_get_current_user();
			if ( in_array( 'lp_teacher', $user->roles ) ) {
				unset( $columns['author'] );
			}
			$keys   = array_keys( $columns );
			$values = array_values( $columns );
			$pos    = array_search( 'title', $keys );
			if ( $pos !== false ) {
				array_splice( $keys, $pos + 1, 0, array( 'sections', 'students', 'price' ) );
				array_splice( $values, $pos + 1, 0, array(
					__( 'Content', 'learnpress' ),
					__( 'Students', 'learnpress' ),
					__( 'Price', 'learnpress' ) ) );
				$columns = array_combine( $keys, $values );
			} else {
				$columns['sections'] = __( 'Content', 'learnpress' );
				$columns['students'] = __( 'Students', 'learnpress' );
				$columns['price']    = __( 'Price', 'learnpress' );
			}

			$columns['taxonomy-course_category'] = __( 'Categories', 'learnpress' );
			return $columns;
		}

		/**
		 * Print content for custom column
		 *
		 * @param string
		 * @param int
		 */
		public function columns_content( $column, $post_id = 0 ) {
			global $post;
			$course = LP_Course::get_course( $post->ID );
			switch ( $column ) {
				case 'sections':
					$sections = $course->get_curriculum();
					if ( $sections ) {
						$items          = $course->get_curriculum_items( array( 'group' => true ) );
						$count_sections = sizeof( $sections );
						$count_lessons  = sizeof( $items['lessons'] );
						$count_quizzes  = sizeof( $items['quizzes'] );
						$output         = sprintf( _nx( '%d section', '%d sections', $count_sections, 'learnpress' ), $count_sections );
						$output .= ' (';
						if ( $count_lessons ) {
							$output .= sprintf( _nx( '%d lesson', '%d lessons', $count_lessons, 'learnpress' ), $count_lessons );
						} else {
							$output .= __( "0 lesson", 'learnpress' );
						}
						$output .= ', ';
						if ( $count_quizzes ) {
							$output .= sprintf( _nx( '%d quiz', '%d quizzes', $count_quizzes, 'learnpress' ), $count_quizzes );
						} else {
							$output .= __( "0 quiz", 'learnpress' );
						}
						$output .= ')';
						echo $output;
					} else {
						_e( 'No content', 'learnpress' );
					}
					break;
				case 'price':
					$price   = get_post_meta( $post->ID, '_lp_price', true );
					$is_paid = get_post_meta( $post->ID, '_lp_payment', true );
					if ( $is_paid === 'yes' && $price ) {
						echo sprintf( '<a href="%s">%s</a>', add_query_arg( 'filter_price', $price ), learn_press_format_price( get_post_meta( $post->ID, '_lp_price', true ), true ) );
					} else {
						echo sprintf( '<a href="%s">%s</a>', add_query_arg( 'filter_price', 0 ), __( 'Free', 'learnpress' ) );
					}
					break;
				case 'students' :
					echo '<span class=lp-label-counter>' . count( $course->get_students_list( true ) ) . '</span>';

			}
		}

		/**
		 * Log the messages between admin and instructor
		 */
		public function post_review_message_box() {
			global $post;
			if ( get_post_type( $post->ID ) != 'lp_course' ) {
				return false;
			}

			//$user = learn_press_get_current_user();
			$course_user = learn_press_get_user( get_post_field( 'post_author', $post->ID ) );
			if ( $course_user->is_admin() ) {
				return;
			}

			$required_review = LP()->settings->get( 'required_review' ) == 'yes';
			//$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';
			//$is_publish            = get_post_status( $post->ID ) == 'publish';

			if ( !$required_review ) {
				return;
			}
			/*if( $enable_edit_published ){
				return;
			}*/
			learn_press_admin_view( 'meta-boxes/course/review-log' );
		}

		public function posts_fields( $fields ) {
			if ( !$this->_is_archive() ) {
				return $fields;
			}

			$fields = " DISTINCT " . $fields;
			if ( ( $this->_get_orderby() == 'price' ) || ( $this->_get_search() ) ) {
				$fields .= ', pm_price.meta_value as course_price';
			}
			return $fields;
		}

		/**
		 * @param $join
		 *
		 * @return string
		 */
		public function posts_join_paged( $join ) {
			if ( !$this->_is_archive() ) {
				return $join;
			}
			global $wpdb;
			$join .= " LEFT JOIN {$wpdb->postmeta} pm_price ON pm_price.post_id = {$wpdb->posts}.ID AND pm_price.meta_key = '_lp_price'";

			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
		public function posts_where_paged( $where ) {

			if ( !$this->_is_archive() ) {
				return $where;
			}
			global $wpdb;
			if ( array_key_exists( 'filter_price', $_REQUEST ) ) {
				if ( $_REQUEST['filter_price'] == 0 ) {
					$where .= " AND ( pm_price.meta_value IS NULL || pm_price.meta_value = 0 )";
				} else {
					$where .= $wpdb->prepare( " AND ( pm_price.meta_value = %s )", $_REQUEST['filter_price'] );
				}
			}

			return $where;
		}

		/**
		 * @param $order_by_statement
		 *
		 * @return string
		 */
		public function posts_orderby( $order_by_statement ) {
			if ( !$this->_is_archive() ) {
				return $order_by_statement;
			}
			switch ( $this->_get_orderby() ) {
				case 'price':
					$order_by_statement = "pm_price.meta_value {$_GET['order']}";
			}
			return $order_by_statement;
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns['price'] = 'price';

			return $columns;
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( LP_COURSE_CPT != $post_type ) ) {
				return false;
			}
			return true;
		}

		private function _get_orderby() {
			return isset( $_REQUEST['orderby'] ) ? $_REQUEST['orderby'] : '';
		}

		private function _get_search() {
			return isset( $_REQUEST['s'] ) ? $_REQUEST['s'] : false;
		}

		public static function instance() {
			if ( !self::$_instance ) {
				self::$_instance = new self( LP_COURSE_CPT );
			}
			return self::$_instance;
		}
	} // end LP_Course_Post_Type

	$course_post_type = LP_Course_Post_Type::instance();
	//$course_post_type->add_meta_box( 'course-editor', 'dfgdfgfdg', 'course_editor', 'advanced', 'default' );
}
