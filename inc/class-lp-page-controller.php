<?php

/**
 * Class LP_Page_Controller
 */
class LP_Page_Controller {

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
	 * @var null
	 */
	protected static $_instance = null;

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
		// Prevent duplicated actions
		if ( self::$_instance || is_admin() ) {
			return;
		}

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		add_filter( 'template_include', array( $this, 'template_content_item' ) );
		add_filter( 'template_include', array( $this, 'maybe_redirect_quiz' ) );
		add_filter( 'the_post', array( $this, 'setup_data' ) );
		add_filter( 'request', array( $this, 'remove_course_post_format' ), 1 );

		add_shortcode( 'learn_press_archive_course', array( $this, 'archive_content' ) );
	}

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
	}

	public function setup_data( $post ) {
		static $courses = array();
		global $wp, $wp_query, $lp_course, $lp_course_item, $lp_quiz_question;

		if ( LP_COURSE_CPT !== get_post_type( $post->ID ) ) {
			return $post;
		}

		if ( ! empty( $courses[ $post->ID ] ) ) {
			return $post;
		}

		$courses[ $post->ID ] = true;

		$vars = $wp->query_vars;

		if ( empty( $vars['course-item'] ) ) {
			return false;
		}

		if ( ! $wp_query->is_main_query() ) {
			return $post;
		}

		if ( $wp_query->queried_object_id !== $lp_course->get_id() ) {
			return $post;
		}

		try {

			// If item name is set in query vars
			if ( ! is_numeric( $vars['course-item'] ) ) {
				$item_type = $vars['item-type'];
				$post_item = learn_press_get_post_by_name( $vars['course-item'], $item_type );
			} else {
				$post_item = get_post( absint( $vars['course-item'] ) );
				$item_type = $post->post_type;
			}

			// Post item is not exists or get it's item failed.
			if ( ! $post_item || ( $post_item && ( ! $lp_course_item = apply_filters( 'learn-press/single-course-request-item', LP_Course_Item::get_item( $post_item->ID ) ) ) ) ) {

				$this->set_404( true );
				throw new Exception( __( 'You can not view this item or it does not exist!', 'learnpress' ), LP_ACCESS_FORBIDDEN_OR_ITEM_IS_NOT_EXISTS );
			}

			// If current course does not contain the item is viewing
			// then the page should become 404
			if ( ! $lp_course->has_item( $post_item->ID ) ) {
				$this->set_404( true );

				return $post;
			}

			$user_item_id = $lp_course->set_viewing_item( $lp_course_item );

			if ( ! $user_item_id ) {
				return $post;
			}

			// If item viewing is a QUIZ and have a question...
			if ( LP_QUIZ_CPT === $item_type && ! empty( $vars['question'] ) ) {

				if ( $question = learn_press_get_post_by_name( $vars['question'], LP_QUESTION_CPT ) ) {
					$lp_quiz_question = LP_Question::get_question( $question->ID );

					// Update current question for user
					if ( $user_item_id && learn_press_get_user_item_meta( $user_item_id, '_current_question', true ) != $question->ID ) {
						learn_press_update_user_item_meta( $user_item_id, '_current_question', $question->ID );
					}
				} else {
					throw new Exception( __( 'Invalid question!', 'learnpress' ), LP_ACCESS_FORBIDDEN_OR_ITEM_IS_NOT_EXISTS );
					// TODO: Process in case question does not exists.
				}
			}
		}
		catch ( Exception $ex ) {
			learn_press_add_message( $ex->getMessage(), 'error' );
		}

		return $post;
	}

	public function set_404( $is_404 ) {
		global $wp_query;
		$wp_query->is_404 = $this->_is_404 = (bool) $is_404;
	}

	public function is_404() {
		return apply_filters( 'learn-press/query/404', $this->_is_404 );
	}

	public function template_content_item( $template ) {
		global $lp_course, $lp_course_item, $lp_user;

		if ( $this->is_404() ) {
			$template = get_404_template();
		} else {

			if ( $lp_course_item ) {
				if ( ! $lp_user->can_view_item( $lp_course_item->get_id() ) ) {
					if ( $redirect = apply_filters( 'learn-press/access-forbidden-item-redirect', false, $lp_course_item->get_id(), $lp_course->get_id() ) ) {
						wp_redirect( $redirect );
						exit();
					}
				}
				do_action( 'learn-press/parse-course-item', $lp_course_item, $lp_course );
			}

		}

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

		$this->_maybe_redirect_courses_page();
		$this->_maybe_redirect_course_item();

		if ( false !== ( $tmpl = $this->_is_profile() ) ) {
			return $tmpl;
		}

		if ( $this->_is_archive() || learn_press_is_course() ) {
			// If there is no template is valid in theme or plugin
			if ( ! ( $lp_template = $this->_find_template( $template ) ) ) {
				// Get template of wp page.
				$template = get_page_template();
			} else {
				$template = $lp_template;
			}

			if ( $this->_is_single() ) {
				global $post;
				setup_postdata( $post );
				add_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
			} elseif ( $this->_is_archive() ) {
				$this->_load_archive_courses( $template );
			}
		}

		return $template;
	}

	/**
	 * Find template for archive or single course in theme.
	 * If there is no template then load default from plugin.
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	protected function _find_template( $template ) {
		$file           = '';
		$find           = array();
		$theme_template = learn_press_template_path();

		/**
		 * Find template for archive or single course page in theme.
		 */
		if ( $this->_is_archive() ) {
			$file   = 'archive-course.php';
			$find[] = $file;
			$find[] = "{$theme_template}/{$file}";
		} elseif ( learn_press_is_course() ) {
			$file   = 'single-course.php';
			$find[] = $file;
			$find[] = "{$theme_template}/{$file}";
		}

		if ( $file ) {
			$_template = locate_template( array_unique( $find ) );

			if ( ! $_template && ! in_array( $file, array( 'single-course.php', 'archive-course.php' ) ) ) {
				$_template = learn_press_plugin_path( 'templates/' ) . $file;
			}

			$template = $_template;
		}

		return $template;
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

		global $wp_query, $wp_rewrite;
		if ( ( $page_id = learn_press_get_page_id( 'courses' ) ) && ( empty( $wp_query->queried_object_id ) || ! empty( $wp_query->queried_object_id ) && $page_id != $wp_query->queried_object_id ) ) {
			$redirect = trailingslashit( learn_press_get_page_link( 'courses' ) );

			if ( ! empty( $wp_query->query['paged'] ) ) {
				if ( $wp_rewrite->using_permalinks() ) {
					$redirect = $redirect . 'page/' . $wp_query->query['paged'] . '/';
				} else {
					$redirect = add_query_arg( 'paged', $wp_query->query['paged'], $redirect );
				}
			}
			if ( $_GET ) {
				foreach ( $_GET as $k => $v ) {
					$redirect = add_query_arg( $k, urlencode( $v ), $redirect );
				}
			}
			// Prevent loop redirect
			if ( $page_id != get_option( 'page_on_front' ) && ! learn_press_is_current_url( $redirect ) ) {
				wp_redirect( $redirect );
				exit();
			}
		}

		return false;
	}

	/**
	 * @return bool
	 */
	protected function _maybe_redirect_course_item() {
		/**
		 * Check if user is viewing a course's item but they do not have
		 * permission to view it
		 */
		if ( ! learn_press_is_course() ) {
			return false;
		}

		if ( ! empty( LP()->global['course-item'] ) && $course_item = LP()->global['course-item'] ) {
			$user = learn_press_get_current_user();
			if ( ! $user->can_view_item( $course_item->id ) ) {
				wp_redirect( get_the_permalink() );
				exit();
			}
		}

		return false;
	}

	/**
	 * Return template if we are in profile.
	 *
	 * @return bool|string
	 */
	protected function _is_profile() {

		if ( ! learn_press_is_profile() ) {
			return false;
		}

		global $wp;

		$current_user = learn_press_get_current_user();
		$viewing_user = false;

		$profile = learn_press_get_profile();

		// If empty query user consider you are viewing of yours.
		if ( empty( $wp->query_vars['user'] ) ) {
			$viewing_user = $current_user;
		} else {
			if ( $wp_user = get_user_by( 'login', urldecode( $wp->query_vars['user'] ) ) ) {
				$viewing_user = learn_press_get_user( $wp_user->ID );
				if ( $viewing_user->is_guest() ) {
					$viewing_user = false;
				}
			}
		}

		try {
			if ( ! $viewing_user ) {
				throw new Exception( sprintf( __( 'The user %s is not available!', 'learnpress' ), $wp->query_vars['user'] ) );
			}
		}
		catch ( Exception $ex ) {
			if ( $message = $ex->getMessage() ) {
				learn_press_add_message( $message, 'error' );
			} else {
				return get_404_template();
			}

			return false;
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
		define( 'LEARNPRESS_IS_COURSES', learn_press_is_courses() );
		define( 'LEARNPRESS_IS_TAG', learn_press_is_course_tag() );
		define( 'LEARNPRESS_IS_CATEGORY', learn_press_is_course_category() );
		define( 'LEARNPRESS_IS_TAX', learn_press_is_course_tax() );
		define( 'LEARNPRESS_IS_SEARCH', learn_press_is_search() );

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
			if ( ( $course_page_id = learn_press_get_page_id( 'courses' ) ) && ( $course_page_slug = get_post_field( 'post_name', $course_page_id ) ) ) {
				if ( $course_page_slug == 'courses' ) {
					$wp_query->queried_object_id = $course_page_id;
					$this->_queried_object       = $wp_query->queried_object = get_post( $course_page_id );
					add_filter( 'document_title_parts', array( $this, 'page_title' ) );
				}
			}

			$wp_query->posts_per_page = 1;
			$wp_query->nopaging       = true;
			$wp_query->post_count     = 1;

			// If we don't have a post, load an empty one
			if ( ! empty( $this->_queried_object ) ) {
				$wp_query->post = $this->_queried_object;
			} elseif ( empty( $wp_query->post ) ) {
				$wp_query->post = new WP_Post( new stdClass() );
			} elseif ( $wp_query->post->post_type != 'page' ) {
				// Do not show content of post if it is not a page
				$wp_query->post->post_content = '';
			}
			$content = $wp_query->post->post_content;

			if ( ! preg_match( '/\[learn_press_archive_course\s?(.*)\]/', $content ) ) {
				$content = $content . '[learn_press_archive_course]';
			}

			$has_filter = false;
			if ( has_filter( 'the_content', 'wpautop' ) ) {
				$has_filter = true;
				remove_filter( 'the_content', 'wpautop' );
			}

			$content = wpautop( $content );
			$content = do_shortcode( $content );

			if ( $has_filter ) {
				//add_filter( 'the_content', 'wpautop' );
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
				// Fixed issue with Yoast Seo plugin
				$wp_query->is_category = learn_press_is_course_category();
				$wp_query->is_tax      = learn_press_is_course_tax();
				$wp_query->is_single   = false;
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
	 * Display content of single course page.
	 *
	 * @param string $content
	 *
	 * @return string
	 */
	public function single_content( $content ) {
		// Should not effect if current post is not a LP Course
		if ( LP_COURSE_CPT != get_post_type() ) {
			return $content;
		}

		#@NOTE: make sure current page is not lesson or quiz before return cache content of single course page
// 		if ( function_exists( 'learn_press_content_single_course' ) && false !== ( $_content = wp_cache_get( 'course-' . get_the_ID(), 'course-content' ) ) ) {
// 			return $_content;
// 		}

		remove_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
		add_filter( 'the_content', 'wpautop' );
		ob_start();

		if ( function_exists( 'learn_press_content_single_course' ) ) {
			do_action( 'learn-press/content-single' );
		} else {
			/**
			 * Display template of content item if user is viewing course's item.
			 * Otherwise, display template of course.
			 */
			if ( $course_item = LP_Global::course_item() ) {
				learn_press_get_template( 'content-single-item.php' );
			} else {
				learn_press_get_template( 'content-single-course.php' );
			}
		}

		$content = ob_get_clean();
		remove_filter( 'the_content', 'wpautop' );

		add_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );

		wp_cache_set( 'course-' . get_the_ID(), $content, 'course-content' );

		return $content;
	}

	/**
	 * Controls WP displays the courses in a page which setup to display on homepage.
	 *
	 * @param $q WP_Query
	 *
	 * @return WP_Query
	 */
	public function pre_get_posts( $q ) {
		// We only want to affect the main query and not in admin
		if ( ! $q->is_main_query() || is_admin() ) {
			return $q;
		}

		// Handle 404 if user are viewing course item directly.
		// Example: http://example.com/lesson/sample-lesson
		$course_support_items = learn_press_get_course_item_types();

		if ( isset( $q->query_vars['post_type'] ) && in_array( $q->query_vars['post_type'], $course_support_items ) ) {
			learn_press_404_page();
			$q->set( 'post_type', '__unknown' );

			return $q;
		}

		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );

		$this->_queried_object = ! empty( $q->queried_object_id ) ? $q->queried_object : false;

		/**
		 * If current page is used for courses page
		 */
		if ( $q->is_main_query() && $q->is_page() && ( $q->queried_object_id == ( $page_id = learn_press_get_page_id( 'courses' ) ) && $page_id ) ) {
			$q->set( 'post_type', LP_COURSE_CPT );
			$q->set( 'page', '' );
			$q->set( 'pagename', '' );

			$q->is_archive           = true;
			$q->is_post_type_archive = true;
			$q->is_singular          = false;
			$q->is_page              = false;
		}

		if ( $q->is_home() && 'page' == get_option( 'show_on_front' ) && get_option( 'page_on_front' ) == learn_press_get_page_id( 'courses' ) ) {
			$_query = wp_parse_args( $q->query );
			if ( empty( $_query ) || ! array_diff( array_keys( $_query ), array(
					'preview',
					'page',
					'paged',
					'cpage',
					'orderby'
				) )
			) {
				$q->is_page = true;
				$q->is_home = false;
				$q->set( 'page_id', get_option( 'page_on_front' ) );
				$q->set( 'post_type', LP_COURSE_CPT );
			}

		}

		/**
		 * If current page is used for courses page and set as home-page
		 */
		if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $q->get( 'page_id' ) == learn_press_get_page_id( 'courses' ) && learn_press_get_page_id( 'courses' ) ) {

			$q->set( 'post_type', LP_COURSE_CPT );
			$q->set( 'page_id', '' );

			global $wp_post_types;

			$course_page                                = get_post( learn_press_get_page_id( 'courses' ) );
			$this->_queried_object                      = $course_page;
			$wp_post_types[ LP_COURSE_CPT ]->ID         = $course_page->ID;
			$wp_post_types[ LP_COURSE_CPT ]->post_title = $course_page->post_title;
			$wp_post_types[ LP_COURSE_CPT ]->post_name  = $course_page->post_name;
			$wp_post_types[ LP_COURSE_CPT ]->post_type  = $course_page->post_type;
			$wp_post_types[ LP_COURSE_CPT ]->ancestors  = get_ancestors( $course_page->ID, $course_page->post_type );

			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

		}

		// Set custom posts per page
		if ( $this->_is_archive() ) {
			if ( 0 < ( $limit = absint( LP()->settings->get( 'archive_course_limit' ) ) ) ) {
				$q->set( 'posts_per_page', $limit );
			}

			if ( isset( $q->query['page'] ) ) {
				$q->set( 'paged', $q->query['page'] );
			}

		}

		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );

		return $q;
	}

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_Page_Controller::instance();
