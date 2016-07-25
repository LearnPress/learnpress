<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Course_Post_Type' ) ) {

	// Base class for custom post type to extends
	learn_press_include( 'custom-post-types/abstract.php' );

	// class LP_Course_Post_Type
	final class LP_Course_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * Constructor
		 */
		public function __construct() {

			add_action( 'save_post', array( $this, 'before_save_curriculum' ), 1000 );
			add_filter( 'manage_lp_course_posts_columns', array( $this, 'columns_head' ) );
			add_filter( 'manage_lp_course_posts_custom_column', array( $this, 'columns_content' ) );
			add_filter( "rwmb__lpr_course_price_html", array( $this, 'currency_symbol' ), 5, 3 );
			add_action( 'edit_form_after_editor', array( $this, 'toggle_editor_button' ), - 10 );
			//add_action( 'add_meta_boxes', array( $this, 'review_logs_meta_box' ) );
			//add_action( 'post_submitbox_start', array( $this, 'post_review_message_box' ) );
			//add_action( 'learn_press_transition_course_status', array( $this, 'review_log' ), 10, 3 );
			add_action( 'load-post.php', array( $this, 'post_actions' ) );
			add_action( 'before_delete_post', array( $this, 'delete_course_sections' ) );
			add_filter( 'manage_edit-lp_course_sortable_columns', array( $this, 'columns_sortable' ) );
			add_filter( 'posts_fields', array( $this, 'posts_fields' ) );
			add_filter( 'posts_join_paged', array( $this, 'posts_join_paged' ) );
			add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ) );
			add_filter( 'posts_orderby', array( $this, 'posts_orderby' ) );
			add_action( 'admin_head', array( __CLASS__, 'print_js_template' ) );
			parent::__construct();
		}

		/**
		 * Delete all questions assign to quiz being deleted
		 *
		 * @param $post_id
		 */
		public function delete_course_sections( $post_id ) {
			global $wpdb;
			if ( get_post_type( $post_id ) !== 'lp_course' ) {
				return;
			}
			// delete all items in section first
			$section_ids = $wpdb->get_col( $wpdb->prepare( "SELECT section_id FROM {$wpdb->prefix}learnpress_sections WHERE section_course_id = %d", $post_id ) );
			if ( $section_ids ) {
				$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->prefix}learnpress_section_items WHERE section_id IN(" . join( ',', $section_ids ) . ")" ) );
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
					$wpdb->query(
						$wpdb->prepare( "
							DELETE FROM {$wpdb->prefix}learnpress_review_logs
							WHERE review_log_id = %d
						", $delete_log )
					);
					wp_redirect( admin_url( 'post.php?post=' . learn_press_get_request( 'post' ) . '&action=edit' ) );
				}
			}
		}

		public function toggle_editor_button( $post ) {
			if ( $post->post_type == LP()->course_post_type ) {
				?>
				<button class="button button-primary" data-hidden="<?php echo get_post_meta( $post->ID, '_lp_editor_hidden', true ); ?>" type="button" id="learn-press-button-toggle-editor"><?php _e( 'Toggle Course Content', 'learnpress' ); ?></button>
				<?php
			}
		}

		/**
		 * Generate params for course used in admin
		 *
		 * @static
		 * @return mixed
		 */
		public static function admin_params() {
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
		public static function admin_scripts() {
			LP_Admin_Assets::add_localize(
				array(
					'notice_remove_section_item' => __( 'Are you sure you want to remove this item?', 'learnpress' )
				),
				null,
				'admin-course'
			);
			if ( in_array( get_post_type(), array( LP()->course_post_type, LP()->lesson_post_type ) ) ) {
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
				wp_localize_script( 'lp-meta-boxes', 'lp_course_params', self::admin_params() );
			}
		}

		/**
		 * Enqueue styles
		 *
		 * @static
		 */
		public static function admin_styles() {

		}

		/**
		 * Print js template
		 */
		public static function print_js_template() {
			if ( get_post_type() != LP()->course_post_type ) return;
			learn_press_admin_view( 'meta-boxes/course/js-template.php' );
		}

		public function currency_symbol( $input_html, $field, $sub_meta ) {
			return $input_html . '<span class="lpr-course-price-symbol">' . learn_press_get_currency_symbol() . '</span>';
		}

		/**
		 * Register course post type
		 */
		public static function register_post_type() {
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
				'not_found'          => sprintf( __( 'You have not got any course yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_course' ) ),
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
				'has_archive'        => ( $page_id = learn_press_get_page_id( 'courses' ) ) && get_post( $page_id ) ? get_page_uri( $page_id ) : 'courses',
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'taxonomies'         => array( 'course_category', 'course_tag' ),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'excerpt' ),
				'hierarchical'       => true,
				'rewrite'            => $course_permalink ? array(
					'slug'       => untrailingslashit( $course_permalink ),
					'with_front' => false
				) : false
			);
			register_post_type( LP_COURSE_CPT, $args );

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

		/**
		 * Add meta boxes to course post type page
		 */
		public static function add_meta_boxes() {

			new RW_Meta_Box( self::curriculum_meta_box() );
			new RW_Meta_Box( self::settings_meta_box() );
			new RW_Meta_Box( self::assessment_meta_box() );
			new RW_Meta_Box( self::payment_meta_box() );

		}

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
						'type' => 'number',
						'desc' => __( 'The duration of the course (by weeks)', 'learnpress' ),
						'std'  => 10,
					),
					array(
						'name' => __( 'Maximum students', 'learnpress' ),
						'id'   => "{$prefix}max_students",
						'type' => 'number',
						'desc' => __( 'Maximum number of students can be enroll this course', 'learnpress' ),
						'std'  => 1000,
					),
					array(
						'name' => __( 'Students enrolled', 'learnpress' ),
						'id'   => "{$prefix}students",
						'type' => 'number',
						'desc' => __( 'How many students has took this course', 'learnpress' ),
						'std'  => 0,
					),
					array(
						'name' => __( 'Re-take course', 'learnpress' ),
						'id'   => "{$prefix}retake_count",
						'type' => 'number',
						'desc' => __( 'How many times the user can re-take this course. Set to 0 to disable', 'learnpress' ),
						'std'  => '0',
					),
					array(
						'name'    => __( 'Load media libraries', 'learnpress' ),
						'id'      => "{$prefix}load_media",
						'type'    => 'radio',
						'desc'    => __( 'Load media assets for shortcode. Only use this option if you use shortcode <code>[audio]</code> in your course or lesson content', 'learnpress' ),
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

		public static function assessment_meta_box() {
			$post_id            = learn_press_get_request( 'post' );
			$prefix             = '_lp_';
			$course_result_desc = __( 'The way to assess the result of course for a student', 'learnpress' );
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
							'evaluate_final_quiz' => __( 'Evaluate result of final quiz', 'learnpress' )
						),
						'std'     => 'evaluate_lesson'
					),
					array(
						'name' => __( 'Passing condition value', 'learnpress' ),
						'id'   => "{$prefix}passing_condition",
						'type' => 'number',
						'min'  => 1,
						'max'  => 100,
						'desc' => __( 'The percentage of quiz result or lessons completed to finish the course', 'learnpress' ),
						'std'  => 50
					)
				)
			);
			return apply_filters( 'learn_press_course_assessment_metabox', $meta_box );
		}

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
						'desc'    => __( 'If Paid be checked, An administrator will review then set course price and commission', 'learnpress' ),
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
				$message = __( 'If free, this field is empty or set 0. (Only admin can edit this field)', 'learnpress' );
				$price   = 0;

				if ( isset( $_GET['post'] ) ) {
					$course_id = $_GET['post'];
					$type      = get_post_meta( $course_id, '_lp_payment', true );
					if ( $type != 'free' ) {
						$suggest_price = get_post_meta( $course_id, '_lp_suggestion_price', true );
						if ( isset( $suggest_price ) ) {
							$message = __( 'This course is enrolled require and the suggestion price is ', 'learnpress' ) . '<span>' . learn_press_get_currency_symbol() . $suggest_price . '</span>';
							$price   = $suggest_price;
						}
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

		public function review_logs_meta_box() {
			add_meta_box(
				'review_logs',
				__( 'Review Logs', 'learnpress' ),
				array( $this, 'review_logs_content' ),
				LP()->course_post_type,
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
			$view_all      = learn_press_get_request( 'view_all_review' );
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

		private function _insert_item( $item = array() ) {
			$item_id = wp_insert_post(
				array(
					'post_title'  => $item['post_title'],
					'post_type'   => $item['post_type'],
					'post_status' => 'publish'
				)
			);

			$item['ID'] = $item_id;
			return $item;
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

			//learn_press_debug($_REQUEST['_lp_curriculum'], true);
			if ( !empty( $_REQUEST['_lp_curriculum'] ) ) {
				$section_order = 0;
				$query_update  = array();
				$update_ids    = array();
				$query_insert  = array();
				foreach ( $_REQUEST['_lp_curriculum'] as $section_id => $_section ) {
					$section_id = 0;//absint( $section_id );
					if ( !$_section['name'] ) continue;

					$section = array(
						'section_name'        => $_section['name'],
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

					// update items, if there are no items in current section then move to next one;
					if ( empty( $_section['items'] ) ) continue;

					$items      = $_section['items'];
					$item_order = 0;
					foreach ( $items as $section_item_id => $_item ) {

						// abort the item has not got a name
						if ( !$_item['name'] ) continue;
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
								//$tmp_post = $_POST;
								///$_POST    = array();
								wp_update_post( $update_data );
								////$_POST = $tmp_post;
							}
							$item_id = $_item['item_id'];
						}
						$query_insert[] = $wpdb->prepare( "(%d, %d, %d, %s)", $section_id, $item_id, ++ $item_order, $_item['post_type'] );
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
			if ( ( $send = $mail->send( 'tunn@foobla.com', 'tunnhn@gmail.com', 'This is the subject', 'this is the content' ) ) !== true ) {
				echo 'error';
				print_r( $send );
				die();
			} else {

			}
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
				$message = __( 'Your course has published', 'learnpress' );
			}
			if ( apply_filters( 'learn_press_review_log_message', $message, $post->ID, $user->id ) ) {
				$query = $wpdb->prepare( "
					INSERT INTO {$wpdb->learnpress_review_logs}(`course_id`, `user_id`, `message`, `date`, `status`, `user_type`)
					VALUES(%d, %d, %s, %s, %s, %s)
				", $post->ID, $user->id, $message, current_time( 'mysql' ), get_post_status( $post->ID ), $user->is_instructor() ? 'instructor' : 'reviewer' );
				$wpdb->query( $query );
				do_action( 'learn_press_update_review_log', $wpdb->insert_id, $post->ID, $user->id );
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
			if ( ($pagenow != 'post.php') || ( get_post_type() != LP()->course_post_type ) ) {
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
			unset( $_POST['_lp_curriculum'] );

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
				array_splice( $keys, $pos + 1, 0, array( 'sections', 'price' ) );
				array_splice( $values, $pos + 1, 0, array( __( 'Content', 'learnpress' ), __( 'Price', 'learnpress' ) ) );
				$columns = array_combine( $keys, $values );
			} else {
				$columns['sections'] = __( 'Content', 'learnpress' );
				$columns['price']    = __( 'Price', 'learnpress' );
			}

			$columns['taxonomy-course_category'] = __( 'Categories', 'learnpress' );
			return $columns;
		}

		/**
		 * Print content for custom column
		 *
		 * @param $column
		 */
		public function columns_content( $column ) {
			global $post;
			switch ( $column ) {
				case 'sections':
					$course   = LP_Course::get_course( $post->ID );
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
					$price = get_post_meta( $post->ID, '_lp_price', true );
					if ( $price ) {
						echo sprintf( '<a href="%s">%s</a>', add_query_arg( 'filter_price', $price ), learn_press_format_price( get_post_meta( $post->ID, '_lp_price', true ), true ) );
					} else {
						echo sprintf( '<a href="%s">%s</a>', add_query_arg( 'filter_price', 0 ), __( 'Free', 'learnpress' ) );
					}
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
		public function columns_sortable( $columns ) {
			$columns['price'] = 'price';
			return $columns;
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( !is_admin() || ( $pagenow != 'edit.php' ) || ( LP()->course_post_type != $post_type ) ) {
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
	} // end LP_Course_Post_Type
	new LP_Course_Post_Type();
}
