<?php
namespace LearnPress\Shortcodes;

use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseModernLayout;
use Throwable;

defined( 'ABSPATH' ) || exit();


/**
 * Class CourseButtonShortcode
 *
 * Refactor from LP_Shortcode_Button_Course
 *
 * @since 4.3.0
 * @version 1.0.0
 */
class CourseButtonShortcode extends AbstractShortcode {
	use singleton;

	protected $shortcode_name = 'button_course';

	/**
	 * Output button course.
	 *
	 * @param $atts
	 *
	 * @return string
	 */
	public function render( $atts ): string {
		$html = '';
		wp_enqueue_style( 'learnpress' );

		$course_id = $atts['id'] ?? 0;
		if ( 'current' === $course_id ) {
			$course_id = get_the_ID();
		}

		try {
			$singleCourseModernLayout = SingleCourseModernLayout::instance();
			$courseModel              = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				return '';
			}

			$userModel = UserModel::find( get_current_user_id(), true );

			// Load js button course.
			wp_enqueue_script( 'lp-single-course' );

			$html = $singleCourseModernLayout->html_buttons( $courseModel, $userModel );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}
}
