<?php
/**
 * Template hooks Single Instructor.
 *
 * @since 4.2.3
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Instructor;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\Profile\ProfileTemplate;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\UserTemplate;
use LP_Course;
use LP_Course_Filter;
use LP_User;
use Throwable;
use WP_Query;

class SingleInstructorTemplate {
	use Singleton;

	public function init() {
		add_action( 'learn-press/single-instructor/layout', [ $this, 'sections' ] );
		//add_action( 'wp_head', [ $this, 'add_internal_style_to_head' ] );
	}

	/**
	 * Get display name html of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 *
	 * @return string
	 */
	public function html_display_name( $instructor ): string {
		$sections = [
			'wrapper'     => '<span class="instructor-display-name">',
			'content'     => $instructor->get_display_name(),
			'wrapper_end' => '</span>',
		];

		return Template::combine_components( $sections );
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
			$socials = $instructor->get_profile_social( $instructor->get_id() );
			ob_start();
			foreach ( $socials as $k => $social ) {
				echo $social;
			}
			$content = ob_get_clean();

			$sections = [
				'wrapper'     => '<div class="instructor-social">',
				'content'     => $content,
				'wrapper_end' => '</div>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
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
	 * @since 4.2.3.4
	 * @version 1.0.0
	 */
	public function html_description( $instructor ): string {
		$content = '';

		try {
			$description = $instructor->get_description();
			if ( empty( $description ) ) {
				return $content;
			}

			$sections = [
				'wrapper'     => '<div class="instructor-description">',
				'content'     => $instructor->get_description(),
				'wrapper_end' => '</div>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html avatar of instructor.
	 *
	 * @param LP_User|UserModel $instructor
	 * @param array $size_display ['width' => 100, 'height' => 100]
	 *
	 * @return string
	 * @since 4.2.3
	 * @version 1.0.2
	 */
	public function html_avatar( $instructor, array $size_display = [] ): string {
		$userTemplate = UserTemplate::instance();
		$html         = '';
		if ( ! $instructor ) {
			return $html;
		}

		if ( $instructor instanceof LP_User ) {
			$instructor = UserModel::find( $instructor->get_id(), true );
			if ( ! $instructor ) {
				return $html;
			}
		}

		return $userTemplate->html_avatar( $instructor, $size_display, 'instructor' );
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
			$instructor_statistic = $instructor->get_instructor_statistic();

			$sections = [
				'wrapper'     => '<span class="instructor-total-courses">',
				'content'     => sprintf(
					'%d %s',
					$instructor_statistic['published_course'],
					_n( 'Course', 'Courses', $instructor_statistic['published_course'], 'learnpress' )
				),
				'wrapper_end' => '</span>',
			];

			$content = Template::combine_components( $sections );
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
			$instructor_statistic = $instructor->get_instructor_statistic();

			$sections = [
				'wrapper'     => '<span class="instructor-total-students">',
				'content'     => sprintf(
					'%d %s',
					$instructor_statistic['total_student'],
					_n( 'Student', 'Students', $instructor_statistic['total_student'], 'learnpress' )
				),
				'wrapper_end' => '</span>',
			];

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get button view instructor.
	 *
	 * @param LP_User|UserModel|false $instructor
	 *
	 * @return string
	 * @version 1.0.0
	 * @since 4.2.3
	 */
	public function html_button_view( $instructor ): string {
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
		$data_render = str_replace(
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

		return apply_filters( 'learn-press/single-instructor/render-data', $data_render, $instructor, $data_content );
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

		try {
			if ( isset( $data['instructor_id'] ) ) {
				$instructor_id = (int) $data['instructor_id'];
				$instructor    = UserModel::find( $instructor_id, true );
			} else {
				$instructor = $this->detect_instructor_by_page();
			}

			if ( ! $instructor || ! $instructor->is_instructor() ) {
				return;
			}

			$sections = apply_filters(
				'learn-press/single-instructor/sections',
				[
					'wrapper'           => '<div class="lp-content-area">',
					'wrapper_inner'     => '<div class="lp-single-instructor">',
					'info'              => $this->info( $instructor ),
					'courses'           => $this->section_list_courses( $instructor ),
					'wrapper_inner_end' => '</div>',
					'wrapper_end'       => '</div>',
				],
				$instructor
			);

			echo Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}
	}

	/**
	 * Detected single instructor Page.
	 *
	 * @return false|UserModel
	 * @since 4.2.3.4
	 * @version 1.0.1
	 */
	public function detect_instructor_by_page() {
		$instructor = false;

		try {
			if ( get_query_var( 'is_single_instructor' ) ) {
				$instructor_name = get_query_var( 'instructor_name' );
				if ( $instructor_name && 'page' !== $instructor_name ) {
					$user = get_user_by( 'slug', $instructor_name );
					if ( $user ) {
						$instructor = UserModel::find( $user->ID, true );
					}
				} else {
					$instructor = UserModel::find( get_current_user_id(), true );
				}
			}
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $instructor;
	}

	/**
	 * @param UserModel $instructor
	 *
	 * @return false|string
	 */
	public function info( UserModel $instructor ): string {
		$sections = apply_filters(
			'learn-press/single-instructor/info/sections',
			[
				'wrapper'             => '<div class="lp-single-instructor__info">',
				'cover_img'           => ProfileTemplate::instance()->html_cover_image( $instructor ),
				'wrapper_content'     => '<div class="lp-single-instructor__info__wrapper">',
				'avatar'              => $this->html_avatar( $instructor ),
				'info_right'          => $this->info_right( $instructor ),
				'wrapper_content_end' => '</div>',
				'wrapper_end'         => '</div>',
			],
			$instructor
		);

		return Template::combine_components( $sections );
	}

	public function info_right( UserModel $instructor ): string {

		$section_instructor_meta = [
			'wrapper'        => '<div class="lp-instructor-meta">',
			'count_courses'  => sprintf(
				'<div class="instructor-item-meta"><i class="lp-icon-courses"></i>%s</div>',
				$this->html_count_courses( $instructor )
			),
			'count_students' => sprintf(
				'<div class="instructor-item-meta"><i class="lp-icon-user-graduate"></i>%s</div>',
				$this->html_count_students( $instructor )
			),
			'wrapper_end'    => '</div>',
		];

		$sections = apply_filters(
			'learn-press/single-instructor/info-right/sections',
			[
				'wrapper'             => '<div class="lp-single-instructor__info__right">',
				'wrapper_content'     => '<div class="lp-single-instructor__info__right__content">',
				'title'               => sprintf( '<h2>%s</h2>', $this->html_display_name( $instructor ) ),
				'social'              => $this->html_social( $instructor ),
				'wrapper_content_end' => '</div>',
				'meta'                => Template::combine_components( $section_instructor_meta ),
				'description'         => $this->html_description( $instructor ),
				'wrapper_end'         => '</div>',
			],
			$instructor
		);

		return Template::combine_components( $sections );
	}

	public function section_list_courses( UserModel $instructor ): string {
		$content = '';

		try {
			// Query courses of instructor
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
					'wrapper'     => '<div class="instructor-courses learn-press-courses">',
					'courses'     => $this->list_courses( $instructor, $courses ),
					'pagination'  => $this->courses_pagination( $filter->page, $filter->limit, $total_courses ),
					'wrapper_end' => '</div>',
				],
				$courses,
				$instructor
			);

			$content = Template::combine_components( $sections );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
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
			// List courses
			ob_start();
			foreach ( $courses as $course_obj ) {
				$course = CourseModel::find( $course_obj->ID, true );
				echo ListCoursesTemplate::render_course( $course );
			}
			$html_ul_wrapper = ob_get_clean();

			$sections = apply_filters(
				'learn-press/single-instructor/courses/sections',
				[
					'wrapper'     => '<ul class="ul-instructor-courses">',
					'list_course' => $html_ul_wrapper,
					'wrapper_end' => '</ul>',
				],
				$courses,
				$instructor
			);

			$content = Template::combine_components( $sections );
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
