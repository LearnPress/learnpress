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

	protected $_filter_content_priority = 10;

	/**
	 * LP_Page_Controller constructor.
	 */
	public function __construct() {
		// Prevent duplicated actions
		if ( self::$_instance ) {
			return;
		}
		add_filter( 'template_include', array( $this, 'template_loader' ) );
		add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 10 );
		add_action( 'learn_press_before_template_part', array( $this, 'before_template_part' ), 10, 4 );
		add_shortcode( 'learn_press_archive_course', array( $this, 'archive_content' ) );
	}

	public function before_template_part( $template_name, $template_path, $located, $args ) {
		if ( $this->has_filter_content && !in_array( $template_name, array( 'content-single-course.php' ) ) ) {
			remove_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
			$this->has_filter_content = false;
			LP_Debug::instance()->add( 'remove filter content' );
		}
	}

	public function template_loader( $template ) {
		global $post;
		$file           = '';
		$find           = array();
		$theme_template = learn_press_template_path();

		global $wp_query;
		$queried_object_id = !empty( $wp_query->queried_object_id ) ? $wp_query->queried_object_id : 0;
		if ( ( $page_id = learn_press_get_page_id( 'taken_course_confirm' ) ) && is_page( $page_id ) && $page_id == $queried_object_id ) {
			if ( !learn_press_user_can_view_order( !empty( $_REQUEST['order_id'] ) ? $_REQUEST['order_id'] : 0 ) ) {
				learn_press_404_page();
			}
			$post->post_content = '[learn_press_confirm_order]';
		} elseif ( ( $page_id = learn_press_get_page_id( 'become_a_teacher' ) ) && is_page( $page_id ) && $page_id == $queried_object_id ) {
			$post->post_content = '[learn_press_become_teacher_form]';
		} else {
			if ( is_post_type_archive( LP_COURSE_CPT ) || ( ( $page_id = learn_press_get_page_id( 'courses' ) ) && is_page( $page_id ) ) || ( is_tax( array( 'course_category', 'course_tag' ) ) ) ) {
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
			if ( !$template && !in_array( $file, array( 'single-course.php', 'archive-course.php' ) ) ) {
				$template = learn_press_plugin_path( 'templates/' ) . $file;
			}
		}
		if ( !$template ) {
			$template = get_page_template();
			if ( learn_press_is_course() ) {
				if ( is_single() ) {
					global $post;
					setup_postdata( $post );
					$post->post_content = $this->single_content( null );
					//add_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
					$this->has_filter_content = true;
				}
			} elseif ( learn_press_is_courses() || learn_press_is_course_tag() || learn_press_is_course_category() || learn_press_is_search() ) {
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

	public function template_loader2( $template ) {
		define( 'LEARNPRESS_IS_COURSES', learn_press_is_courses() );
		define( 'LEARNPRESS_IS_TAG', learn_press_is_course_tag() );
		define( 'LEARNPRESS_IS_CATEGORY', learn_press_is_course_category() );
		define( 'LEARNPRESS_IS_TAX', is_tax( get_object_taxonomies( 'lp_course' ) ) );

		if ( LEARNPRESS_IS_COURSES || LEARNPRESS_IS_TAG || LEARNPRESS_IS_CATEGORY ) {
			global $wp_query, $post;

			LP()->wp_query = clone( $wp_query );
			$template      = get_page_template();

			$wp_query->posts_per_page = 1;
			$wp_query->nopaging       = true;
			$wp_query->post_count     = 1;
			// If we don't have a post, load an empty one
			if ( !empty( $this->queried_object ) ) {
				$wp_query->post = $this->queried_object;
			} elseif ( empty( $wp_query->post ) ) {
				$wp_query->post = new WP_Post( new stdClass() );
			} elseif ( $wp_query->post->post_type != 'page' ) {
				// Do not show content of post if it is not a page
				$wp_query->post->post_content = '';
			}
			$content = $wp_query->post->post_content;

			if ( preg_match( '/\[learn_press_archive_course\s?(.*)\]/', $content ) ) {
				$content = do_shortcode( $content );
			} else {
				$content = $content . $this->archive_content();
			}

			$wp_query->post->ID = 0;

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

			//$GLOBALS['post'] = $this->queried_object;

			remove_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
			remove_filter( 'the_content', 'wpautop' );
		}

		return $template;
	}

	/**
	 * @param $content
	 *
	 * @return string
	 */
	public function single_content( $content ) {
		remove_filter( 'the_content', array( $this, 'single_content' ), $this->_filter_content_priority );
		add_filter( 'the_content', 'wpautop' );
		ob_start();
		learn_press_get_template( 'content-single-course.php' );
		$content = ob_get_clean();

		remove_filter( 'the_content', 'wpautop' );
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
		if ( !$q->is_main_query() || is_admin() ) {
			return $q;
		}
		if ( ( learn_press_is_courses() || learn_press_is_course_category() ) && $limit = absint( LP()->settings->get( 'archive_course_limit' ) ) ) {
			$q->set( 'posts_per_page', $limit );
		}
		$this->queried_object = !empty( $q->queried_object_id ) ? $q->queried_object : false;
		if ( $q->get( 'post_type' ) == 'lp_course' && is_single() ) {
			global $post;
			$course_name = $q->get( 'lp_course' );
			$post        = learn_press_get_post_by_name( $course_name, 'lp_course', true );

			if ( !$post ) {
				learn_press_404_page();
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
							if ( !$question ) {
								learn_press_404_page();
							} elseif ( !$quiz->has_question( $question->ID ) ) {
								learn_press_404_page();
							} else {
								LP()->global['quiz-question'] = $question;
							}
						}
						$item_object = LP_Quiz::get_quiz( $item->ID );
					}
				}
			}

			if ( $item_name && !$item_object ) {
				learn_press_404_page();
			} elseif ( $item_object && !$course->has( 'item', $item_object->id ) ) {
				learn_press_404_page();
			} else {
				LP()->global['course-item'] = $item_object;
			}
		}

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
			if ( empty( $_query ) || !array_diff( array_keys( $_query ), array( 'preview', 'page', 'paged', 'cpage', 'orderby' ) ) ) {
				$q->is_page = true;
				$q->is_home = false;
				$q->set( 'page_id', get_option( 'page_on_front' ) );
				$q->set( 'post_type', 'lp_course' );
			}
		}

		if ( $q->is_page() && 'page' == get_option( 'show_on_front' ) && $q->get( 'page_id' ) == learn_press_get_page_id( 'courses' ) && learn_press_get_page_id( 'courses' ) ) {

			$q->set( 'post_type', 'lp_course' );
			$q->set( 'page_id', '' );
			if ( isset( $q->query['paged'] ) ) {
				$q->set( 'paged', $q->query['paged'] );
			}

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
		return $q;
	}

	public static function instance() {
		if ( !self::$_instance ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

LP_Page_Controller::instance();
