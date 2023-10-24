<?php

/**
 * Class LP_Page_Controller
 */
class LP_Page_Controller {
	protected static $_instance = null;

	/**
	 * Store the object has queried by WP.
	 *
	 * @var int
	 */
	//protected $_queried_object = 0;

	/**
	 * @var int
	 */
	//protected $_filter_content_priority = 10000;

	/**
	 * Flag for 404 content.
	 *
	 * @var bool
	 */
	protected $_is_404 = false;

	/**
	 * LP_Page_Controller constructor.
	 */
	protected function __construct() {
		// Set link course, item course.
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 2 );

		if ( is_admin() ) {

		} else {
			//add_filter( 'post_type_archive_link', [ $this, 'link_archive_course' ], 10, 2 );
			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), -1 );
			// For return result query course to cache.
			//add_action( 'posts_pre_query', [ $this, 'posts_pre_query' ], 10, 2 );
			add_filter( 'template_include', array( $this, 'template_loader' ), 10 );
			add_filter( 'template_include', array( $this, 'check_pages' ), 30 );
			//add_filter( 'template_include', array( $this, 'auto_shortcode' ), 50 );

			add_filter( 'the_post', array( $this, 'setup_data_for_item_course' ) );
			add_filter( 'request', array( $this, 'remove_course_post_format' ), 1 );

			//add_shortcode( 'learn_press_archive_course', array( $this, 'archive_content' ) );
			add_filter( 'pre_get_document_title', array( $this, 'set_title_pages' ), 20, 1 );

			// Yoast seo
			add_filter( 'wpseo_opengraph_desc', array( $this, 'lp_desc_item_yoast_seo' ), 11, 1 );
			add_filter( 'wpseo_metadesc', array( $this, 'lp_desc_item_yoast_seo' ), 11, 1 );

			// Set link profile to admin menu
			//add_action( 'admin_bar_menu', array( $this, 'learn_press_edit_admin_bar' ) );

			// Set again x-wp-nonce on header when has cache with not login.
			add_filter( 'rest_send_nocache_headers', array( $this, 'check_x_wp_nonce_cache' ) );

			// Rewrite lesson comment links
			add_filter( 'get_comment_link', array( $this, 'edit_lesson_comment_links' ), 10, 2 );
			// Active menu
			add_filter( 'wp_nav_menu_objects', [ $this, 'menu_active' ], 10, 1 );
		}
	}

	/**
	 * Set link archive course.
	 *
	 * @param string $link
	 * @param string $post_type
	 *
	 * @return string
	 */
	public function link_archive_course( string $link, string $post_type ): string {
		if ( $post_type == LP_COURSE_CPT && learn_press_get_page_id( 'courses' ) ) {
			$link = learn_press_get_page_link( 'courses' );
		}

		return $link;
	}

	/**
	 * Set link course, item course
	 *
	 * @param string $post_link
	 * @param object $post
	 */
	public function post_type_link( $post_link, $post ) {
		// Set item's course permalink
		$course_item_types = learn_press_get_course_item_types();
		$item_id           = $post->ID;

		// Link item course on search page of WP.
		if ( in_array( $post->post_type, $course_item_types ) && is_search() ) {
			$section_id = LP_Section_DB::getInstance()->get_section_id_by_item_id( $item_id );
			if ( ! $section_id ) {
				return $post_link;
			}

			$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $section_id );
			if ( ! $course_id ) {
				return $post_link;
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				return $post_link;
			}

			$post_link = $course->get_item_link( $item_id );
		} elseif ( LP_COURSE_CPT === $post->post_type ) {
			// Link single course (with %course_category%).
			$post_link = LP_Helper::handle_lp_permalink_structure( $post_link, $post );
		}

		return $post_link;
	}

	/**
	 * Set title of pages
	 *
	 * 1. Title course archive page
	 * 2. Title item of course
	 * 3. Title page Profile
	 *
	 * @param string $title
	 *
	 * @return string
	 * @author tungnx
	 * @since  3.2.7.7
	 * @version 1.0.1
	 */
	public function set_title_pages( $title = '' ): string {
		$flag_title_course = false;

		$course_archive_page_id = LP_Settings::get_option( 'courses_page_id', 0 );

		// Set title single course.
		if ( learn_press_is_course() ) {
			$item = LP_Global::course_item();
			if ( $item ) {
				$title = apply_filters( 'learn-press/document-course-title-parts', get_the_title() . ' &rarr; ' . $item->get_title(), $item );

				$flag_title_course = true;
			}
		} elseif ( LP_Page_Controller::is_page_courses() ) { // Set title course archive page.
			if ( isset( $_GET['c_search'] ) ) {
				$title = __( 'Course Search Results', 'learnpress' );
			} elseif ( is_tax( LP_COURSE_CATEGORY_TAX ) || is_tax( LP_COURSE_TAXONOMY_TAG ) ) {
				/**
				 * @var WP_Query $wp_query
				 */
				global $wp_query;
				if ( $wp_query->queried_object ) {
					$title = $wp_query->queried_object->name;
				}
			} else {
				$title = $course_archive_page_id ? get_the_title( $course_archive_page_id ) : __( 'Courses', 'learnpress' );
			}

			$flag_title_course = true;
		} elseif ( LP_Page_Controller::is_page_profile() ) {
			$profile  = LP_Profile::instance();
			$tab_slug = $profile->get_current_tab();
			$tab      = $profile->get_tab_at( $tab_slug );
			$page_id  = learn_press_get_page_id( 'profile' );

			if ( $page_id ) {
				$page_title = get_the_title( $page_id );
			} else {
				$page_title = '';
			}
			if ( $tab instanceof LP_Profile_Tab ) {
				$title = join(
					' ',
					apply_filters(
						'learn-press/document-profile-title-parts',
						array(
							$page_title,
							'&rarr;',
							$tab->get( 'title' ),
						)
					)
				);
			}

			$flag_title_course = true;
		}

		if ( $flag_title_course ) {
			$title .= ' - ' . get_bloginfo( 'name', 'display' );
		}

		if ( ! is_string( $title ) ) {
			$title = get_bloginfo( 'name', 'display' );
		}

		return apply_filters( 'learn-press/title-page', $title );
	}

	/**
	 * Set description of course's item for yoast seo
	 *
	 * @param $desc
	 *
	 * @return mixed
	 * @author tungnx
	 * @since 3.2.7.9
	 */
	public function lp_desc_item_yoast_seo( $desc ) {
		if ( learn_press_is_course() ) {

			$item = LP_Global::course_item();

			if ( empty( $item ) ) {
				return $desc;
			}

			$desc = get_post_meta( $item->get_id(), '_yoast_wpseo_metadesc', true );
		}

		return $desc;
	}

	public function check_pages( $template ) {
		if ( learn_press_is_checkout() ) {
			$available_gateways = LP_Gateways::instance()->get_available_payment_gateways();

			if ( ! $available_gateways ) {
				learn_press_add_message( __( 'No payment method is available.', 'learnpress' ), 'error' );
			}
		} else {
			global $wp_query;

			$logout_slug = learn_press_profile_logout_slug();

			if ( $logout_slug && ( $wp_query->get( 'view' ) === $logout_slug ) ) {
				wp_safe_redirect( str_replace( '&amp;', '&', wp_logout_url( learn_press_get_page_link( 'profile' ) ) ) );
				exit;
			}
		}

		return $template;
	}

	/**
	 * Auto inserting a registered shortcode to a specific page
	 * if that page is viewing in single mode.
	 *
	 * @param string $template
	 *
	 * @return string;
	 * @since 3.3.0
	 * @deprecated 4.2.3
	 */
	public function auto_shortcode( $template ) {
		_deprecated_function( __METHOD__, '4.2.3' );
		global $post;
		$the_post = $post;
		if ( $the_post && is_page( $the_post->ID ) ) {

			// Filter here to insert the shortcode
			$auto_shortcodes = apply_filters( 'learn-press/auto-shortcode-pages', array() );

			if ( ! empty( $auto_shortcodes[ $the_post->ID ] ) ) {
				$shortcode_tag = $auto_shortcodes[ $the_post->ID ];

				preg_match( '/\[' . $shortcode_tag . '\s?(.*)\]/', $the_post->post_content, $results );

				if ( empty( $results ) ) {
					$content                = $the_post->post_content . "[$shortcode_tag]";
					$the_post->post_content = $content;
				}
			}
		}

		return $template;
	}

	/**
	 * Load data for item of course
	 *
	 * @param $post
	 *
	 * @return mixed
	 * @editor tungnx
	 * Todo: should remove this function when load true type post's item
	 */
	public function setup_data_for_item_course( $post ): WP_Post {
		/**
		 * @var WP $wp
		 * @var WP_Query $wp_query
		 * @var LP_Course $lp_course
		 * @var LP_Course_Item|LP_Quiz|LP_Lesson $lp_course_item
		 */
		global $wp, $wp_query, $lp_course_item;
		$vars = $wp->query_vars;
		if ( empty( $vars['course-item'] ) ) {
			return $post;
		}

		if ( LP_COURSE_CPT !== $post->post_type ) {
			return $post;
		}

		$course = learn_press_get_course();
		if ( ! $course ) {
			return $post;
		}

		/**
		 * @deprecated v4.1.6.1 LearnPress::instance()->global['course'], $GLOBALS['course']
		 * Some theme still use: global $course; LearnPress::instance()->global['course']
		 */
		//LearnPress::instance()->global['course'] = $GLOBALS['course'] = $GLOBALS['lp_course'] = $course;
		LearnPress::instance()->global['course'] = $GLOBALS['course'] = $course;

		if ( wp_verify_nonce( LP_Request::get_param( 'preview' ), 'preview-' . $post->ID ) ) {
			$GLOBALS['preview_course'] = $post->ID;
		}

		if ( ! $wp_query->is_main_query() ) {
			return $post;
		}

		try {
			$user = learn_press_get_current_user();

			// If item name is set in query vars
			if ( ! is_numeric( $vars['course-item'] ) ) {
				$item_type = $vars['item-type'];
				$post_item = learn_press_get_post_by_name( $vars['course-item'], $item_type );
			} else {
				$post_item = get_post( absint( $vars['course-item'] ) );
			}

			if ( ! $post_item ) {
				return $post;
			}

			$lp_course_item = apply_filters( 'learn-press/single-course-request-item', $course->get_item( $post_item->ID ) );

			if ( ! $lp_course_item ) {
				return $post;
			}

			// Set item viewing
			$user->set_viewing_item( $lp_course_item );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $post;
	}

	public function is_404() {
		return apply_filters( 'learn-press/query/404', $this->_is_404 );
	}

	/**
	 * In preview mode, if there is a 'post_format' in query var
	 * wp check and replace our post-type to post. This make preview
	 * course item become 404
	 *
	 * @param $qv
	 *
	 * @return mixed
	 */
	public function remove_course_post_format( $qv ) {
		if ( ! empty( $qv['post_type'] ) && LP_COURSE_CPT === $qv['post_type'] ) {
			if ( ! empty( $qv['post_format'] ) ) {
				unset( $qv['post_format'] );
			}
		}

		return $qv;
	}

	/**
	 * @return bool
	 */
	protected function _is_archive() {
		return learn_press_is_courses() || learn_press_is_course_tag() || learn_press_is_course_category() || learn_press_is_search() || learn_press_is_course_tax();
	}

	/**
	 * @return bool
	 */
	protected function _is_single() {
		return learn_press_is_course() && is_single();
	}

	/**
	 * Load content of course depending on query.
	 *
	 * @param string $template
	 *
	 * @return bool|string
	 */
	public function template_loader( $template ) {
		if ( wp_is_block_theme() ) {
			return $template;
		}

		if ( is_embed() ) {
			return $template;
		}

		// $this->_maybe_redirect_courses_page();

		$default_template = $this->get_page_template();

		if ( $default_template ) {
			$templates = $this->get_page_templates( $default_template );

			/**
			 * Disable override templates in theme by default since LP 4.0.0
			 */
			if ( learn_press_override_templates() ) {
				$new_template = locate_template( $templates );
			}

			if ( ! isset( $new_template ) || ! $new_template ) {
				$new_template = LP_TEMPLATE_PATH . $default_template;
			}

			$template = $new_template;
		}

		return $template;
	}

	/**
	 * Get the default filename for a template.
	 *
	 * @return string
	 * @since  4.0.0
	 */
	private function get_page_template() {
		$page_template = '';

		if ( is_singular( LP_COURSE_CPT ) ) {
			$page_template = 'single-course.php';

			if ( $this->_is_single() ) {
				global $post;
				setup_postdata( $post );

				$course_item = LP_Global::course_item();
				if ( $course_item ) {
					$page_template = 'content-single-item.php';
				}
			}
		} elseif ( learn_press_is_course_taxonomy() ) {
			$object = get_queried_object();

			if ( is_tax( 'course_category' ) || is_tax( 'course_tag' ) ) {
				$page_template = 'taxonomy-' . $object->taxonomy . '.php';

				if ( ! file_exists( learn_press_locate_template( $page_template ) ) ) {
					$page_template = 'archive-course.php';
				}
			} else {
				$page_template = 'archive-course.php';
			}
		} elseif ( self::is_page_courses() ) {
			$page_template = 'archive-course.php';
		} elseif ( learn_press_is_checkout() ) {
			$page_template = 'pages/checkout.php';
		}

		return apply_filters( 'learn-press/page-template', $page_template );
	}

	private function get_page_templates( $default_template ) {
		$templates = apply_filters( 'learn-press/page-templates', array(), $default_template );

		if ( is_page_template() ) {
			$page_template = get_page_template_slug();

			if ( $page_template ) {
				$validated_file = validate_file( $page_template );
				if ( 0 === $validated_file ) {
					$templates[] = $page_template;
				} else {
					error_log( "LearnPress: Unable to validate template path: \"$page_template\". Error Code: $validated_file." );
				}
			}
		}

		if ( is_singular( LP_COURSE_CPT ) ) {
			$object       = get_queried_object();
			$name_decoded = urldecode( $object->post_name );

			if ( $name_decoded !== $object->post_name ) {
				$templates[] = "single-course-$name_decoded.php";
			}

			$templates[] = "single-product-$object->post_name.php";
		}

		if ( learn_press_is_course_taxonomy() ) {
			$object      = get_queried_object();
			$templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = learn_press_template_path() . '/taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = 'taxonomy-' . $object->taxonomy . '.php';
			$templates[] = learn_press_template_path() . '/taxonomy-' . $object->taxonomy . '.php';
		}

		$templates[] = $default_template;
		$templates[] = learn_press_template_path() . '/' . $default_template;

		return array_unique( $templates );
	}

	/**
	 * Filter to allow search more templates in theme for wp page template hierarchy.
	 * Theme twentytwenty used 'singular.php' instead of 'page.php'
	 *
	 * @param array $templates
	 *
	 * @return array
	 * @since 3.x.x
	 * @deprecated 4.1.6.9.2
	 */
	/*public function page_template_hierarchy( $templates ) {
		$templates = array_merge( $templates, array( 'singular.php' ) );

		return $templates;
	}*/

	/**
	 * Archive course content.
	 *
	 * @return false|string
	 * @deprecated 4.2.3.3.
	 */
	public function archive_content() {
		_deprecated_function( __METHOD__, '4.2.3.3' );
		ob_start();
		learn_press_get_template( 'content-archive-course.php' );

		return ob_get_clean();
	}

	/**
	 * @param $title
	 *
	 * @return mixed
	 */
	public function page_title( $title ) {
		global $wp_query;
		if ( ! empty( $wp_query->queried_object_id ) ) {
			$title['title'] = get_the_title( $wp_query->queried_object_id );
		}

		return $title;
	}

	/**
	 * Query courses if page is archive courses
	 *
	 * @param $q WP_Query
	 *
	 * @return WP_Query
	 * @editor tungnx
	 * @since 3.0.0
	 * @version 4.1.3
	 * @throws Exception
	 */
	public function pre_get_posts( WP_Query $q ): WP_Query {
		// Affect only the main query and not in admin
		if ( ! $q->is_main_query() && ! is_admin() ) {
			return $q;
		}

		try {
			if ( LP_Page_Controller::is_page_courses() ) {
				if ( LP_Settings_Courses::is_ajax_load_courses() && ! LP_Settings_Courses::is_no_load_ajax_first_courses()
				&& ! LP_Settings::theme_no_support_load_courses_ajax() ) {
					/**
					 * If page is archive course - query set posts_per_page = 1
					 * For fastest - because when page loaded - call API to load list courses
					 *
					 * Current, apply only for LP, not apply for theme Thimpress, because theme override
					 */
					$q->set( 'posts_per_page', 1 );
					$q->set( 'suppress_filters', true );
					//$q->set( 'posts_per_archive_page', 1 );
					//$q->set( 'nopaging', true );
				} else {
					$filter               = new LP_Course_Filter();
					$filter->only_fields  = [ 'ID' ];
					$filter->limit        = -1;
					$is_need_check_in_arr = false;
					$limit                = LP_Settings::get_option( 'archive_course_limit', 6 );

					if ( LP_Settings_Courses::is_ajax_load_courses() &&
						LP_Settings_Courses::get_type_pagination() != 'number' &&
						! LP_Settings::theme_no_support_load_courses_ajax() ) {
						$q->set( 'paged', 1 );
					}

					$q->set( 'posts_per_page', $limit );
					// $q->set( 'cache_results', true ); // it default true

					// Search courses by keyword
					if ( ! empty( $_REQUEST['c_search'] ) ) {
						$filter->post_title   = LP_Helper::sanitize_params_submitted( $_REQUEST['c_search'] );
						$is_need_check_in_arr = true;
					}

					$author_ids_str = LP_Helper::sanitize_params_submitted( $_REQUEST['c_authors'] ?? 0 );
					if ( ! empty( $author_ids_str ) ) {
						$q->set( 'author', $author_ids_str );
					}

					// Search course has price/free
					$meta_query = [];
					if ( isset( $_REQUEST['sort_by'] ) ) {
						$sort_by = LP_Helper::sanitize_params_submitted( $_REQUEST['sort_by'] );
						if ( 'on_paid' === $sort_by ) {
							$meta_query[] = array(
								'key'     => '_lp_price',
								'value'   => 0,
								'compare' => '>',
							);
						}

						if ( 'on_free' === $sort_by ) {
							$meta_query[] = array(
								'relation' => 'OR',
								[
									'key'     => '_lp_price',
									'value'   => 0,
									'compare' => '=',
								],
								[
									'key'     => '_lp_price',
									'value'   => '',
									'compare' => '=',
								],
								[
									'key'     => '_lp_price',
									'compare' => 'NOT EXISTS',
								],
							);
						}
					}

					// Search by level
					$c_level = LP_Helper::sanitize_params_submitted( urldecode( $_REQUEST['c_level'] ?? '' ) );
					if ( ! empty( $c_level ) ) {
						$c_level      = str_replace( 'all', '', $c_level );
						$c_level      = explode( ',', $c_level );
						$meta_query[] = array(
							'key'     => '_lp_level',
							'value'   => $c_level,
							'compare' => 'IN',
						);
					}

					$q->set( 'meta_query', $meta_query );
					// End Meta query

					// Search on Category
					$tax_query    = [];
					$term_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $_REQUEST['term_id'] ?? '' ) );
					if ( ! empty( $term_ids_str ) ) {
						$term_ids = explode( ',', $term_ids_str );

						$tax_query[] = array(
							'taxonomy' => 'course_category',
							'field'    => 'term_id',
							'terms'    => $term_ids,
							'operator' => 'IN',
						);
					}

					// Tag query
					$tag_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $_REQUEST['tag_id'] ?? '' ) );
					if ( ! empty( $tag_ids_str ) ) {
						$term_ids = explode( ',', $tag_ids_str );

						$tax_query[] = array(
							'taxonomy' => 'course_tag',
							'field'    => 'term_id',
							'terms'    => $term_ids,
							'operator' => 'IN',
						);
					}

					$q->set( 'tax_query', $tax_query );
					// End Tax query

					// Author query
					if ( isset( $_REQUEST['c_author'] ) ) {
						$author_ids = LP_Helper::sanitize_params_submitted( $_REQUEST['c_author'] );
						$q->set( 'author__in', $author_ids );
					}
					// End Author query

					// Order query
					if ( isset( $_REQUEST['order_by'] ) ) {
						$order_by = LP_Helper::sanitize_params_submitted( $_REQUEST['order_by'] );
						$order    = 'DESC';

						switch ( $order_by ) {
							case 'post_title':
								$order_by = 'title';
								$order    = 'ASC';
								break;
							case 'popular':
								$filter->order_by     = 'popular';
								$order_by             = 'post__in';
								$is_need_check_in_arr = true;
								break;
							default:
								$order_by = 'date';
								break;
						}

						$q->set( 'orderby', $order_by );
						$q->set( 'order', $order );
					}

					if ( $is_need_check_in_arr ) {
						$posts_in = LP_Course::get_courses( $filter );
						if ( ! empty( $posts_in ) ) {
							$posts_in = LP_Database::get_values_by_key( $posts_in );
							$q->set( 'post__in', $posts_in );
						} else {
							$q->set( 'post__in', 0 );
						}
					}

					$q = apply_filters( 'lp/page-courses/query/legacy', $q );
				}

				return $q;
			}

			// Exclude item not assign
			if ( $q->is_search() ) {
				// Exclude item not assign any course
				$course_item_types = learn_press_get_course_item_types();
				$list_ids_exclude  = array();

				foreach ( $course_item_types as $item_type ) {
					$filter            = new LP_Post_Type_Filter();
					$filter->post_type = $item_type;
					$exclude_item      = LP_Course_DB::getInstance()->get_item_ids_unassigned( $filter );
					$exclude_item      = LP_Course_DB::get_values_by_key( $exclude_item );

					$list_ids_exclude = array_merge( $list_ids_exclude, $exclude_item );
				}

				// Exclude question not assign any quiz
				$question_ids     = LP_Question_DB::getInstance()->get_questions_not_assign_quiz();
				$question_ids     = LP_Course_DB::get_values_by_key( $question_ids );
				$list_ids_exclude = array_merge( $list_ids_exclude, $question_ids );

				if ( ! empty( $list_ids_exclude ) ) {
					$q->set( 'post__not_in', $list_ids_exclude );
				}

				return $q;
			}

			// Handle 404 if user are viewing course item directly.
			$this->set_link_item_course_default_wp_to_page_404( $q );

			// set 404 if viewing single instructor but not logged
			$slug_instructor = get_query_var( 'instructor_name' );
			if ( get_query_var( 'is_single_instructor' ) ) {
				if ( empty( $slug_instructor ) && ! is_user_logged_in() ) {
					self::set_page_404();
				}
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $q;
	}

	/**
	 * Write temporary for optimize.
	 *
	 * @param $posts
	 * @param $wp_query
	 *
	 * @return array|mixed
	 */
	/*public function posts_pre_query( $posts, $wp_query ) {
		if ( self::is_page_courses() ) {
			$filter                  = new LP_Course_Filter();
			$filter->only_fields     = array( 'ID' );
			$filter->run_query_count = false;
			$filter->where[]         = "AND post_status = 'publish'";
			$filter->limit           = 1;
			$courses                 = LP_Course::get_courses( $filter );

			$posts = [ get_post( $courses[0]->ID ) ];
		}
		return $posts;
	}*/

	/**
	 * Handle 404 if user are viewing course item directly.
	 * Example: http://example.com/lesson/slug-lesson
	 * Apply for user not admin, instructor, co-instructor
	 *
	 * @param WP_Query $q
	 * @editor tungnx
	 * @since  3.2.7.5
	 */
	public function set_link_item_course_default_wp_to_page_404( $q ) {
		$post_type_apply_404 = apply_filters( 'lp/page-controller/', array( LP_LESSON_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT, 'lp_assignment' ) );

		if ( ! isset( $q->query_vars['post_type'] ) || ! in_array( $q->query_vars['post_type'], $post_type_apply_404 ) ) {
			return $q;
		}

		try {
			$flag_load_404 = true;
			$user          = wp_get_current_user();
			$post_author   = 0;

			if ( $user ) {
				if ( isset( $_GET['preview_id'] ) ) {
					$post_id     = absint( $_GET['preview_id'] );
					$post        = get_post( $post_id );
					$post_author = $post->post_author;
				} elseif ( isset( $_GET['preview'] ) && isset( $_GET['p'] ) ) {
					$post_id     = absint( $_GET['p'] );
					$post        = get_post( $post_id );
					$post_author = $post->post_author;
				} else {
					$post_author = LP_Database::getInstance()->getPostAuthorByTypeAndSlug( $q->query_vars['post_type'] ?? '', $q->query_vars['name'] ?? '' );
				}

				if ( $user->has_cap( 'administrator' ) ) {
					$flag_load_404 = false;
				} elseif ( $user->has_cap( LP_TEACHER_ROLE ) && $post_author == $user->ID ) {
					$flag_load_404 = false;
				}
			}

			$flag_load_404 = apply_filters( 'learnpress/page/set-link-item-course-404', $flag_load_404, $post_author, $user );

			if ( $flag_load_404 ) {
				self::set_page_404();
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * @deprecated 4.1.6.9.2
	 */
	/*public function the_content_callback( $content ) {
		if ( $this->_archive_contents ) {
			preg_match( '/\[learn_press_archive_course\s?(.*)\]/', $content, $results );
			$this->_shortcode_exists = ! empty( $results );
			if ( $this->_shortcode_exists ) {
				$this->_shortcode_tag = $results[0];
				$content              = str_replace( $this->_shortcode_tag, $this->_archive_contents, $content );
			} else {
				$content .= $this->_archive_contents;
			}
		}

		return $content;
	}*/

	/**
	 * Get page current on frontend
	 *
	 * @return string
	 * @since 3.2.8
	 * @author tungnx
	 */
	public static function page_current(): string {
		/**
		 * @var WP_Query $wp_query
		 */
		global $wp_query;

		if ( ! is_object( $wp_query ) || ! $wp_query->get_queried_object() ) {
			return '';
		}

		if ( self::is_page_checkout() ) {
			return LP_PAGE_CHECKOUT;
		} elseif ( LP_Global::course_item_quiz() ) {
			return LP_PAGE_QUIZ;
		} elseif ( learn_press_is_course() && LP_Global::course_item() ) {
			return LP_PAGE_SINGLE_COURSE_CURRICULUM;
		} elseif ( self::is_page_courses() ) {
			return LP_PAGE_COURSES;
		} elseif ( learn_press_is_course() ) {
			return LP_PAGE_SINGLE_COURSE;
		} elseif ( self::is_page_become_a_teacher() ) {
			return LP_PAGE_BECOME_A_TEACHER;
		} elseif ( self::is_page_profile() ) {
			return LP_PAGE_PROFILE;
		} elseif ( learn_press_is_instructors() ) {
			return LP_PAGE_INSTRUCTORS;
		} elseif ( self::is_page_instructor() ) {
			return LP_PAGE_INSTRUCTOR;
		} else {
			return apply_filters( 'learnpress/page/current', '' );
		}
	}

	/**
	 * Check is page viewing
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
	public static function page_is( string $name = '' ): bool {
		$page_id = learn_press_get_page_id( $name );
		if ( ! $page_id || 'page' !== get_post_type( $page_id ) ) {
			return false;
		}

		// If pages of LP set to homepage will return false
		$link_page = get_the_permalink( $page_id );
		$home_url  = home_url( '/' );
		if ( $home_url === $link_page ) {
			return false;
		}

		$page_profile_option = untrailingslashit( $link_page );
		$page_profile_option = str_replace( '/', '\/', $page_profile_option );
		$pattern             = '/' . $page_profile_option . '/';
		if ( preg_match( $pattern, LP_Helper::getUrlCurrent() ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Check is page courses
	 *
	 * @return bool
	 */
	public static function is_page_courses(): bool {
		static $flag;
		if ( ! is_null( $flag ) ) {
			return $flag;
		}

		$is_tag      = is_tax( LP_COURSE_TAXONOMY_TAG );
		$is_category = is_tax( LP_COURSE_CATEGORY_TAX );

		if ( $is_category || $is_tag || is_post_type_archive( 'lp_course' ) ) {
			$flag = true;
		} else {
			$page_courses_id  = learn_press_get_page_id( 'courses' );
			$page_courses_url = untrailingslashit( get_the_permalink( $page_courses_id ) );
			if ( empty( $page_courses_url ) ) {
				$page_courses_url = home_url( 'courses' );
			}

			$page_courses_regex = str_replace( '/', '\/', $page_courses_url );
			$pattern            = '/' . $page_courses_regex . '\/?(page\/[0-9]*)?$/';
			if ( preg_match( $pattern, LP_Helper::getUrlCurrent() ) ) {
				$flag = true;
			} else {
				$flag = false;
			}
		}

		return $flag;
	}

	/**
	 * Check is page profile
	 *
	 * @return bool
	 */
	public static function is_page_profile(): bool {
		static $flag;
		if ( ! is_null( $flag ) ) {
			return $flag;
		}

		$flag = self::page_is( 'profile' );

		return $flag;
	}

	/**
	 * Check is page instructor
	 *
	 * @return bool
	 */
	public static function is_page_instructors(): bool {
		static $flag;
		if ( ! is_null( $flag ) ) {
			return $flag;
		}

		$flag = self::page_is( 'instructors' );

		return $flag;
	}

	/**
	 * Check is page instructor
	 *
	 * @return bool
	 */
	public static function is_page_instructor(): bool {
		global $wp_query;
		static $flag;
		if ( ! is_null( $flag ) ) {
			return $flag;
		}

		$flag = false;
		if ( $wp_query->get( 'is_single_instructor' ) ) {
			$flag = true;
		}

		return $flag;
	}

	/**
	 * Check is page profile
	 *
	 * @return bool
	 */
	public static function is_page_checkout(): bool {
		return self::page_is( 'checkout' );
	}

	/**
	 * Check is page Become a teacher
	 *
	 * @return bool
	 * @since 3.2.8
	 * @author tungnx
	 */
	public static function is_page_become_a_teacher(): bool {
		return self::page_is( 'become_a_teacher' );
	}

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add user profile link into admin bar
	 *
	 * @editor tungnx
	 * @version 1.0.1
	 * @since  3.0.0
	 * @deprecated 4.1.7.3
	 */
	public function learn_press_edit_admin_bar() {
		global $wp_admin_bar;

		$current_user = wp_get_current_user();

		if ( ! in_array( LP_TEACHER_ROLE, $current_user->roles ) && ! in_array( 'administrator', $current_user->roles ) ) {
			return;
		}

		$page_profile_id = learn_press_get_page_id( 'profile' );
		if ( $page_profile_id && get_post_status( $page_profile_id ) != 'trash' ) {
			$user_id = $current_user->ID;

			$wp_admin_bar->add_menu(
				array(
					'id'     => 'course_profile',
					'parent' => 'user-actions',
					'title'  => get_the_title( $page_profile_id ),
					'href'   => learn_press_user_profile_link( $user_id, false ),
				)
			);
		}
	}

	/**
	 * Set again HTTP_X_WP_NONCE when cache make 403 error.
	 *
	 * @param $send_no_cache_headers
	 *
	 * @return mixed
	 * @since 4.1.7
	 * @version 1.0.0
	 */
	public function check_x_wp_nonce_cache( $send_no_cache_headers ) {
		if ( ! $send_no_cache_headers && ! is_admin() && $_SERVER['REQUEST_METHOD'] == 'GET' && LP_Helper::isRestApiLP() ) {
			$nonce                      = wp_create_nonce( 'wp_rest' );
			$_SERVER['HTTP_X_WP_NONCE'] = $nonce;
		}

		return $send_no_cache_headers;
	}

	/**
	 * Override lesson comment permalink.
	 *
	 * @return string $link The comment permalink with '#comment-$id' appended.
	 * @param string     $link    The comment permalink with '#comment-$id' appended.
	 * @param WP_Comment $comment The current comment object.
	 * @since 4.2.3
	 * @version 1.0.0
	 */
	public function edit_lesson_comment_links( $link, $comment ): string {
		try {
			$comment = get_comment( $comment );
			if ( get_post_type( $comment->comment_post_ID ) == LP_LESSON_CPT ) {
				$link = wp_get_referer() . '#comment-' . $comment->comment_ID;
			}

			return $link;
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $link;
	}

	/**
	 * Set menu active for page courses.
	 *
	 * @param $menu_items
	 * @return mixed
	 */
	public function menu_active( $menu_items ) {
		$course_page    = learn_press_get_page_id( 'courses' );
		$page_for_posts = (int) get_option( 'page_for_posts' );

		if ( is_array( $menu_items ) && ! empty( $menu_items ) ) {
			foreach ( $menu_items as $key => $menu_item ) {
				$classes = (array) $menu_item->classes;
				$menu_id = (int) $menu_item->object_id;

				// Unset active class for blog page.
				if ( $page_for_posts === $menu_id ) {
					$menu_item->current = false;

					if ( in_array( 'current_page_parent', $classes, true ) ) {
						unset( $classes[ array_search( 'current_page_parent', $classes, true ) ] );
					}

					if ( in_array( 'current-menu-item', $classes, true ) ) {
						unset( $classes[ array_search( 'current-menu-item', $classes, true ) ] );
					}
				} elseif ( ( is_post_type_archive( 'lp_course' ) || is_page( $course_page ) ) && $course_page === $menu_id && 'page' === $menu_item->object ) {
					// Set active state if this is the shop page link.
					$menu_item->current = true;
					$classes[]          = 'current-menu-item';
					$classes[]          = 'current_page_item';
				} elseif ( is_singular( 'lp_course' ) && $course_page === $menu_id ) {
					// Set parent state if this is a product page.
					$classes[] = 'current_page_parent';
				}

				$menu_item->classes = array_unique( $classes );
				$menu_items[ $key ] = $menu_item;
			}
		}

		return $menu_items;
	}

	/**
	 * Set Page viewing to 404
	 *
	 * @return void
	 */
	public static function set_page_404() {
		global $wp_query;
		$wp_query->set_404();
		status_header( 404 );
	}
}

return LP_Page_Controller::instance();
