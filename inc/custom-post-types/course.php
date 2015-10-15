<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( !class_exists( 'LP_Course_Post_Type' ) ) {

	// Base class for custom post type to extends
	LP()->_include( 'custom-post-types/abstract.php' );

	// class LP_Course_Post_Type
	final class LP_Course_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * Constructor
		 */
		function __construct() {

			add_action( 'rwmb_course_curriculum_before_save_post', array( $this, 'before_save_curriculum' ) );
			add_filter( 'manage_lpr_course_posts_columns', array( $this, 'columns_head' ) );
			add_filter( "rwmb__lpr_course_price_html", array( $this, 'currency_symbol' ), 5, 3 );


			parent::__construct();
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
			if ( LP()->course_post_type != get_post_type() ) return;
			wp_enqueue_style( 'lp-meta-boxes', LP()->plugin_url( 'assets/css/meta-boxes.css' ) );
			wp_enqueue_script( 'jquery-caret', LP()->plugin_url( 'assets/js/jquery.caret.js', 'jquery' ) );
			wp_enqueue_script( 'lp-meta-boxes', LP()->plugin_url( 'assets/js/meta-boxes.js', 'jquery', 'backbone', 'util' ) );

			wp_localize_script( 'lp-meta-boxes', 'lp_course_params', self::admin_params() );
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
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'author' ),
				'hierarchical'       => true,
				'rewrite'            => array( 'slug' => 'courses', 'hierarchical' => true, 'with_front' => false )
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
						'slug'         => 'course_category',
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
			$prefix = '_lpr_';

			$meta_box = array(
				'id'       => 'course_settings',
				'title'    => __( 'General Settings', 'learn_press' ),
				'pages'    => array( LP_COURSE_CPT ),
				'priority' => 'high',
				'fields'   => array(
					array(
						'name' => __( 'Duration', 'learn_press' ),
						'id'   => "{$prefix}_duration",
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
						'id'   => "{$prefix}_students",
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
			$prefix = '_lpr_';

			$meta_box = array(
				'id'       => 'course_assessment',
				'title'    => __( 'Assessment', 'learn_press' ),
				'priority' => 'high',
				'pages'    => array( LP_COURSE_CPT ),
				'fields'   => array(
					array(
						'name'    => __( 'Course result', 'learn_press' ),
						'id'      => "{$prefix}final_quiz",
						'type'    => 'radio',
						'desc'    => __( 'The way to assess the result of course for a student', 'learn_press' ),
						'options' => array(
							'no'  => __( 'Evaluate lessons', 'learn_press' ),
							'yes' => __( 'Evaluate result of final quiz', 'learn_press' )
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

			$prefix = '_lpr_';

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
						'id'      => "{$prefix}_payment",
						'type'    => 'radio',
						'desc'    => __( 'If Paid be checked, An administrator will review then set course price and commission', 'learn_press' ),
						'options' => array(
							'free'     => __( 'Free', 'learn_press' ),
							'not_free' => __( 'Paid', 'learn_press' ),
						),
						'std'     => 'free',
						'class'   => 'lpr-course-payment-field'
					)
				)
			);

			if ( current_user_can( 'manage_options' ) ) {
				$message = __( 'If free, this field is empty or set 0. (Only admin can edit this field)', 'learn_press' );
				$price   = 0;

				if ( isset( $_GET['post'] ) ) {
					$course_id = $_GET['post'];
					$type      = get_post_meta( $course_id, '_lpr_course_payment', true );
					if ( $type != 'free' ) {
						$suggest_price = get_post_meta( $course_id, '_lpr_course_suggestion_price', true );
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
						'id'    => "{$prefix}_price",
						'type'  => 'number',
						'min'   => 0,
						'step'  => 0.01,
						'desc'  => $message,
						'std'   => $price,
						'class' => 'lpr-course-price-field hide-if-js'
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
						'class' => 'lpr-course-price-field hide-if-js',
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
				foreach ( $_REQUEST['_lp_curriculum'] as $order => $section ) {
					if ( !is_numeric( $order ) ) continue;
					if ( !empty( $section['ID'] ) ) {
						$query_update_section_name[]  = 'WHEN ' . $section['ID'] . ' THEN \'' . $section['name'] . '\'';
						$query_update_section_order[] = 'WHEN ' . $section['ID'] . ' THEN \'' . ( $order + 1 ) . '\'';
						$section_update_ids[] = $section['ID'];
					} else {
						$wpdb->insert(
							$wpdb->learnpress_sections,
							array(
								'name'      => $section['name'],
								'course_id' => $post->ID,
								'order'     => $order + 1
							),
							array( '%s', '%d', '%d' )
						);
						$section['ID'] = $wpdb->insert_id;
					}
					$section_ids[] = $section['ID'];

					// update items;
					if ( empty( $section['items'] ) ) continue;
					foreach ( $section['items'] as $item_order => $item ) {
						if ( !is_numeric( $item_order ) ) continue;
						if ( !empty( $item['ID'] ) ) {
							$query_update_item_name[]  = 'WHEN ' . $item['ID'] . ' THEN \'' . $item['name'] . '\'';
							$query_update_item_order[] = 'WHEN item_id = ' . $item['ID'] . ' AND section_id = ' . $section['ID'] . ' THEN \'' . ( $item_order + 1 ) . '\'';
							$item_update_ids[$item['item_id']] = $item['ID'];
						} else {
							$item['ID'] = wp_insert_post(
								array(
									'post_title' => $item['name'],
									'post_type' => $item['post_type'],
									'post_status' => 'publish'
								)
							);
							$wpdb->insert(
								$wpdb->learnpress_section_items,
								array(
									'section_id' => $section['ID'],
									'item_id'    => $item['ID'],
									'order'      => $item_order + 1
								)
							);
						}
						$item_ids[] = $item['ID'];
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

				// remove all sections not existing in course
				if ( $section_ids ) {
					$wpdb->query(
						"DELETE FROM {$wpdb->learnpress_sections} WHERE course_id = " . $post->ID . " AND ID NOT IN(" . join( ',', $section_ids ) . ")"
					);

					$all_sections    = $wpdb->get_col( "SELECT ID FROM {$wpdb->learnpress_sections} WHERE course_id = {$post->ID}" );
					$remove_sections = array_diff( $all_sections, $section_ids );
					if ( $remove_sections ) {
						$wpdb->query(
							"DELETE FROM {$wpdb->learnpress_section_items} WHERE section_id IN(" . join( ',', $remove_sections ) . ")"
						);
					}
				}

				// update the name and ordering of existing items
				if ( $query_update_item_name ) {
					$query_update_item_name = "
						UPDATE {$wpdb->posts} SET
							`post_title` = CASE `ID` " . join( ' ', $query_update_item_name ) . " END
						WHERE ID IN(" . join( ',', $item_update_ids ) . ")
					";
					$wpdb->query( $query_update_item_name );

					$query_update_item_order = "
						UPDATE {$wpdb->learnpress_section_items} SET
							`order` = CASE " . join( ' ', $query_update_item_order ) . " END
						WHERE ID IN(" . join( ',', array_keys( $item_update_ids ) ) . ")
					";
					$wpdb->query( $query_update_item_order );

					$all_items = $wpdb->get_col("
						SELECT lp_si.ID
						FROM {$wpdb->learnpress_section_items} lp_si
						INNER JOIN{$wpdb->learnpress_sections} lp_s ON lp_s.ID = lp_si.section_id
						INNER JOIN {$wpdb->posts} p ON p.ID = lp_s.course_id
						WHERE p.ID = " . $post->ID . "
					");

					$remove_items = array_diff( $all_items, $item_ids );

					$wpdb->query("
						DELETE
						FROM {$wpdb->learnpress_section_items}
						WHERE ID IN (" . join( ',', $remove_items ) . ")
					");
				}
			}
			if( ! empty( $_POST['_lp_remove_item_ids'] ) ){
				$query = "
					DELETE
					FROM {$wpdb->learnpress_section_items}
					WHERE item_id IN(" . $_POST['_lp_remove_item_ids'] . ")
					 	AND section_id IN (" . join(',', $section_ids ) . ")
				";
				$wpdb->query( $query );
			}
			//echo '<pre>';print_r($_POST);echo '</pre>';
			//die();
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
			if ( in_array( 'lpr_teacher', $user->roles ) ) {
				unset( $columns['author'] );
			}
			return $columns;
		}
	} // end LP_Course_Post_Type
	new LP_Course_Post_Type();
}


