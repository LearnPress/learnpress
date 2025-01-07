<?php
/**
 * Template hooks Single Instructor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\UserItem;

use LearnPress\Helpers\Template;
use LearnPress\Models\UserItems\UserCourseModel;

class UserCourseTemplate extends UserItemBaseTemplate {
	public static function instance() {
		static $instance = null;
		if ( is_null( $instance ) ) {
			$instance = new self();
		}

		return $instance;
	}

	/**
	 * HTML button continue course.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 * @since 4.2.7.5
	 * @version 1.0.0
	 */
	public function html_btn_continue( UserCourseModel $userCourseModel ): string {
		$html = '';

		if ( in_array( $userCourseModel->get_status(), [ LP_COURSE_FINISHED, LP_USER_COURSE_CANCEL ] ) ) {
			return $html;
		}

		// Course is locked.
		if ( $userCourseModel->timestamp_remaining_duration() === 0 ) {
			return $html;
		}

		$courseModel       = $userCourseModel->get_course_model();
		$itemModelContinue = $userCourseModel->get_item_continue();
		$link_continue     = '';
		if ( empty( $itemModelContinue ) ) {
			$link_continue = $courseModel->get_permalink();
		} else {
			$link_continue = $courseModel->get_item_link( $itemModelContinue->ID );
		}

		$html = apply_filters(
			'learn-press/user/course/html-button-continue',
			sprintf(
				'<a href="%s">%s</a>',
				esc_url_raw( $link_continue ),
				sprintf(
					'<button class="lp-button course-btn-continue">%s</button>',
					esc_html__( 'Continue', 'learnpress' )
				)
			),
			$userCourseModel
		);

		return $html;
	}

	/**
	 * HTML button finish course.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 * @since 4.2.7.5
	 * @version 1.0.0
	 */
	public function html_btn_finish( UserCourseModel $userCourseModel ): string {
		$html = '';

		$can_finish = $userCourseModel->can_finish();
		if ( is_wp_error( $can_finish ) ) {
			return $html;
		}

		$section = apply_filters(
			'learn-press/user/course/html-button-finish',
			[
				'form'     => sprintf(
					'<form class="lp-form form-button form-button-finish-course" method="post" data-confirm="%s">',
					__( 'Do you want to finish the course?', 'learnpress' )
				),
				'btn'      => sprintf(
					'<button class="lp-button btn-finish-course">%s</button>',
					esc_html__( 'Finish', 'learnpress' )
				),
				'input'    => sprintf(
					'<input type="hidden" name="course-id" value="%d"/>',
					esc_attr( $userCourseModel->ref_id )
				),
				'nonce'    => sprintf(
					'<input type="hidden" name="finish-course-nonce" value="%s"/>',
					esc_attr(
						wp_create_nonce(
							sprintf( 'finish-course-%d-%d', $userCourseModel->item_id, $userCourseModel->user_id )
						)
					)
				),
				'lpajax'   => '<input type="hidden" name="lp-ajax" value="finish-course"/>',
				'noajax'   => '<input type="hidden" name="noajax" value="yes"/>',
				'form_end' => '</form>',
			],
			$userCourseModel
		);

		return Template::combine_components( $section );
	}
}
