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
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;

class SingleCourseModernLayout {
	use Singleton;

	/**
	 * @var SingleCourseTemplate
	 */
	public $singleCourseTemplate;

	public function init() {
		$this->singleCourseTemplate = SingleCourseTemplate::instance();

		add_action(
			'learn-press/single-course/layout',
			[ $this, 'course_model_layout' ]
		);
	}

	/**
	 * Single course layout model
	 *
	 * @param $course
	 *
	 * @return void
	 */
	public function course_model_layout( $course ) {
		if ( ! $course instanceof CourseModel ) {
			return;
		}

		$user = UserModel::find( get_current_user_id(), true );

		// Related courses
		ob_start();
		do_action( 'learn-press/single-course/courses-related/layout', $course, 4 );
		$html_courses_related = ob_get_clean();

		// Global message
		ob_start();
		learn_press_show_message();
		$global_message = ob_get_clean();

		$sections = apply_filters(
			'learn-press/single-course/modern/sections',
			[
				'wrapper'               => '<div class="lp-single-course">',
				'section_header'        => $this->header_sections( $course, $user ),
				'wrapper_container'     => '<div class="lp-content-area">',
				'wrapper_main'          => '<div class="lp-single-course-main">',
				'global_message'        => $global_message,
				'section_left'          => $this->section_left( $course, $user ),
				'section_right'         => $this->section_right( $course, $user ),
				'wrapper_main_end'      => '</div>',
				'related_courses'       => $html_courses_related,
				'wrapper_container_end' => '</div>',
				'wrapper_end'           => '</div>',
			]
		);

		echo Template::combine_components( $sections );
	}

