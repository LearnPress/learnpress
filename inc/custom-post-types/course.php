<?php
/**
 * Class LP_Course_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! class_exists( 'LP_Course_Post_Type' ) ) {

	/**
	 * Class LP_Course_Post_Type
	 */
	final class LP_Course_Post_Type extends LP_Abstract_Post_Type_Core {
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
		 * @var bool
		 */
		protected static $_enable_review = true;

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
				->add_map_method( 'before_delete', 'before_delete_course' );

			add_action( 'init', array( $this, 'register_taxonomy' ) );
			add_action( 'load-post.php', array( $this, 'post_actions' ) );
			add_filter( 'get_edit_post_link', array( $this, 'add_course_tab_arg' ) );
			add_filter( 'rwmb__lpr_course_price_html', array( $this, 'currency_symbol' ), 5, 3 );
			// add_filter( 'posts_where_paged', array( $this, 'posts_where_paged' ), 10 );
			add_filter( 'posts_where_paged', array( $this, '_posts_where_paged_course_items' ), 10 );
			add_filter( 'posts_join_paged', array( $this, '_posts_join_paged_course_items' ), 10 );

			if ( self::$_enable_review ) {
				add_action( 'post_submitbox_start', array( $this, 'post_review_message_box' ) );
			}

			add_action( 'edit_form_after_editor', array( $this, 'template_course_editor' ) );
			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_course_editor' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_script_data' ) );
		}

		public function add_script_data() {
			global $post, $pagenow;

			if ( empty( $post ) || ( get_post_type() !== $this->_post_type ) ||
			     ! in_array( $pagenow, array( 'post.php', 'post-new.php', ) ) ) {
				return;
			}

			$course          = learn_press_get_course( $post->ID );
			$hidden_sections = get_post_meta( $post->ID, '_admin_hidden_sections', true );

			$data = apply_filters( 'learn-press/admin-localize-course-editor',
				array(
					'root'        => array(
						'course_id'          => $post->ID,
						'auto_draft'         => get_post_status( $post->ID ) == 'auto-draft',
						'ajax'               => admin_url( 'index.php' ),
						'disable_curriculum' => false,
						'action'             => 'admin_course_editor',
						'nonce'              => wp_create_nonce( 'learnpress_update_curriculum' ),
					),
					'chooseItems' => array(
						'types'      => learn_press_course_get_support_item_types(),
						'open'       => false,
						'addedItems' => array(),
						'items'      => array(),
					),
					'i18n'        => array(
						'item'                   => __( 'item', 'learnpress' ),
						'new_section_item'       => __( 'Create a new', 'learnpress' ),
						'back'                   => __( 'Back', 'learnpress' ),
						'selected_items'         => __( 'Selected items', 'learnpress' ),
						'confirm_trash_item'     => __( 'Do you want to remove item "{{ITEM_NAME}}" to trash?',
							'learnpress' ),
						'item_labels'            => array(
							'singular' => __( 'Item', 'learnpress' ),
							'plural'   => __( 'Items', 'learnpress' ),
						),
						'notice_sale_price'      => __( 'Course sale price must less than the regular price',
							'learnpress' ),
						'notice_price'           => __( 'Course price must greater than the sale price', 'learnpress' ),
						'notice_sale_start_date' => __( 'Sale start date must before sale end date', 'learnpress' ),
						'notice_sale_end_date'   => __( 'Sale end date must after sale start date', 'learnpress' ),
						'notice_invalid_date'    => __( 'Invalid date', 'learnpress' ),
					),
					'sections'    => array(
						'sections'        => $course->get_curriculum_raw(),
						'hidden_sections' => ! empty( $hidden_sections ) ? $hidden_sections : array(),
						'urlEdit'         => admin_url( 'post.php?action=edit&post=' ),
					),
				)
			);

			learn_press_admin_assets()->add_script_data( 'learn-press-admin-course-editor', $data );
		}

		/**
		 * Register course post type.
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
				'not_found'          => sprintf( __( 'You haven\'t had any courses yet. Click <a href="%s">Add new</a> to start',
					'learnpress' ), admin_url( 'post-new.php?post_type=lp_course' ) ),
				'not_found_in_trash' => __( 'No course found in Trash', 'learnpress' ),
			);
			$course_base      = $settings->get( 'course_base' );
			$course_permalink = empty( $course_base ) ? _x( 'courses', 'slug', 'learnpress' ) : $course_base;

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'query_var'          => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => 'courses',
				// ( $page_id = learn_press_get_page_id( 'courses' ) ) && get_post( $page_id ) ? get_page_uri( $page_id ) : 'courses',
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'taxonomies'         => array( 'course_category', 'course_tag' ),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'excerpt' ),
				'hierarchical'       => false,
				'rewrite'            => $course_permalink ? array(
					'slug'       => untrailingslashit( $course_permalink ),
					'with_front' => false,
				) : false,
			);

			return $args;
		}

		/**
		 * Register course taxonomy.
		 */
		public function register_taxonomy() {

			$settings = LP()->settings;

			$category_base = $settings->get( 'course_category_base' );
			register_taxonomy( 'course_category',
				array( LP_COURSE_CPT ),
				array(
					'label'             => __( 'Course Categories', 'learnpress' ),
					'labels'            => array(
						'name'          => __( 'Course Categories', 'learnpress' ),
						'menu_name'     => __( 'Category', 'learnpress' ),
						'singular_name' => __( 'Category', 'learnpress' ),
						'add_new_item'  => __( 'Add New Course Category', 'learnpress' ),
						'all_items'     => __( 'All Categories', 'learnpress' ),
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
						'slug'         => empty( $category_base ) ? _x( 'course-category', 'slug',
							'learnpress' ) : $category_base,
						'hierarchical' => true,
						'with_front'   => false,
					),
				)
			);

			$tag_base = $settings->get( 'course_tag_base' );
			register_taxonomy( 'course_tag',
				array( LP_COURSE_CPT ),
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
						'with_front' => false,
					),
				)
			);
		}

		/**
		 * Load data for course editor.
		 *
		 * @since 3.0.0
		 */
		public function data_course_editor() {
			if ( LP_COURSE_CPT !== get_post_type() ) {
				return;
			}

		}

		/**
		 * Template course editor v2.
		 *
		 * @since 3.0.0
		 */
		public function template_course_editor() {
			if ( LP_COURSE_CPT !== get_post_type() ) {
				return;
			}
			learn_press_admin_view( 'course/editor' );
		}

		/**
		 * Add tab arg to admin edit course url.
		 *
		 * @param $m
		 *
		 * @return string
		 */
		public function add_course_tab_arg( $m ) {
			if ( array_key_exists( '_lp_curriculum', $_POST ) && ! empty( $_POST['course-tab'] ) ) {
				$m = add_query_arg( 'tab', sanitize_text_field( wp_unslash( $_POST['course-tab'] ) ), $m );
			}

			return $m;
		}

		/**
		 * Update course.
		 *
		 * @param $course_id
		 */
		public function update_course( $course_id ) {
			global $wpdb;

			/**
			 * Update all course items if set Course Author option
			 */
			$course      = learn_press_get_course( $course_id );
			$post_author = isset( $_POST['_lp_course_author'] ) ? sanitize_text_field( wp_unslash( $_POST['_lp_course_author'] ) ) : '';

			if ( ! $curriculum = $course->get_items() ) {
				if ( $post_author ) {
					$wpdb->update(
						$wpdb->posts,
						array( 'post_author' => $post_author ),
						array( 'ID' => $course_id )
					);
				}

				return;
			}
			// course curriculum items / quiz items / questions of quiz
			$item_ids = $quiz_ids = $question_ids = array();

			// get curriculum item
			foreach ( $curriculum as $item_id ) {
				$item_ids[] = (int) $item_id;

				// filter quiz item
				if ( learn_press_get_post_type( $item_id ) == LP_QUIZ_CPT ) {
					$quiz = LP_Quiz::get_quiz( $item_id );
					if ( $questions = $quiz->get_questions() ) {
						$question_ids = array_merge( $question_ids, $questions );
					}
				}
			}

			// merge all post type on course
			$ids = array_merge( (array) $course_id, $item_ids, $question_ids );

			// update post author
			if ( $post_author ) {
				foreach ( $ids as $id ) {
					$wpdb->update(
						$wpdb->posts,
						array( 'post_author' => $post_author ),
						array( 'ID' => $id )
					);
				}
			}

			// update passing grade for final quiz meta
			if ( 'evaluate_final_quiz' === LP_Request::get_string( '_lp_course_result' ) ) {

				$api = LP_Repair_Database::instance();
				$api->sync_course_final_quiz( $course->get_id() );

				$passing_grade = LP_Request::get_string( '_lp_course_result_final_quiz_passing_condition' );
				$quiz_id       = $course->get_final_quiz();

//				var_dump($passing_grade, $quiz_id);

				update_post_meta( $quiz_id, '_lp_passing_grade', $passing_grade );

//				var_dump(123123);die;
			}

		}

		/**
		 * Delete course sections before delete course.
		 *
		 * @param $post_id
		 *
		 * @since 3.0.0
		 *
		 */
		public function before_delete_course( $post_id ) {
			// course curd
			$curd = new LP_Course_CURD();
			// remove all items from each section and delete course's sections
			$curd->delete( $post_id );
		}

		/**
		 * Process request actions on post.php loaded
		 */
		public function post_actions() {
			$post_id = learn_press_get_request( 'post_ID' );
			if ( empty( $post_id ) ) {
				$post_id = learn_press_get_request( 'post' );
			}
			if ( empty( $post_id ) ) {
				return;
			}
			if ( self::$_enable_review ) {
				if ( ! empty( $_POST ) && learn_press_get_current_user()->is_instructor() && 'yes' == get_post_meta( $post_id,
						'_lp_submit_for_reviewer', true ) ) {
					LP_Admin_Notice::instance()->add_redirect( __( 'Sorry! You can not update a course while it is being viewed!',
						'learnpress' ), 'error' );
					wp_redirect( admin_url( 'post.php?post=' . $post_id . '&action=edit' ) );
					exit();
				}
			}
			$delete_log = learn_press_get_request( 'delete_log' );
			// ensure that user can do this
			if ( $delete_log && current_user_can( 'delete_others_lp_courses' ) ) {
				$nonce = learn_press_get_request( '_wpnonce' );
				if ( wp_verify_nonce( sanitize_key( $nonce ), 'delete_log_' . $post_id . '_' . $delete_log ) ) {
					global $wpdb;
					$table = $wpdb->prefix . 'learnpress_review_logs';
					if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) === $table ) {
						$wpdb->query(
							$wpdb->prepare( "
                                DELETE FROM {$table}
                                WHERE review_log_id = %d",
								$delete_log
							)
						);
					}
					wp_redirect( admin_url( 'post.php?post=' . learn_press_get_request( 'post' ) . '&action=edit' ) );
					exit();
				}
			}
		}

		/**
		 * @param string $fields
		 *
		 * @return string
		 */
		public function posts_fields( $fields ) {
			if ( ! $this->_is_archive() ) {
				return $fields;
			}

			$fields = ' DISTINCT ' . $fields;
			if ( ( $this->_get_orderby() == 'price' ) || ( $this->_get_search() ) ) {
				$fields .= ', pm_price.meta_value as course_price';
			}

			return $fields;
		}

		public function _posts_join_paged_course_items( $join ) {
			global $wpdb;

			if ( ( $course_id = $this->_filter_items_by_course() ) || ( LP_Request::get( 'orderby' ) == 'course-name' ) ) {
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_section_items si ON {$wpdb->posts}.ID = si.item_id";
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id";
				$join .= " LEFT JOIN {$wpdb->posts} c ON c.ID = s.section_course_id";
			}

			return $join;
		}

		public function _posts_where_paged_course_items( $where ) {
			global $wpdb;

			if ( $course_id = $this->_filter_items_by_course() ) {
				$where .= $wpdb->prepare( ' AND (c.ID = %d)', $course_id );
				$where .= $wpdb->prepare( " AND {$wpdb->posts}.post_status = %s", 'publish' );
			}

			return $where;
		}

		/**
		 * @param $join
		 *
		 * @return string
		 */
		public function posts_join_paged( $join ) {
			global $wpdb;

			if ( ! $this->_is_archive() ) {
				return $join;
			}

			$join .= " LEFT JOIN {$wpdb->postmeta} pm_price ON pm_price.post_id = {$wpdb->posts}.ID AND pm_price.meta_key = '_lp_price'";

			return $join;
		}

		/**
		 * @param $where
		 *
		 * @return mixed|string
		 */
		public function posts_where_paged( $where ) {
			global $wpdb;

			if ( ! $this->_is_archive() ) {
				return $where;
			}

			if ( array_key_exists( 'filter_price', $_REQUEST ) ) {
				if ( $_REQUEST['filter_price'] == 0 ) {
					$where .= ' AND ( pm_price.meta_value IS NULL || pm_price.meta_value = 0 )';
				} else {
					$where .= $wpdb->prepare( ' AND ( pm_price.meta_value = %s )',
						sanitize_text_field( wp_unslash( $_REQUEST['filter_price'] ) ) );
				}
			}

			$not_in = $wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s 
				WHERE pm.meta_value = %s",
				'_lp_preview_course', 'yes'
			);

			$where .= " AND {$wpdb->posts}.ID NOT IN( {$not_in} )";

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

			$order = $this->_get_order();
			switch ( $this->_get_orderby() ) {
				case 'price':
					$order_by_statement = "pm_price.meta_value {$order}";
			}

			return $order_by_statement;
		}

		/**
		 * @param $columns
		 *
		 * @return mixed
		 */
		public function sortable_columns( $columns ) {
			$columns['author'] = 'author';
			$columns['price']  = 'price';

			return $columns;
		}

		private function _is_archive() {
			global $pagenow, $post_type;
			if ( ! is_admin() || ( $pagenow != 'edit.php' ) || ( LP_COURSE_CPT != $post_type ) ) {
				return false;
			}

			return true;
		}

		/**
		 * Add meta boxes to course post type page
		 */
		public function add_meta_boxes() {
			if ( LP_COURSE_CPT != learn_press_get_requested_post_type() || ! is_admin() ) {
				return;
			}

			$default_tabs = array(
				'settings'   => new RW_Meta_Box( self::settings_meta_box() ),
				'assessment' => new RW_Meta_Box( self::assessment_meta_box() ),
				'payment'    => new RW_Meta_Box( self::payment_meta_box() ),
			);
			if ( self::$_enable_review ) {
				$default_tabs['review_logs'] = array(
					'callback' => array( $this, 'review_logs_meta_box' ),
					'meta_box' => 'review_logs',
					'icon'     => 'dashicons-format-chat',
				);
			}

			if ( is_super_admin() ) {
				$default_tabs['author'] = new RW_Meta_Box( self::author_meta_box() );
			}

			$course_tabs = apply_filters( 'learn-press/admin-course-tabs', $default_tabs );
			new LP_Meta_Box_Tabs(
				array(
					'post_type' => LP_COURSE_CPT,
					'tabs'      => $course_tabs,
					'title'     => __( 'Course Settings', 'learnpress' ),
				)
			);

			parent::add_meta_boxes();
		}

		/**
		 * Fields of general settings displays in course metabox
		 *
		 * @return mixed
		 */
		public static function settings_meta_box() {

			$meta_box = array(
				'id'       => 'course_settings',
				'title'    => __( 'General', 'learnpress' ),
				'pages'    => array( LP_COURSE_CPT ),
				'priority' => 'high',
				'icon'     => 'dashicons-admin-tools',
				'fields'   => array(
					array(
						'name' => __( 'Duration', 'learnpress' ),
						'id'   => '_lp_duration',
						'type' => 'duration',
						'desc' => __( 'The duration of the course.', 'learnpress' ),
						'std'  => '10 weeks',
					),
					array(
						'name' => __( 'Block course', 'learnpress' ),
						'id'   => '_lp_block_course_item_duration_content',
						'type' => 'yes_no',
						'desc' => __( 'Block course when duration expires', 'learnpress' ),
						'std'  => 'no',
					),
					array(
						'name' => __( '', 'learnpress' ),
						'id'   => '_lp_block_lesson_content',
						'type' => 'yes_no',
						'desc' => __( 'Block of course when finished course.',
							'learnpress' ),
						'std'  => 'no',
					),
					array(
						'name' => __( 'Allow repurchase ', 'learnpress' ),
						'id'   => '_lp_allow_course_repurchase',
						'type' => 'yes_no',
						'desc' => __( 'Allow users to repurchase this course after course finished or blocked (Do not apply to free courses)', 'learnpress' ),
						'std'  => 'no',
					),
					array(
						'name' => __( 'Maximum Students', 'learnpress' ),
						'id'   => '_lp_max_students',
						'type' => 'number',
						'desc' => __( 'Maximum number of students who can enroll in this course.', 'learnpress' ),
						'std'  => 1000,
					),
					array(
						'name' => __( 'Students Enrolled', 'learnpress' ),
						'id'   => '_lp_students',
						'type' => 'number',
						'desc' => __( 'How many students have taken this course.', 'learnpress' ),
						'std'  => 0,
					),
					array(
						'name' => __( 'Re-take Course', 'learnpress' ),
						'id'   => '_lp_retake_count',
						'type' => 'number',
						'min'  => - 1,
						'desc' => __( 'How many times the user can re-take this course. Set to 0 to disable re-taking',
							'learnpress' ),
						'std'  => 0,
					),
					array(
						'name' => __( 'Featured', 'learnpress' ),
						'id'   => '_lp_featured',
						'type' => 'yes_no',
						'desc' => __( 'Set course as featured.', 'learnpress' ),
						'std'  => 'no',
					),
					array(
						'name' => __( 'External Link', 'learnpress' ),
						'id'   => '_lp_external_link_buy_course',
						'type' => 'url',
						'desc' => __( 'Redirect to this url when you press button buy this course. Not handle any task with the course (enroll, buy the course, v.v…). So if you want to user can learn courses you need to create an order manually',
							'learnpress' ),
						'std'  => '',
					),
					// array(
					// 'name' => __( 'Show item links', 'learnpress' ),
					// 'id'   => '_lp_submission',
					// 'type' => 'yes-no',
					// 'desc' => __( 'Enable link of course items in case user can not view content of them.', 'learnpress' ),
					// 'std'  => 'yes'
					// )
				),
			);

			return apply_filters( 'learn_press_course_settings_meta_box_args', $meta_box );
		}

		/**
		 * Course assessment
		 *
		 * @return mixed
		 */
		public static function assessment_meta_box() {
			global $post;
			$post_id = LP_Request::get_int( 'post' );
			$post_id = $post_id ? $post_id : ( ! empty( $post ) ? $post->ID : 0 );

			$course_result_desc = '';

			if ( $course_results = get_post_meta( $post_id, '_lp_course_result', true ) ) {
				if ( in_array( $course_results, array( '', 'evaluate_lesson', 'evaluate_final_quiz' ) ) ) {
					// $course_result_desc .= sprintf( '<a href="" data-advanced="%2$s" data-basic="%1$s" data-click="basic">%2$s</a>', __( 'Basic Options', 'learnpress' ), __( 'Advanced Options', 'learnpress' ) );
				}
			}

			// $course_result_desc = "<span id=\"learn-press-toggle-course-results\">{$course_result_desc}</span>";
			$course_result_desc .= __( 'The method to assess the result of a student for a course.', 'learnpress' );

			if ( $course_results == 'evaluate_final_quiz' && ! get_post_meta( $post_id, '_lp_final_quiz', true ) ) {
				$course_result_desc .= __( '<br /><strong>Note! </strong>No final quiz in course, please add a final quiz',
					'learnpress' );
			}

			$quiz_passing_condition_html = '';

			if ( $course = learn_press_get_course( $post_id ) ) {
				$passing_grade = '';

				if ( $final_quiz = $course->get_final_quiz() ) {
					if ( $quiz = learn_press_get_quiz( $final_quiz ) ) {
						$passing_grade = $quiz->get_passing_grade();
					}
				}

				$quiz_passing_condition_html = '
					<div id="passing-condition-quiz-result">
					<input type="number" name="_lp_course_result_final_quiz_passing_condition" value="' . absint( $passing_grade ) . '" /> %
					<p>' . __( 'This is conditional "passing grade" of Final quiz will apply for result of this course. When you change it here, the "passing grade" also change with new value for the Final quiz.',
						'learnpress' ) . '</p>
					</div>
				';
			}

			$course_result_option_desc = array(
				'evaluate_lesson'     => __( 'Evaluate by number of lessons completed per number of total lessons.',
						'learnpress' )
				                         . sprintf( '<p>%s</p>',
						__( 'E.g: Course has 10 lessons and user completed 5 lessons then the result = 5/10 = 50.%',
							'learnpress' ) ),
				'evaluate_final_quiz' => __( 'Evaluate by results of final quiz in course. You have to add a quiz into end of course.',
					'learnpress' ),
				'evaluate_quizzes'    => __( 'Evaluate as a percentage of completed quizzes on the total number of quizzes.',
						'learnpress' )
				                         . __( '<p>E.g: Course has 3 quizzes and user completed quiz 1: 30% correct, quiz 2: 50% corect, quiz 3: 100% correct => Result: (30% + 50% + 100%) / 3 = 60%.</p>',
						'learnpress' ),
				// 'evaluate_passed_quizzes' => __( 'Evaluate by achieved points of quizzes passed per total point of all quizzes.', 'learnpress' ),
				'evaluate_quiz'       => __( '<p>Evaluate by number of quizzes completed per number of total quizzes.</p>',
						'learnpress' )
				                         . __( '<p>E.g: Course has 10 quizzes and user completed 5 quizzes then the result = 5/10 = 50%.</p>',
						'learnpress' ),
				'evaluate_questions'  => __( 'Evaluate by achieved points of question passed per total point of all questions.',
						'learnpress' )
				                         . sprintf( '<p>%s</p>',
						__( 'E.g: Course has 10 questions. User correct 5 questions. Result is 5/10 = 50%.',
							'learnpress' ) ),
				'evaluate_mark'       => __( 'Evaluate by achieved marks per total marks of all questions.',
					'learnpress' ),
			);

			$course_result_option_tip = '<span class="learn-press-tip">%s</span>';

			$meta_box = array(
				'id'       => 'course_assessment',
				'title'    => __( 'Assessment', 'learnpress' ),
				'priority' => 'high',
				'icon'     => 'dashicons-awards',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name'    => __( 'Course result', 'learnpress' ),
						'id'      => '_lp_course_result',
						'type'    => 'radio',
						'desc'    => $course_result_desc,
						'options' => array(
							'evaluate_lesson'     => __( 'Evaluate via lessons', 'learnpress' )
							                         . learn_press_quick_tip( $course_result_option_desc['evaluate_lesson'],
									false ),
							'evaluate_final_quiz' => __( 'Evaluate via results of the final quiz', 'learnpress' )
							                         . sprintf( $course_result_option_tip,
									$course_result_option_desc['evaluate_final_quiz'] )
							                         . $quiz_passing_condition_html,
							'evaluate_quizzes'    => __( 'Evaluate via results of quizzes',
									'learnpress' ) . sprintf( $course_result_option_tip,
									$course_result_option_desc['evaluate_quizzes'] ),
							// @nhamdv: remove after version: 3.2.7.3
							// 'evaluate_passed_quizzes' => __( 'Evaluate via results of quizzes passed', 'learnpress' )
							// . sprintf( $course_result_option_tip, $course_result_option_desc['evaluate_passed_quizzes'] ),
							'evaluate_quiz'       => __( 'Evaluate via quizzes', 'learnpress' )
							                         . sprintf( $course_result_option_tip,
									$course_result_option_desc['evaluate_quiz'] ),
							'evaluate_questions'  => __( 'Evaluate via questions', 'learnpress' )
							                         . sprintf( $course_result_option_tip,
									$course_result_option_desc['evaluate_questions'] ),
							'evaluate_mark'       => __( 'Evaluate via mark', 'learnpress' )
							                         . sprintf( $course_result_option_tip,
									$course_result_option_desc['evaluate_mark'] ),
						),
						'std'     => 'evaluate_lesson',
						'inline'  => false,
					),
					array(
						'name'        => __( 'Passing condition value', 'learnpress' ),
						'id'          => '_lp_passing_condition',
						'type'        => 'number',
						'min'         => 0,
						'max'         => 100,
						'desc'        => __( 'The percentage of quiz result or completed lessons to finish the course.',
							'learnpress' ),
						'std'         => 80,
						'after_input' => '&nbsp;%',
						'visibility'  => array(
							'state'       => 'show',
							'conditional' => array(
								array(
									'field'   => '_lp_course_result',
									'compare' => '!=',
									'value'   => 'evaluate_final_quiz',
								),
							),
						),
					),
				),
			);

			return apply_filters( 'learn_press_course_assessment_metabox', $meta_box );
		}

		/**
		 * Course payment
		 *
		 * @return array
		 */
		public static function payment_meta_box() {

			$course_id = ! empty( $_GET['post'] ) ? sanitize_text_field( wp_unslash( $_GET['post'] ) ) : 0;

			$meta_box = array(
				'id'       => 'course_payment',
				'title'    => __( 'Pricing', 'learnpress' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'icon'     => 'dashicons-clipboard',
				'fields'   => array(),
			);

			$payment = get_post_meta( $course_id, '_lp_payment', true );

			if ( current_user_can( LP_TEACHER_ROLE ) || current_user_can( 'administrator' ) ) {
				$message    = '';
				$price      = get_post_meta( $course_id, '_lp_price', true );
				$sale_price = '';
				$start_date = '';
				$end_date   = '';

				if ( $course_id ) {
					if ( $payment != 'free' ) {
						$suggest_price = get_post_meta( $course_id, '_lp_suggestion_price', true );
						$course        = get_post( $course_id );

						$author = get_userdata( $course->post_author );

						if ( isset( $suggest_price ) && ! empty( $author->roles[0] ) && $author->roles[0] === 'lp_teacher' ) {
							$message = sprintf( __( 'This course requires enrollment and the suggested price is <strong>%s</strong>',
								'learnpress' ), learn_press_format_price( $suggest_price, true ) );
							$price   = $suggest_price;
						}

						$sale_price = get_post_meta( $course_id, '_lp_sale_price', true );
						$start_date = get_post_meta( $course_id, '_lp_sale_start', true );
						$end_date   = get_post_meta( $course_id, '_lp_sale_end', true );
					} else {
						$message = __( 'This course is free.', 'learnpress' );
					};
				}
				$sale_price_dates_class = '';
				if ( ! $start_date && ! $end_date ) {
					$sale_price_dates_class .= 'hide-if-js';
				}
				$message     .= sprintf( __( 'Course price in <strong>%s</strong> currency.', 'learnpress' ),
					learn_press_get_currency() );
				$conditional = array(
					'state'       => 'show',
					'conditional' => array(
						array(
							'field'   => '_lp_price',
							'compare' => '>',
							'value'   => '0',
						),
					),
				);
				array_push(
					$meta_box['fields'],
					array(
						'name' => __( 'Price', 'learnpress' ),
						'id'   => '_lp_price',
						'type' => 'number',
						'min'  => 0,
						'step' => 0.01,
						'desc' => $message,
						'std'  => $price,
						// 'visibility' => $conditional
					),
					array(
						'name'       => __( 'Sale Price', 'learnpress' ),
						'id'         => '_lp_sale_price',
						'type'       => 'number',
						'min'        => 0,
						'step'       => 0.01,
						'desc'       => sprintf(
							                '%s %s',
							                sprintf( __( 'Course sale price in <strong>%s</strong> currency.',
								                'learnpress' ), learn_press_get_currency() ),
							                __( 'Leave blank to remove sale price.', 'learnpress' )
						                )
						                . ' <a href="#"' . ( $start_date || $end_date ? ' style="display:none;"' : '' ) . ' id="_lp_sale_price_schedule">' . __( 'Schedule',
								'learnpress' ) . '</a>',
						'std'        => $sale_price,
						'visibility' => $conditional,
					),
					array(
						'name'       => __( 'Sale start date', 'learnpress' ),
						'id'         => '_lp_sale_start',
						'type'       => 'datetime',
						'std'        => $start_date,
						'class'      => $sale_price_dates_class . ' lp-course-sale_start-field',
						'visibility' => $conditional,
					),
					array(
						'name'       => __( 'Sale end date', 'learnpress' ),
						'id'         => '_lp_sale_end',
						'type'       => 'datetime',
						'desc'       => '<a href="#" id="_lp_sale_price_schedule_cancel">' . __( 'Cancel',
								'learnpress' ) . '</a>',
						'std'        => $end_date,
						'class'      => $sale_price_dates_class . ' lp-course-sale_end-field',
						'visibility' => $conditional,
					)
				);
			} else {
				$price                = get_post_meta( $course_id, '_lp_price', true );
				$meta_box['fields'][] = array(
					'name'  => __( 'Price set by Admin', 'learnpress' ),
					'id'    => '_lp_price',
					'type'  => 'html',
					'class' => 'lp-course-price-field' . ( $payment != 'yes' ? ' hide-if-js' : '' ),
					'html'  => $price !== '' ? sprintf( '<strong>%s</strong>',
						learn_press_format_price( $price, true ) ) : __( 'Not set', 'learnpress' ),
				);
				$meta_box['fields'][] = array(
					'name'  => __( 'Course Suggestion Price', 'learnpress' ),
					'id'    => '_lp_suggestion_price',
					'type'  => 'number',
					'min'   => 0,
					'step'  => 0.01,
					'desc'  => __( 'The course price you want to suggest for admin to set.', 'learnpress' ),
					'class' => 'lp-course-price-field' . ( $payment != 'yes' ? ' hide-if-js' : '' ),
					'std'   => 0,
				);

			}
			$conditional['conditional'][0]['compare'] = '<=';
			$meta_box['fields']                       = array_merge(
				$meta_box['fields'],
				array(
					array(
						'name'       => __( 'No requirement enroll', 'learnpress' ),
						'id'         => '_lp_required_enroll',
						'type'       => 'yes_no',
						'desc'       => __( 'Require users logged in to study or public to all.', 'learnpress' ),
						'std'        => 'yes',
						'compare'    => '<>',
						'visibility' => $conditional,
					),
				)
			);

			$meta_box = apply_filters( 'learn_press_course_payment_meta_box_args', $meta_box );

			return apply_filters( 'learn-press/course-settings/payments', $meta_box );
		}

		/**
		 * Course author.
		 *
		 * @return mixed|null
		 */
		public static function author_meta_box() {

			$course_id = LP_Request::get_int( 'post' );
			$post      = get_post( $course_id );
			$author    = $post ? $post->post_author : get_current_user_id();

			$include = array();
			$role    = array( 'administrator', 'lp_teacher' );

			$role = apply_filters( 'learn_press_course_author_role_meta_box', $role );

			foreach ( $role as $_role ) {
				$users_by_role = get_users( array( 'role' => $_role ) );
				if ( $users_by_role ) {
					foreach ( $users_by_role as $user ) {
						$include[ $user->get( 'ID' ) ] = $user->user_login;
					}
				}
			}
			$fields = array();
			if ( is_super_admin() ) {
				$fields [] = array(
					'name'       => __( 'Author', 'learnpress' ),
					'id'         => '_lp_course_author',
					'desc'       => '',
					'multiple'   => false,
					'allowClear' => false,
					'type'       => 'select',
					'options'    => $include,
					'std'        => $author,
				);
			}
			$meta_box = array(
				'id'       => 'course_authors',
				'title'    => __( 'Author', 'learnpress' ),
				'pages'    => array( LP_COURSE_CPT ),
				'icon'     => 'dashicons-businessman',
				'priority' => 'default',
				'fields'   => $fields,
			);
			$meta_box = apply_filters( 'learn_press_course_author_meta_box', $meta_box );
			if ( empty( $meta_box['fields'] ) ) {
				return false;
			}

			return $meta_box;

		}

		/**add_meta_boxes
		 * Course review logs.
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
		 * Log the messages between admin and instructor
		 */
		public function post_review_message_box() {
			global $post;

			if ( learn_press_get_post_type( $post->ID ) != 'lp_course' ) {
				return false;
			}

			// $user = learn_press_get_current_user();
			$course_user = learn_press_get_user( get_post_field( 'post_author', $post->ID ) );

			if ( $course_user->is_admin() ) {
				return;
			}

			$required_review = LP()->settings->get( 'required_review' ) == 'yes';
			// $enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';
			// $is_publish            = get_post_status( $post->ID ) == 'publish';

			if ( ! $required_review ) {
				return;
			}
			/*
			if( $enable_edit_published ){
				return;
			}*/

			learn_press_admin_view( 'meta-boxes/course/review-log' );
		}

		/**
		 * Course video
		 *
		 * @return mixed|null
		 */
		public static function video_meta_box() {

			$meta_box = array(
				'id'       => 'course_video',
				'title'    => __( 'Course Video', 'learnpress' ),
				'pages'    => array( LP_COURSE_CPT ),
				'priority' => 'high',
				'fields'   => array(
					array(
						'name' => __( 'Video ID', 'learnpress' ),
						'id'   => '_lp_video_id',
						'type' => 'text',
						'desc' => __( 'The ID of Youtube or Vimeo video', 'learnpress' ),
						'std'  => '',
					),
					array(
						'name'    => __( 'Video Type', 'learnpress' ),
						'id'      => '_lp_video_type',
						'type'    => 'select',
						'desc'    => __( 'Chose video type', 'learnpress' ),
						'std'     => 'youtube',
						'options' => array(
							'youtube' => __( 'Youtube', 'learnpress' ),
							'vimeo'   => __( 'Vimeo', 'learnpress' ),
						),
					),
					array(
						'name' => __( 'Embed width', 'learnpress' ),
						'id'   => '_lp_video_embed_width',
						'type' => 'number',
						'desc' => __( 'Set width of embed', 'learnpress' ),
						'std'  => '560',
					),
					array(
						'name' => __( 'Embed height', 'learnpress' ),
						'id'   => '_lp_video_embed_height',
						'type' => 'number',
						'desc' => __( 'Set height of embed', 'learnpress' ),
						'std'  => '315',
					),
				),
			);

			return apply_filters( 'learn_press_course_video_meta_box_args', $meta_box );
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
			$limit    = $view_all ? '' : ' LIMIT 0, 10';
			$tb_exits = $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" );

			if ( $tb_exits === $table ) {
				$query = $wpdb->prepare( "
					SELECT SQL_CALC_FOUND_ROWS *
					FROM {$wpdb->learnpress_review_logs}
					WHERE course_id = %d
					ORDER BY `date` DESC" . $limit,
					$post->ID
				);

				$reviews       = $wpdb->get_results( $query );
				$total_reviews = $wpdb->get_var( 'SELECT FOUND_ROWS()' );
				$count_reviews = sizeof( $reviews );

				$view = learn_press_get_admin_view( 'meta-boxes/course/review-logs.php' );
				include $view;
			}
		}

		/**
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
					WHERE p.ID = %d",
					$post->ID )
			);
			$wpdb->query( "ALTER TABLE {$wpdb->learnpress_section_items} AUTO_INCREMENT = 1" );

			$wpdb->query(
				$wpdb->prepare( "
					DELETE FROM {$wpdb->learnpress_sections}
					WHERE section_course_id = %d",
					$post->ID )
			);
			$wpdb->query( "ALTER TABLE {$wpdb->learnpress_sections} AUTO_INCREMENT = 1" );
		}

		private function _update_final_quiz() {
			global $post;

			$final_quiz = learn_press_get_final_quiz( $post->ID );

			return $final_quiz;
		}

		private function _send_mail() {
			if ( ! LP()->user->is_instructor() ) {
				return;
			}
			$mail = LP()->mail;

		}

		private function _review_log() {
			global $wpdb, $post;
			$user                  = learn_press_get_current_user();
			$action                = '';
			$old_status            = $post->post_status;
			$new_status            = get_post_status( $post->ID );
			$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
			$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';

			$submit_for_review = learn_press_get_request( 'learn-press-submit-for-review' ) == 'yes' || ( ( $required_review ) );

			// If course is submitted by administrator
			if ( $user->is_admin() ) {
				if ( $old_status != $new_status ) {
					if ( $new_status == 'publish' ) {
						$action = 'approved';
					} elseif ( $new_status != 'publish' ) {
						$action = 'rejected';
					}
					delete_post_meta( $post->ID, '_lp_submit_for_reviewer', 'yes' );
				}
			} elseif ( $user->is_instructor() ) { // Course is submitted by instructor

				if ( $enable_edit_published && ( $old_status == $new_status && $new_status == 'publish' ) ) {
					$submit_for_review = false;
				}
				if ( ( $submit_for_review || ( $old_status != $new_status ) ) && $post->post_status != 'auto-draft' ) {
					if ( isset( $_POST['learn-press-submit-for-review'] ) && $_POST['learn-press-submit-for-review'] === 'yes' ) {
						$action = 'for_reviewer';
						update_post_meta( $post->ID, '_lp_submit_for_reviewer', 'yes' );
					}
				}
			}
			$message = learn_press_get_request( 'review-message' );
			if ( ! $action && ! $message ) {
				return;
			}

			switch ( $action ) {
				case 'approved':
					if ( ! $message ) {
						$message = __( 'Course has been approved by Reviewer', 'learnpress' );
					}
					break;
				case 'rejected':
					if ( ! $message ) {
						$message = __( 'Course has been rejected by Reviewer', 'learnpress' );
					}
					break;
				case 'for_reviewer':
					if ( ! $message ) {
						$message = sprintf( __( 'Course has been submitted by %s', 'learnpress' ),
							learn_press_get_profile_display_name( $user ) );
					}
					break;
				default:
					if ( ! $message ) {
						$message = __( 'Course has been updated by Reviewer', 'learnpress' );
					}
			}
			if ( apply_filters( 'learn_press_review_log_message', $message, $post->ID, $user->get_id() ) ) {
				$table = $wpdb->prefix . 'learnpress_review_logs';
				$wpdb->insert(
					$table,
					array(
						'course_id' => $post->ID,
						'user_id'   => $user->get_id(),
						'message'   => $message,
						'date'      => current_time( 'mysql' ),
						'status'    => $new_status,
						'user_type' => $user->is_admin() ? 'reviewer' : 'instructor',
					),
					array( '%d', '%d', '%s', '%s', '%s', '%s' )
				);
				do_action( 'learn_press_update_review_log', $wpdb->insert_id, $post->ID, $user->get_id() );
			}
			if ( $action ) {
				do_action( "learn_press_course_submit_{$action}", $post->ID, $user );
			}
		}

		/**
		 * Update course price and sale price
		 *
		 * @return mixed
		 */
		private function _update_price() {
			global $wpdb, $post;
			$request          = $_POST;
			$price            = floatval( LP_Request::get( '_lp_price' ) );
			$sale_price       = LP_Request::get( '_lp_sale_price' );
			$sale_price_start = LP_Request::get( '_lp_sale_start' );
			$sale_price_end   = LP_Request::get( '_lp_sale_end' );
			$keys             = array();
			// Delete all meta no need anymore
			if ( $price <= 0 ) {
				$keys = array( '_lp_payment', '_lp_price', '_lp_sale_price', '_lp_sale_start', '_lp_sale_end' );
			} elseif ( ( $sale_price == '' ) || ( $sale_price < 0 ) || ( absint( $sale_price ) >= $price ) || ! $this->_validate_sale_price_date() ) {
				$keys = array( '_lp_sale_price', '_lp_sale_start', '_lp_sale_end' );
			}

			if ( $keys ) {
				$format = array_fill( 0, sizeof( $keys ), '%s' );
				$sql    = "
					DELETE
					FROM {$wpdb->postmeta}
					WHERE meta_key IN(" . join( ',', $format ) . ')
					AND post_id = %d
				';
				$keys[] = $post->ID;
				$sql    = $wpdb->prepare( $sql, $keys );
				$wpdb->query( $sql );
				foreach ( $keys as $key ) {
					unset( $_REQUEST[ $key ] );
					unset( $_POST[ $key ] );
				}
			}

			if ( $price ) {
				update_post_meta( $post->ID, '_lp_required_enroll', 'yes' );
			}

			return true;
		}

		/**
		 * Check sale price dates are in range
		 *
		 * @return bool
		 */
		private function _validate_sale_price_date() {
			$now              = current_time( 'timestamp' );
			$sale_price_start = learn_press_get_request( '_lp_sale_start' );
			$sale_price_end   = learn_press_get_request( '_lp_sale_end' );
			$end              = strtotime( $sale_price_end );
			$start            = strtotime( $sale_price_start );

			return ( ( $sale_price_start ) && ( $now <= $end || ! $sale_price_end ) || ( ! $sale_price_start && ! $sale_price_end ) );
		}

		/**
		 * Add columns to admin manage course page
		 *
		 * @param array $columns
		 *
		 * @return array
		 */
		public function columns_head( $columns ) {

			/**
			 * @var WP_Query $wp_query
			 */
			$user = wp_get_current_user();
			if ( in_array( 'lp_teacher', $user->roles ) ) {
				unset( $columns['author'] );
			}
			$keys   = array_keys( $columns );
			$values = array_values( $columns );
			$pos    = array_search( 'title', $keys );
			if ( $pos !== false ) {
				array_splice( $keys, $pos + 1, 0, array( 'author', 'sections', 'students', 'price' ) );
				array_splice(
					$values,
					$pos + 1,
					0,
					array(
						__( 'Author', 'learnpress' ),
						__( 'Content', 'learnpress' ),
						__( 'Students', 'learnpress' ),
						__( 'Price', 'learnpress' ),
					)
				);
				$columns = array_combine( $keys, $values );
			} else {
				$columns['author']   = __( 'Author', 'learnpress' );
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

			/**
			 * @var WP_Post_Type[] $post_types
			 */
			global $post;

			$course = learn_press_get_course( $post->ID );

			switch ( $column ) {

				case 'sections':
					// course curd
					$curd            = new LP_Course_CURD();
					$number_sections = $curd->count_sections( $post_id );
					if ( $number_sections ) {
						$output     = sprintf( _n( '<strong>%d</strong> section', '<strong>%d</strong> sections',
							$number_sections, 'learnpress' ), $number_sections );
						$html_items = array();
						$post_types = get_post_types( null, 'objects' );

						if ( $stats_objects = $curd->count_items( $post_id, 'edit' ) ) {
							foreach ( $stats_objects as $type => $count ) {
								if ( ! $count || ! isset( $post_types[ $type ] ) ) {
									continue;
								}
								$post_type_object = $post_types[ $type ];
								$singular_name    = strtolower( $post_type_object->labels->singular_name );
								$plural_name      = strtolower( $post_type_object->label );
								$html_items[]     = sprintf( _n( '<strong>%d</strong> ' . $singular_name,
									'<strong>%d</strong> ' . $plural_name, $count, 'learnpress' ), $count );
							}
						}

						if ( $html_items = apply_filters( 'learn-press/course-count-items', $html_items ) ) {
							$output .= ' (' . join( ', ', $html_items ) . ')';
						}

						echo $output;
					} else {
						_e( 'No content', 'learnpress' );
					}

					break;

				case 'price':
					$price   = $course->get_price();
					$is_paid = ! $course->is_free();

					$origin_price = '';
					if ( $course->get_origin_price() && $course->has_sale_price() ) {
						$origin_price = sprintf( '<span class="origin-price">%s</span>',
							$course->get_origin_price_html() );
					}

					if ( $is_paid ) {
						echo sprintf( '<a href="%s" class="price">%s%s</a>', add_query_arg( 'filter_price', $price ),
							$origin_price, learn_press_format_price( $course->get_price(), true ) );
					} else {
						echo sprintf( '<a href="%s" class="price">%s%s</a>', add_query_arg( 'filter_price', 0 ),
							$origin_price, __( 'Free', 'learnpress' ) );

						if ( ! $course->is_required_enroll() ) {
							printf( '<p class="description">(%s)</p>', __( 'No requirement enroll', 'learnpress' ) );
						}
					}
					break;
				case 'students':
					// Replace count_completed_orders() by count_enrolled_course()
					$count = LP_Database::getInstance()->count_enrolled_course( $post_id );

					echo '<span class="lp-label-counter' . ( ! $count ? ' disabled' : '' ) . '">' . $count . '</span>';

			}
		}

		/**
		 * Before save curriculum action.
		 */
		public function before_save_curriculum() {
			global $post, $pagenow;

			// Ensure that we are editing course in admin side
			if ( ( $pagenow != 'post.php' ) || ( get_post_type() != LP_COURSE_CPT ) ) {
				return;
			}

			remove_action( 'save_post', array( $this, 'before_save_curriculum' ), 1 );

			$user                  = learn_press_get_current_user();
			$required_review       = LP()->settings->get( 'required_review' ) == 'yes';
			$enable_edit_published = LP()->settings->get( 'enable_edit_published' ) == 'yes';

			if ( $user->is_instructor() && $required_review && ! $enable_edit_published ) {
				wp_update_post(
					array(
						'ID'          => $post->ID,
						'post_status' => 'pending',
					),
					array( '%d', '%s' )
				);

			}

			$new_status = get_post_status( $post->ID );
			$old_status = get_post_meta( $post->ID, '_lp_course_status', true );

			// Final quiz
			$this->_update_final_quiz();

			// Update price
			$this->_update_price();

			if ( $new_status != $old_status ) {
				do_action( 'learn_press_transition_course_status', $new_status, $old_status, $post->ID );
				update_post_meta( $post->ID, '_lp_course_status', $new_status );
			}

			$this->_review_log();
			delete_post_meta( $post->ID, '_lp_curriculum' );
		}

		public function currency_symbol( $input_html, $field, $sub_meta ) {
			return $input_html . '<span class="lpr-course-price-symbol">' . learn_press_get_currency_symbol() . '</span>';
		}

		/**
		 * Instance LP_Course_Post_Type.
		 *
		 * @return LP_Course_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self( LP_COURSE_CPT );
			}

			return self::$_instance;
		}
	} // end LP_Course_Post_Type

	$course_post_type = LP_Course_Post_Type::instance();
}
