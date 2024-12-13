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
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\UserTemplate;

class SingleCourseClassicTemplate {
	use Singleton;

	/**
	 * @var SingleCourseTemplate
	 */
	public $singleCourseTemplate;

	public function init() {
		$this->singleCourseTemplate = SingleCourseTemplate::instance();

		add_action(
			'learn-press/single-course/classic/layout',
			[ $this, 'course_classic_layout' ]
		);
	}

	/**
	 * Single course layout classic
	 *
	 * @param $course
	 *
	 * @return void
	 */
	public function course_classic_layout( $course ) {
		if ( ! $course instanceof CourseModel ) {
			return;
		}

		$user = UserModel::find( get_current_user_id(), true );

		ob_start();
		learn_press_breadcrumb();
		$html_breadcrumb = ob_get_clean();

		$content = [
			'wrapper'           => '<div class="course-content course-summary-content">',
			'section_header'    => $this->header_sections( $course, $user ),
			'wrapper_end'       => '</div>',
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

		$header_sections = apply_filters(
			'learn-press/single-course/classic/header/sections',
			[
				'wrapper'                     => '<div class="course-detail-info">',
				'wrapper_inner'               => '<div class="course-detail-info__inner">',
				'wrapper_instructor_cate'     => '<div class="course-meta course-meta-primary course-meta__pull-left">',
				'instructor'                  => $this->html_instructor( $course ),
				'category'                    => $this->html_category( $course ),
				'wrapper_instructor_cate_end' => '</div>',
				'wrapper_inner_end'           => '</div>',
				'wrapper_end'                 => '</div>',
			]
		);

		return Template::combine_components( $header_sections );
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
				'wrapper'            => '<div class="meta-item meta-item-instructor">',
				'avartar_instructor' => sprintf( '<div class="meta-item__image">%s</div>', UserTemplate::instance()->html_avatar( $instructor, [], 'instructor' ) ),
				'instructor'         => '<div class="meta-item__value">',
				'label'              => sprintf( '<label>%s</label>', esc_html__( 'Instructor', 'learnpress' ) ),
				'name'               => sprintf(
					'<div><a href="%s">%s</a></div>',
					$instructor->get_url_instructor(),
					$singleInstructorTemplate->html_display_name( $instructor )
				),
				'instructor_end'     => '</div>',
				'wrapper_end'        => '</div>',
			],
			$course,
			$instructor,
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
}
