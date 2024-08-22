<?php
/**
 * Template hooks Single Course Offline.
 *
 * @since 4.2.7
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Datetime;
use Throwable;

class SingleCourseOfflineTemplate {
	use Singleton;

	/**
	 * @var SingleCourseTemplate
	 */
	public $singleCourseTemplate;

	public function init() {
		$this->singleCourseTemplate = SingleCourseTemplate::instance();

		add_action(
			'learn-press/single-course/offline/layout',
			[ $this, 'course_offline_layout' ]
		);
	}

	/**
	 * Offline course layout
	 *
	 * @param $course
	 *
	 * @return void
	 */
	public function course_offline_layout( $course ) {
		if ( ! $course instanceof CourseModel ) {
			return;
		}

		if ( ! $course->is_offline() ) {
			return;
		}

		$user = UserModel::find( get_current_user_id() );

		ob_start();
		learn_press_breadcrumb();
		$html_breadcrumb = ob_get_clean();

		// Author
		$singleInstructorTemplate = SingleInstructorTemplate::instance();
		$author                   = $course->get_author_model();
		$html_author              = '';
		if ( $author ) {
			$html_author = sprintf(
				'%s %s',
				__( 'By', 'learnpress' ),
				sprintf( '<a href="%s">%s</a>', $author->get_url_instructor(), $singleInstructorTemplate->html_display_name( $author ) )
			);
		}
		// End author

		// Instructor
		$html_instructor = '';
		if ( $author ) {
			$html_instructor_image = sprintf(
				'<a href="%s" title="%s">%s</a>',
				$author->get_url_instructor(),
				$author->get_display_name(),
				$singleInstructorTemplate->html_avatar( $author )
			);

			$section_instructor_right = [
				'wrapper_instructor_right_start' => '<div class="lp-section-instructor">',
				'name'                           => $singleInstructorTemplate->html_display_name( $author ),
				'description'                    => $singleInstructorTemplate->html_description( $author ),
				'social'                         => $singleInstructorTemplate->html_social( $author ),
				'wrapper_instructor_right_end'   => '</div>',
			];
			$html_instructor_right    = Template::combine_components( $section_instructor_right );
			$section_instructor       = [
				'wrapper_instructor_start' => '<div class="lp-section-instructor">',
				'image'                    => $html_instructor_image,
				'instructor_right'         => $html_instructor_right,
				'wrapper_instructor_end'   => '</div>'
			];
			$html_instructor          = Template::combine_components( $section_instructor );
		}
		// End instructor

		// Info one
		$section_info_one = [
			'wrapper_info_one_open'  => '<div class="lp-single-course-offline-info-one">',
			'author'                 => $html_author,
			'address'                => $this->singleCourseTemplate->html_address( $course ),
			'wrapper_info_one_close' => '</div>',
		];
		$html_info_one    = Template::combine_components( $section_info_one );

		$html_wrapper_section_left = [
			'<div class="lp-single-offline-course__left">' => '</div>'
		];
		$section_left              = apply_filters(
			'learn-press/single-course/offline/section-left',
			[
				'breadcrumb'  => $html_breadcrumb,
				'title'       => $this->singleCourseTemplate->html_title( $course, 'h1' ),
				'info_one'    => $html_info_one,
				'image'       => $this->singleCourseTemplate->html_image( $course ),
				'description' => $this->singleCourseTemplate->html_description( $course ),
				'instructor'  => $html_instructor,
			],
			$course,
			$user
		);
		$html_section_left         = Template::combine_components( $section_left );
		$html_section_left         = Template::instance()->nest_elements( $html_wrapper_section_left, $html_section_left );

		// Section right

		// Info two
		$data_info_meta =
			apply_filters(
				'learn-press/single-course/offline/info-meta',
				[
					'price'        => [
						'label' => sprintf( '<span class="currency">%s</span> %s', learn_press_get_currency_symbol(), __( 'Price', 'learnpress' ) ),
						'value' => $this->singleCourseTemplate->html_price( $course )
					],
					'deliver_type' => [
						'label' => sprintf( '<span class="lp-icon-bookmark-o"></span> %s', __( 'Deliver type', 'learnpress' ) ),
						'value' => $this->singleCourseTemplate->html_deliver_type( $course )
					],
					'capacity'     => [
						'label' => sprintf( '<span class="lp-icon-students"></span> %s', __( 'Capacity', 'learnpress' ) ),
						'value' => $this->singleCourseTemplate->html_capacity( $course )
					],
					'level'        => [
						'label' => sprintf( '<span class="lp-icon-signal"></span> %s', __( 'Level', 'learnpress' ) ),
						'value' => $this->singleCourseTemplate->html_deliver_type( $course )
					],
					'duration'     => [
						'label' => sprintf( '<span class="lp-icon-clock-o"></span> %s', __( 'Duration', 'learnpress' ) ),
						'value' => $this->singleCourseTemplate->html_duration( $course )
					],
					'lessons'      => [
						'label' => sprintf( '<span class="lp-icon-copy"></span> %s', __( 'Lessons', 'learnpress' ) ),
						'value' => $this->singleCourseTemplate->html_deliver_type( $course )
					],
				],
				$course,
				$user
			);

		$html_info_two_items = '';
		foreach ( $data_info_meta as $info_meta ) {
			$label               = $info_meta['label'];
			$value               = $info_meta['value'];
			$html_info_two_item  = sprintf(
				'<div class="info-meta-item">
					<span class="info-meta-left">%s</span>
					<span class="info-meta-right">%s</span>
				</div>',
				$label,
				$value
			);
			$html_info_two_items .= $html_info_two_item;
		}

		$section_buttons = [
			'wrapper_buttons_start' => '<div class="course-buttons">',
			'btn_contact'           => $this->singleCourseTemplate->html_btn_external( $course ),
			'btn_buy'               => $this->singleCourseTemplate->html_btn_purchase_course( $course, $user ),
			'wrapper_buttons_end'   => '</div>',
		];
		$html_buttons    = Template::combine_components( $section_buttons );

		$section_info_two = [
			'wrapper_section_info_two_start' => '<div class="info-metas">',
			'items'                          => $html_info_two_items,
			'buttons'                        => $html_buttons,
			'wrapper_section_info_two_end'   => '</div>',
		];
		$html_info_two    = Template::combine_components( $section_info_two );
		// End info two
		$section_right      = [
			'wrapper_section_right_start' => '<div class="lp-single-offline-course__right">',
			'info_two'                    => $html_info_two,
			'featured_review'             => $this->singleCourseTemplate->html_feature_review( $course ),
			'sidebar'                     => $this->singleCourseTemplate->html_sidebar( $course ),
			'wrapper_section_right_end'   => '</div>',
		];
		$html_section_right = Template::combine_components( $section_right );
		// End section right

		// Related courses
		ob_start();
		do_action( 'learn-press/single-course/courses-related/layout', $course, 4 );
		$html_courses_related = ob_get_clean();
		// End related courses

		$sections = [
			'wrapper_section_offline_course_start'      => '<div class="lp-single-offline-course">',
			'wrapper_section_offline_course_main_start' => '<div class="lp-single-offline-course-main">',
			'section_left'                              => $html_section_left,
			'section_right'                             => $html_section_right,
			'wrapper_section_offline_course_main_end'   => '</div>',
			'related_courses'                           => $html_courses_related,
			'wrapper_section_offline_course_end'        => '</div>',
		];

		echo Template::combine_components( $sections );
	}
}
