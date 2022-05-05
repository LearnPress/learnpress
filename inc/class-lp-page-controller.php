<?php

/**
 * Class LP_Page_Controller
 */
class LP_Page_Controller {
	protected static $_instance  = null;
	protected $_shortcode_exists = false;
	protected $_shortcode_tag    = '[learn_press_archive_course]';
	protected $_archive_contents = null;

	/**
	 * Store the object has queried by WP.
	 *
	 * @var int
	 */
	protected $_queried_object = 0;

	/**
	 * @var int
	 */
	protected $_filter_content_priority = 10000;

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
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		add_filter( 'template_include', array( $this, 'template_loader' ), 10 );
		// Comment by tungnx
		// add_filter( 'template_include', array( $this, 'template_content_item' ), 20 );
		// add_filter( 'template_include', array( $this, 'maybe_redirect_quiz' ), 30 );
		add_filter( 'template_include', array( $this, 'check_pages' ), 30 );
		add_filter( 'template_include', array( $this, 'auto_shortcode' ), 50 );

		add_filter( 'the_post', array( $this, 'setup_data_for_item_course' ) );
		add_filter( 'request', array( $this, 'remove_course_post_format' ), 1 );

		add_shortcode( 'learn_press_archive_course', array( $this, 'archive_content' ) );
		add_filter( 'pre_get_document_title', array( $this, 'set_title_pages' ), 20, 1 );

		// Yoast seo
		add_filter( 'wpseo_opengraph_desc', array( $this, 'lp_desc_item_yoast_seo' ), 11, 1 );
		add_filter( 'wpseo_metadesc', array( $this, 'lp_desc_item_yoast_seo' ), 11, 1 );

		// edit link item course when form search default wp
		add_filter( 'post_type_link', array( $this, 'post_type_link' ), 10, 2 );
		// add_action( 'the_post', array( $this, 'learn_press_setup_object_data' ) );

