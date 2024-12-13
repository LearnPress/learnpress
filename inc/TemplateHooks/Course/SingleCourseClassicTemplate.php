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

        $content = [];

        $sections = apply_filters(
			'learn-press/single-course/classic/sections',
			[
                'wrapper'               => '<div class="lp-single-course lp-archive-courses">',
                'breadcrumb'            => $html_breadcrumb,
                'course-summary'        => '<div id="learn-press-course" class="course-summary">',
                'content'               => Template::combine_components( $content ),
                'course-summary_end'    => '</div>',
                'wrapper_end'           => '</div>',
            ]
        );

        echo Template::combine_components( $sections );
    }
}