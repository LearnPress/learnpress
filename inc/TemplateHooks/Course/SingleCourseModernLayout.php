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

		$sections = apply_filters(
			'learn-press/single-course/model/sections',
			[
				'wrapper'               => '<div class="lp-single-course">',
				'section_header'        => $this->header_sections( $course, $user ),
				'wrapper_container'     => '<div class="lp-content-area">',
				'wrapper_main'          => '<div class="lp-single-course-main">',
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

		$html_instructor = $this->singleCourseTemplate->html_instructor( $course );

		$section_info_one = apply_filters(
			'learn-press/single-course/model/header/info-meta',
			[
				'wrapper'     => '<div class="lp-single-course-info-one">',
				'last_update' => sprintf( '<div class="item-meta">%s: %s</div>', esc_html__( 'Last updated', 'learnpress' ), esc_attr( get_post_modified_time( get_option( 'date_format' ), true ) ) ),
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);

		$header_sections = apply_filters(
			'learn-press/single-course/model/header/sections',
			[
				'wrapper_header'              => '<div class="lp-single-course__header">',
				'wrapper_container'           => '<div class="lp-single-course__header__inner">',
				'breadcrumb'                  => $html_breadcrumb,
				'title'                       => $this->singleCourseTemplate->html_title( $course, 'h1' ),
				'wrapper_instructor_cate'     => '<div class="course-instructor-category">',
				'instructor'                  => sprintf(
					'<div>%s %s</div>',
					sprintf( '<label>%s</label>', __( 'by', 'learnpress' ) ),
					$html_instructor
				),
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

	public function section_left( $course, $user ): string {

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

		$section_left = apply_filters(
			'learn-press/single-course/model/section_left',
			[
				'wrapper'      => '<div class="lp-single-course-main__left">',
				'description'  => $this->singleCourseTemplate->html_description( $course ),
				'features'     => $this->singleCourseTemplate->html_features( $course ),
				'target'       => $this->singleCourseTemplate->html_target( $course ),
				'requirements' => $this->singleCourseTemplate->html_requirements( $course ),
				'curriculum'   => $this->singleCourseTemplate->html_curriculum( $course, $user ),
				'material'     => $this->singleCourseTemplate->html_material( $course ),
				'faqs'         => $this->singleCourseTemplate->html_faqs( $course ),
				'instructor'   => $html_instructor,
				'wrapper_end'  => '</div>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section_left );
	}

	public function section_right( $course, $user ): string {
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

		$data_info_meta = apply_filters( 'learn-press/single-course/model/info-meta', $data_info_meta, $course, $user );
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

		$section_info_two = apply_filters(
			'learn-press/single-course/model/section-right/info-meta',
			[
				'wrapper'     => '<div class="info-metas">',
				'meta'        => $html_info_meta,
				'wrapper_end' => '</div>',
			],
			$course,
			$user
		);

		$user_id = 0;
		if ( $user instanceof UserModel ) {
			$user_id = $user->get_id();
		}
		$userCourseModel         = UserCourseModel::find( $user_id, $course->get_id(), true );
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

		$section_right = apply_filters(
			'learn-press/single-course/model/section_right',
			[
				'wrapper'           => '<div class="lp-single-course-main__right">',
				'wrapper_inner'     => '<div class="lp-single-course-main__right__inner">',
				'image'             => $this->singleCourseTemplate->html_image( $course ),
				'price'             => $this->singleCourseTemplate->html_price( $course ),
				//'sale_discount'       => $this->singleCourseTemplate->html_sale_discount( $course ), to do
				'info_two'          => Template::combine_components( $section_info_two ),
				'buttons'           => Template::combine_components( $section_buttons ),
				'share'             => $this->html_share( $course ),
				'featured_review'   => $this->singleCourseTemplate->html_feature_review( $course ),
				'sidebar'           => $this->singleCourseTemplate->html_sidebar( $course ),
				'wrapper_inner_end' => '</div>',
				'wrapper_end'       => '</div>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section_right );
	}

	public function html_share( $course ): string {

		$list_socials = [
			'facebook'  => esc_html__( 'Facebook', 'learnpress' ),
			'twitter'   => esc_html__( 'Twitter', 'learnpress' ),
			'pinterest' => esc_html__( 'Pinterest', 'learnpress' ),
			'linkedin'  => esc_html__( 'Linkedin', 'learnpress' ),
		];

		ob_start();
		if ( $list_socials ) {
			foreach ( $list_socials as $key => $social ) {
				switch ( $key ) {
					case 'facebook':
						$link_share = 'https://www.facebook.com/sharer.php?u=' . urlencode( get_permalink() );
						$icon       = '<i class="lp-icon-facebook"></i>';
						break;
					case 'twitter':
						$link_share = 'https://twitter.com/share?url=' . urlencode( get_permalink() ) . '&amp;text=' . rawurlencode( esc_attr( get_the_title() ) );
						$icon       = '<i class="lp-icon-twitter"></i>';
						break;
					case 'pinterest':
						$link_share = 'https://pinterest.com/pin/create/button/?url=' . urlencode( get_permalink() ) . '&amp;description=' . rawurlencode( esc_attr( get_the_excerpt() ) ) . '&amp;media=' . urlencode( wp_get_attachment_url( get_post_thumbnail_id() ) ) . ' onclick="window.open(this.href); return false;"';
						$icon       = '<i class="lp-icon-pinterest-p"></i>';
						break;
					case 'linkedin':
						$link_share = 'https://www.linkedin.com/shareArticle?mini=true&url=' . urlencode( get_permalink() ) . '&title=' . rawurlencode( esc_attr( get_the_title() ) ) . '&summary=&source=' . rawurlencode( esc_attr( get_the_excerpt() ) );
						$icon       = '<i class="lp-icon-linkedin"></i>';
						break;
					default:
						$link_share = '';
						$icon       = '';
						break;
				}
				echo sprintf( '<li><a target="_blank" href="%s" title="%s">%s<span>%s</span></a></li>', $link_share, $social, $icon, $social );
			}
		}

		$html_social = ob_get_clean();

		$social_media = apply_filters(
			'learn-press/single-course/social-share',
			[
				'wrapper'     => '<ul class="lp-social-media">',
				'content'     => $html_social,
				'wrapper_end' => '</ul>',
			]
		);

		$clipboard = [
			'wrapper'     => '<div class="clipboard-post">',
			'input'       => sprintf( '<input class="clipboard-value" type="text" value="%s">', get_permalink() ),
			'button'      => sprintf(
				'<button class="btn-clipboard" data-copied="%s">%s<span class="tooltip">%s</span></button>',
				esc_html__( 'Copied!', 'learnpress' ),
				esc_html__( 'Copy', 'learnpress' ),
				esc_html__( 'Copy to Clipboard', 'learnpress' )
			),
			'wrapper_end' => '</div>',
		];

		$section_share = apply_filters(
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

		return Template::combine_components( $section_share );
	}
}