		// Set link profile to admin menu
		add_action( 'admin_bar_menu', array( $this, 'learn_press_edit_admin_bar' ) );
	}

	/**
	 * When the_post is called, put course data into a global.
	 *
	 * Todo: after replace code LP::course()
	 *
	 * @param mixed $post
	 *
	 * @return LP_Course
	 */
	/*public function learn_press_setup_object_data( $post ) {
		$object = null;

		if ( is_int( $post ) ) {
			$post = get_post( $post );
		}

		if ( ! $post ) {
			return $object;
		}

		if ( LP_COURSE_CPT === $post->post_type ) {
			if ( isset( $GLOBALS['course'] ) ) {
				unset( $GLOBALS['course'] );
			}

			$object = learn_press_get_course( $post->ID );

			LP()->global['course'] = $GLOBALS['course'] = $GLOBALS['lp_course'] = $object;
		}

		return $object;
	}*/

	/**
	 * Set link item course when form search default wp
	 *
	 * @param string $post_link
	 * @param object $post
	 */
	public function post_type_link( $post_link, $post ) {
		// Set item's course permalink
		$course_item_types = learn_press_get_course_item_types();
		$item_id           = $post->ID;

		if ( in_array( $post->post_type, $course_item_types ) ) {
			$section_id = LP_Section_DB::getInstance()->get_section_id_by_item_id( $item_id );

			if ( ! $section_id ) {
				return $post_link;
			}

			$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $section_id );

			if ( ! $course_id ) {
				return $post_link;
			}

			$course    = learn_press_get_course( $course_id );
			$post_link = $course->get_item_link( $item_id );

		} elseif ( LP_COURSE_CPT === $post->post_type ) {
			// Abort early if the placeholder rewrite tag isn't in the generated URL
			if ( false === strpos( $post_link, '%' ) ) {
				return $post_link;
			}

			// Get the custom taxonomy terms in use by this post
			$terms = get_the_terms( $post->ID, 'course_category' );

			if ( ! empty( $terms ) ) {
				$terms           = _learn_press_usort_terms_by_ID( $terms ); // order by ID
				$category_object = apply_filters(
					'learn_press_course_post_type_link_course_category',
					$terms[0],
					$terms,
					$post
				);
				$category_object = get_term( $category_object, 'course_category' );
				$course_category = $category_object->slug;

				$parent = $category_object->parent;
				if ( $parent ) {
					$ancestors = get_ancestors( $category_object->term_id, 'course_category' );
					foreach ( $ancestors as $ancestor ) {
						$ancestor_object = get_term( $ancestor, 'course_category' );
						$course_category = $ancestor_object->slug . '/' . $course_category;
					}
				}
			} else {
				// If no terms are assigned to this post, use a string instead (can't leave the placeholder there)
				$course_category = _x( 'uncategorized', 'slug', 'learnpress' );
			}

			$find = array(
				'%year%',
				'%monthnum%',
				'%day%',
				'%hour%',
				'%minute%',
				'%second%',
				'%post_id%',
				'%category%',
				'%course_category%',
			);

			$replace = array(
				date_i18n( 'Y', strtotime( $post->post_date ) ),
				date_i18n( 'm', strtotime( $post->post_date ) ),
				date_i18n( 'd', strtotime( $post->post_date ) ),
				date_i18n( 'H', strtotime( $post->post_date ) ),
				date_i18n( 'i', strtotime( $post->post_date ) ),
				date_i18n( 's', strtotime( $post->post_date ) ),
				$post->ID,
				$course_category,
				$course_category,
			);

			$post_link = str_replace( $find, $replace, $post_link );
		}

		return $post_link;
	}

	private function has_block_template( $template_name ) {
		if ( ! $template_name ) {
			return false;
		}

		$has_template      = false;
		$template_name     = str_replace( 'course', LP_COURSE_CPT, $template_name );
		$template_filename = $template_name . '.html';
		// Since Gutenberg 12.1.0, the conventions for block templates directories have changed,
		// we should check both these possible directories for backwards-compatibility.
		$possible_templates_dirs = array( 'templates', 'block-templates' );

		// Combine the possible root directory names with either the template directory
		// or the stylesheet directory for child themes, getting all possible block templates
		// locations combinations.
		$filepath        = DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . $template_filename;
		$legacy_filepath = DIRECTORY_SEPARATOR . 'block-templates' . DIRECTORY_SEPARATOR . $template_filename;
		$possible_paths  = array(
			get_stylesheet_directory() . $filepath,
			get_stylesheet_directory() . $legacy_filepath,
			get_template_directory() . $filepath,
			get_template_directory() . $legacy_filepath,
		);

		// Check the first matching one.
		foreach ( $possible_paths as $path ) {
			if ( is_readable( $path ) ) {
				$has_template = true;
				break;
			}
		}

		/**
		 * Filters the value of the result of the block template check.
		 *
		 * @since x.x.x
		 *
		 * @param boolean $has_template value to be filtered.
		 * @param string $template_name The name of the template.
		 */
		return (bool) apply_filters( 'learnpress_has_block_template', $has_template, $template_name );
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
	 */
	public function set_title_pages( $title = '' ) {
		global $wp_query;
		$flag_title_course = false;

		$course_archive_page_id = LP()->settings()->get( 'courses_page_id', 0 );

		// Set title course archive page
		if ( ! empty( $course_archive_page_id ) && $wp_query->post &&
			 $course_archive_page_id == $wp_query->post->ID ) {
			$title             = get_the_title( $course_archive_page_id );
			$flag_title_course = true;
		} elseif ( learn_press_is_course() ) {
			$item = LP_Global::course_item();
			if ( $item ) {
				$title = apply_filters( 'learn-press/document-course-title-parts', get_the_title() . ' &rarr; ' . $item->get_title(), $item );

				$flag_title_course = true;
			}
		} elseif ( learn_press_is_courses() ) {
			if ( learn_press_is_search() ) {
				$title = __( 'Course Search Results', 'learnpress' );
			} else {
				$title = __( 'Courses', 'learnpress' );
			}

			$flag_title_course = true;
		} elseif ( learn_press_is_profile() ) {
			$profile  = LP_Profile::instance();
			$tab_slug = $profile->get_current_tab();
			$tab      = $profile->get_tab_at( $tab_slug );

			if ( $page_id = learn_press_get_page_id( 'profile' ) ) {
				$page_title = get_the_title( $page_id );
			} else {
				$page_title = '';
			}
			if ( $tab ) {
				$title = join(
					' ',
					apply_filters(
						'learn-press/document-profile-title-parts',
						array(
							$page_title,
							'&rarr;',
							$tab['title'],
						)
					)
				);
			}

			$flag_title_course = true;
		}

		if ( $flag_title_course ) {
			$title .= ' - ' . get_bloginfo( 'name', 'display' );
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
	 */
	public function auto_shortcode( $template ) {
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
	 * @editor tungnx
	 * @modify 4.1.3 - comment - not use
	 */
	/*
	public function maybe_redirect_quiz( $template ) {
		$course   = LP_Global::course();
		$quiz     = LP_Global::course_item_quiz();
		$user     = learn_press_get_current_user();
		$redirect = false;

		if ( learn_press_is_review_questions() ) {
			if ( ! $quiz->get_review_questions() ) {
				$redirect = $course->get_item_link( $quiz->get_id() );
			}
		}

		if ( LP_Global::quiz_question() && ! $user->has_started_quiz( $quiz->get_id(), $course->get_id() ) ) {
			$redirect = $course->get_item_link( $quiz->get_id() );
		}

		if ( $redirect ) {
			wp_redirect( $redirect );
			exit();
		}

		return $template;
	}*/

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

		$lp_course = learn_press_get_course();

		if ( LP_COURSE_CPT !== $post->post_type ) {
			return $post;
		}

		$course = learn_press_get_course( $post->ID );
		if ( ! $course ) {
			return $post;
		}

		/**
		 * @deprecated v4.1.6.1 LP()->global['course'], $GLOBALS['course']
		 */
		LP()->global['course'] = $GLOBALS['course'] = $GLOBALS['lp_course'] = $course;

		if ( wp_verify_nonce( LP_Request::get( 'preview' ), 'preview-' . $post->ID ) ) {
			$GLOBALS['preview_course'] = $post->ID;
		}

		$vars = $wp->query_vars;

		if ( empty( $vars['course-item'] ) ) {
			return $post;
		}

		if ( ! $wp_query->is_main_query() ) {
			return $post;
		}

		try {
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

			/**
			 * Comment for reason some page-builder run wrong
			 *
			 * 1. Anywhere elementor
			 * 2. WPBakery
			 */
			/*$section_id = LP_Section_DB::getInstance()->get_section_id_by_item_id( $post_item->ID );

			if ( ! $section_id ) {
				throw new Exception( __( 'The item is not assigned to any section', 'learnpress' ) );
			}

			$course_id = LP_Section_DB::getInstance()->get_course_id_by_section( $section_id );

			if ( ! $course_id ) {
				throw new Exception( __( 'The item is not assigned to any course', 'learnpress' ) );
			}

			if ( $course_id != $post->ID ) {
				throw new Exception( __( 'The item is not assigned to this course', 'learnpress' ) );
			}*/

			$lp_course_item = apply_filters( 'learn-press/single-course-request-item', LP_Course_Item::get_item( $post_item->ID, $course->get_id() ) );

			if ( ! $lp_course_item ) {
				return $post;
			}

			$lp_course->set_viewing_item( $lp_course_item );
		} catch ( Exception $ex ) {
			learn_press_add_message( $ex->getMessage(), 'error' );
		}

		return $post;
	}

	/**
	 * Set page 404
	 *
	 * @return mixed
	 * @editor tungnx
	 * @reason not use
	 * @deprecated 4.0.0
	 */
	/*
	public function set_404( $is_404 ) {
		global $wp_query;
		$wp_query->is_404 = $this->_is_404 = (bool) $is_404;
	}*/

	public function is_404() {
		return apply_filters( 'learn-press/query/404', $this->_is_404 );
	}

	public function template_content_item( $template ) {

		/**
		 * @var LP_Course $lp_course
		 * @var LP_Course_Item $lp_course_item
		 * @var LP_User $lp_user
		 */
		global $lp_course, $lp_course_item, $lp_user;

		do_action( 'learn-press/parse-course-item', $lp_course_item, $lp_course );

		return $template;
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
			if ( ! self::has_block_template( 'single-course' ) ) {
				$page_template = 'single-course.php';
			}

			if ( $this->_is_single() ) {
				global $post;
				setup_postdata( $post );

				$course_item = LP_Global::course_item();
				if ( $course_item && ! self::has_block_template( 'content-single-course-item' ) ) {
					$page_template = 'content-single-item.php';
				}
			}
		} elseif ( learn_press_is_course_taxonomy() ) {
			$object = get_queried_object();

			if ( is_tax( 'course_category' ) || is_tax( 'course_tag' ) ) {
				$page_template = 'taxonomy-' . $object->taxonomy . '.php';

				if ( self::has_block_template( 'taxonomy-' . $object->taxonomy ) ) {
					$page_template = '';
				} elseif ( ! file_exists( learn_press_locate_template( $page_template ) ) && ! self::has_block_template( 'archive-course' ) ) {
					$page_template = 'archive-course.php';
				}
			} elseif ( ! self::has_block_template( 'archive-course' ) ) {
				$page_template = 'archive-course.php';
			}
		} elseif ( ( is_post_type_archive( LP_COURSE_CPT ) || ( ! empty( learn_press_get_page_id( 'courses' ) ) && is_page( learn_press_get_page_id( 'courses' ) ) ) ) && ! self::has_block_template( 'archive-course' ) ) {
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
				$templates[] = "single-course-{$name_decoded}.php";
			}

			$templates[] = "single-product-{$object->post_name}.php";
		}

		if ( learn_press_is_course_taxonomy() ) {
			$object      = get_queried_object();
			$templates[] = 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = learn_press_template_path( true ) . 'taxonomy-' . $object->taxonomy . '-' . $object->slug . '.php';
			$templates[] = 'taxonomy-' . $object->taxonomy . '.php';
			$templates[] = learn_press_template_path( true ) . 'taxonomy-' . $object->taxonomy . '.php';
		}

		$templates[] = $default_template;
		$templates[] = learn_press_template_path( true ) . $default_template;

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
	 */
	public function page_template_hierarchy( $templates ) {
		$templates = array_merge( $templates, array( 'singular.php' ) );

		return $templates;
	}

	/**
	 * @return bool
	 */
	protected function _maybe_redirect_courses_page() {
		/**
		 * If is archive course page and a static page is used for displaying courses
		 * we need to redirect it to the right page
		 */
		if ( ! is_post_type_archive( LP_COURSE_CPT ) ) {
			return false;
		}

		/**
		 * @var WP_Query $wp_query
		 * @var WP_Rewrite $wp_rewrite
		 */
		global $wp_query, $wp_rewrite;

		$page_id = learn_press_get_page_id( 'courses' );

		if ( $page_id && ( empty( $wp_query->queried_object_id ) || ! empty( $wp_query->queried_object_id ) && $page_id != $wp_query->queried_object_id ) ) {
			$redirect = trailingslashit( learn_press_get_page_link( 'courses' ) );

			if ( ! empty( $wp_query->query['paged'] ) ) {
				if ( $wp_rewrite->using_permalinks() ) {
					$redirect = $redirect . 'page/' . $wp_query->query['paged'] . '/';
				} else {
					$redirect = add_query_arg( 'paged', $wp_query->query['paged'], $redirect );
				}
			}

			if ( isset( $_GET ) ) {
				$_GET = array_map( 'stripslashes_deep', $_GET );
				foreach ( $_GET as $k => $v ) {
					$redirect = add_query_arg( $k, urlencode( $v ), $redirect );
				}
			}

			if ( $page_id != get_option( 'page_on_front' ) && ! learn_press_is_current_url( $redirect ) ) {
				wp_redirect( $redirect );
				exit();
			}
		}

		return false;
	}


	public function archive_content() {
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
	 * Load archive courses content.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public function _load_archive_courses( $template ) {

		if ( ! defined( 'LEARNPRESS_IS_COURSES' ) ) {
			define( 'LEARNPRESS_IS_COURSES', learn_press_is_courses() );
		}

		if ( ! defined( 'LEARNPRESS_IS_TAG' ) ) {
			define( 'LEARNPRESS_IS_TAG', learn_press_is_course_tag() );
		}

		if ( ! defined( 'LEARNPRESS_IS_CATEGORY' ) ) {
			define( 'LEARNPRESS_IS_CATEGORY', learn_press_is_course_category() );
		}

		if ( ! defined( 'LEARNPRESS_IS_TAX' ) ) {
			define( 'LEARNPRESS_IS_TAX', learn_press_is_course_tax() );
		}

		if ( ! defined( 'LEARNPRESS_IS_SEARCH' ) ) {
			define( 'LEARNPRESS_IS_SEARCH', learn_press_is_search() );
		}

		if ( LEARNPRESS_IS_COURSES || LEARNPRESS_IS_TAG || LEARNPRESS_IS_CATEGORY || LEARNPRESS_IS_SEARCH || LEARNPRESS_IS_TAX ) {
			global $wp_query;
			// PHP 7
			LP()->wp_query = clone $wp_query;

			$template = get_page_template();

			/**
			 * Fix in case a static page is used for archive course page and
			 * it's slug is the same with course archive slug (courses).
			 * In this case, WP know it as a course archive page not a
			 * single page.
			 */
			$course_page_id   = learn_press_get_page_id( 'courses' );
			$course_page_slug = get_post_field( 'post_name', $course_page_id );
			if ( ! LEARNPRESS_IS_CATEGORY && $course_page_id && $course_page_slug ) {
				if ( $course_page_slug == 'courses' ) {
					$wp_query->queried_object_id = $course_page_id;
					$this->queried_object        = $wp_query->queried_object = get_post( $course_page_id );
					add_filter( 'document_title_parts', array( $this, 'page_title' ) );
				}
			}

			$wp_query->posts_per_page = 1;
			$wp_query->nopaging       = true;
			$wp_query->post_count     = 1;

			// If we don't have a post, load an empty one
			if ( ! empty( $this->_queried_object ) ) {
				$wp_query->post = $this->_queried_object;
			} elseif ( empty( $wp_query->post ) || learn_press_is_courses() /* -> Fixed: archive course page displays name of first course */ ) {
				$wp_query->post = new WP_Post( new stdClass() );
			} elseif ( $wp_query->post->post_type != 'page' ) {
				// Do not show content of post if it is not a page
				$wp_query->post->post_content = '';
			}
			$content = $wp_query->post->post_content;

			preg_match( '/\[learn_press_archive_course\s?(.*)\]/', $content, $results );
			$this->_shortcode_exists = ! empty( $results );

			if ( empty( $results ) ) {
				$content = wpautop( $content ) . $this->_shortcode_tag;
			} else {
				$this->_shortcode_tag = $results[0];
			}

			$has_filter = false;
			if ( has_filter( 'the_content', 'wpautop' ) ) {
				$has_filter = true;
				remove_filter( 'the_content', 'wpautop' );
			}

			// $content = do_shortcode( $content );

			if ( $has_filter ) {
				// add_filter( 'the_content', 'wpautop' );
			}

			$this->_archive_contents = do_shortcode( $this->_shortcode_tag );
			if ( class_exists( 'SiteOrigin_Panels' ) ) {
				if ( class_exists( 'SiteOrigin_Panels' ) &&
					 has_filter( 'the_content', array( SiteOrigin_Panels::single(), 'generate_post_content' ) )
				) {
					remove_shortcode( 'learn_press_archive_course' );
					add_filter(
						'the_content',
						array(
							$this,
							'the_content_callback',
						),
						$this->_filter_content_priority
					);
				}
			} else {
				$content = do_shortcode( $content );
			}

			if ( empty( $wp_query->post->ID ) || LEARNPRESS_IS_CATEGORY ) {
				$wp_query->post->ID = 0;
			}

			$wp_query->post->filter = 'raw';
			if ( learn_press_is_course_category() ) {
				$wp_query->post->post_title = single_term_title( '', false );
			}

			$wp_query->post->post_content = $content;
			$wp_query->posts              = array( $wp_query->post );

			if ( is_post_type_archive( LP_COURSE_CPT ) || LEARNPRESS_IS_CATEGORY ) {
				$wp_query->is_page    = false;
				$wp_query->is_archive = true;
				$wp_query->is_single  = false;
			} else {
				$wp_query->found_posts          = 1;
				$wp_query->is_single            = true;
				$wp_query->is_preview           = false;
				$wp_query->is_archive           = false;
				$wp_query->is_date              = false;
				$wp_query->is_year              = false;
				$wp_query->is_month             = false;
				$wp_query->is_day               = false;
				$wp_query->is_time              = false;
				$wp_query->is_author            = false;
				$wp_query->is_category          = false;
				$wp_query->is_tag               = false;
				$wp_query->is_tax               = false;
				$wp_query->is_search            = false;
				$wp_query->is_feed              = false;
				$wp_query->is_comment_feed      = false;
				$wp_query->is_trackback         = false;
				$wp_query->is_home              = false;
				$wp_query->is_404               = false;
				$wp_query->is_comments_popup    = false;
				$wp_query->is_paged             = false;
				$wp_query->is_admin             = false;
				$wp_query->is_attachment        = false;
				$wp_query->is_singular          = false;
				$wp_query->is_posts_page        = false;
				$wp_query->is_post_type_archive = false;
			}
		}

		return $template;
	}

	/**
	 * Query courses if page is archive courses
	 *
	 * @param $q WP_Query
	 *
	 * @return WP_Query
	 * @editor tungnx
	 * @modify 4.1.2
	 * @throws Exception
	 */
	public function pre_get_posts( WP_Query $q ): WP_Query {
		// Affect only the main query and not in admin
		if ( ! $q->is_main_query() && ! is_admin() ) {
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
		}

		$is_archive_course = false;

		// Handle 404 if user are viewing course item directly.
		$this->set_link_item_course_default_wp_to_page_404( $q );

		$this->_queried_object = ! empty( $q->queried_object_id ) ? $q->queried_object : false;

		/**
		 * If current page is used for courses page
		 * Set on both: "Homepage" and "Posts page" on Reading Settings
		 */
		$page_courses_id = learn_press_get_page_id( 'courses' );

		if ( $q->is_home() && 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) == $page_courses_id ) {
			$is_archive_course = 1;
			// $q->is_home = false;
			// $q->set( 'page_id', get_option( 'page_on_front' ) );
		}

		/**
		 * If current page is used for courses page and set as "Homepage"
		 */
		if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $page_courses_id && $q->get( 'page_id' ) == $page_courses_id ) {
			$is_archive_course = 1;

			/*
			global $wp_post_types;

			$course_page                                = get_post( $page_courses_id );
			$this->_queried_object                      = $course_page;
			$wp_post_types[ LP_COURSE_CPT ]->ID         = $course_page->ID;
			$wp_post_types[ LP_COURSE_CPT ]->post_title = $course_page->post_title;
			$wp_post_types[ LP_COURSE_CPT ]->post_name  = $course_page->post_name;
			$wp_post_types[ LP_COURSE_CPT ]->post_type  = $course_page->post_type;
			$wp_post_types[ LP_COURSE_CPT ]->ancestors  = get_ancestors( $course_page->ID, $course_page->post_type );*/
		}

		// Set custom posts per page
		if ( $this->_is_archive() ) {
			$is_archive_course = 1;
		}

		$apply_lazy_load_for_theme = apply_filters( 'lp/page/courses/query/lazy_load', false );

		if ( $is_archive_course ) {
			if ( ( lp_is_archive_course_load_via_api() && ! class_exists( 'TP' ) ) || $apply_lazy_load_for_theme ) {
				LP()->template( 'course' )->remove_callback( 'learn-press/after-courses-loop', 'loop/course/pagination.php', 10 );
				/**
				 * If page is archive course - query set posts_per_page = 1
				 * For fastest - because when page loaded - call API to load list courses
				 *
				 * Current, apply only for LP, not apply for theme Thimpress, because theme override
				 */
				$q->set( 'posts_per_page', 1 );
			} else {
				$limit = LP_Settings::get_option( 'archive_course_limit', 6 );
				$q->set( 'posts_per_page', $limit );
			}
		}

		return $q;
	}

	/**
	 * Handle 404 if user are viewing course item directly.
	 * Example: http://example.com/lesson/slug-lesson
	 * Apply for user not admin, instructor, co-instructor
	 *
	 * @param WP_Query $q
	 *
	 * @return mixed
	 * @editor tungnx
	 * @since  3.2.7.5
	 */
	public function set_link_item_course_default_wp_to_page_404( $q ) {
		if ( ! $q->is_main_query() || is_admin() ) {
			return $q;
		}

		$post_type_apply_404 = array( LP_LESSON_CPT, LP_QUIZ_CPT, LP_QUESTION_CPT, 'lp_assignment' );

		if ( isset( $q->query_vars['post_type'] ) && in_array( $q->query_vars['post_type'], $post_type_apply_404 ) ) {
			$flag_load_404 = true;
			$user          = wp_get_current_user();
			$post_author   = 0;

			if ( $user ) {
				$post = null;

				if ( isset( $_GET['preview_id'] ) ) {
					$post_id     = absint( $_GET['preview_id'] );
					$post        = get_post( $post_id );
					$post_author = $post->post_author;
				} elseif ( isset( $_GET['preview'] ) && isset( $_GET['p'] ) ) {
					$post_id     = absint( $_GET['p'] );
					$post        = get_post( $post_id );
					$post_author = $post->post_author;
				} else {
					$post_author = LP_Database::getInstance()->getPostAuthorByTypeAndSlug( $q->query_vars['post_type'], $q->query_vars[ $q->query_vars['post_type'] ] );
				}

				if ( $user->has_cap( 'administrator' ) ) {
					$flag_load_404 = false;
				} elseif ( $user->has_cap( LP_TEACHER_ROLE ) && $post_author == $user->ID ) {
					$flag_load_404 = false;
				}
			}

			$flag_load_404 = apply_filters( 'learnpress/page/set-link-item-course-404', $flag_load_404, $post_author, $user );

			if ( $flag_load_404 ) {
				learn_press_404_page();
				$q->set( 'post_type', '' );
			}
		}

		return $q;
	}

	public function the_content_callback( $content ) {
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
	}

	/**
	 * Check is page Become a teacher
	 *
	 * @return bool|mixed|void
	 * @since 3.2.8
	 * @author tungnx
	 */
	public static function is_page_become_a_teacher() {
		$page_id = learn_press_get_page_id( 'become_a_teacher' );

		if ( $page_id && is_page( $page_id ) ) {
			return true;
		}

		return apply_filters( 'learnpress/is-page/become-a-teacher', false );
	}

	/**
	 * Get page current on frontend
	 *
	 * @return string
	 * @since 3.2.8
	 * @author tungnx
	 */
	public static function page_current(): string {
		if ( learn_press_is_checkout() ) {
			return LP_PAGE_CHECKOUT;
		} elseif ( LP_Global::course_item_quiz() ) {
			return LP_PAGE_QUIZ;
		} elseif ( learn_press_is_course() && is_single() && LP_Global::course_item() ) {
			return LP_PAGE_SINGLE_COURSE_CURRICULUM;
		} elseif ( learn_press_is_courses() ) {
			return LP_PAGE_COURSES;
		} elseif ( learn_press_is_course() ) {
			return LP_PAGE_SINGLE_COURSE;
		} elseif ( self::is_page_become_a_teacher() ) {
			return LP_PAGE_BECOME_A_TEACHER;
		} elseif ( learn_press_is_profile() ) {
			return LP_PAGE_PROFILE;
		} else {
			return apply_filters( 'learnpress/page/current', '' );
		}
	}

	public static function instance() {
		if ( is_admin() ) {
			return null;
		}

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
}

if ( ! function_exists( 'lp_page_controller' ) ) {
	function lp_page_controller() {
		return LP_Page_Controller::instance();
	}

	lp_page_controller();
}
