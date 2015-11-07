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
			parent::__construct();
		}

		function toggle_editor_button( $post ) {
			if( $post->post_type == LP()->course_post_type ) {
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

				wp_enqueue_style( 'lp-meta-boxes', LP()->plugin_url( 'assets/css/meta-boxes.css' ) );
				wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
				wp_enqueue_script( 'lp-meta-boxes', LP()->plugin_url( 'assets/js/meta-boxes.js', 'jquery', 'backbone', 'util' ) );

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
					'slug' => $course_permalink,
					'hierarchical' => true,
					'with_front' => false
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
			if ( !is_admin() ) {
				LP_Assets::enqueue_script( 'tipsy', LP_PLUGIN_URL . '/assets/js/jquery.tipsy.js' );
				LP_Assets::enqueue_style( 'tipsy', LP_PLUGIN_URL . '/assets/css/tipsy.css' );
			}
			flush_rewrite_rules();
		}

		/**
		 * Add meta boxes to course post type page
		 */
		static function add_meta_boxes() {

			new RW_Meta_Box( self::curriculum_meta_box() );
			new RW_Meta_Box( self::settings_meta_box() );
			new RW_Meta_Box( self::assessment_meta_box() );
			new RW_Meta_Box( self::payment_meta_box() );
			new RW_Meta_Box(
				array(
					'id'       => 'course_tabs',
					'title'    => __( 'Curriculum', 'learn_press' ),
					'priority' => 'high',
					'pages'    => array( LP_COURSE_CPT ),
					'fields'   => array(
						array(
							'name' => __( 'Course Curriculum', 'learn_press' ),
							'id'   => "course_tabs",
							'type' => 'course_tabs',
							'desc' => '',
						),
					)
				)
			);
		}

		static function curriculum_meta_box() {
			$prefix = '_lpr_';

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
			$post_id = learn_press_get_request( 'post' );
			$prefix = '_lp_';
			$course_result_desc = __( 'The way to assess the result of course for a student', 'learn_press' );
			if( $post_id && get_post_meta( $post_id, '_lp_course_result', true ) == 'evaluate_final_quiz' && !get_post_meta( $post_id, '_lp_final_quiz', true ) ){
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
							'evaluate_lesson'  => __( 'Evaluate lessons', 'learn_press' ),
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
						'id'      => "{$prefix}enroll_requirement",
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
							'free'     => __( 'Free', 'learn_press' ),
							'not_free' => __( 'Paid', 'learn_press' ),
						),
						'std'     => 'free',
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

		function before_save_curriculum() {

			global $wpdb, $post;
			$section_ids        = array();
			$section_update_ids = array();

			$item_ids        = array();
			$item_update_ids = array();
			if ( !empty( $_REQUEST['_lp_curriculum'] ) ) {
				$query_update_section_name  = array();
				$query_update_section_order = array();
				$query_update_item_name     = array();
				$query_update_item_order    = array();
				$query_update_item_section  = array();
				$section_order              = 0;
				$all_item_ids = $wpdb->get_results(
					$wpdb->prepare("
						SELECT lp_si.ID, lp_si.item_id
						FROM {$wpdb->learnpress_section_items} lp_si
						INNER JOIN {$wpdb->learnpress_sections} lp_s ON lp_s.ID = lp_si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = lp_s.course_id
						WHERE lp_s.course_id = %d
					", $post->ID)
				);
				foreach ( $_REQUEST['_lp_curriculum'] as $section_id => $section ) {
					$section_id = absint( $section_id );
					if ( !$section['name'] ) continue;
					// update section with new name and ordering if it is an integer
					if ( $section_id ) {
						$query_update_section_name[]  = 'WHEN ' . $section_id . ' THEN \'' . $section['name'] . '\'';
						$query_update_section_order[] = 'WHEN ' . $section_id . ' THEN \'' . ( ++ $section_order ) . '\'';
						$section_update_ids[]         = $section_id;
						$section['ID']                = $section_id;
						do_action( 'learn_press_update_section', $section, $post->ID );
					} else {
						// else, need to insert
						$wpdb->insert(
							$wpdb->learnpress_sections,
							array(
								'name'      => $section['name'],
								'course_id' => $post->ID,
								'order'     => ++ $section_order
							),
							array( '%s', '%d', '%d' )
						);
						$section_id    = $wpdb->insert_id;
						$section['ID'] = $section_id;
						do_action( 'learn_press_add_section', $section, $post->ID );
					}
					$section_ids[] = $section_id;

					// update items;
					if ( empty( $section['items'] ) ) continue;
					$items      = $section['items'];
					$item_order = 0;
					foreach ( $items as $item_id => $item ) {
						if ( !$item['name'] ) continue;
						$item_id = absint( $item_id );

						if ( $item_id ) {
							$query_update_item_name[]    = 'WHEN ' . $item_id . ' THEN \'' . $item['name'] . '\'';
							$query_update_item_section[] = 'WHEN item_id = ' . $item_id . ' THEN ' . $section_id;
							$query_update_item_order[]   = 'WHEN item_id = ' . $item_id . ' AND section_id = ' . $section_id . ' THEN \'' . ( ++ $item_order ) . '\'';
							$item_update_ids[]           = $item_id;
							if( !in_array( $item_id, $all_item_ids ) ){
								$wpdb->insert(
									$wpdb->learnpress_section_items,
									array(
										'section_id' => $section_id,
										'item_id'    => $item_id,
										'order'      => $item_order
									)
								);
							}
						} else {
							$item_id = wp_insert_post(
								array(
									'post_title'  => $item['name'],
									'post_type'   => $item['post_type'],
									'post_status' => 'publish'
								)
							);

							$wpdb->insert(
								$wpdb->learnpress_section_items,
								array(
									'section_id' => $section_id,
									'item_id'    => $item_id,
									'order'      => ++ $item_order
								)
							);
						}

						$item_ids[] = $item_id;
					}
				}
				// update the name and ordering of existing sections
				if ( $query_update_section_name ) {
					$query_update_section = "
						UPDATE {$wpdb->learnpress_sections} SET
							`name` = CASE `ID` " . join( ' ', $query_update_section_name ) . " END,
							`order` = CASE `ID` " . join( ' ', $query_update_section_order ) . " END
						WHERE
							ID IN (" . join( ',', $section_update_ids ) . ")
					";
					$wpdb->query( $query_update_section );
				}

				// update the name and ordering of existing items
				if ( $query_update_item_name ) {
					$query_update_item_name = "
						UPDATE {$wpdb->posts} SET
							`post_title` = CASE `ID` " . join( ' ', $query_update_item_name ) . " END
						WHERE ID IN(" . join( ',', $item_update_ids ) . ")
					";
					$wpdb->query( $query_update_item_name );

					$query_update_item_order = $wpdb->prepare( "
						UPDATE {$wpdb->learnpress_section_items} lp_si
						INNER JOIN {$wpdb->learnpress_sections} lp_s ON lp_s.ID = lp_si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = lp_s.course_id
						SET
							lp_si.`order` = CASE " . join( ' ', $query_update_item_order ) . " END,
							lp_si.`section_id` = CASE " . join( ' ', $query_update_item_section ) . " END
						WHERE p.ID = %d
						AND lp_si.item_id IN(" . join( ',', $item_update_ids ) . ")
					", $post->ID );
					$wpdb->query( $query_update_item_order );
				}

				$wpdb->query(
					$wpdb->prepare( "
						DELETE FROM lp_si
						USING {$wpdb->learnpress_section_items} lp_si
						INNER JOIN {$wpdb->learnpress_sections} lp_s ON lp_s.ID = lp_si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = lp_s.course_id
						WHERE p.ID = %d
						AND lp_si.item_id NOT IN(" . join( ',', $item_ids ) . ")"
						, $post->ID )
				);

				// remove all sections not existing in course
				if ( $section_ids ) {
					$wpdb->query(
						$wpdb->prepare( "
							DELETE FROM lp_s
							USING {$wpdb->learnpress_sections} lp_s
							INNER JOIN {$wpdb->posts} p ON p.ID = lp_s.course_id
								WHERE p.ID = %d
								AND p.post_type = %s
								AND lp_s.ID NOT IN(" . join( ',', $section_ids ) . ")"
							, $post->ID, 'lp_course' )
					);

					$all_sections    = $wpdb->get_col( "SELECT ID FROM {$wpdb->learnpress_sections} WHERE course_id = {$post->ID}" );
					$remove_sections = array_diff( $all_sections, $section_ids );
					if ( $remove_sections ) {
						$wpdb->query(
							"DELETE FROM {$wpdb->learnpress_section_items} WHERE section_id IN(" . join( ',', $remove_sections ) . ")"
						);
					}
				}
				// remove all duplicate items
				$all_item_ids = $wpdb->get_results(
					$wpdb->prepare("
						SELECT lp_si.ID, lp_si.item_id
						FROM {$wpdb->learnpress_section_items} lp_si
						INNER JOIN {$wpdb->learnpress_sections} lp_s ON lp_s.ID = lp_si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = lp_s.course_id
						WHERE lp_s.course_id = %d
					", $post->ID)
				);
				if( $all_item_ids ){
					$duplicate_ids = array();
					$keep_ids = array();
					foreach($all_item_ids as $row){
						if( empty( $keep_ids[$row->item_id] ) ){
							$keep_ids[$row->item_id] = $row->ID;
						}else{
							$duplicate_ids[] = $row->ID;
						}
					}

					if($duplicate_ids){
						$wpdb->query("
							DELETE FROM {$wpdb->learnpress_section_items}
							WHERE ID IN(" . join( ',', $duplicate_ids ) . ")
						");
					}
				}
			}
			if( learn_press_get_request( '_lp_course_result' ) == 'evaluate_final_quiz' ){
				if( $final_quiz = learn_press_get_final_quiz( $post->ID ) ) {
					update_post_meta( $post->ID, '_lp_final_quiz', $final_quiz );
				}else{
					delete_post_meta( $post->ID, '_lp_final_quiz' );
				}
			}else{
				delete_post_meta( $post->ID, '_lp_final_quiz' );
			}
			//learn_press_debug($_POST);die();
			//echo '<pre>';print_r($_POST);echo '</pre>';
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
				array_splice( $keys, $pos + 1, 0, 'sections' );
				array_splice( $values, $pos + 1, 0, __( 'Sections', 'learn_press' ) );
				$columns = array_combine( $keys, $values );
			} else {
				$columns['sections'] = __( 'Sections', 'learn_press' );
			}
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
						$output = sprintf( _nx( '%d section', '%d sections', $count_sections, 'learn_press' ), $count_sections );
						$output .= ' (';
						if( $count_lessons ){
							$output .= sprintf( _nx( '%d lesson', '%d lessons', $count_lessons, 'learn_press' ), $count_lessons );
						}else{
							$output .= __( "0 lesson", 'learn_press' );
						}
						$output .= ', ';
						if( $count_quizzes ){
							$output .= sprintf( _nx( '%d quiz', '%d quizzes', $count_quizzes, 'learn_press' ), $count_quizzes );
						}else{
							$output .= __( "0 quiz", 'learn_press' );
						}
						$output .= ')';
						echo $output;
					} else {
						__( '_', 'learn_press' );
					}
					break;
			}
		}
	} // end LP_Course_Post_Type
	new LP_Course_Post_Type();
}