	/**
	 * HTML header section
	 *
	 * @param $course
	 * @param $user
	 *
	 * @return string
	 */
	public function header_sections( $course, $user ): string {
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

		$section_info_one = apply_filters(
			'learn-press/single-course/modern/header/info-meta',
			[
				'wrapper'     => '<div class="lp-single-course-info-one">',
				'author'      => '',
				'last_update' => sprintf(
					'<div class="item-meta">%s: %s</div>',
					esc_html__( 'Last updated', 'learnpress' ),
					esc_attr( get_post_modified_time( get_option( 'date_format' ), true ) )
				),
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);
		if ( ! has_filter( 'learn-press/single-course/modern/header/info-meta' ) ) {
			// Do not use this hook, this hook only for handle hook without update from Addon, when handle on Addon, will remove this hook
			$section_info_one = apply_filters(
				'learn-press/single-course/offline/info-bar',
				$section_info_one,
				$course,
				$user
			);
		}

		$html_instructor = sprintf(
			'<div>%s %s</div>',
			sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
			$this->singleCourseTemplate->html_instructor( $course )
		);

		$header_sections = apply_filters(
			'learn-press/single-course/modern/header/sections',
			[
				'wrapper_header'              => '<div class="lp-single-course__header">',
				'wrapper_container'           => '<div class="lp-single-course__header__inner">',
				'breadcrumb'                  => $html_breadcrumb,
				'title'                       => $this->singleCourseTemplate->html_title( $course, 'h1' ),
				'wrapper_instructor_cate'     => '<div class="course-instructor-category">',
				'instructor'                  => $html_instructor,
				'category'                    => $html_categories,
				'wrapper_instructor_cate_end' => '</div>',
				'info_one'                    => Template::combine_components( $section_info_one ),
				'wrapper_container_end'       => '</div>',
				'wrapper_header_end'          => '</div>',
			],
			$course,
			$user
		);

		return Template::combine_components( $header_sections );
	}

	/**
	 * HTML left section
	 *
	 * @param $course
	 * @param $user
	 *
	 * @return string
	 */
	public function section_left( $course, $user ): string {
		$section = apply_filters(
			'learn-press/single-course/modern/section_left',
			[
				'wrapper'                => '<div class="lp-single-course-main__left">',
				'description'            => $this->singleCourseTemplate->html_description( $course ),
				'features'               => $this->singleCourseTemplate->html_features( $course ),
				'target'                 => $this->singleCourseTemplate->html_target( $course ),
				'requirements'           => $this->singleCourseTemplate->html_requirements( $course ),
				'curriculum'             => $this->singleCourseTemplate->html_curriculum( $course, $user ),
				'material'               => $this->singleCourseTemplate->html_material( $course, $user ),
				'faqs'                   => $this->singleCourseTemplate->html_faqs( $course ),
				'instructor'             => $this->html_instructor_info( $course, $user ),
				'featured_review_mobile' => $this->singleCourseTemplate->html_feature_review( $course, $user, [ 'lp_display_on' => 'lp-is-mobile' ] ),
				'comment'                => $this->singleCourseTemplate->html_comment( $course, $user ),
				'sidebar_mobile'         => $this->singleCourseTemplate->html_sidebar( $course, [ 'lp_display_on' => 'lp-is-mobile' ] ),
				'wrapper_end'            => '</div>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML right section
	 *
	 * @param $course
	 * @param $user
	 *
	 * @return string
	 */
	public function section_right( $course, $user ): string {
		$user_id = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		}
		$userCourseModel = UserCourseModel::find( $user_id, $course->get_id(), true );

		$data_info_meta = [
			'student'  => [
				'label' => sprintf( '<i class="lp-icon-user-graduate"></i>%s:', __( 'Student', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_count_student( $course ),
			],
			'lesson'   => [
				'label' => sprintf( '<i class="lp-icon-file-o"></i>%s:', __( 'Lesson', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_count_item( $course, LP_LESSON_CPT ),
			],
			'duration' => [
				'label' => sprintf( '<i class="lp-icon-clock-o"></i>%s:', __( 'Duration', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_duration( $course ),
			],
			'quiz'     => [
				'label' => sprintf( '<i class="lp-icon-puzzle-piece"></i>%s:', __( 'Quiz', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_count_item( $course, LP_QUIZ_CPT ),
			],
			'level'    => [
				'label' => sprintf( '<i class="lp-icon-signal"></i>%s:', __( 'Level', 'learnpress' ) ),
				'value' => $this->singleCourseTemplate->html_level( $course ),
			],
		];

		$data_info_meta = apply_filters( 'learn-press/single-course/modern/info-meta', $data_info_meta, $course, $user );
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

		$section_info_meta = apply_filters(
			'learn-press/single-course/modern/section-right/info-meta',
			[
				'wrapper'     => '<div class="info-metas">',
				'featured'    => $this->singleCourseTemplate->html_featured( $course ),
				'meta'        => $html_info_meta,
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);

		$html_price = '';
		if ( ! $userCourseModel || $user_id === 0
			|| $userCourseModel->get_status() === UserItemModel::STATUS_CANCEL ) {
			$html_price = $this->singleCourseTemplate->html_price( $course );
		}

		$section = apply_filters(
			'learn-press/single-course/modern/section_right',
			[
				'wrapper'           => '<div class="lp-single-course-main__right">',
				'wrapper_inner'     => '<div class="lp-single-course-main__right__inner">',
				'image'             => $this->singleCourseTemplate->html_image( $course ),
				'price'             => $html_price,
				'info_learning'     => $this->html_info_learning( $course, $user ),
				//'sale_discount'       => $this->singleCourseTemplate->html_sale_discount( $course ), to do
				'metas'             => Template::combine_components( $section_info_meta ),
				'buttons'           => $this->html_buttons( $course, $user ),
				'share'             => $this->html_share( $course ),
				'featured_review'   => $this->singleCourseTemplate->html_feature_review( $course, $user, [ 'lp_display_on' => 'lp-is-pc' ] ),
				'sidebar'           => $this->singleCourseTemplate->html_sidebar( $course, [ 'lp_display_on' => 'lp-is-pc' ] ),
				'wrapper_inner_end' => '</div>',
				'wrapper_end'       => '</div>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML share social.
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 */
	public function html_share( CourseModel $courseModel ): string {
		$list_socials = apply_filters(
			'learn-press/single-course/social-share',
			[
				'facebook'  => [
					'label' => esc_html__( 'Facebook', 'learnpress' ),
					'icon'  => '<i class="lp-icon-facebook"></i>',
					'url'   => sprintf(
						'https://www.facebook.com/sharer.php?u=%s',
						urlencode( $courseModel->get_permalink() )
					),
				],
				'twitter'   => [
					'label' => esc_html__( 'Twitter', 'learnpress' ),
					'icon'  => '<i class="lp-icon-twitter"></i>',
					'url'   => sprintf(
						'https://twitter.com/share?url=%1$s&amp;text=%2$s',
						urlencode( $courseModel->get_permalink() ),
						rawurlencode( esc_attr( $courseModel->get_title() ) )
					),
				],
				'pinterest' => [
					'label' => esc_html__( 'Pinterest', 'learnpress' ),
					'icon'  => '<i class="lp-icon-pinterest-p"></i>',
					'url'   => sprintf(
						'https://pinterest.com/pin/create/button/?url=%1$s&amp;description=%2$s&amp;media=%3$s',
						urlencode( $courseModel->get_permalink() ),
						rawurlencode( esc_attr( $courseModel->get_short_description() ) ),
						urlencode( $courseModel->get_image_url() )
					),
				],
				'linkedin'  => [
					'label' => esc_html__( 'Linkedin', 'learnpress' ),
					'icon'  => '<i class="lp-icon-linkedin"></i>',
					'url'   => sprintf(
						'https://www.linkedin.com/shareArticle?mini=true&url=%1$s&title=%2$s&summary=&source=%3$s',
						urlencode( $courseModel->get_permalink() ),
						esc_attr( $courseModel->get_title() ),
						esc_attr( $courseModel->get_short_description() )
					),
				],
			]
		);

		$html_social = '';
		if ( ! empty( $list_socials ) ) {
			foreach ( $list_socials as $key => $social ) {
				$html_social .= sprintf(
					'<li>
						<a target="_blank" href="%s" title="%s">%s<span>%s</span></a>
					</li>',
					$social['url'],
					$social['label'],
					$social['icon'],
					$social['label']
				);
			}
		} else {
			return '';
		}

		$social_media = apply_filters(
			'learn-press/single-course/social-share/ul',
			[
				'wrapper'     => '<ul class="lp-social-media">',
				'content'     => $html_social,
				'wrapper_end' => '</ul>',
			]
		);

		$clipboard = [
			'wrapper'     => '<div class="clipboard-post">',
			'input'       => sprintf( '<input class="clipboard-value" type="text" value="%s">', $courseModel->get_permalink() ),
			'button'      => sprintf(
				'<button class="btn-clipboard" data-copied="%s">%s<span class="tooltip">%s</span></button>',
				esc_html__( 'Copied!', 'learnpress' ),
				esc_html__( 'Copy', 'learnpress' ),
				esc_html__( 'Copy to Clipboard', 'learnpress' )
			),
			'wrapper_end' => '</div>',
		];

		$section = apply_filters(
			'learn-press/single-course/social-share/sections',
			[
				'wrapper'                   => '<div class="social-swapper social-share-toggle">',
				'toggle'                    => '<div class="share-toggle-icon">',
				'toggle_icon'               => sprintf( '<i class="lp-icon-share-alt"></i><label class="share-label">%s</label>', __( 'Share', 'learnpress' ) ),
				'toggle_end'                => '</div>',
				'wrapper_content'           => '<div class="wrapper-content-widget">',
				'wrapper_content_inner'     => '<div class="content-widget-social-share">',
				'clipboard'                 => Template::combine_components( $clipboard ),
				'social'                    => Template::combine_components( $social_media ),
				'wrapper_content_inner_end' => '</div>',
				'wrapper_content_end'       => '</div>',
				'wrapper_end'               => '</div>',
			]
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get html instructor info
	 *
	 * @param CourseModel $course
	 * @param UserModel|false $user
	 *
	 * @return string
	 * @since 4.2.8.3
	 * @version 1.0.0
	 */
	public function html_instructor_info( CourseModel $course, $user ): string {
		$html_instructor          = '';
		$singleInstructorTemplate = SingleInstructorTemplate::instance();
		$author                   = $course->get_author_model();

		if ( ! $author ) {
			return $html_instructor;
		}

		$html_instructor_image    = sprintf(
			'<a href="%s" title="%s">%s</a>',
			$author->get_url_instructor(),
			$author->get_display_name(),
			$singleInstructorTemplate->html_avatar( $author )
		);
		$section_instructor_meta  = [
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
		$section_instructor_right = apply_filters(
			'learn-press/single-course/modern/section-instructor/right',
			[
				'wrapper'     => '<div class="lp-section-instructor">',
				'name'        => sprintf(
					'<a href="%s">%s</a>',
					$author->get_url_instructor(),
					$singleInstructorTemplate->html_display_name( $author )
				),
				'meta'        => Template::combine_components( $section_instructor_meta ),
				'description' => $singleInstructorTemplate->html_description( $author ),
				'social'      => $singleInstructorTemplate->html_social( $author ),
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);
		$section_instructor       = apply_filters(
			'learn-press/single-course/modern/section-instructor',
			[
				'wrapper'          => '<div class="lp-section-instructor">',
				'header'           => sprintf( '<h3 class="section-title">%s</h3>', __( 'Instructor', 'learnpress' ) ),
				'wrapper_info'     => '<div class="lp-instructor-info">',
				'image'            => $html_instructor_image,
				'instructor_right' => Template::combine_components( $section_instructor_right ),
				'wrapper_info_end' => '</div>',
				'wrapper_end'      => '</div>',
			],
			$course,
			$user
		);

		if ( ! has_filter( 'learn-press/single-course/modern/section-instructor' ) ) {
			// Do not use this hook, this hook only for handle hook without update from Addon, when handle on Addon, will remove this hook
			$section_instructor = apply_filters( 'learn-press/single-course/offline/section-instructor', $section_instructor, $course, $user );
		}

		return Template::combine_components( $section_instructor );
	}

	/**
	 * Get html course info learning
	 *
	 * @param CourseModel $course
	 * @param UserModel|false $user
	 *
	 * @return string
	 * @since 4.2.8.3
	 * @version 1.0.1
	 */
	public function html_info_learning( CourseModel $course, $user = false ): string {
		$html_info_learning = '';
		$user_id            = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		} else {
			return $html_info_learning;
		}

		$userCourseModel = UserCourseModel::find( $user_id, $course->get_id(), true );
		if ( $userCourseModel instanceof UserCourseModel
			&& $userCourseModel->get_status() !== UserItemModel::STATUS_CANCEL
			&& $userCourseModel->get_status() !== UserCourseModel::STATUS_PURCHASED ) {
			$userCourseTemplate = UserCourseTemplate::instance();
			$html_end_date      = '';
			$html_graduation    = '';

			if ( $userCourseModel->has_finished() ) {
				$html_end_date   = sprintf(
					'<div>%s: %s</div>',
					__( 'End date', 'learnpress' ),
					$userCourseTemplate->html_end_date_time( $userCourseModel, false )
				);
				$html_graduation = $userCourseTemplate->html_graduation( $userCourseModel );
			}

			$section_info_learning = [
				'wrapper'               => '<div class="info-learning">',
				'message_lock'          => $userCourseTemplate->html_message_lock( $userCourseModel ),
				'graduation'            => $html_graduation,
				'progress'              => $userCourseTemplate->html_progress( $userCourseModel ),
				'start_date'            => sprintf(
					'<div>%s: %s</div>',
					__( 'Start date', 'learnpress' ),
					$userCourseTemplate->html_start_date_time( $userCourseModel, false )
				),
				'end_date'              => $html_end_date,
				'count_items_completed' => $userCourseTemplate->html_count_items_completed( $userCourseModel ),
				'wrapper_end'           => '</div>',
			];

			$html_info_learning = Template::combine_components( $section_info_learning );
		}

		return $html_info_learning;
	}

	/**
	 * Get html button
	 *
	 * @param CourseModel $course
	 * @param UserModel|false $user
	 *
	 * @return string
	 * @since 4.2.8.3
	 * @version 1.0.1
	 */
	public function html_buttons( CourseModel $course, $user = false ): string {
		$user_id = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		}

		$userCourseModel         = UserCourseModel::find( $user_id, $course->get_id(), true );
		$btn_continue_and_finish = [];
		if ( $userCourseModel instanceof UserCourseModel ) {
			$userCourseTemplate      = UserCourseTemplate::instance();
			$btn_continue_and_finish = [
				'btn_continue' => $userCourseTemplate->html_btn_continue( $userCourseModel ),
				'btn_finish'   => $userCourseTemplate->html_btn_finish( $userCourseModel ),
				'btn_retake'   => $userCourseTemplate->html_btn_retake( $userCourseModel ),
			];
		}

		$section_buttons = apply_filters(
			'learn-press/single-course/modern/section-right/buttons',
			[
				'wrapper'      => '<div class="course-buttons">',
				'btn_contact'  => SingleCourseTemplate::instance()->html_btn_external( $course, $user ),
				'btn_buy'      => SingleCourseTemplate::instance()->html_btn_purchase_course( $course, $user ),
				'btn_enroll'   => SingleCourseTemplate::instance()->html_btn_enroll_course( $course, $user ),
				'btn_learning' => Template::combine_components( $btn_continue_and_finish ),
				'wrapper_end'  => '</div>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section_buttons );
	}
}
