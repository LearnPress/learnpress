<?php
/**
 * Template hooks Single Course Offline.
 *
 * @since 4.2.7
 * @version 1.0.1
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

		$user = UserModel::find( get_current_user_id(), true );

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
				'learn-press/single-course/offline/section-instructor/right',
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
				'learn-press/single-course/offline/section-instructor',
				[
					'wrapper'          => '<div class="lp-section-instructor">',
					'header'           => sprintf( '<h3 class="section-title">%s</h3>', __( 'Instructor', 'learnpress' ) ),
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
		// End instructor

		// Info one
		$html_address     = $this->html_address( $course );
		$section_info_one = apply_filters(
			'learn-press/single-course/offline/info-bar',
			[
				'wrapper'     => '<div class="lp-single-course-offline-info-one">',
				'author'      => sprintf( '<div class="item-meta">%s</div>', $html_author ),
				'address'     => ! empty( $html_address ) ? sprintf( '<div class="item-meta">%s</div>', $html_address ) : '',
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);

		// Section left
		$section_left = apply_filters(
			'learn-press/single-course/offline/section-left',
			[
				'wrapper'                => '<div class="lp-single-offline-course__left">',
				'breadcrumb'             => $html_breadcrumb,
				'title'                  => $this->singleCourseTemplate->html_title( $course, 'h1' ),
				'info_one'               => Template::combine_components( $section_info_one ),
				'image'                  => $this->singleCourseTemplate->html_image( $course, [ 'size' => 'full' ] ),
				'info_main_mobile'       => $this->render_html_info_main( $course, $user, [ 'lp_display_on' => 'lp-is-mobile' ] ),
				'description'            => $this->singleCourseTemplate->html_description( $course ),
				'features'               => $this->singleCourseTemplate->html_features( $course ),
				'target'                 => $this->singleCourseTemplate->html_target( $course ),
				'requirements'           => $this->singleCourseTemplate->html_requirements( $course ),
				'material'               => $this->singleCourseTemplate->html_material( $course ),
				'faqs'                   => $this->singleCourseTemplate->html_faqs( $course ),
				'instructor'             => $html_instructor,
				'featured_review_mobile' => $this->singleCourseTemplate->html_feature_review( $course, $user, [ 'lp_display_on' => 'lp-is-mobile' ] ),
				'wrapper_end'            => '</div>',
			],
			$course,
			$user
		);

		// Section right
		$section_right = apply_filters(
			'learn-press/single-course/offline/section-right',
			[
				'wrapper'           => '<div class="lp-single-offline-course__right">',
				'wrapper_inner'     => '<div class="lp-single-offline-course__right__sticky">',
				'info_main'         => $this->render_html_info_main( $course, $user, [ 'lp_display_on' => 'lp-is-pc' ] ),
				'featured_review'   => $this->singleCourseTemplate->html_feature_review( $course, $user, [ 'lp_display_on' => 'lp-is-pc' ] ),
				'sidebar'           => $this->singleCourseTemplate->html_sidebar( $course ),
				'wrapper_inner_end' => '</div>',
				'wrapper_end'       => '</div>',
			],
			$course,
			$user
		);
		// End section right

		// Related courses
		ob_start();
		do_action( 'learn-press/single-course/courses-related/layout', $course, 4 );
		$html_courses_related = ob_get_clean();
		// End related courses

		$sections = apply_filters(
			'learn-press/single-course/offline/sections',
			[
				'wrapper_container'     => '<div class="lp-content-area">',
				'wrapper'               => '<div class="lp-single-course lp-single-offline-course">',
				'wrapper_main'          => '<div class="lp-single-offline-course-main">',
				'section_left'          => Template::combine_components( $section_left ),
				'section_right'         => Template::combine_components( $section_right ),
				'wrapper_main_end'      => '</div>',
				'related_courses'       => $html_courses_related,
				'wrapper_end'           => '</div>',
				'wrapper_container_end' => '</div>',
			]
		);

		echo Template::combine_components( $sections );
	}

	/**
	 * Render html info main
	 * Price, deliver type, capacity, level, duration, lessons
	 * Buttons: Contact, Buy, Enroll
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.1
	 */
	public function render_html_info_main( CourseModel $courseModel, $userModel, array $data = [] ): string {
		// Info two
		$data_info_meta = [
			'price'        => [
				'label' => sprintf( '<span class="currency">%s</span> %s', learn_press_get_currency_symbol(), __( 'Price', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_price( $courseModel ),
			],
			'deliver_type' => [
				'label' => sprintf( '<span class="lp-icon-bookmark-o"></span> %s', __( 'Delivery type', 'learnpress' ) ),
				'value' => $this->html_deliver_type( $courseModel ),
			],
			'capacity'     => [
				'label' => sprintf( '<span class="lp-icon-students"></span> %s', __( 'Capacity', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_capacity( $courseModel ),
			],
			'level'        => [
				'label' => sprintf( '<span class="lp-icon-signal"></span> %s', __( 'Level', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_level( $courseModel ),
			],
			'duration'     => [
				'label' => sprintf( '<span class="lp-icon-clock-o"></span> %s', __( 'Duration', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_duration( $courseModel ),
			],
		];

		$html_lesson = $this->html_lesson_info( $courseModel );
		if ( ! empty( $html_lesson ) ) {
			$data_info_meta['lessons'] = [
				'label' => sprintf( '<span class="lp-icon-copy"></span> %s', __( 'Lessons', 'learnpress' ) ),
				'value' => $html_lesson,
			];
		}

		$data_info_meta = apply_filters( 'learn-press/single-course/offline/info-meta', $data_info_meta, $courseModel, $userModel );
		$html_info_meta = '';
		if ( ! empty( $data_info_meta ) ) {
			foreach ( $data_info_meta as $info_meta ) {
				$label              = $info_meta['label'];
				$value              = $info_meta['value'];
				$html_info_two_item = sprintf(
					'<div class="info-meta-item">
						<span class="info-meta-left">%s</span>
						<span class="info-meta-right">%s</span>
					</div>',
					$label,
					$value
				);
				$html_info_meta    .= $html_info_two_item;
			}
		}

		$section_buttons = apply_filters(
			'learn-press/single-course/offline/section-right/info-meta/buttons',
			[
				'wrapper'     => '<div class="course-buttons">',
				'btn_contact' => $this->singleCourseTemplate->html_btn_external( $courseModel, $userModel ),
				'btn_buy'     => $this->singleCourseTemplate->html_btn_purchase_course( $courseModel, $userModel ),
				'btn_enroll'  => $this->singleCourseTemplate->html_btn_enroll_course( $courseModel, $userModel ),
				'wrapper_end' => '</div>',
			],
			$courseModel,
			$userModel
		);
		$html_buttons    = Template::combine_components( $section_buttons );

		$section = apply_filters(
			'learn-press/single-course/offline/section-right/info-meta',
			[
				'wrapper'     => sprintf(
					'<div class="info-metas %s">',
					$data['lp_display_on'] ?? ''
				),
				'meta'        => $html_info_meta,
				'buttons'     => $html_buttons,
				'wrapper_end' => '</div>',
			],
			$courseModel,
			$userModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * Html lesson info
	 *
	 * @param CourseModel $course
	 * @param bool $show_label
	 *
	 * @return string
	 */
	public function html_lesson_info( CourseModel $course, bool $show_label = false ): string {
		if ( ! $course->is_offline() ) {
			return '';
		}

		$lesson_count = $course->get_meta_value_by_key( CoursePostModel::META_KEY_OFFLINE_LESSON_COUNT, 10 );

		if ( ! $lesson_count ) {
			return '';
		}

		$html = sprintf(
			'<span class="course-count-lesson">%s %s</span>',
			$lesson_count,
			$show_label ? __( 'lessons', 'learnpress' ) : ''
		);

		return $html;
	}

	/**
	 * Get html address of course offline
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function html_address( CourseModel $course ): string {
		$content = '';

		try {
			if ( ! $course->is_offline() ) {
				return $content;
			}

			$address = $course->get_meta_value_by_key( CoursePostModel::META_KEY_ADDRESS, '' );
			if ( empty( $address ) ) {
				return $content;
			}

			$html_wrapper = [
				'<span class="course-address">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $address );
			apply_filters( 'learn-press/single-course/html-address', $content, $course );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get deliver type
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function html_deliver_type( CourseModel $course ): string {
		$content = '';

		if ( ! $course->is_offline() ) {
			return $content;
		}

		$html_wrapper = [
			'<span class="course-deliver-type">' => '</span>',
		];

		$deliver_type_options = Config::instance()->get( 'course-deliver-type' );
		$key                  = $course->get_meta_value_by_key( CoursePostModel::META_KEY_DELIVER, 'private_1_1' );
		$content              = $deliver_type_options[ $key ] ?? '';

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}
}
