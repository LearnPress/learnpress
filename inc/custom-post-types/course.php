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
		function __construct() {

			add_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );
			add_filter( 'manage_lp_course_posts_columns', array( $this, 'columns_head' ) );
			add_filter( 'manage_lp_course_posts_custom_column', array( $this, 'columns_content' ) );
			add_filter( "rwmb__lpr_course_price_html", array( $this, 'currency_symbol' ), 5, 3 );
			add_action( 'edit_form_after_editor', array( $this, 'toggle_editor_button' ), - 10 );
			add_action( 'add_meta_boxes', array( $this, 'review_logs_meta_box' ) );
			add_action( 'post_submitbox_start', array( $this, 'post_review_message_box' ) );
			add_action( 'learn_press_transition_course_status', array( $this, 'review_log' ), 10, 3 );
			add_action( 'load-post.php', array( $this, 'post_actions' ) );
			add_action( 'before_delete_post', array( $this, 'delete_course_sections' ) );

			parent::__construct();
		}

		/**
		 * Delete all questions assign to quiz being deleted
		 *
		 * @param $post_id
		 */
		function delete_course_sections( $post_id ) {
			global $wpdb;
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
		function post_actions() {
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

		function toggle_editor_button( $post ) {
			if ( $post->post_type == LP()->course_post_type ) {
				?>
				<button class="button button-primary" data-hidden="<?php echo get_post_meta( $post->ID, '_lp_editor_hidden', true ); ?>" type="button" id="learn-press-button-toggle-editor"><?php _e( 'Toggle Course Content', 'learn_press' ); ?></button>
				<?php
			}
		}

		/**
		 * Generate params for course used in admin
		 *
		 * @static
		 * @return mixed
		 */
		static function admin_params() {
			global $post;
			return apply_filters( 'learn_press_admin_course_params',
				array(
					'id'                        => absint( $post->ID ),
					'notice_empty_title'        => __( 'Please enter the title of the course', 'learn_press' ),
					'notice_empty_section'      => __( 'Please add at least one section for the course', 'learn_press' ),
					'notice_empty_section_name' => __( 'Please enter the title of the section', 'learn_press' ),
					'notice_empty_price'        => __( 'Please set a price for this course', 'learn_press' )
				)
			);
		}

		/**
		 * Enqueue scripts
		 *
		 * @static
		 */
		static function admin_scripts() {
			LP_Admin_Assets::add_localize(
				array(
					'notice_remove_section_item' => __( 'Are you sure you want to remove this item?', 'learn_press' )
				),
				null,
				'admin-course'
			);
			if ( in_array( get_post_type(), array( LP()->course_post_type, LP()->lesson_post_type ) ) ) {

				//wp_enqueue_style( 'lp-meta-boxes', LP()->plugin_url( 'assets/css/meta-boxes.css' ) );
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
				///wp_enqueue_script( 'lp-meta-boxes', LP()->plugin_url( 'assets/js/meta-boxes.js', 'jquery', 'backbone', 'util' ) );

				wp_localize_script( 'lp-meta-boxes', 'lp_course_params', self::admin_params() );
			}
		}

		/**
		 * Enqueue styles
		 *
		 * @static
		 */
		static function admin_styles() {

		}

		/**
		 * Print js template
		 */
		static function print_js_template() {
			if ( get_post_type() != LP()->course_post_type ) return;
			learn_press_admin_view( 'meta-boxes/course/js-template.php' );
		}

		function currency_symbol( $input_html, $field, $sub_meta ) {
			return $input_html . '<span class="lpr-course-price-symbol">' . learn_press_get_currency_symbol() . '</span>';
		}

		/**
		 * Register course post type
		 */
		static function register_post_type() {
			$settings = LP_Settings::instance();
			$labels   = array(
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
				'not_found'          => sprintf( __( 'You have not got any course yet. Click <a href="%s">Add new</a> to start', 'learn_press' ), admin_url( 'post-new.php?post_type=lp_course' ) ),
				'not_found_in_trash' => __( 'No course found in Trash', 'learn_press' ),
			);

			$course_permalink = empty( $course_base = $settings->get( 'course_base' ) ) ? _x( 'courses', 'slug', 'learn_press' ) : $course_base;

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'query_var'          => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => ( $page_id = learn_press_get_page_id( 'courses' ) ) && get_post( $page_id ) ? get_page_uri( $page_id ) : 'course',
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'taxonomies'         => array( 'course_category', 'course_tag' ),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'author' ),
				'hierarchical'       => true,
				'rewrite'            => array(
					'slug'         => $course_permalink,
					'hierarchical' => true,
					'with_front'   => false
				)
			);
			register_post_type( LP_COURSE_CPT, $args );

			register_taxonomy( 'course_category', array( LP_COURSE_CPT ),
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
						'slug'         => empty( $category_base = $settings->get( 'course_category_base' ) ) ? _x( 'course-category', 'slug', 'learn_press' ) : $category_base,
						'hierarchical' => true,
						'with_front'   => false
					),
				)
			);
			register_taxonomy( 'course_tag', array( LP_COURSE_CPT ),
				array(
					'labels'                => array(
						'name'                       => __( 'Course Tags', 'learn_press' ),
						'singular_name'              => __( 'Tag', 'learn_press' ),
						'search_items'               => __( 'Search Course Tags', 'learn_press' ),
						'popular_items'              => __( 'Popular Course Tags', 'learn_press' ),
						'all_items'                  => __( 'All Course Tags', 'learn_press' ),
						'parent_item'                => null,
						'parent_item_colon'          => null,
						'edit_item'                  => __( 'Edit Course Tag', 'learn_press' ),
						'update_item'                => __( 'Update Course Tag', 'learn_press' ),
						'add_new_item'               => __( 'Add New Course Tag', 'learn_press' ),
						'new_item_name'              => __( 'New Course Tag Name', 'learn_press' ),
						'separate_items_with_commas' => __( 'Separate tags with commas', 'learn_press' ),
						'add_or_remove_items'        => __( 'Add or remove tags', 'learn_press' ),
						'choose_from_most_used'      => __( 'Choose from the most used tags', 'learn_press' ),
						'menu_name'                  => __( 'Tags', 'learn_press' ),
					),
					'public'                => true,
					'hierarchical'          => false,
					'show_ui'               => true,
					'show_in_menu'          => 'learn_press',
					'update_count_callback' => '_update_post_term_count',
					'query_var'             => true,
					'rewrite'               => array(
						'slug'       => empty( $tag_base = $settings->get( 'course_tag_base' ) ) ? _x( 'course-tag', 'slug', 'learn_press' ) : $tag_base,
						'with_front' => false
					),
				)
			);
		}

		/**
		 * Add meta boxes to course post type page
		 */
		static function add_meta_boxes() {

			new RW_Meta_Box( self::curriculum_meta_box() );
			new RW_Meta_Box( self::settings_meta_box() );
			new RW_Meta_Box( self::assessment_meta_box() );
			new RW_Meta_Box( self::payment_meta_box() );
		}

		static function curriculum_meta_box() {
			$prefix = '_lp_';

			$meta_box = array(
				'id'       => 'course_curriculum',
				'title'    => __( 'Curriculum', 'learn_press' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name' => __( 'Course Curriculum', 'learn_press' ),
						'id'   => "{$prefix}curriculum",
						'type' => 'curriculum',
						'desc' => '',
					),
				)
			);

			return apply_filters( 'learn_press_course_curriculum_meta_box_args', $meta_box );
		}

		static function settings_meta_box() {
			$prefix = '_lp_';

			$meta_box = array(
				'id'       => 'course_settings',
				'title'    => __( 'General Settings', 'learn_press' ),
				'pages'    => array( LP_COURSE_CPT ),
				'priority' => 'high',
				'fields'   => array(
					array(
						'name' => __( 'Duration', 'learn_press' ),
						'id'   => "{$prefix}duration",
						'type' => 'number',
						'desc' => __( 'The duration of the course (by weeks)', 'learn_press' ),
						'std'  => 10,
					),
					array(
						'name' => __( 'Maximum students', 'learn_press' ),
						'id'   => "{$prefix}max_students",
						'type' => 'number',
						'desc' => __( 'Maximum number of students can be enroll this course', 'learn_press' ),
						'std'  => 1000,
					),
					array(
						'name' => __( 'Students enrolled', 'learn_press' ),
						'id'   => "{$prefix}students",
						'type' => 'number',
						'desc' => __( 'How many students has took this course', 'learn_press' ),
						'std'  => 0,
					),
					array(
						'name' => __( 'Re-take course', 'learn_press' ),
						'id'   => "{$prefix}retake_count",
						'type' => 'number',
						'desc' => __( 'How many times the user can re-take this course. Set to 0 to disable', 'learn_press' ),
						'std'  => '0',
					),

				)
			);

			return apply_filters( 'learn_press_course_settings_meta_box_args', $meta_box );
		}

		static function assessment_meta_box() {
			$post_id            = learn_press_get_request( 'post' );
			$prefix             = '_lp_';
			$course_result_desc = __( 'The way to assess the result of course for a student', 'learn_press' );
			if ( $post_id && get_post_meta( $post_id, '_lp_course_result', true ) == 'evaluate_final_quiz' && !get_post_meta( $post_id, '_lp_final_quiz', true ) ) {
				$course_result_desc .= __( '<br /><strong>Note! </strong>No final quiz in course, please add a final quiz', 'learn_press' );
			}
			$meta_box = array(
				'id'       => 'course_assessment',
				'title'    => __( 'Assessment', 'learn_press' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name'    => __( 'Course result', 'learn_press' ),
						'id'      => "{$prefix}course_result",
						'type'    => 'radio',
						'desc'    => $course_result_desc,
						'options' => array(
							'evaluate_lesson'     => __( 'Evaluate lessons', 'learn_press' ),
							'evaluate_final_quiz' => __( 'Evaluate result of final quiz', 'learn_press' )
						),
						'std'     => 'no'
					),
					array(
						'name' => __( 'Passing condition value', 'learn_press' ),
						'id'   => "{$prefix}passing_condition",
						'type' => 'number',
						'min'  => 1,
						'max'  => 100,
						'desc' => __( 'The percentage of quiz result or lessons completed to finish the course', 'learn_press' ),
						'std'  => 50
					)
				)
			);
			return apply_filters( 'learn_press_course_assessment_metabox', $meta_box );
		}

		static function payment_meta_box() {

			$prefix = '_lp_';

			$meta_box = array(
				'id'       => 'course_payment',
				'title'    => __( 'Payment Settings', 'learn_press' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name'    => __( 'Requires enroll', 'learn_press' ),
						'id'      => "{$prefix}required_enroll",
						'type'    => 'radio',
						'desc'    => __( 'Require users logged in to study or public to all', 'learn_press' ),
						'options' => array(
							'yes' => __( 'Yes, enroll is required', 'learn_press' ),
							'no'  => __( 'No', 'learn_press' ),
						),
						'std'     => 'yes',
						'class'   => 'hide-if-js'
					),
					array(
						'name'    => __( 'Course payment', 'learn_press' ),
						'id'      => "{$prefix}payment",
						'type'    => 'radio',
						'desc'    => __( 'If Paid be checked, An administrator will review then set course price and commission', 'learn_press' ),
						'options' => array(
							'no'     => __( 'Free', 'learn_press' ),
							'yes' => __( 'Paid', 'learn_press' ),
						),
						'std'     => 'no',
						'class'   => 'lp-course-payment-field'
					)
				)
			);

			if ( current_user_can( 'manage_options' ) ) {
				$message = __( 'If free, this field is empty or set 0. (Only admin can edit this field)', 'learn_press' );
				$price   = 0;

				if ( isset( $_GET['post'] ) ) {
					$course_id = $_GET['post'];
					$type      = get_post_meta( $course_id, '_lp_payment', true );
					if ( $type != 'free' ) {
						$suggest_price = get_post_meta( $course_id, '_lp_suggestion_price', true );
						if ( isset( $suggest_price ) ) {
							$message = __( 'This course is enrolled require and the suggestion price is ', 'learn_press' ) . '<span>' . learn_press_get_currency_symbol() . $suggest_price . '</span>';
							$price   = $suggest_price;
						}
					} else {
						$message = __( 'This course is free.', 'learn_press' );
					};
				}
				array_push(
					$meta_box['fields'],
					array(
						'name'  => __( 'Price', 'learn_press' ),
						'id'    => "{$prefix}price",
						'type'  => 'number',
						'min'   => 0,
						'step'  => 0.01,
						'desc'  => $message,
						'std'   => $price,
						'class' => 'lp-course-price-field hide-if-js'
					)
				);
			} else {
				array_push(
					$meta_box['fields'],
					array(
						'name'  => __( 'Course Suggestion Price', 'learn_press' ),
						'id'    => "{$prefix}course_suggestion_price",
						'type'  => 'number',
						'min'   => 0,
						'step'  => 0.01,
						'desc'  => __( 'The course price you want to suggest for admin to set.', 'learn_press' ),
						'class' => 'lp-course-price-field hide-if-js',
						'std'   => 0
					)
				);
			}
			return apply_filters( 'learn_press_course_payment_meta_box_args', $meta_box );
		}

		function review_logs_meta_box() {
			add_meta_box(
				'review_logs',
				__( 'Review Logs', 'learn_press' ),
				array( $this, 'review_logs_content' ),
				LP()->course_post_type,
				'normal',
				'default'
			);
		}

		function review_logs_content( $post ) {
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

		function _save() {
			global $wpdb, $post;

			$this->_reset_sections();
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

					// update items;
					if ( empty( $_section['items'] ) ) continue;

					$items      = $_section['items'];
					$item_order = 0;
					//print_r($items);die();
					foreach ( $items as $section_item_id => $_item ) {
						if ( !$_item['name'] ) continue;
						//$section_item_id = absint( $section_item_id );
						$item_id = $_item['item_id'];
						if ( !$item_id ) {
							$item    = $this->_insert_item(
								array(
									'post_title' => $_item['name'],
									'post_type'  => $_item['post_type'],
								)
							);
							$item_id = $item['ID'];
						} else {
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
								$tmp_post = $_POST;
								$_POST = array();
								wp_update_post( $update_data );
								$_POST = $tmp_post;
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

		function review_log( $new_status, $old_status, $course_id ) {
			global $wpdb, $post;

			$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
			$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';

			if ( !$required_review || ( $required_review && $enable_edit_published ) ) {
				return;
			}

			$user    = learn_press_get_current_user();
			$message = learn_press_get_request( 'review_message' );

			if ( LP()->user->is_instructor() && $required_review && !$enable_edit_published ) {
				remove_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => 'pending'
					),
					array( '%d', '%s' )
				);
				add_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );
			}

			if ( !$message && !$user->is_instructor() && get_post_status( $post->ID ) == 'publish' ) {
				$message = __( 'Your course has published', 'learn_press' );
			}
			if ( apply_filters( 'learn_press_review_log_message', $message, $post->ID, $user->id ) ) {
				$query = $wpdb->prepare( "
					INSERT INTO {$wpdb->learnpress_review_logs}(`course_id`, `user_id`, `message`, `date`, `status`, `user_type`)
					VALUES(%d, %d, %s, %s, %s, %s)
				", $post->ID, $user->id, $message, current_time( 'mysql' ), get_post_status( $post->ID ), $user->is_instructor() ? 'instructor' : 'reviewer' );
				$wpdb->query( $query );
				do_action( 'learn_press_update_review_log', $wpdb->insert_id, $post->ID, $user->id );
			}
		}

		function before_save_curriculum() {

			global $post;

			if ( get_post_type() != LP()->course_post_type ) return;

			$new_status = get_post_status( $post->ID );
			$old_status = get_post_meta( $post->ID, '_lp_course_status', true );

			$this->_save();
			$this->_update_final_quiz();

			if ( $new_status != $old_status ) {
				do_action( 'learn_press_transition_course_status', $new_status, $old_status, $post->ID );
				update_post_meta( $post->ID, '_lp_course_status', $new_status );
			} else {
				$this->review_log();
			}

			do_action( 'learn_press_new_course_submitted', $post->ID, LP()->user );
		}

		static function enqueue_scripts() {

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
			if ( in_array( 'lp_teacher', $user->roles ) ) {
				unset( $columns['author'] );
			}
			$keys   = array_keys( $columns );
			$values = array_values( $columns );
			$pos    = array_search( 'title', $keys );
			if ( $pos !== false ) {
				array_splice( $keys, $pos + 1, 0, array( 'sections', 'price' ) );
				array_splice( $values, $pos + 1, 0, array( __( 'Content', 'learn_press' ), __( 'Price', 'learn_press' ) ) );
				$columns = array_combine( $keys, $values );
			} else {
				$columns['sections'] = __( 'Content', 'learn_press' );
				$columns['price'] = __( 'Price', 'learn_press' );
			}

			$columns['taxonomy-course_category'] = __( 'Categories', 'learn_press' );
			return $columns;
		}

		/**
		 * Print content for custom column
		 *
		 * @param $column
		 */
		function columns_content( $column ) {
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
						$output         = sprintf( _nx( '%d section', '%d sections', $count_sections, 'learn_press' ), $count_sections );
						$output .= ' (';
						if ( $count_lessons ) {
							$output .= sprintf( _nx( '%d lesson', '%d lessons', $count_lessons, 'learn_press' ), $count_lessons );
						} else {
							$output .= __( "0 lesson", 'learn_press' );
						}
						$output .= ', ';
						if ( $count_quizzes ) {
							$output .= sprintf( _nx( '%d quiz', '%d quizzes', $count_quizzes, 'learn_press' ), $count_quizzes );
						} else {
							$output .= __( "0 quiz", 'learn_press' );
						}
						$output .= ')';
						echo $output;
					} else {
						_e( 'No content', 'learn_press' );
					}
					break;
				case 'price':
					$price = get_post_meta( $post->ID, '_lp_price', true );
					echo $price ? learn_press_format_price( get_post_meta( $post->ID, '_lp_price', true ), true ) : __( 'Free', 'learn_press' );
			}
		}

		/**
		 * Log the messages between admin and instructor
		 */
		function post_review_message_box() {
			global $post;
			learn_press_admin_view( 'meta-boxes/course/review-log' );
		}
	} // end LP_Course_Post_Type
	new LP_Course_Post_Type();
}
