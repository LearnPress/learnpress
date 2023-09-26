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
	final class LP_Course_Post_Type extends LP_Abstract_Post_Type {
		/**
		 * @var null
		 */
		protected static $_instance = null;

		/**
		 * @var string
		 */
		protected $_post_type = LP_COURSE_CPT;

		/**
		 * Constructor
		 */
		public function __construct() {
			parent::__construct();

			add_action( 'init', array( $this, 'register_taxonomy' ) );
			add_filter( 'posts_where_paged', array( $this, '_posts_where_paged_course_items' ), 10 );
			add_filter( 'posts_join_paged', array( $this, '_posts_join_paged_course_items' ), 10 );
		}

		/**
		 * Register course post type.
		 */
		public function args_register_post_type() : array {
			$settings         = LP_Settings::instance();
			$labels           = array(
				'name'               => _x( 'Courses', 'Post Type General Name', 'learnpress' ),
				'singular_name'      => _x( 'Course', 'Post Type Singular Name', 'learnpress' ),
				'menu_name'          => __( 'Courses', 'learnpress' ),
				'parent_item_colon'  => __( 'Parent Item:', 'learnpress' ),
				'all_items'          => __( 'Courses', 'learnpress' ),
				'view_item'          => __( 'View Course', 'learnpress' ),
				'add_new_item'       => __( 'Add a New Course', 'learnpress' ),
				'add_new'            => __( 'Add New', 'learnpress' ),
				'edit_item'          => __( 'Edit Course', 'learnpress' ),
				'update_item'        => __( 'Update Course', 'learnpress' ),
				'search_items'       => __( 'Search Courses', 'learnpress' ),
				'not_found'          => sprintf( __( 'You have not had any courses yet. Click <a href="%s">Add new</a> to start', 'learnpress' ), admin_url( 'post-new.php?post_type=lp_course' ) ),
				'not_found_in_trash' => __( 'There was no course found in the trash', 'learnpress' ),
			);
			$course_base      = LP_Settings::get_option( 'course_base' );
			$course_permalink = empty( $course_base ) ? 'courses' : $course_base;

			// Set to $has_archive return link to courses page, is_archive will check is true
			$courses_page_id = learn_press_get_page_id( 'courses' );
			$has_archive     = $courses_page_id ? urldecode( get_page_uri( $courses_page_id ) ) : 'courses';

			$args = array(
				'labels'             => $labels,
				'public'             => true,
				'query_var'          => true,
				'publicly_queryable' => true,
				'show_ui'            => true,
				'has_archive'        => $has_archive,
				'capability_type'    => $this->_post_type,
				'map_meta_cap'       => true,
				'show_in_menu'       => 'learn_press',
				'show_in_admin_bar'  => true,
				'show_in_nav_menus'  => true,
				'show_in_rest'       => true,
				'taxonomies'         => array( 'course_category', 'course_tag' ),
				'supports'           => array( 'title', 'editor', 'thumbnail', 'revisions', 'comments', 'excerpt' ),
				'hierarchical'       => false,
				'rewrite'            => ! empty( $course_permalink ) ? array(
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
			$category_base = LP_Settings::get_option( 'course_category_base' );
			register_taxonomy(
				'course_category',
				array( LP_COURSE_CPT ),
				array(
					'label'             => __( 'Course Categories', 'learnpress' ),
					'labels'            => array(
						'name'          => __( 'Course Categories', 'learnpress' ),
						'menu_name'     => __( 'Course Category', 'learnpress' ),
						'singular_name' => __( 'Category', 'learnpress' ),
						'add_new_item'  => __( 'Add A New Course Category', 'learnpress' ),
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
					'show_in_rest'      => true,
					'rewrite'           => array(
						'slug'         => empty( $category_base ) ? 'course-category' : $category_base,
						'hierarchical' => true,
						'with_front'   => false,
					),
				)
			);

			$tag_base = LP_Settings::get_option( 'course_tag_base' );
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
						'add_new_item'               => __( 'Add A New Course Tag', 'learnpress' ),
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
					'show_in_rest'          => true,
					'rewrite'               => array(
						'slug'       => empty( $tag_base ) ? 'course-tag' : $tag_base,
						'with_front' => false,
					),
				)
			);
		}

		/**
		 * Delete course sections before delete course.
		 *
		 * @param int $post_id
		 * @since 3.0.0
		 * @editor tungnx
		 * @since modify 4.0.9
		 */
		public function before_delete( int $post_id ) {
			// course curd
			// $curd = new LP_Course_CURD();
			// $curd->remove_course( $post_id );
			$course = learn_press_get_course( $post_id );
			if ( ! $course ) {
				return;
			}
			$course->delete_relate_data_when_delete_course();
		}

		/**
		 * @param string $fields
		 *
		 * @return string
		 */
		public function posts_fields( $fields ): string {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $fields;
			}

			$fields = ' DISTINCT ' . $fields;
			if ( $this->get_order_by() == 'price' ) {
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

			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $join;
			}

			if ( ! isset( $_GET['orderby'] ) || $_GET['orderby'] !== 'price' ) {
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

			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $where;
			}

			$filter_price = LP_Helper::sanitize_params_submitted( $_REQUEST['filter_price'] ?? 0 );

			if ( array_key_exists( 'filter_price', $_REQUEST ) ) {
				if ( $filter_price == 0 ) {
					$where .= ' AND ( pm_price.meta_value IS NULL || pm_price.meta_value = 0 )';
				} else {
					$where .= $wpdb->prepare( ' AND ( pm_price.meta_value = %s )', $filter_price );
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
		 * @param $orderby
		 *
		 * @return string
		 */
		public function posts_orderby( $orderby ) {
			if ( ! $this->is_page_list_posts_on_backend() ) {
				return $orderby;
			}

			$order = $this->get_order_sort();
			switch ( $this->get_order_by() ) {
				case 'price':
					$orderby = "pm_price.meta_value {$order}";
			}

			return $orderby;
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

			return apply_filters( 'lp/admin/courses/columns', $columns );
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
					echo wp_kses_post( $course->get_image( 'thumbnail' ) );
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

						foreach ( learn_press_get_course_item_types() as $item_type ) {
							$count_item = $course->count_items( $item_type );

							if ( ! $count_item ) {
								continue;
							}

							$post_type_object = $post_types[ $item_type ];
							$singular_name    = $post_type_object->labels->singular_name;
							$plural_name      = $post_type_object->label;
							$html_items[]     = sprintf( _n( '<strong>%d</strong> ' . $singular_name, '<strong>%d</strong> ' . $plural_name, $count_item, 'learnpress' ), $count_item );
						}

						$html_items = apply_filters( 'learn-press/course-count-items', $html_items );

						if ( $html_items ) {
							$output .= ' (' . implode( ', ', $html_items ) . ')';
						}

						echo wp_kses_post( $output );
					} else {
						esc_html_e( 'No content', 'learnpress' );
					}

					break;

				case 'price':
					echo wp_kses_post( $course->get_course_price_html() );
					break;
				case 'students':
					$count = $course->get_total_user_enrolled_or_purchased();
					echo '<span class="lp-label-counter' . ( ! $count ? ' disabled' : '' ) . '">' . ( $count ? $count : 0 ) . '</span>';
					break;
			}
		}

		public function meta_boxes() {
			return array(
				'course-editor' => array(
					'title'    => esc_html__( 'Curriculum', 'learnpress' ),
					'callback' => array( $this, 'admin_editor' ),
					'context'  => 'normal',
					'priority' => 'high',
				),
			);
		}

		/**
		 * Save course post
		 * Should write run background if handle big and need more time
		 *
		 * @param int     $post_id
		 * @param WP_Post $post
		 * @since 4.0.9
		 * @version 1.0.0
		 * @editor tungnx
		 * @see LP_Background_Single_Course::handle()
		 */
		public function save( int $post_id = 0, WP_Post $post = null ) {
			// Save in background.
			$bg = LP_Background_Single_Course::instance();

			$bg->data(
				array(
					'handle_name' => 'save_post',
					'course_id'   => $post_id,
					'data'        => $_POST ?? array(),
				)
			)->dispatch();
		}

		/**
		 * Instance LP_Course_Post_Type.
		 *
		 * @return LP_Course_Post_Type|null
		 */
		public static function instance() {
			if ( ! self::$_instance ) {
				self::$_instance = new self();
			}

			return self::$_instance;
		}
	}

	$course_post_type = LP_Course_Post_Type::instance();
}
