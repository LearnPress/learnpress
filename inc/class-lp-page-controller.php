<?php

/**
 * Class LP_Page_Controller
 */
class LP_Page_Controller {

	/**
	 * @var null
	 */
	protected $queried_object = null;

	/**
	 * @var null
	 */
	protected static $_instance = null;

	/**
	 * @var bool
	 */
	protected $has_filter_content = false;

	protected $_filter_content_priority = 10000;

	protected $_origin_post = null;

	/**
	 * LP_Page_Controller constructor.
	 */
	public function __construct() {
		// Prevent duplicated actions
		if ( self::$_instance || is_admin() ) {
			return;
		}
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		//add_action( 'learn_press_before_template_part', array( $this, 'before_template_part' ), 10, 4 );
		add_shortcode( 'learn_press_archive_course', array( $this, 'archive_content' ) );
		add_filter( 'request', array( $this, 'remove_course_post_format' ), 1 );
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

	public function fix_global_post( $post, $query ) {
		global $wp_query;

		//$post = $this->_origin_post;


	}

	/*public function before_template_part( $template_name, $template_path, $located, $args ) {
		if ( $this->has_filter_content && !in_array( $template_name, array( 'content-single-course.php' ) ) ) {
			remove_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
			$this->has_filter_content = false;
			LP_Debug::instance()->add( 'remove filter content' );
		}
	}*/

	public function template_loader( $template ) {
		global $wp_query, $post, $wp_rewrite;
		$file           = '';
		$find           = array();
		$theme_template = learn_press_template_path();

		/**
		 * If is archive course page and a static page is used for displaying courses
		 * we need to redirect it to the right page
		 */
		if ( is_post_type_archive( 'lp_course' ) ) {
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
		}

		/**
		 * Check if user is viewing a course's item but they do not have
		 * permission to view it
		 */
		if ( learn_press_is_course() ) {
			if ( ! empty( LP()->global['course-item'] ) && $course_item = LP()->global['course-item'] ) {
				$user = learn_press_get_current_user();
				if ( ! $user->can_view_item( $course_item->id ) ) {
					wp_redirect( get_the_permalink() );
					exit();
				}
			}
		}

		if ( learn_press_is_profile() ) {
			$current_tab = learn_press_get_current_profile_tab( false );
			if ( $current_tab && ! learn_press_profile_tab_exists( $current_tab ) ) {
				global $wp;
				if ( empty( $wp->query_vars['view'] ) ) {
					wp_redirect( learn_press_get_page_link( 'profile' ) );
					exit();
				}
			}
		}
		$queried_object_id = ! empty( $wp_query->queried_object_id ) ? $wp_query->queried_object_id : 0;
		if ( ( $page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && is_page( $page_id ) && $page_id == $queried_object_id ) {
			if ( ! learn_press_user_can_view_order( ! empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0 ) ) {
				learn_press_is_404();
			}
			$post->post_content = '[learn_press_confirm_order]';
		} elseif ( ( $page_id = learn_press_get_page_id( 'become_a_teacher' ) ) && is_page( $page_id ) && $page_id == $queried_object_id ) {
			$post->post_content = '[learn_press_become_teacher_form]';
		} else {
			if ( learn_press_is_courses() || learn_press_is_course_tag() || learn_press_is_course_category() || learn_press_is_search() ) {
				$file   = 'archive-course.php';
				$find[] = $file;
				$find[] = "{$theme_template}/{$file}";

			} else {
				if ( learn_press_is_course() ) {
					$file   = 'single-course.php';
					$find[] = $file;
					$find[] = "{$theme_template}/{$file}";
				}
			}
		}
		if ( $file ) {
			$template = locate_template( array_unique( $find ) );
			if ( ! $template && ! in_array( $file, array( 'single-course.php', 'archive-course.php' ) ) ) {
				$template = learn_press_plugin_path( 'templates/' ) . $file;
			}
		}
		if ( ! $template ) {
			$template = get_page_template();
			if ( learn_press_is_course() ) {
				if ( is_single() ) {
					global $post;
					setup_postdata( $post );
					$this->_origin_post = $post;
					add_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
					$this->has_filter_content = true;
				}
			} elseif ( learn_press_is_courses() || learn_press_is_course_tag() || learn_press_is_course_category() || learn_press_is_search() ) {
				add_action( 'the_post', array( $this, 'fix_global_post' ), 99, 2 );
				$this->template_loader2( $template );
			}
		}

		return $template;
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

	public function template_loader2( $template ) {
		define( 'LEARNPRESS_IS_COURSES', learn_press_is_courses() );
		define( 'LEARNPRESS_IS_TAG', learn_press_is_course_tag() );
		define( 'LEARNPRESS_IS_CATEGORY', learn_press_is_course_category() );
		define( 'LEARNPRESS_IS_TAX', is_tax( get_object_taxonomies( 'lp_course' ) ) );
		define( 'LEARNPRESS_IS_SEARCH', learn_press_is_search() );
		if ( LEARNPRESS_IS_COURSES || LEARNPRESS_IS_TAG || LEARNPRESS_IS_CATEGORY || LEARNPRESS_IS_SEARCH || LEARNPRESS_IS_TAX ) {

			global $wp_query, $post, $wp;
			if ( is_callable( 'clone' ) ) {
				LP()->wp_query = clone( $wp_query );
			} else {
				// PHP 7
				LP()->wp_query = clone $wp_query;
			}

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
					$this->queried_object        = $wp_query->queried_object = get_post( $course_page_id );
					add_filter( 'document_title_parts', array( $this, 'page_title' ) );
				}
			}

			$wp_query->posts_per_page = 1;
			$wp_query->nopaging       = true;
			$wp_query->post_count     = 1;
			// If we don't have a post, load an empty one
			if ( ! empty( $this->queried_object ) ) {
				$wp_query->post = $this->queried_object;
			} elseif ( empty( $wp_query->post ) ) {
				$wp_query->post = new WP_Post( new stdClass() );
			} elseif ( $wp_query->post->post_type != 'page' ) {
				// Do not show content of post if it is not a page
				$wp_query->post->post_content = '';
			}
			$content = $wp_query->post->post_content;

			if ( ! preg_match( '/\[learn_press_archive_course\s?(.*)\]/', $content ) ) {
				$content = $content . '[learn_press_archive_course]';//$this->archive_content();
			}

			$has_filter = false;
			if ( has_filter( 'the_content', 'wpautop' ) ) {
				$has_filter = true;
				remove_filter( 'the_content', 'wpautop' );
			}
			$content = do_shortcode( $content );
			if ( $has_filter ) {
				has_filter( 'the_content', 'wpautop' );
			}
			//if ( empty( $wp_query->post->ID ) ) {
			$wp_query->post->ID = 0;
			//}
			$wp_query->post->filter = 'raw';
			if ( learn_press_is_course_category() ) {
				$wp_query->post->post_title = single_term_title( '', false );//__( 'Course Category', 'learnpress' );
			}

			$wp_query->post->post_content   = $content;
			$wp_query->posts                = array( $wp_query->post );
			$wp_query->found_posts          = 1;
			$wp_query->is_single            = false;
			$wp_query->is_preview           = false;
			$wp_query->is_page              = false;
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

		return $template;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function single_content( $content ) {
		// Should not effect if current post is not a LP Course
		if ( LP_COURSE_CPT != get_post_type() ) {
			return $content;
		}

		remove_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
		add_filter( 'the_content', 'wpautop' );
		ob_start();
		learn_press_get_template( 'content-single-course.php' );
		$content = ob_get_clean();
		remove_filter( 'the_content', 'wpautop' );
		add_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );

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

		global $course;


		// We only want to affect the main query and not in admin
		if ( ! $q->is_main_query() || is_admin() ) {
			return $q;
		}
		remove_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );

		$this->queried_object = ! empty( $q->queried_object_id ) ? $q->queried_object : false;

		global $wp, $wp_rewrite;
		/**
		 * If is single course content
		 */
		if ( $q->get( 'post_type' ) == 'lp_course' && is_single() ) {
			global $post;
			/**
			 * Added in LP 2.0.5 to fix issue in some cases course become 404
			 * including case course link is valid but it also get 404 if
			 * plugin WPML is installed
			 */
			if ( ! empty( $q->query_vars['p'] ) && LP_COURSE_CPT == get_post_type( $q->query_vars['p'] ) ) {
				$post = get_post( $q->query_vars['p'] );
			} else {
				$course_name = $q->get( 'lp_course' );
				$post        = learn_press_get_post_by_name( $course_name, 'lp_course', true );
			}

			if ( ! $post ) {
				LP_Debug::instance()->add( sprintf( '%s: File %s, line #%d', '404', __FILE__, __LINE__ ) );
				learn_press_is_404();

				return $q;
			}
			$course      = learn_press_get_course( $post->ID );
			$item        = null;
			$item_name   = null;
			$item_object = null;

			if ( $course ) {
				LP()->global['course'] = $course;
				if ( $item_name = $q->get( 'lesson' ) ) {
					$item = learn_press_get_post_by_name( $item_name, 'lp_lesson', true );
					if ( $item ) {
						$item_object = LP_Lesson::get_lesson( $item->ID );
					}
				} elseif ( $item_name = $q->get( 'quiz' ) ) {
					$item = learn_press_get_post_by_name( $item_name, 'lp_quiz', true );
					if ( $item ) {
						$quiz = LP_Quiz::get_quiz( $item->ID );
						if ( $question_name = $q->get( 'question' ) ) {
							$question = learn_press_get_post_by_name( $question_name, 'lp_question', true );
							if ( ! $question ) {
								LP_Debug::instance()->add( sprintf( '%s: File %s, line #%d', '404', __FILE__, __LINE__ ) );
								learn_press_is_404();
							} elseif ( ! $quiz->has_question( $question->ID ) ) {
								LP_Debug::instance()->add( sprintf( '%s: File %s, line #%d', '404', __FILE__, __LINE__ ) );
								learn_press_is_404();
							} else {
								LP()->global['quiz-question'] = $question;
							}
						}
						$item_object = LP_Quiz::get_quiz( $item->ID );
					}
				}
			}

			if ( $item_name && ! $item_object ) {
				LP_Debug::instance()->add( sprintf( '%s: File %s, line #%d', '404', __FILE__, __LINE__ ) );
				learn_press_is_404();
			} elseif ( $item_object && ! $course->has( 'item', $item_object->id ) ) {
				LP_Debug::instance()->add( sprintf( '%s: File %s, line #%d', '404', __FILE__, __LINE__ ) );
				learn_press_is_404();
			} else {
				LP()->global['course-item'] = $item_object;
			}

			return $q;
		}


		/**
		 * If current page is used for courses page
		 */
		if ( $q->is_main_query() && $q->is_page() && ( $q->queried_object_id == ( $page_id = learn_press_get_page_id( 'courses' ) ) && $page_id ) ) {
			$q->set( 'post_type', 'lp_course' );
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
				$q->set( 'post_type', 'lp_course' );
			}

		}

		/**
		 * If current page is used for courses page and set as home-page
		 */
		if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $q->get( 'page_id' ) == learn_press_get_page_id( 'courses' ) && learn_press_get_page_id( 'courses' ) ) {

			$q->set( 'post_type', 'lp_course' );
			$q->set( 'page_id', '' );

			global $wp_post_types;

			$course_page                            = get_post( learn_press_get_page_id( 'courses' ) );
			$this->queried_object                   = $course_page;
			$wp_post_types['lp_course']->ID         = $course_page->ID;
			$wp_post_types['lp_course']->post_title = $course_page->post_title;
			$wp_post_types['lp_course']->post_name  = $course_page->post_name;
			$wp_post_types['lp_course']->post_type  = $course_page->post_type;
			$wp_post_types['lp_course']->ancestors  = get_ancestors( $course_page->ID, $course_page->post_type );

			$q->is_singular          = false;
			$q->is_post_type_archive = true;
			$q->is_archive           = true;
			$q->is_page              = true;

		}

		if ( ( learn_press_is_courses() || learn_press_is_course_category() ) ) {
			if ( $limit = absint( LP()->settings->get( 'archive_course_limit' ) ) ) {
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
