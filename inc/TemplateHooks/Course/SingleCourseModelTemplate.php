<?php
/**
 * Template hooks Single Course Offline.
 *
 * @since 4.2.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;

class SingleCourseModelTemplate {
	use Singleton;

    /**
	 * @var SingleCourseTemplate
	 */
	public $singleCourseTemplate;

	public function init() {
		$this->singleCourseTemplate = SingleCourseTemplate::instance();
        
		add_action(
			'learn-press/single-course/model/layout',
			[ $this, 'course_model_layout' ]
		);
	}

    /**
	 * Single course layout
	 *
	 * @param $course
	 *
	 * @return void
	 */
	public function course_model_layout( $course ) {

        $sections = apply_filters(
			'learn-press/single-course/model/sections',
			[
				'wrapper_container'     => '<div class="lp-content-area">',
				'wrapper'               => '<div class="lp-single-course">',
                'section_header'        => $this->header_sections( $course ),
				// 'wrapper_main'          => '<div class="lp-single-course-main">',
				// 'section_left'          => Template::combine_components( $section_left ),
				// 'section_right'         => Template::combine_components( $section_right ),
				// 'wrapper_main_end'      => '</div>',
				// 'related_courses'       => $html_courses_related,
				'wrapper_end'           => '</div>',
				'wrapper_container_end' => '</div>',
			]
		);

		echo Template::combine_components( $sections );
    }

    public function header_sections( $course ): string {

        ob_start();
		learn_press_breadcrumb();
		$html_breadcrumb = ob_get_clean();

        $html_categories = $this->singleCourseTemplate->html_categories( $course );
        if ( ! empty( $html_categories ) ) {
            $html_categories = sprintf(
                '<div>%s %s</div>',
                sprintf( '<label>%s</label>', __( 'in', 'learnpress' ) ),
                $html_categories
            );
        }

        $html_instructor = $this->singleCourseTemplate->html_instructor( $course );

        $header_sections =  apply_filters(
			'learn-press/single-course/model/header/sections',
			[
                'wrapper_header'        => '<div class="lp-single-course__header">',
                'breadcrumb'            => $html_breadcrumb,
                'title'                 => $this->singleCourseTemplate->html_title( $course, 'h1' ),
                'wrapper_instructor_cate'     => '<div class="course-instructor-category">',
                'instructor'                  => sprintf(
                    '<div>%s %s</div>',
                    sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
                    $html_instructor
                ),
                'category'                    => $html_categories,
                'wrapper_instructor_cate_end' => '</div>',
                'wrapper_header_end'    => '</div>',
            ]
        );

        return Template::combine_components( $header_sections );
    }
}