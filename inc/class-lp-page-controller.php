<?php

/**
 * Class LP_Page_Controller
 */
class LP_Page_Controller {

	protected $_shortcode_exists = false;
	protected $_shortcode_tag = '[learn_press_archive_course]';
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
		add_filter( 'the_post', array( $this, 'setup_data_items_course' ) );
		add_filter( 'request', array( $this, 'remove_course_post_format' ), 1 );

		add_shortcode( 'learn_press_archive_course', array( $this, 'archive_content' ) );
		add_filter( 'pre_get_document_title', array( $this, 'set_title_pages' ), 11, 1 );
	}

	/**
	 * Set title of pages
	 *
	 * 1. Title course archive page
	 *
	 * @param string $title
	 *
	 * @return string
	 * @author tungnx
	 * @since  3.2.7.7
	 */
	public function set_title_pages( $title = '' ) {
		global $wp_query;

		$course_archive_page_id = LP()->settings()->get( 'courses_page_id', 0 );

		// Set title course archive page
		if ( ! empty( $course_archive_page_id ) && $wp_query->post &&
			$course_archive_page_id == $wp_query->post->ID ) {
			$title = get_the_title( $course_archive_page_id ) . ' - ' . get_bloginfo();
		}

		return $title;
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

	public function setup_data_items_course( $post ) {
		static $courses = array();

		/**
		 * @var WP                               $wp
		 * @var WP_Query                         $wp_query
		 * @var LP_Course                        $lp_course
		 * @var LP_Course_Item|LP_Quiz|LP_Lesson $lp_course_item
		 * @var LP_Question                      $lp_quiz_question
		 */
		global $wp, $wp_query, $lp_course, $lp_course_item, $lp_quiz_question;

		if ( LP_COURSE_CPT !== learn_press_get_post_type( $post->ID ) ) {
			return $post;
		}

		if ( ! empty( $courses[ $post->ID ] ) ) {
			return $post;
		}

		$courses[ $post->ID ] = true;
		$vars                 = $wp->query_vars;

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

			if ( ! $post_item ) {
				return $post;
			}

			$lp_course_item = apply_filters( 'learn-press/single-course-request-item', LP_Course_Item::get_item( $post_item->ID ) );

			if ( ! $lp_course_item ) {
				return $post;
			}

			$user = learn_press_get_current_user();

			/**
			 * @editor       tungnx
			 * @reason       not use
			 * @deprecated   3.2.7.5
			 */
			/*if ( false === $user->can_view_item( $lp_course_item->get_id() ) && ! $user->get_item_url( $lp_course_item->get_id() ) ) {
				if ( false !== ( $redirect = apply_filters( 'learn-press/redirect-forbidden-access-item-url', $lp_course->get_permalink() ) ) ) {
					wp_redirect( $redirect );
					exit();
				}
			}*/

			$lp_course->set_viewing_item( $lp_course_item );

			// If item viewing is a QUIZ and have a question...
			if ( LP_QUIZ_CPT === $item_type ) {
				$question = false;

				// If has question in request but it seems the question does not exists
				if ( ! empty( $vars['question'] ) && ! $question = learn_press_get_post_by_name( $vars['question'], LP_QUESTION_CPT ) ) {
					$this->set_404( true );
					throw new Exception( '404' );
				}

				// If we are requesting to a question but current quiz does not contain it
				if ( $question && ! $lp_course_item->has_question( $question->ID ) ) {
					$this->set_404( true );
					throw new Exception( '404' );
				}

				$quiz_data   = $user->get_quiz_data( $post_item->ID, $lp_course->get_id() );
				$redirect    = false;
				$quiz_status = $quiz_data ? $quiz_data->get_status() : false;

				if ( $quiz_status == 'started' ) {
					$current_question = 0;
					if ( empty( $vars['question'] ) ) {
						$current_question = learn_press_get_user_item_meta( $quiz_data->get_user_item_id(), '_current_question', true );
					} elseif ( $question ) {
						$current_question = $question->ID;
					}

					if ( $current_question && ! $lp_course_item->has_question( $current_question ) ) {
						$this->set_404( true );
						throw new Exception( '404' );
					}

					if ( ! $current_question ) {
						$current_question = $lp_course_item->get_question_at( 0 );
						learn_press_update_user_item_meta( $quiz_data->get_user_item_id(), '_current_question', $current_question );
					}

					if ( ! $question ) {
						$redirect = $lp_course_item->get_question_link( $current_question );
					}
				} elseif ( $quiz_status === 'completed' ) {
					$current_question = $question ? $question->ID : null;
				} elseif ( $quiz_status !== 'completed' ) {
					if ( $question ) {
						$this->set_404( true );
						throw new Exception( '404' );
					}
				}

				if ( isset( $current_question ) && $current_question ) {
					$lp_quiz_question = learn_press_get_question( $current_question );
				}

				if ( $redirect ) {
					//var_dump($redirect);
					wp_redirect( $redirect );
					exit();
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
		/**
		 * @var LP_Course      $lp_course
		 * @var LP_Course_Item $lp_course_item
		 * @var LP_User        $lp_user
		 */
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
				if ( get_option( 'template' ) == 'twentytwenty' ) {
					$template = get_singular_template();
				}

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

		/**
		 * @var WP_Query   $wp_query
		 * @var WP_Rewrite $wp_rewrite
		 */
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
				$_GET = array_map( 'stripslashes_deep', $_GET );
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
				throw new Exception( sprintf( '%s %s %s', __( 'The user', 'learnpress' ), $wp->query_vars['user'], __( 'is not available!', 'learnpress' ) ) );
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
			if ( get_option( 'template' ) == 'twentytwenty' ) {
				$template = get_singular_template();
			}
			/**
			 * Fix in case a static page is used for archive course page and
			 * it's slug is the same with course archive slug (courses).
			 * In this case, WP know it as a course archive page not a
			 * single page.
			 */
			if ( ! LEARNPRESS_IS_CATEGORY && ( $course_page_id = learn_press_get_page_id( 'courses' ) ) && ( $course_page_slug = get_post_field( 'post_name', $course_page_id ) ) ) {
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

			// 			$content = do_shortcode( $content );

			if ( $has_filter ) {
				//add_filter( 'the_content', 'wpautop' );
			}

			$this->_archive_contents = do_shortcode( $this->_shortcode_tag );
			if ( class_exists( 'SiteOrigin_Panels' ) ) {
				if ( class_exists( 'SiteOrigin_Panels' ) && has_filter( 'the_content', array(
						SiteOrigin_Panels::single(),
						'generate_post_content'
					) )
				) {
					remove_shortcode( 'learn_press_archive_course' );
					add_filter( 'the_content', array(
						$this,
						'the_content_callback'
					), $this->_filter_content_priority );
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
		// 		if ( function_exists( 'learn_press_content_single_course' ) && false !== ( $_content = LP_Object_Cache::get( 'course-' . get_the_ID(), 'course-content' ) ) ) {
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

		LP_Object_Cache::set( 'course-' . get_the_ID(), $content, 'learn-press/course-content' );

		return $content;
	}

	/**
	 * Controls WP displays the courses in a page which setup to display on homepage
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
		$this->set_link_item_course_default_wp_to_page_404( $q );

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
			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
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
			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}
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

		return $q;
	}

	/**
	 * Handle 404 if user are viewing course item directly.
	 * Example: http://example.com/lesson/sample-lesson
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

		// Remove param post_format and redirect
		if ( isset( $q->query_vars['post_format'] ) ) {
			$link_redirect = remove_query_arg( 'post_format', LP_Helper::getUrlCurrent() );

			wp_redirect( $link_redirect );
			die;
		}

		if ( isset( $q->query_vars['post_type'] ) && in_array( $q->query_vars['post_type'], $post_type_apply_404 ) ) {
			$flag_load_404 = true;
			$user          = wp_get_current_user();

			if ( $user ) {
				$post        = null;
				$post_author = 0;

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

	public static function instance() {
		if ( ! self::$_instance ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}
}

LP_Page_Controller::instance();
