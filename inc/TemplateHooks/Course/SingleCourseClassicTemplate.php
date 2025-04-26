<?php
/**
 * Template hooks Single Course Online.
 *
 * @since 4.2.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\UserTemplate;

defined( 'ABSPATH' ) || exit();

class SingleCourseClassicTemplate {
	use Singleton;

	/**
	 * @var SingleCourseTemplate $singleCourseTemplate
	 */
	public $singleCourseTemplate;

	public function init() {
		$this->singleCourseTemplate = SingleCourseTemplate::instance();

		add_action( 'learn-press/single-course/layout/classic', [ $this, 'section' ] );
	}

	/**
	 * Single course layout classic
	 *
	 * @param CourseModel $course
	 *
	 * @return void
	 */
	public function section( CourseModel $course ) {
		$user = UserModel::find( get_current_user_id(), true );

		ob_start();
		learn_press_breadcrumb();
		$html_breadcrumb = ob_get_clean();

		$content = [
			'wrapper'        => '<div class="course-content course-summary-content">',
			'section_header' => $this->header_sections( $course, $user ),
			'section_main'   => $this->main_sections( $course, $user ),
			'wrapper_end'    => '</div>',
		];

		$sections = apply_filters(
			'learn-press/single-course/classic/sections',
			[
				'wrapper'            => '<div class="lp-single-course lp-archive-courses">',
				'breadcrumb'         => $html_breadcrumb,
				'course_summary'     => '<div id="learn-press-course" class="course-summary">',
				'content'            => Template::combine_components( $content ),
				'course_summary_end' => '</div>',
				'wrapper_end'        => '</div>',
			]
		);

		echo Template::combine_components( $sections );
	}

	public function header_sections( $course, $user ): string {

		$meta_primary = apply_filters(
			'learn-press/single-course/classic/meta-primary/sections',
			[
				'wrapper'     => '<div class="course-meta course-meta-primary">',
				'instructor'  => $this->html_instructor( $course ),
				'category'    => $this->html_category( $course ),
				'wrapper_end' => '</div>',
			]
		);

		$meta_secondary = apply_filters(
			'learn-press/single-course/classic/meta-secondary/sections',
			[
				'wrapper'     => '<div class="course-meta course-meta-secondary">',
				'duration'    => sprintf(
					'<div class="meta-item meta-item-duration">%s</div>',
					$this->singleCourseTemplate->html_duration( $course )
				),
				'level'       => sprintf(
					'<div class="meta-item meta-item-level">%s</div>',
					$this->singleCourseTemplate->html_level( $course )
				),
				'lesson'      => sprintf(
					'<div class="meta-item meta-item-lesson">%s</div>',
					$this->singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT )
				),
				'quiz'        => sprintf(
					'<div class="meta-item meta-item-quiz">%s</div>',
					$this->singleCourseTemplate->html_count_item( $course, LP_QUIZ_CPT )
				),
				'student'     => sprintf(
					'<div class="meta-item meta-item-student">%s</div>',
					$this->singleCourseTemplate->html_count_student( $course )
				),
				'wrapper_end' => '</div>',
			]
		);

		$header_sections = apply_filters(
			'learn-press/single-course/classic/header/sections',
			[
				'wrapper'             => '<div class="course-detail-info">',
				'wrapper_inner'       => '<div class="course-detail-info__inner">',
				'wrapper_content'     => '<div class="course-meta__pull-left">',
				'meta_primary'        => Template::combine_components( $meta_primary ),
				'title'               => $this->singleCourseTemplate->html_title( $course, 'h1' ),
				'meta_secondary'      => Template::combine_components( $meta_secondary ),
				'wrapper_content_end' => '</div>',
				'wrapper_inner_end'   => '</div>',
				'wrapper_end'         => '</div>',
			]
		);

		return Template::combine_components( $header_sections );
	}

	public function main_sections( $course, $user ): string {

		$content_left = apply_filters(
			'learn-press/single-course/classic/content-left/sections',
			[
				'wrapper'      => '<div class="entry-content-left">',
				'course_tabs'  => $this->html_course_tabs( $course, $user ),
				'features'     => $this->singleCourseTemplate->html_features( $course ),
				'target'       => $this->singleCourseTemplate->html_target( $course ),
				'requirements' => $this->singleCourseTemplate->html_requirements( $course ),
				'wrapper_end'  => '</div>',
			]
		);

		$userCourseModel         = UserCourseModel::find( get_current_user_id(), $course->get_id(), true );
		$userCourseTemplate      = UserCourseTemplate::instance();
		$btn_continue_and_finish = [];

		if ( $userCourseModel instanceof UserCourseModel ) {
			$btn_continue_and_finish = [
				'btn_continue' => $userCourseTemplate->html_btn_continue( $userCourseModel ),
				'btn_finish'   => $userCourseTemplate->html_btn_finish( $userCourseModel ),
			];
		}

		$section_buttons = apply_filters(
			'learn-press/single-course/model/section-right/info-meta/buttons',
			[
				'wrapper'                 => '<div class="course-buttons">',
				'btn_contact'             => $this->singleCourseTemplate->html_btn_external( $course, $user ),
				'btn_buy'                 => $this->singleCourseTemplate->html_btn_purchase_course( $course, $user ),
				'btn_enroll'              => $this->singleCourseTemplate->html_btn_enroll_course( $course, $user ),
				'btn_continue_and_finish' => Template::combine_components( $btn_continue_and_finish ),
				'wrapper_end'             => '</div>',
			],
			$course,
			$user
		);

		$summary_sidebar = apply_filters(
			'learn-press/single-course/classic/content-left/sections',
			[
				'wrapper'           => '<div class="course-summary-sidebar">',
				'wrapper_inner'     => '<div class="course-summary-sidebar__inner">',
				'image'             => $this->singleCourseTemplate->html_image( $course ),
				'price'             => $this->singleCourseTemplate->html_price( $course ),
				'buttons'           => Template::combine_components( $section_buttons ),
				'featured_review'   => $this->singleCourseTemplate->html_feature_review( $course, $user ),
				'sidebar'           => $this->singleCourseTemplate->html_sidebar( $course ),
				'wrapper_inner_end' => '</div>',
				'wrapper_end'       => '</div>',
			]
		);

		$main_sections = apply_filters(
			'learn-press/single-course/classic/main/sections',
			[
				'wrapper'         => '<div class="lp-entry-content lp-content-area">',
				'content_left'    => Template::combine_components( $content_left ),
				'summary_sidebar' => Template::combine_components( $summary_sidebar ),
				'wrapper_end'     => '</div>',
			]
		);

		return Template::combine_components( $main_sections );
	}

	public function html_instructor( $course ): string {
		$instructor = $course->get_author_model();
		if ( ! $instructor ) {
			return '';
		}

		$singleInstructorTemplate = SingleInstructorTemplate::instance();

		$html_instructor = apply_filters(
			'learn-press/course/instructor-html',
			[
				'wrapper'           => '<div class="meta-item meta-item-instructor">',
				'avatar_instructor' => sprintf( '<div class="meta-item__image">%s</div>', SingleInstructorTemplate::instance()->html_avatar( $instructor, [] ) ),
				'instructor'        => '<div class="meta-item__value">',
				'label'             => sprintf( '<label>%s</label>', esc_html__( 'Instructor', 'learnpress' ) ),
				'name'              => sprintf(
					'<div><a href="%s">%s</a></div>',
					$instructor->get_url_instructor(),
					$singleInstructorTemplate->html_display_name( $instructor )
				),
				'instructor_end'    => '</div>',
				'wrapper_end'       => '</div>',
			],
			$course,
			$instructor
		);

		return Template::combine_components( $html_instructor );
	}

	public function html_category( $course ): string {
		$cats = $course->get_categories();
		if ( empty( $cats ) ) {
			return '';
		}

		$cat_names = [];
		array_map(
			function ( $cat ) use ( &$cat_names ) {
				$term        = sprintf( '<a href="%s">%s</a>', get_term_link( $cat->term_id ), $cat->name );
				$cat_names[] = $term;
			},
			$cats
		);

		$content = implode( ' | ', $cat_names );

		$html_category = apply_filters(
			'learn-press/course/html-categories',
			[
				'wrapper'      => '<div class="meta-item meta-item-categories">',
				'category'     => '<div class="meta-item__value">',
				'label'        => sprintf( '<label>%s</label>', esc_html__( 'Category', 'learnpress' ) ),
				'content'      => sprintf( '<div>%s</div>', $content ),
				'category_end' => '</div>',
				'wrapper_end'  => '</div>',
			],
			$course,
			$cats,
			$cat_names
		);

		return Template::combine_components( $html_category );
	}

	public function html_course_tabs( $course, $user ): string {

		$tabs = apply_filters(
			'learn-press/single-course/classic/course-tabs',
			[
				'overview'   => esc_html__( 'Overview', 'learnpress' ),
				'curriculum' => esc_html__( 'Curriculum', 'learnpress' ),
				'instructor' => esc_html__( 'Instructor', 'learnpress' ),
				'materials'  => esc_html__( 'Materials', 'learnpress' ),
				'faqs'       => esc_html__( 'FAQs', 'learnpress' ),
			]
		);

		$active_tab = 'overview';
		$lp_user    = learn_press_get_current_user();

		if ( $lp_user && ! $lp_user instanceof LP_User_Guest ) {
			$can_view_course = $lp_user->can_view_content_course( get_the_ID() );

			if ( ! $can_view_course->flag ) {
				if ( LP_BLOCK_COURSE_FINISHED === $can_view_course->key ) {
					learn_press_display_message(
						esc_html__( 'You finished this course. This course has been blocked', 'learnpress' ),
						'warning'
					);
				} elseif ( LP_BLOCK_COURSE_DURATION_EXPIRE === $can_view_course->key ) {
					learn_press_display_message(
						esc_html__( 'This course has been blocked for expiration', 'learnpress' ),
						'warning'
					);
				}
			}
		}

		$html_tabs = [];
		if ( $tabs ) {
			ob_start();
			foreach ( $tabs as $key => $tab ) {
				echo '<input type="radio" name="learn-press-course-tab-radio" id="tab-' . esc_attr( $key ) . '-input"
					' . ( $active_tab === $key ? 'checked ' : '' ) . 'value="' . esc_attr( $key ) . '"/>';
			}
			$html_input = ob_get_clean();

			ob_start();
			foreach ( $tabs as $key => $tab ) {
				$classes = array( 'course-nav course-nav-tab-' . esc_attr( $key ) );

				if ( $active_tab === $key ) {
					$classes[] = 'active';
				}

				echo '<li class="' . esc_attr( implode( ' ', $classes ) ) . '">';
				echo '<label for="tab-' . esc_attr( $key ) . '-input">' . esc_html( $tab ) . '</label>';
				echo '</li>';
			}
			$html_tabs_nav = ob_get_clean();

			$tabs_nav = [
				'wrapper'      => '<div class="wrapper-course-nav-tabs TabsDragScroll">',
				'nav_tabs'     => '<ul class="learn-press-nav-tabs course-nav-tabs" data-tabs="' . esc_attr( count( $tabs ) ) . '">',
				'content'      => $html_tabs_nav,
				'nav_tabs_end' => '</ul>',
				'wrapper_end'  => '</div>',
			];

			ob_start();

			foreach ( $tabs as $key => $tab ) {
				echo '<div class="course-tab-panel-' . esc_attr( $key ) . ' course-tab-panel"
					id="tab-' . esc_attr( $key ) . '">';

				switch ( $key ) {
					case 'overview':
						echo $this->singleCourseTemplate->html_description( $course );
						break;
					// case 'curriculum':
					//  echo $this->singleCourseTemplate->html_curriculum( $course );
					//  break;
					case 'instructor':
						echo $this->html_instructor_main( $course, $user );
						break;
					case 'faqs':
						echo $this->singleCourseTemplate->html_faqs( $course );
						break;
					case 'materials':
						echo $this->singleCourseTemplate->html_material( $course );
						break;
				}
				echo '</div>';
			}

			$html_tabs_content = ob_get_clean();

			$tabs_content = [
				'wrapper'     => '<div class="course-tab-panels">',
				'content'     => $html_tabs_content,
				'wrapper_end' => '</div>',
			];

			$html_tabs = [
				'input'   => $html_input,
				'nav'     => Template::combine_components( $tabs_nav ),
				'content' => Template::combine_components( $tabs_content ),
			];
		}

		$course_tabs = apply_filters(
			'learn-press/single-course/classic/main/course-tabs',
			[
				'wrapper'     => '<div id="learn-press-course-tabs" class="course-tabs">',
				'tabs'        => Template::combine_components( $html_tabs ),
				'wrapper_end' => '</div>',
			]
		);

		return Template::combine_components( $course_tabs );
	}

	public function html_instructor_main( $course, $user ): string {

		$singleInstructorTemplate = SingleInstructorTemplate::instance();
		$author                   = $course->get_author_model();

		// Instructor
		$html_instructor = '';
		if ( $author ) {
			$html_instructor_image   = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$author->get_url_instructor(),
				$author->get_display_name(),
				$singleInstructorTemplate->html_avatar( $author )
			);
			$section_instructor_meta = [
				'wrapper'        => '<div class="lp-instructor-meta">',
				'count_students' => sprintf(
					'<div class="instructor-item-meta">%s</div>',
					$singleInstructorTemplate->html_count_students( $author )
				),
				'count_courses'  => sprintf(
					'<div class="instructor-item-meta">%s</div>',
					$singleInstructorTemplate->html_count_courses( $author )
				),
				'wrapper_end'    => '</div>',
			];
			$html_instructor_meta    = Template::combine_components( $section_instructor_meta );

			$section_instructor_right = apply_filters(
				'learn-press/single-course/model/section-instructor/right',
				[
					'wrapper'     => '<div class="lp-section-instructor">',
					'name'        => sprintf(
						'<a href="%s">%s</a>',
						$author->get_url_instructor(),
						$singleInstructorTemplate->html_display_name( $author )
					),
					'meta'        => $html_instructor_meta,
					'description' => $singleInstructorTemplate->html_description( $author ),
					'social'      => $singleInstructorTemplate->html_social( $author ),
					'wrapper_end' => '</div>',
				],
				$course,
				$user
			);
			$html_instructor_right    = Template::combine_components( $section_instructor_right );
			$section_instructor       = apply_filters(
				'learn-press/single-course/model/section-instructor',
				[
					'wrapper'          => '<div class="lp-section-instructor">',
					'wrapper_info'     => '<div class="lp-instructor-info">',
					'image'            => $html_instructor_image,
					'instructor_right' => $html_instructor_right,
					'wrapper_info_end' => '</div>',
					'wrapper_end'      => '</div>',
				],
				$course,
				$user
			);
			$html_instructor          = Template::combine_components( $section_instructor );
		}

		return $html_instructor;
	}
}
