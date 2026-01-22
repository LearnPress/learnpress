<?php
/**
 * Template hooks Single Instructor.
 *
 * @since 4.2.3.5
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\UserItem;

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LP_Helper;
use WP_Error;

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
	 * @version 1.0.1
	 */
	public function html_btn_continue( UserCourseModel $userCourseModel ): string {
		$html = '';

		if ( $userCourseModel->can_impact_item() instanceof WP_Error ) {
			return $html;
		}

		$courseModel = $userCourseModel->get_course_model();
		if ( ! $courseModel ) {
			return $html;
		}

		$total_items = $courseModel->count_items();
		if ( empty( $total_items ) ) {
			return $html;
		}

		$itemModelContinue = $userCourseModel->get_item_continue();
		if ( empty( $itemModelContinue ) ) {
			$link_continue = $courseModel->get_permalink();
		} else {
			$link_continue = $courseModel->get_item_link( $itemModelContinue->ID );
		}

		$html = sprintf(
			'<a href="%s">%s</a>',
			esc_url_raw( $link_continue ),
			sprintf(
				'<button class="lp-button course-btn-continue">%s</button>',
				esc_html__( 'Continue', 'learnpress' )
			)
		);

		return apply_filters( 'learn-press/user/course/html-button-continue', $html, $userCourseModel );
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
					esc_html__( 'Finish Course', 'learnpress' )
				),
				'input'    => sprintf(
					'<input type="hidden" name="course-id" value="%d"/>',
					esc_attr( $userCourseModel->item_id )
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

	/**
	 * HTML button retake course
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_btn_retake( UserCourseModel $userCourseModel ): string {
		$retake_remaining = $userCourseModel->can_retake();

		if ( $retake_remaining === 0 ) {
			return '';
		}

		$html_btn = sprintf(
			'<button type="submit" class="lp-button button-retake-course">%s (%d)</button>',
			__( 'Retake course', 'learnpress' ),
			$retake_remaining
		);

		$section = apply_filters(
			'learn-press/course/html-button-retake',
			[
				'form'         => sprintf(
					'<form name="lp-form-retake-course" class="lp-form-retake-course" method="post" data-confirm="%s">',
					esc_html__( 'Do you want to retake the course', 'learnpress' )
				),
				'input'        => sprintf(
					'<input type="hidden" name="retake-course" value="%d"/>',
					esc_attr( $userCourseModel->item_id )
				),
				'btn'          => $html_btn,
				'lp-ajax-mess' => '<div class="lp-ajax-message"></div>',
				'form_end'     => '</form>',
			],
			$userCourseModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML count items completed of Course.
	 *
	 * @return mixed|string|null
	 * @since 4.2.7.6
	 * @version 1.0.1
	 */
	public function html_count_items_completed( UserCourseModel $userCourseModel ): string {
		$html = '';

		// For case Guest user.
		$userModel = $userCourseModel->get_user_model();
		if ( ! $userModel ) {
			return $html;
		}

		$courseModel           = $userCourseModel->get_course_model();
		$item_types            = CourseModel::item_types_support();
		$count_items_completed = $userCourseModel->count_items_completed();
		foreach ( $item_types as $item_type ) {
			$count_item           = $courseModel->count_items( $item_type );
			$count_item_completed = $count_items_completed->{$item_type . '_status_completed'} ?? '';
			if ( empty( $count_item_completed ) ) {
				continue;
			}

			$html .= sprintf(
				'<div class="item-completed"><span>%s</span><span>%s</span></div>',
				sprintf(
					'%s %s: ',
					LP_Helper::get_i18n_string_plural( $count_item, $item_type, false ),
					__( 'completed', 'learnpress' )
				),
				sprintf( '%d/%d', $count_item_completed, $count_item )
			);
		}

		return apply_filters( 'learn-press/user/course/html-count-items-completed', $html, $userCourseModel );
	}

	/**
	 * HTMl progress course.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return mixed|null
	 * @since 4.2.7.5
	 * @version 1.0.1
	 */
	public function html_progress( UserCourseModel $userCourseModel ) {
		$html = '';

		// For case Guest user.
		$userModel = $userCourseModel->get_user_model();
		if ( ! $userModel ) {
			return $html;
		}

		$courseModel       = $userCourseModel->get_course_model();
		$evaluation_type   = $courseModel::get_evaluation_types( $courseModel->get_evaluation_type() );
		$calculate         = $userCourseModel->calculate_course_results();
		$passing_condition = $courseModel->get_passing_condition();
		if ( array_key_first( $evaluation_type ) === 'evaluate_final_quiz' ) {
			$final_quiz = $courseModel->get_final_quiz();
			if ( $final_quiz ) {
				$quizModel         = QuizPostModel::find( $final_quiz, true );
				$passing_condition = $quizModel->get_passing_grade();
			}
		}
		$total_items = $courseModel->count_items();

		$progress_items_completed_percent = round(
			$total_items > 0 ? $calculate['completed_items'] * 100 / $total_items : 0,
			2
		);

		$section = [
			'wrapper'          => '<div class="course-progress">',
			'progress'         => sprintf(
				'<div class="course-progress__label">%s %s</div>',
				esc_html__( 'Course progress:', 'learnpress' ),
				sprintf(
					'<span class="course-progress__number">
						<span class="number">%s<span class="percentage">%s</span></span>
					</span>',
					$progress_items_completed_percent,
					'%'
				)
			),
			'line-progress'    => $this->html_items_completed_progress_bar( $userCourseModel ),
			'passing-progress' => sprintf(
				'<div class="course-progress__label">%s %s
					<span class="lp-icon-question-circle" title="%s"></span>
				</div>',
				esc_html__( 'Passing grade progress:', 'learnpress' ),
				sprintf(
					'<span class="course-progress__number">
						<span class="number">%s<span class="percentage">%s</span></span>
					</span>',
					$calculate['result'],
					'%'
				),
				sprintf(
					'%s. %s',
					isset( $evaluation_type['label'] ) ? esc_attr( $evaluation_type['label'] ) : '',
					esc_attr( sprintf( __( 'Passing condition: %s%%', 'learnpress' ), $passing_condition ) )
				)
			),
			'wrapper_end'      => '</div>',
		];

		$html = Template::combine_components( $section );

		return apply_filters( 'learn-press/user/course/html-progress', $html, $userCourseModel );
	}

	/**
	 * HTML course items completed progress bar.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 *
	 * @since 4.3.2.6
	 * @version 1.0.0
	 */
	public function html_passing_grade_progress_bar( UserCourseModel $userCourseModel ): string {
		$courseModel       = $userCourseModel->get_course_model();
		$calculate         = $userCourseModel->calculate_course_results();
		$passing_condition = $courseModel->get_passing_condition();

		$section = [
			'wrapper'               => '<div class="course-passing-grade-progress-bar">',
			'line-passing-progress' => sprintf(
				'<div class="course-progress__line">
					<div class="course-progress__line__active" style="width: %s%%"></div>
					<div class="course-progress__line__point" style="left: %s%%"></div>
				</div>',
				$calculate['result'],
				$passing_condition
			),
			'wrapper_end'           => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTML course items completed progress bar.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return string
	 *
	 * @since 4.3.2.6
	 * @version 1.0.0
	 */
	public function html_items_completed_progress_bar( UserCourseModel $userCourseModel ): string {
		$courseModel = $userCourseModel->get_course_model();
		$calculate   = $userCourseModel->calculate_course_results();
		$total_items = $courseModel->count_items();

		$progress_items_completed_percent = round(
			$total_items > 0 ? $calculate['completed_items'] * 100 / $total_items : 0,
			2
		);

		$section = [
			'wrapper'       => sprintf(
				'<div class="course-items-completed-progress-bar" title="%s">',
				sprintf(
					esc_html__( '%1$d of %2$d items completed', 'learnpress' ),
					$calculate['completed_items'],
					$total_items
				)
			),
			'line-progress' => sprintf(
				'<div class="course-progress__line">
					<div class="course-progress__line__active" style="width: %s%%"></div>
				</div>',
				$progress_items_completed_percent,
			),
			'wrapper_end'   => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * HTMl progress course.
	 *
	 * @param UserCourseModel $userCourseModel
	 *
	 * @return mixed|null
	 * @since 4.2.7.6
	 * @version 1.0.1
	 */
	public function html_message_lock( UserCourseModel $userCourseModel ): string {
		$html = '';

		// For case Guest user.
		$userModel = $userCourseModel->get_user_model();
		if ( ! $userModel ) {
			return $html;
		}

		$courseModel = $userCourseModel->get_course_model();

		if ( $courseModel->enable_block_when_finished() && $userCourseModel->is_finished() ) {
			$message = __( 'This course is finished.', 'learnpress' );
		} elseif ( $courseModel->enable_block_when_expire() && $userCourseModel->timestamp_remaining_duration() === 0 ) {
			$message = __( 'This course is expired.', 'learnpress' );
		} else {
			return $html;
		}

		$html = Template::print_message( $message, 'warning', false );

		return apply_filters( 'learn-press/user/course/html-message-lock', $html, $userCourseModel );
	}
}
