<?php
/**
 * Class LP_Course_Post_Type
 *
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
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
		 * Constructor
		 *
		 * @param string
		 */
		public function __construct( $post_type ) {
			parent::__construct( $post_type );

			// Map origin methods to another method
			$this
				->add_map_method( 'save', 'before_save_curriculum', false )
				->add_map_method( 'before_delete', 'before_delete_course' );

			add_action( 'init', array( $this, 'register_taxonomy' ) );
			add_filter( 'posts_where_paged', array( $this, '_posts_where_paged_course_items' ), 10 );
			add_filter( 'posts_join_paged', array( $this, '_posts_join_paged_course_items' ), 10 );

			add_action( 'learn-press/admin/after-enqueue-scripts', array( $this, 'data_course_editor' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'add_script_data' ) );
		}

		public function add_script_data() {
			global $post, $pagenow;

			if ( empty( $post ) || ( get_post_type() !== $this->_post_type ) || ! in_array( $pagenow, array( 'post.php', 'post-new.php' ) ) ) {
				return;
			}

			$course          = learn_press_get_course( $post->ID );
			$hidden_sections = get_post_meta( $post->ID, '_admin_hidden_sections', true );

			$data = apply_filters(
				'learn-press/admin-localize-course-editor',
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
						'confirm_trash_item'     => __( 'Do you want to remove item "{{ITEM_NAME}}" to trash?', 'learnpress' ),
						'item_labels'            => array(
							'singular' => __( 'Item', 'learnpress' ),
							'plural'   => __( 'Items', 'learnpress' ),
						),
						'notice_sale_price'      => __( 'Course sale price must less than the regular price', 'learnpress' ),
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
				'not_found'          => sprintf( __( 'You haven\'t had any courses yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_course' ) ),
				'not_found_in_trash' => __( 'No course found in Trash', 'learnpress' ),
			);
			$course_base      = $settings->get( 'course_base' );
			$course_permalink = empty( $course_base ) ? _x( 'courses', 'slug', 'learnpress' ) : $course_base;

			$courses_page_id = learn_press_get_page_id( 'courses' );

			$has_archive = $courses_page_id && get_post( $courses_page_id ) ? urldecode( get_page_uri( $courses_page_id ) ) : 'courses';

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'query_var'          => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => $has_archive,
				'capability_type'    => LP_COURSE_CPT,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => $this->is_support_gutenberg(),
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

			register_taxonomy(
				'course_category',
				array( LP_COURSE_CPT ),
				array(
					'label'             => __( 'Course Categories', 'learnpress' ),
					'labels'            => array(
						'name'          => __( 'Course Categories', 'learnpress' ),
						'menu_name'     => __( 'Course Category', 'learnpress' ),
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
					'show_in_rest'      => $this->is_support_gutenberg(),
					'rewrite'           => array(
						'slug'         => empty( $category_base ) ? _x( 'course-category', 'slug', 'learnpress' ) : $category_base,
						'hierarchical' => true,
						'with_front'   => false,
					),
				)
			);

			$tag_base = $settings->get( 'course_tag_base' );

			register_taxonomy(
				'course_tag',
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
						'menu_name'                  => __( 'Course Tags', 'learnpress' ),
					),
					'public'                => true,
					'hierarchical'          => false,
					'show_ui'               => true,
					'show_in_menu'          => 'learn_press',
					'update_count_callback' => '_update_post_term_count',
					'query_var'             => true,
					'show_in_rest'          => $this->is_support_gutenberg(),
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
		 * Delete course sections before delete course.
		 *
		 * @param $post_id
		 *
		 * @since 3.0.0
		 */
		public function before_delete_course( $post_id ) {
			// course curd
			$curd = new LP_Course_CURD();
			// remove all items from each section and delete course's sections
			$curd->delete( $post_id );
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

			$course_id = $this->_filter_items_by_course();
			if ( $course_id || ( LP_Request::get( 'orderby' ) == 'course-name' ) ) {
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_section_items si ON {$wpdb->posts}.ID = si.item_id";
				$join .= " LEFT JOIN {$wpdb->prefix}learnpress_sections s ON s.section_id = si.section_id";
				$join .= " LEFT JOIN {$wpdb->posts} c ON c.ID = s.section_course_id";
			}

			return $join;
		}

		public function _posts_where_paged_course_items( $where ) {
			global $wpdb;

			$course_id = $this->_filter_items_by_course();
			if ( $course_id ) {
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
					$where .= $wpdb->prepare( ' AND ( pm_price.meta_value = %s )', $_REQUEST['filter_price'] );
				}
			}

			$not_in = $wpdb->prepare(
				"
				SELECT ID
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s
				WHERE pm.meta_value = %s
			",
				'_lp_preview_course',
				'yes'
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
			$columns['instructor'] = 'author';
			$columns['price']      = 'price';

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
		 * Use when enable Gutenberg.
		 *
		 * @return void
		 */
		public function admin_editor() {
			$course = LP_Course::get_course();

			learn_press_admin_view( 'course/editor' );
		}

		/**
		 * Delete all sections in a course and reset auto increment
		 */
		private function _reset_sections() {
			global $wpdb, $post;

			$wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM si
					USING {$wpdb->learnpress_section_items} si
					INNER JOIN {$wpdb->learnpress_sections} s ON s.section_id = si.section_id
					INNER JOIN {$wpdb->posts} p ON p.ID = s.section_course_id
					WHERE p.ID = %d
				",
					$post->ID
				)
			);
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_section_items} AUTO_INCREMENT = 1
			"
			);

			$wpdb->query(
				$wpdb->prepare(
					"
					DELETE FROM {$wpdb->learnpress_sections}
					WHERE section_course_id = %d
				",
					$post->ID
				)
			);
			$wpdb->query(
				"
				ALTER TABLE {$wpdb->learnpress_sections} AUTO_INCREMENT = 1
			"
			);
		}

		private function _send_mail() {
			if ( ! LP()->user->is_instructor() ) {
				return;
			}
			$mail = LP()->mail;

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

			/*
			if ( $price ) {
				update_post_meta( $post->ID, '_lp_required_enroll', 'yes' );
			}*/

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
			$user   = wp_get_current_user();
			$keys   = array_keys( $columns );
			$values = array_values( $columns );
			$pos    = array_search( 'title', $keys );

			if ( ! empty( $columns['author'] ) ) {
				unset( $columns['author'] );
			}

			if ( $pos !== false ) {
				array_splice( $keys, $pos + 1, 0, array( 'instructor', 'sections', 'students', 'price' ) );
				array_splice(
					$values,
					$pos + 1,
					0,
					array(
						esc_html__( 'Author', 'learnpress' ),
						esc_html__( 'Content', 'learnpress' ),
						esc_html__( 'Students', 'learnpress' ),
						esc_html__( 'Price', 'learnpress' ),
					)
				);

				if ( $pos === 0 ) {
					array_unshift( $keys, 'thumbnail' );
					array_unshift( $values, esc_html__( 'Thumbnail', 'learnpress' ) );
				} else {
					array_splice( $keys, $pos, 0, array( 'thumbnail' ) );
					array_splice( $values, $pos, 0, array( esc_html__( 'Thumbnail', 'learnpress' ) ) );
				}

				$columns = array_combine( $keys, $values );
			} else {
				$columns['instructor'] = esc_html__( 'Author', 'learnpress' );
				$columns['sections']   = esc_html__( 'Content', 'learnpress' );
				$columns['students']   = esc_html__( 'Students', 'learnpress' );
				$columns['price']      = esc_html__( 'Price', 'learnpress' );
			}

			$columns['taxonomy-course_category'] = esc_html__( 'Categories', 'learnpress' );

			if ( in_array( 'lp_teacher', $user->roles ) ) {
				unset( $columns['instructor'] );
			}

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

			$course = learn_press_get_course( $post->ID );

			switch ( $column ) {
				case 'thumbnail':
					echo $course->get_image( 'thumbnail' );
					break;
				case 'instructor':
					$this->column_instructor( $post->ID );
					break;
				case 'sections':
					$curd            = new LP_Course_CURD();
					$number_sections = $curd->count_sections( $post_id );
					if ( $number_sections ) {
						$output     = sprintf( _n( '<strong>%d</strong> section', '<strong>%d</strong> sections', $number_sections, 'learnpress' ), $number_sections );
						$html_items = array();
						$post_types = get_post_types( null, 'objects' );

						$stats_objects = $curd->count_items( $post_id, 'edit' );

						if ( $stats_objects ) {
							foreach ( $stats_objects as $type => $count ) {
								if ( ! $count || ! isset( $post_types[ $type ] ) ) {
									continue;
								}

								$post_type_object = $post_types[ $type ];
								$singular_name    = strtolower( $post_type_object->labels->singular_name );
								$plural_name      = strtolower( $post_type_object->label );
								$html_items[]     = sprintf( _n( '<strong>%d</strong> ' . $singular_name, '<strong>%d</strong> ' . $plural_name, $count, 'learnpress' ), $count );
							}
						}

						$html_items = apply_filters( 'learn-press/course-count-items', $html_items );

						if ( $html_items ) {
							$output .= ' (' . implode( ', ', $html_items ) . ')';
						}

						echo $output;

					} else {
						esc_html_e( 'No content', 'learnpress' );
					}

					break;

				case 'price':
					$price   = $course->get_price();
					$is_paid = ! $course->is_free();

					$origin_price = '';
					if ( $course->get_origin_price() && $course->has_sale_price() ) {
						$origin_price = sprintf( '<span class="origin-price">%s</span>', $course->get_origin_price_html() );
					}

					if ( $is_paid ) {
						echo sprintf( '<a href="%s" class="price">%s%s</a>', add_query_arg( 'filter_price', $price ), $origin_price, learn_press_format_price( $course->get_price(), true ) );
					} else {
						echo sprintf( '<a href="%s" class="price">%s%s</a>', add_query_arg( 'filter_price', 0 ), $origin_price, esc_html__( 'Free', 'learnpress' ) );

						if ( ! $course->is_required_enroll() ) {
							printf( '<p class="description">(%s)</p>', esc_html__( 'No requirement enroll', 'learnpress' ) );
						}
					}
					break;
				case 'students':
					// In ra so student da enroll.
					$count = LP()->utils->count_course_users(
						array(
							'course_id'  => $course->get_id(),
							'status'     => learn_press_course_enrolled_slugs(),
							'total_only' => true,
						)
					);

					echo '<span class="lp-label-counter' . ( ! $count ? ' disabled' : '' ) . '">' . ( $count ? $count : 0 ) . '</span>';

			}
		}

		/**
		 * Before save curriculum action.
		 * If is instructor will pending course if enable required review in settings.
		 */
		public function before_save_curriculum() {
			global $post, $pagenow;

			if ( ( $pagenow != 'post.php' ) || ( get_post_type() != LP_COURSE_CPT ) ) {
				return;
			}

			remove_action( 'save_post', array( $this, 'before_save_curriculum' ), 1 );

			$user            = learn_press_get_current_user();
			$required_review = LP()->settings->get( 'required_review' ) == 'yes';

			if ( $user->is_instructor() && $required_review ) {
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

			// Update price
			$this->_update_price();

			if ( $new_status != $old_status ) {
				do_action( 'learn_press_transition_course_status', $new_status, $old_status, $post->ID );
				update_post_meta( $post->ID, '_lp_course_status', $new_status );
			}

			delete_post_meta( $post->ID, '_lp_curriculum' );
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
	}

	$course_post_type = LP_Course_Post_Type::instance();

	$course_post_type->add_meta_box( 'course-editor', esc_html__( 'Curriculum', 'learnpress' ), 'admin_editor', 'normal', 'high' );
}
