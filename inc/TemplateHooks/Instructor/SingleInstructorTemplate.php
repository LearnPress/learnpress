<?php
/**
 * Template hooks Single Instructor.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Instructor;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LP_Course;
use LP_Course_Filter;
use LP_User;
use Throwable;
use WP_Query;

class SingleInstructorTemplate {
	public static function instance() {
		static $instance = null;

		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	protected function __construct() {
		add_action( 'learn-press/single-instructor/layout', [ $this, 'sections' ] );
		//add_action( 'wp_head', [ $this, 'add_internal_style_to_head' ] );
	}

	/*public function add_internal_style_to_head() {
		echo '<style id="123123" type="text/css">body{background: red !important;}</style>';
	}*/

	/**
	 * Get display name html of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_display_name( $instructor ): string {
		$html_wrapper = [
			'<span class="instructor-display-name">' => '</span>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $instructor->get_display_name() );
	}

	/**
	 * Get html social of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_social( $instructor ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<div class="instructor-social">' => '</div>',
			];
			$socials      = $instructor->get_profile_social( $instructor->get_id() );
			ob_start();
			foreach ( $socials as $k => $social ) {
				echo $social;
			}
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html description of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_description( $instructor ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<div class="instructor-description">' => '</div>',
			];

			$content = Template::instance()->nest_elements( $html_wrapper, $instructor->get_description() );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html avatar of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_avatar( $instructor ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<div class="instructor-avatar">' => '</div>',
			];

			$content = Template::instance()->nest_elements( $html_wrapper, $instructor->get_profile_picture() );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html total courses of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function html_count_courses( $instructor ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="instructor-total-courses">' => '</span>',
			];

			$instructor_statistic = $instructor->get_instructor_statistic();

			$content = Template::instance()->nest_elements(
				$html_wrapper,
				sprintf(
					'%d %s',
					$instructor_statistic['published_course'],
					_n( 'Course', 'Courses', $instructor_statistic['published_course'], 'learnpress' )
				)
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html total students learn instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function html_count_students( $instructor ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="instructor-total-students">' => '</span>',
			];

			$instructor_statistic = $instructor->get_instructor_statistic();

			$content = Template::instance()->nest_elements( $html_wrapper, $instructor_statistic['total_student'] );
			$content = Template::instance()->nest_elements(
				$html_wrapper,
				sprintf(
					'%d %s',
					$instructor_statistic['total_student'],
					_n( 'Student', 'Students', $instructor_statistic['total_student'], 'learnpress' )
				)
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get button view instructor.
	 *
	 * @param LP_User $instructor
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function html_button_view( LP_User $instructor ): string {
		$btn_view = '';

		try {
			$btn_view = sprintf(
				'<a href="%s" class="instructor-btn-view">%s</a>',
				$instructor->get_url_instructor(),
				__( 'View Profile', 'learnpress' )
			);
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $btn_view;
	}

	/**
	 * Render string to data content
	 *
	 * @param LP_User $instructor
	 * @param string $data_content
	 *
	 * @return string
	 */
	public function render_data( LP_User $instructor, string $data_content = '' ): string {
		return str_replace(
			[
				'{{instructor_id}}',
				'{{instructor_avatar}}',
				'{{instructor_display_name}}',
				'{{instructor_description}}',
				'{{instructor_total_courses}}',
				'{{instructor_total_students}}',
				'{{instructor_social}}',
				'{{instructor_url}}',
			],
			[
				$instructor->get_id(),
				$this->html_avatar( $instructor ),
				$this->html_display_name( $instructor ),
				$this->html_description( $instructor ),
				$this->html_count_courses( $instructor ),
				$this->html_count_students( $instructor ),
				$this->html_social( $instructor ),
				$instructor->get_url_instructor(),
			],
			$data_content
		);
	}

	/**
	 * List section of layout.
	 *
	 * @param array $data
	 *
	 * @return void
	 */
	public function sections( array $data = [] ) {
		wp_enqueue_style( 'lp-instructor' );
		/**
		 * @var WP_Query $wp_query
		 */
		global $wp_query;
		$instructor = false;

		try {
			if ( isset( $data['instructor_id'] ) ) {
				$instructor_id = $data['instructor_id'];
				$instructor    = learn_press_get_user( $instructor_id );
			} else {
				$instructor = $this->detect_instructor_by_page();
			}

			if ( ! $instructor || ! $instructor->can_create_course() ) {
				return;
			}

			$html_wrapper = apply_filters(
				'learn-press/single-instructor/sections/wrapper',
				[
					'<article class="lp-content-area">'  => '</article>',
					'<div class="lp-single-instructor">' => '</div>',
				],
				$instructor
			);
			$sections     = apply_filters(
				'learn-press/single-instructor/sections/wrapper',
				[
					'info'    => [ 'text_html' => $this->info( $instructor ) ],
					'courses' => [ 'text_html' => $this->section_list_courses( $instructor ) ],
				],
				$instructor
			);

			ob_start();
			Template::instance()->print_sections( $sections, compact( 'instructor' ) );
			$content = ob_get_clean();
			echo Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Detected single instructor Page.
	 *
	 * @return false|LP_User
	 */
	public function detect_instructor_by_page() {
		$instructor = false;

		try {
			if ( get_query_var( 'is_single_instructor' ) ) {
				$instructor_name = get_query_var( 'instructor_name' );
				if ( $instructor_name && 'page' !== $instructor_name ) {
					$user = get_user_by( 'slug', $instructor_name );
					if ( $user ) {
						$instructor = learn_press_get_user( $user->ID );
					}
				} else {
					$instructor = learn_press_get_user( get_current_user_id() );
				}
			}
		} catch ( Throwable $e ) {

		}

		return $instructor;
	}

	/**
	 * @param LP_User $instructor
	 *
	 * @return false|string
	 */
	public function info( LP_User $instructor ): string {
		$content = '';

		try {
			$html_wrapper = apply_filters(
				'learn-press/single-instructor/info/wrapper',
				[
					'<div class="lp-single-instructor__info">' => '</div>',
				],
				$instructor
			);

			$sections = apply_filters(
				'learn-press/single-instructor/info/sections',
				[
					'image'      => [ 'text_html' => $this->html_avatar( $instructor ) ],
					'info_right' => [ 'text_html' => $this->info_right( $instructor ) ],
				],
				$instructor
			);

			ob_start();
			Template::instance()->print_sections( $sections, compact( 'instructor' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	public function info_right( LP_User $instructor ): string {
		$content = '';

		try {
			$html_wrapper = apply_filters(
				'learn-press/single-instructor/info-right/wrapper',
				[
					'<div class="lp-single-instructor__info__right">' => '</div>',
				],
				$instructor
			);

			$count_course = sprintf(
				'<div class="wrapper-instructor-total-courses">%s%s</div>',
				'<span class="lp-ico lp-icon-courses"></span> ',
				$this->html_count_courses( $instructor )
			);

			$count_student = sprintf(
				'<div class="wrapper-instructor-total-students">%s%s</div>',
				'<span class="lp-ico lp-icon-students"></span> ',
				$this->html_count_students( $instructor )
			);

			$sections = apply_filters(
				'learn-press/single-instructor/info-right/sections',
				[
					'title'         => [ 'text_html' => "<h2>{$this->html_display_name( $instructor )}</h2>" ],
					'social'        => [ 'text_html' => $this->html_social( $instructor ) ],
					'description'   => [ 'text_html' => $this->html_description( $instructor ) ],
					'count_course'  => [ 'text_html' => $count_course ],
					'count_student' => [ 'text_html' => $count_student ],
				],
				$instructor
			);

			ob_start();
			Template::instance()->print_sections( $sections, compact( 'instructor' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	public function section_list_courses( LP_User $instructor ): string {
		$content      = '';
		$html_wrapper = [
			'<div class="instructor-courses">' => '</div>',
		];

		try {
			// Get option load courses of Instructor via ajax
			$load_ajax = false;

			// Query courses of instructor
			if ( ! $load_ajax ) {
				$filter = new LP_Course_Filter();
				Courses::handle_params_for_query_courses( $filter, [] );
				$filter->post_author = $instructor->get_id();
				$filter->limit       = \LP_Settings::get_option( 'archive_course_limit', 20 );
				$filter->page        = $GLOBALS['wp_query']->get( 'paged', 1 ) ? $GLOBALS['wp_query']->get( 'paged', 1 ) : 1;
				// $filter              = apply_filters( 'lp/single-instructor/courses/query/filter', $filter, [] );

				$total_courses = 0;
				$courses       = Courses::get_courses( $filter, $total_courses );

				$sections = apply_filters(
					'learn-press/single-instructor/courses/sections',
					[
						'courses'    => [ 'text_html' => $this->list_courses( $instructor, $courses ) ],
						'pagination' => [ 'text_html' => $this->courses_pagination( $filter->page, $filter->limit, $total_courses ) ],
					],
					$courses,
					$instructor
				);

				ob_start();
				Template::instance()->print_sections( $sections, compact( 'instructor', 'courses' ) );
				$content = ob_get_clean();
			} else {
				ob_end_clean();
				$html_wrapper['<ul class="ul-instructor-courses">'] = '</ul>';
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		$html_wrapper = apply_filters(
			'learn-press/single-instructor/courses/wrapper',
			$html_wrapper,
			$instructor
		);

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Display list courses.
	 *
	 * @param $instructor
	 * @param $courses
	 *
	 * @return string
	 */
	public function list_courses( $instructor, $courses ): string {
		$content = '';

		try {
			$html_ul_wrapper = apply_filters(
				'learn-press/single-instructor/ul-courses/wrapper',
				[
					'<ul class="ul-instructor-courses">' => '</ul>',
				],
				$courses,
				$instructor
			);

			// List courses
			$ul_courses = '';
			foreach ( $courses as $course_obj ) {
				$course     = LP_Course::get_course( $course_obj->ID );
				$ul_courses .= $this->course_item( $course );
			}
			$content = Template::instance()->nest_elements( $html_ul_wrapper, $ul_courses );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Display item course.
	 *
	 * @param LP_Course $course
	 *
	 * @return void
	 */
	public function course_item( LP_Course $course ): string {
		$content      = '';
		$html_wrapper = apply_filters(
			'learn-press/single-instructor/course_items/wrapper',
			[
				'<li class="item-course">' => '</li>',
			],
			$course
		);

		try {
			$singleCourseTemplate = SingleCourseTemplate::instance();
			ob_start();
			$html_img              = sprintf(
				'<a href="%s">%s</a>',
				$course->get_permalink(),
				$singleCourseTemplate->html_image( $course )
			);
			$html_title            = sprintf(
				'<h2><a href="%s">%s</a></h2>',
				$course->get_permalink(),
				$singleCourseTemplate->html_title( $course )
			);
			$html_price_categories = sprintf(
				'<div class="price-categories">%s %s</div>',
				$course->get_course_price_html(),
				$singleCourseTemplate->html_categories( $course )
			);

			$count_lesson  = $course->count_items( LP_LESSON_CPT );
			$count_student = $course->get_total_user_enrolled_or_purchased();
			$ico_lesson    = '<span class="course-ico lp-icon-file"></span>';
			$ico_student   = '<span class="course-ico lp-icon-students"></span>';
			$html_count    = sprintf(
				'<div class="course-count">%s %s</div>',
				sprintf( '<div class="course-count-lesson">%s %d %s</div>', $ico_lesson, $count_lesson, _n( 'Lesson', 'Lessons', $count_lesson, 'learnpress' ) ),
				sprintf( '<div class="course-count-student">%s %d %s</div>', $ico_student, $count_student, _n( 'Student', 'Students', $count_student, 'learnpress' ) )
			);

			$sections = apply_filters(
				'learn-press/single-instructor/course_items/sections',
				[
					'img'              => [ 'text_html' => $html_img ],
					'price-categories' => [ 'text_html' => $html_price_categories ],
					'title'            => [ 'text_html' => $html_title ],
					'count'            => [ 'text_html' => $html_count ],
				],
				$course,
				$singleCourseTemplate
			);
			Template::instance()->print_sections( $sections, compact( 'course' ) );
			$content = ob_get_clean();
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Pagination courses.
	 *
	 * @param int $page
	 * @param int $limit
	 * @param int $total_courses
	 *
	 * @return string
	 */
	public function courses_pagination( int $page, int $limit, int $total_courses ): string {
		$content = '';

		try {
			$total_pages     = \LP_Database::get_total_pages( $limit, $total_courses );
			$data_pagination = array(
				'total'    => $total_pages,
				'current'  => max( 1, $page ),
				'base'     => esc_url_raw( str_replace( 999999999, '%#%', get_pagenum_link( 999999999, false ) ) ),
				'format'   => '',
				'per_page' => $limit,
			);

			ob_start();
			Template::instance()->get_frontend_template( 'shared/pagination.php', $data_pagination );
			$content = ob_get_clean();
		} catch ( Throwable $e ) {
			ob_end_clean();
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}
}
