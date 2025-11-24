<?php
/**
 * Template hooks Single Course.
 *
 * @since 4.2.3
 * @version 1.0.5
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\LessonPostModel;
use LearnPress\Models\PostModel;
use LearnPress\Models\QuizPostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\TemplateAJAX;
use LearnPress\TemplateHooks\UserTemplate;
use LearnPressAssignment\Models\AssignmentPostModel;
use LP_Checkout;
use LP_Course;
use LP_Course_Item;
use LP_Datetime;
use LP_Debug;
use LP_Global;
use LP_Material_Files_DB;
use LP_Settings;
use stdClass;
use Throwable;

class SingleCourseTemplate {
	use Singleton;

	/**
	 * @var false|LessonPostModel|QuizPostModel|PostModel|mixed $currentItemModel
	 */
	public $currentItemModel = false;

	public function init() {
		add_filter( 'lp/rest/ajax/allow_callback', [ $this, 'allow_callback' ] );
	}

	/**
	 * Allow callback for AJAX.
	 * @use self::render_html_comments
	 * @param array $callbacks
	 *
	 * @return array
	 */
	public function allow_callback( array $callbacks ): array {
		$callbacks[] = get_class( $this ) . ':render_html_comments';

		return $callbacks;
	}

	/**
	 * Get display title course.
	 *
	 * @param LP_Course|CourseModel $course
	 * @param string $tag_html
	 *
	 * @return string
	 */
	public function html_title( $course, string $tag_html = 'span' ): string {
		$tag_html     = esc_attr( sanitize_key( $tag_html ) );
		$html_wrapper = apply_filters(
			'learn-press/single-course/html-title',
			[
				"<{$tag_html} class='course-title'>" => "</{$tag_html}>",
			],
			$course
		);

		return Template::instance()->nest_elements( $html_wrapper, $course->get_title() );
	}

	/**
	 * Get short description course.
	 *
	 * @param LP_Course|CourseModel $course
	 * @param int $number_words
	 *
	 * @return string
	 */
	public function html_short_description( $course, int $number_words = 0 ): string {
		$html_wrapper = [
			'<p class="course-short-description">' => '</p>',
		];

		if ( $course instanceof LP_Course ) {
			$course = CourseModel::find( $course->get_id(), true );
		}

		$short_description = $course->get_short_description();
		if ( empty( $short_description ) ) {
			return '';
		}

		if ( $number_words > 0 ) {
			$short_description = wp_trim_words( $short_description, $number_words, '...' );
		}

		return Template::instance()->nest_elements( $html_wrapper, $short_description );
	}

	/**
	 * Get description course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 */
	public function html_description( $course ): string {
		$content = '';

		if ( $course instanceof LP_Course ) {
			$content = $course->get_data( 'description' );
		} elseif ( $course instanceof CourseModel ) {
			$content = $course->get_description();
		}

		$section = apply_filters(
			'learn-press/course/html-description',
			[
				'wrapper'     => '<div class="lp-course-description">',
				'content'     => $content,
				'wrapper_end' => '</div>',
			],
			$course
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get display categories course.
	 *
	 * @param LP_Course|CourseModel $course
	 * @param array $setting
	 *
	 * @return string
	 * @since 4.2.6
	 * @version 1.0.3
	 */
	public function html_categories( $course, array $setting = [] ): string {
		$html = '';

		try {
			if ( $course instanceof LP_Course ) {
				$course = CourseModel::find( $course->get_id(), true );
			}

			if ( empty( $course ) ) {
				return '';
			}

			$cats = $course->get_categories();
			if ( empty( $cats ) ) {
				return '';
			}

			$is_link          = $setting['is_link'] ?? true;
			$attribute_target = ! empty( $setting['new_tab'] ) ? 'target="_blank"' : '';
			$cat_names        = [];
			foreach ( $cats as $cat ) {
				if ( $is_link ) {
					$term = sprintf(
						'<a href="%s" %s>%s</a>',
						get_term_link( $cat ),
						$attribute_target,
						$cat->name
					);
				} else {
					$term = $cat->name;
				}

				$cat_names[] = $term;
			}

			$content = implode( ', ', $cat_names );

			$section = apply_filters(
				'learn-press/course/html-categories',
				[
					'wrapper'     => '<div class="course-categories">',
					'content'     => $content,
					'wrapper_end' => '</div>',
				],
				$course,
				$cats,
				$cat_names
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get display tags course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.6
	 * @version 1.0.2
	 */
	public function html_tags( $course ): string {
		$html = '';

		try {
			if ( $course instanceof LP_Course ) {
				$course = CourseModel::find( $course->get_id(), true );
			}

			if ( empty( $course ) ) {
				return '';
			}

			$tags = $course->get_tags();
			if ( empty( $tags ) ) {
				return '';
			}

			$tag_names = [];
			foreach ( $tags as $tag ) {
				$term        = sprintf(
					'<a href="%s">%s</a>',
					get_term_link( $tag ),
					$tag->name
				);
				$tag_names[] = $term;
			}

			$content = implode( ', ', $tag_names );

			$section = apply_filters(
				'learn-press/course/html-tags',
				[
					'wrapper'     => '<div class="course-tags">',
					'content'     => $content,
					'wrapper_end' => '</div>',
				],
				$course,
				$tags,
				$tag_names
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get display image course.
	 *
	 * @param LP_Course|CourseModel $course
	 * @param array $data ['size'] Size of image to get, Ex: [500, 300] or string 'thumbnail', 'medium', 'large', 'full' etc.
	 *
	 * @return string
	 * @since 4.2.3.2
	 * @version 1.0.3
	 */
	public function html_image( $course, array $data = [] ): string {
		$content = '';

		try {
			$courseModel = $course;
			if ( $course instanceof LP_Course ) {
				$courseModel = CourseModel::find( $course->get_id(), true );
			}

			if ( ! $courseModel instanceof CourseModel ) {
				return '';
			}

			if ( ! empty( $data['size'] ) ) {
				$size_img_send = $data['size'];

				// If custom size, data size is type int[], like [500, 300], not [ width => 500, height => 300 ]
				// Convert if data is [ width => 500, height => 300 ]
				if ( is_array( $size_img_send ) && array_key_exists( 'width', $size_img_send ) ) {
					$size_img_send = [
						$size_img_send['width'] ?? 500,
						$size_img_send['height'] ?? 300,
					];
				}
			} else {
				$size_img_setting = LP_Settings::get_option( 'course_thumbnail_dimensions', [] );
				$size_img_send    = [
					$size_img_setting['width'] ?? 500,
					$size_img_setting['height'] ?? 300,
				];
			}

			// Check cache before get image url
			$cache     = new \LP_Course_Cache();
			$key_cache = 'image_url/' . $courseModel->get_id();
			if ( is_array( $size_img_send ) && count( $size_img_send ) === 2 ) {
				$key_cache .= '/' . implode( 'x', $size_img_send );
			} elseif ( is_string( $size_img_send ) ) {
				$key_cache .= '/' . $size_img_send;
			}

			// Set cache for image url
			$course_img_url = $cache->get_cache( $key_cache );
			if ( false === $course_img_url ) {
				$course_img_url = $courseModel->get_image_url( $size_img_send );
				$cache->set_cache( $key_cache, $course_img_url );
				$cache->save_cache_keys( sprintf( 'image_urls/%s', $courseModel->get_id() ), $key_cache );
			}

			$content = sprintf(
				'<img src="%s" alt="%s">',
				esc_url_raw( $course_img_url ),
				_x( 'course thumbnail', 'no course thumbnail', 'learnpress' )
			);

			$section = apply_filters(
				'learn-press/course/html-image',
				[
					'wrapper'     => '<div class="course-img">',
					'content'     => $content,
					'wrapper_end' => '</div>',
				],
				$courseModel
			);

			$content = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get display instructor course.
	 *
	 * @param CourseModel $course
	 * @param bool $with_avatar
	 *
	 * @return string
	 * @since 4.2.5.8
	 * @version 1.0.2
	 */
	public function html_instructor( $course, bool $with_avatar = false, $setting = [] ): string {
		$content = '';

		try {
			$instructor = $course->get_author_model();
			if ( ! $instructor ) {
				return '';
			}

			$singleInstructorTemplate = SingleInstructorTemplate::instance();
			$userTemplate             = new UserTemplate( 'instructor' );
			$is_link                  = $setting['is_link'] ?? true;
			if ( $is_link ) {
				$attribute_target = ! empty( $setting['new_tab'] ) ? 'target="_blank"' : '';
				$link_instructor  = sprintf(
					'<a href="%s" %s >%s %s</a>',
					$instructor->get_url_instructor(),
					$attribute_target,
					$with_avatar ? $userTemplate->html_avatar( $instructor ) : '',
					$singleInstructorTemplate->html_display_name( $instructor )
				);
			} else {
				$link_instructor = sprintf(
					'%s %s',
					$with_avatar ? $userTemplate->html_avatar( $instructor ) : '',
					$singleInstructorTemplate->html_display_name( $instructor )
				);
			}

			$section = apply_filters(
				'learn-press/course/instructor-html',
				[
					'wrapper'     => '<div class="course-instructor">',
					'content'     => $link_instructor,
					'wrapper_end' => '</div>',
				],
				$course,
				$instructor,
				$with_avatar
			);

			$content = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html regular price
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 */
	public function html_regular_price( CourseModel $course ): string {
		$price = learn_press_format_price( $course->get_regular_price() );
		$price = apply_filters( 'learn-press/course/regular-price-html', $price, $course, $this );

		return sprintf( '<span class="origin-price">%s</span>', $price );
	}

	/**
	 * Get display price course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 */
	public function html_price( $course ): string {
		$price_html = '';

		if ( $course instanceof LP_Course ) {
			$course = CourseModel::find( $course->get_id(), true );
		}

		if ( ! $course ) {
			return $price_html;
		}

		if ( $course->is_free() ) {
			if ( '' != $course->get_sale_price() ) {
				$price_html .= $this->html_regular_price( $course );
			}

			$price_html .= sprintf( '<span class="free">%s</span>', esc_html__( 'Free', 'learnpress' ) );
			$price_html  = apply_filters( 'learn_press_course_price_html_free', $price_html, $this );
		} elseif ( $course->has_no_enroll_requirement() ) {
			$price_html .= '';
		} else {
			if ( $course->has_sale_price() ) {
				$price_html .= $this->html_regular_price( $course );
			}

			$price_html .= sprintf(
				'<span class="price">%s</span>',
				learn_press_format_price( $course->get_price(), true )
			);
			$price_html  = sprintf(
				'%1$s %2$s %3$s',
				$this->html_price_prefix( $course ),
				$price_html,
				$this->html_price_suffix( $course )
			);
			$price_html  = apply_filters(
				'learn_press_course_price_html',
				$price_html,
				$course->has_sale_price(),
				$course->get_id()
			);
		}

		// @since 4.2.7
		$price_html = sprintf( '<span class="course-price"><span class="course-item-price">%s</span></span>', $price_html );

		return apply_filters( 'learn-press/course/html-price', $price_html, $course );
	}

	/**
	 * Get deliver type
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 */
	public function html_capacity( CourseModel $course ): string {
		$content = '';

		$html_wrapper = [
			'<span class="course-capacity">' => '</span>',
		];
		$capacity     = $course->get_meta_value_by_key( CoursePostModel::META_KEY_MAX_STUDENTS, 0 );

		if ( $capacity == 0 ) {
			$content = __( 'Unlimited', 'learnpress' );
		} else {
			$content = sprintf( '%d %s', $capacity, _n( 'Student', 'Students', $capacity, 'learnpress' ) );
		}

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get display total student's course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.3.4
	 * @version 1.0.2
	 */
	public function html_count_student( $course ): string {
		if ( $course instanceof LP_Course ) {
			$course = CourseModel::find( $course->get_id(), true );
		}

		if ( empty( $course ) ) {
			return '';
		}

		$count_student  = $course->get_total_user_enrolled_or_purchased();
		$count_student += $course->get_fake_students();
		$content        = sprintf( '%d %s', $count_student, _n( 'Student', 'Students', $count_student, 'learnpress' ) );
		$html_wrapper   = [
			'<div class="course-count-student">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get display total lesson's course.
	 *
	 * @param LP_Course|CourseModel $course
	 * @param string $item_type custom post type item
	 * @param bool $show_only_number
	 *
	 * @return string
	 */
	public function html_count_item( $course, string $item_type, bool $show_only_number = false ): string {
		if ( $course instanceof LP_Course ) {
			$course = CourseModel::find( $course->get_id(), true );
		}

		if ( empty( $course ) ) {
			return '';
		}

		$info_total_items = $course->get_total_items();
		if ( empty( $info_total_items ) ) {
			return '';
		}

		$count_item = $info_total_items->{$item_type} ?? 0;

		if ( $show_only_number ) {
			$content = $count_item;
		} else {
			switch ( $item_type ) {
				case LP_LESSON_CPT:
					$content = sprintf( '%d %s', $count_item, _n( 'Lesson', 'Lessons', $count_item, 'learnpress' ) );
					break;
				case LP_QUIZ_CPT:
					$content = sprintf( '%d %s', $count_item, _n( 'Quiz', 'Quizzes', $count_item, 'learnpress' ) );
					break;
				case 'lp_assignment':
					$content = sprintf( '%d %s', $count_item, _n( 'Assignment', 'Assignments', $count_item, 'learnpress' ) );
					break;
				case 'lp_h5p':
					$content = sprintf( '%d %s', $count_item, _n( 'H5P', 'H5Ps', $count_item, 'learnpress' ) );
					break;
				default:
					$content = apply_filters( 'learn-press/single-course/i18n/count-item', $count_item, $course, $item_type );
					break;
			}
		}

		$section = apply_filters(
			'learn-press/single-course/html-count-item',
			[
				'wrapper'     => sprintf( '<div class="course-count-item %s">', $item_type ),
				'content'     => $content,
				'wrapper_end' => '</div>',
			],
			$course,
			$item_type,
			$count_item
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get html level course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.1
	 */
	public function html_level( $course ): string {
		$content = '';

		try {
			if ( $course instanceof LP_Course ) {
				$course = CourseModel::find( $course->get_id(), true );
			}

			$level = $course->get_meta_value_by_key( CoursePostModel::META_KEY_LEVEL, '' );
			if ( empty( $level ) ) {
				$level = 'all';
			}

			$levels = lp_course_level();
			$level  = $levels[ $level ] ?? $levels['all'];

			$html_wrapper = [
				'<span class="course-level">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $level );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get html duration course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_duration( $course ): string {
		$content = '';

		try {
			if ( $course instanceof LP_Course ) {
				$course = CourseModel::find( $course->get_id(), true );
			}

			$duration        = $course->get_meta_value_by_key( CoursePostModel::META_KEY_DURATION, '' );
			$duration_arr    = explode( ' ', $duration );
			$duration_number = floatval( $duration_arr[0] ?? 0 );
			$duration_type   = $duration_arr[1] ?? '';
			if ( empty( $duration_number ) ) {
				$duration_str = __( 'Lifetime', 'learnpress' );
			} else {
				$duration_str = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );
			}

			$html_wrapper = [
				'<span class="course-duration">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $duration_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get feature review
	 *
	 * @param CourseModel $course
	 * @param UserModel|false $userModel
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.7
	 * @version 1.0.2
	 */
	public function html_feature_review( CourseModel $course, $userModel = false, array $data = [] ): string {
		$feature_review = $course->get_meta_value_by_key( CoursePostModel::META_KEY_FEATURED_REVIEW, '' );
		if ( empty( $feature_review ) ) {
			return '';
		}

		if ( $userModel instanceof UserModel ) {
			$userCourseModel = UserCourseModel::find( $userModel->get_id(), $course->get_id(), true );
			if ( $userCourseModel ) {
				return '';
			}
		}

		$stars = '';
		foreach ( range( 1, 5 ) as $star ) {
			$stars .= '<i class="lp-icon-star"></i>';
		}

		$section = [
			'wrapper'     => sprintf(
				'<div class="course-featured-review %s">',
				esc_attr( $data['lp_display_on'] ?? '' )
			),
			'title'       => sprintf(
				'<div class="featured-review__title">%s</div>',
				esc_html__( 'Featured Review', 'learnpress' )
			),
			'stars'       => sprintf(
				'<div class="featured-review__stars">%s</div>',
				$stars
			),
			'content'     => sprintf(
				'<div class="featured-review__content">%s</div>',
				wp_kses_post( wpautop( $feature_review ) )
			),
			'wrapper_end' => '</div>',
		];

		return Template::combine_components( $section );
	}

	/**
	 * Get button external
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 *
	 * @return string
	 * @since 4.2.7
	 * @version 1.0.3
	 */
	public function html_btn_external( CourseModel $courseModel, $userModel ): string {
		$external_link = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_EXTERNAL_LINK_BY_COURSE, '' );
		if ( empty( $external_link ) ) {
			return '';
		}

		// Check user has enrolled, finished or purchased course
		if ( $userModel instanceof UserModel ) {
			$userCourse = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( $userCourse && ( $userCourse->has_enrolled_or_finished() || $userCourse->has_purchased() ) ) {
				return '';
			}
		}

		$content = sprintf(
			'<a href="%s" class="lp-button course-btn-extra" target="_blank">%s</a>',
			esc_url_raw( $external_link ),
			__( 'Contact To Request', 'learnpress' )
		);

		return apply_filters( 'learn-press/course/html-button-external', $content, $courseModel, $userModel );
	}

	/**
	 * HTML button purchase course
	 *
	 * @param CourseModel $courseModel
	 * @param false|UserModel $userModel
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.2
	 */
	public function html_btn_purchase_course( CourseModel $courseModel, $userModel ): string {
		$html_btn     = '';
		$can_purchase = $courseModel->can_purchase( $userModel );
		if ( is_wp_error( $can_purchase ) ) {
			$error_code_show = apply_filters(
				'learn-press/course/html-button-purchase/show-messages',
				[]
			);
			if ( in_array( $can_purchase->get_error_code(), $error_code_show )
				&& ! empty( $can_purchase->get_error_message() ) ) {
				$html_btn = Template::print_message( $can_purchase->get_error_message(), 'warning', false );
			}
		} else {
			$html_btn = sprintf(
				'<button class="lp-button button-purchase-course">%s</button>',
				__( 'Buy Now', 'learnpress' )
			);
		}

		if ( empty( $html_btn ) ) {
			return apply_filters(
				'learn-press/course/html-button-purchase/empty',
				$html_btn,
				$courseModel,
				$userModel
			);
		}

		$class_guest_checkout = LP_Checkout::instance()->is_enable_guest_checkout() ? 'guest_checkout' : '';

		// Hook action old
		$html_hook_old = '';
		if ( has_action( 'learn-press/after-purchase-button' ) ) {
			ob_start();
			do_action( 'learn-press/after-purchase-button' );
			$html_hook_old = ob_get_clean();
		}

		if ( has_filter( 'learnpress/course/template/button-purchase/can-show' ) ) {
			$user_id = 0;
			if ( $userModel instanceof UserModel ) {
				$user_id = $userModel->get_id();
			}
			$user_old   = learn_press_get_user( $user_id );
			$course_old = learn_press_get_course( $courseModel->get_id() );
			$can_show   = apply_filters( 'learnpress/course/template/button-purchase/can-show', true, $user_old, $course_old );
			if ( ! $can_show ) {
				return '';
			}
		}
		// End hook action old

		$section = apply_filters(
			'learn-press/course/html-button-purchase',
			[
				'form'     => sprintf(
					'<form name="purchase-course" class="purchase-course %s" method="post">',
					esc_attr( $class_guest_checkout )
				),
				'input'    => sprintf(
					'<input type="hidden" name="purchase-course" value="%d"/>',
					esc_attr( $courseModel->get_id() )
				),
				'btn'      => $html_btn,
				'hook_old' => $html_hook_old,
				'form_end' => '</form>',
			],
			$courseModel,
			$userModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML button enroll course
	 *
	 * @param CourseModel $courseModel
	 * @param false|UserModel $userModel
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function html_btn_enroll_course( CourseModel $courseModel, $userModel ): string {
		$html_btn   = '';
		$can_enroll = $courseModel->can_enroll( $userModel );
		if ( is_wp_error( $can_enroll ) ) {
			$error_code_show = apply_filters(
				'learn-press/course/html-button-enroll/show-messages',
				[ 'course_is_no_required_enroll_not_login', 'course_out_of_stock' ]
			);
			if ( in_array( $can_enroll->get_error_code(), $error_code_show )
				&& ! empty( $can_enroll->get_error_message() ) ) {
				$html_btn = Template::print_message( $can_enroll->get_error_message(), 'warning', false );
			}
		} else {
			$html_btn = sprintf(
				'<button type="submit" class="lp-button button-enroll-course">%s</button>',
				__( 'Start Now', 'learnpress' )
			);
		}

		if ( empty( $html_btn ) ) {
			return $html_btn;
		}

		// Hook old
		$html_hook_before_old = '';
		if ( has_action( 'learn-press/before-enroll-button' ) ) {
			ob_start();
			do_action( 'learn-press/before-enroll-button' );
			$html_hook_before_old = ob_get_clean();
		}

		$html_hook_after_old = '';
		if ( has_action( 'learn-press/after-enroll-button' ) ) {
			ob_start();
			do_action( 'learn-press/after-enroll-button' );
			$html_hook_after_old = ob_get_clean();
		}

		if ( has_filter( 'learnpress/course/template/button-enroll/can-show' ) ) {
			$user_id = 0;
			if ( $userModel instanceof UserModel ) {
				$user_id = $userModel->get_id();
			}
			$user_old   = learn_press_get_user( $user_id );
			$course_old = learn_press_get_course( $courseModel->get_id() );
			$can_show   = apply_filters( 'learnpress/course/template/button-enroll/can-show', true, $user_old, $course_old );
			if ( ! $can_show ) {
				return '';
			}
		}
		// End hook old

		$section = apply_filters(
			'learn-press/course/html-button-enroll',
			[
				'form'            => '<form name="enroll-course" class="enroll-course" method="post">',
				'hook_before_old' => $html_hook_before_old,
				'input'           => sprintf(
					'<input type="hidden" name="enroll-course" value="%s"/>',
					esc_attr( $courseModel->get_id() )
				),
				'btn'             => $html_btn,
				'hook_after_old'  => $html_hook_after_old,
				'form_end'        => '</form>',
			],
			$courseModel,
			$userModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * Sidebar
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 *
	 * @return void
	 * @version 1.0.1
	 * @since 4.2.7
	 */
	public function html_sidebar( CourseModel $courseModel, array $data = [] ): string {
		$html = '';

		try {
			if ( is_active_sidebar( 'course-sidebar' ) ) {
				ob_start();
				dynamic_sidebar( 'course-sidebar' );
				$sidebar_content = ob_get_clean();

				$section = apply_filters(
					'learn-press/course/html-sidebar',
					[
						'wrapper'     => sprintf(
							'<div class="lp-single-course-sidebar %s">',
							esc_attr( $data['lp_display_on'] ?? '' )
						),
						'content'     => $sidebar_content,
						'wrapper_end' => '</div>',
					],
					$courseModel
				);

				$html = Template::combine_components( $section );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML meta faqs
	 *
	 * @param CourseModel $courseModel
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.1
	 */
	public function html_faqs( CourseModel $courseModel, array $data = [] ): string {
		$html = '';

		try {
			$show_heading = $data['show_heading'] ?? true;
			$faqs         = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_FAQS, [] );
			if ( empty( $faqs ) ) {
				return '';
			}

			foreach ( $faqs as $faq ) {
				$title       = $faq[0];
				$description = $faq[1];
				if ( empty( $title ) || empty( $description ) ) {
					continue;
				}

				$key          = uniqid();
				$section_item = [
					'input_checkbox'    => sprintf(
						'<input type="checkbox" name="course-faqs-box-ratio" id="course-faqs-box-ratio-%s">',
						$key
					),
					'wrapper'           => '<div class="course-faqs-box">',
					'title'             => sprintf(
						'<label class="course-faqs-box__title" for="course-faqs-box-ratio-%s">%s</label>',
						$key,
						$title
					),
					'content'           => '<div class="course-faqs-box__content">',
					'content_inner'     => '<div class="course-faqs-box__content-inner">',
					'content_main'      => wp_kses_post( $description ),
					'content_inner_end' => '</div>',
					'content_end'       => '</div>',
					'wrapper_end'       => '</div>',
				];
				$html        .= Template::combine_components( $section_item );
			}

			$section = apply_filters(
				'learn-press/course/html-faqs',
				[
					'wrapper'     => '<div class="course-faqs course-tab-panel-faqs">',
					'title'       => $show_heading ? sprintf(
						'<h3 class="course-faqs__title">%s</h3>',
						__( 'FAQs', 'learnpress' )
					) : '',
					'content'     => $html,
					'wrapper_end' => '</div>',
				],
				$courseModel
			);
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML struct course box extra
	 *
	 * @param CourseModel $courseModel
	 * @param $title
	 * @param string $html_list
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_course_box_extra( CourseModel $courseModel, $title, string $html_list ): string {
		if ( empty( $html_list ) ) {
			return '';
		}

		$section = apply_filters(
			'learn-press/course/html-course-box-extra',
			[
				'wrapper'           => '<div class="course-extra-box">',
				'title'             => sprintf( '<h3 class="course-extra-box__title">%s</h3>', $title ),
				'content'           => '<div class="course-extra-box__content">',
				'content_inner'     => '<div class="course-extra-box__content-inner">',
				'list'              => $html_list,
				'content_inner_end' => '</div>',
				'content_end'       => '</div>',
				'wrapper_end'       => '</div>',
			],
			$courseModel
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML meta requirements
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_requirements( CourseModel $courseModel ): string {
		$html = '';

		try {
			$requirements = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_REQUIREMENTS, [] );
			if ( empty( $requirements ) ) {
				return '';
			}

			$html_lis = '';
			foreach ( $requirements as $requirement ) {
				$html_lis .= sprintf( '<li>%s</li>', $requirement );
			}

			$section_list = [
				'wrapper'     => '<ul>',
				'content'     => $html_lis,
				'wrapper_end' => '</ul>',
			];

			$section = apply_filters(
				'learn-press/course/html-requirements',
				[
					'wrapper'     => '<div class="course-requirements extra-box">',
					'title'       => sprintf( '<h3 class="extra-box__title">%s</h3>', __( 'Requirements', 'learnpress' ) ),
					'content'     => Template::combine_components( $section_list ),
					'wrapper_end' => '</div>',
				],
				$courseModel,
				$requirements
			);
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML meta features
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_features( CourseModel $courseModel ): string {
		$html = '';

		try {
			$features = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_FEATURES, [] );
			if ( empty( $features ) ) {
				return '';
			}

			$html_lis = '';
			foreach ( $features as $feature ) {
				$html_lis .= sprintf( '<li>%s</li>', $feature );
			}

			$section_list = [
				'wrapper'     => '<ul>',
				'content'     => $html_lis,
				'wrapper_end' => '</ul>',
			];

			$section = apply_filters(
				'learn-press/course/html-features',
				[
					'wrapper'     => '<div class="course-features extra-box">',
					'title'       => sprintf( '<h3 class="extra-box__title">%s</h3>', __( 'Features', 'learnpress' ) ),
					'content'     => Template::combine_components( $section_list ),
					'wrapper_end' => '</div>',
				],
				$courseModel,
				$features
			);
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML meta target
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_target( CourseModel $courseModel ): string {
		$html = '';

		try {
			$targets = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_TARGET, [] );
			if ( empty( $targets ) ) {
				return '';
			}

			$html_lis = '';
			foreach ( $targets as $target ) {
				$html_lis .= sprintf( '<li>%s</li>', $target );
			}

			$section_list = [
				'wrapper'     => '<ul>',
				'content'     => $html_lis,
				'wrapper_end' => '</ul>',
			];

			$section = apply_filters(
				'learn-press/course/html-target',
				[
					'wrapper'     => '<div class="course-target extra-box">',
					'title'       => sprintf( '<h3 class="extra-box__title">%s</h3>', __( 'Target audiences', 'learnpress' ) ),
					'content'     => Template::combine_components( $section_list ),
					'wrapper_end' => '</div>',
				],
				$courseModel,
				$targets
			);
			$html    = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML material
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param array $data
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.4
	 */
	public function html_material( CourseModel $courseModel, $userModel = false, array $data = [] ): string {
		$html = '';

		try {
			$show_heading = $data['show_heading'] ?? true;
			$can_show     = false;

			if ( $userModel instanceof UserModel ) {
				if ( $courseModel->check_user_is_author( $userModel )
					|| user_can( $userModel->get_id(), ADMIN_ROLE ) ) {
					$can_show = true;
				} else {
					$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
					if ( $userCourseModel &&
						( $userCourseModel->has_enrolled_or_finished()
							|| $userCourseModel->has_purchased() ) ) {
						$can_show = true;
					}
				}
			} elseif ( $courseModel->has_no_enroll_requirement() ) {
				$can_show = true;
			}

			$can_show = apply_filters( 'learn-press/course-material/can-show', $can_show, $courseModel, $userModel );

			$file_per_page = LP_Settings::get_option( 'material_file_per_page', - 1 );
			$count_files   = LP_Material_Files_DB::getInstance()->get_total( $courseModel->get_id() );
			if ( ! $can_show || $file_per_page == 0 || $count_files <= 0 ) {
				return $html;
			}

			ob_start();
			do_action( 'learn-press/course-material/layout', [] );
			$html_content = ob_get_clean();

			$section = [
				'wrapper'     => '<div class="course-material">',
				'title'       => $show_heading ? sprintf(
					'<h3 class="course-material__title">%s</h3>',
					__( 'Course Material', 'learnpress' )
				) : '',
				'content'     => $html_content,
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html level course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.5
	 * @version 1.0.1
	 */
	public function html_price_prefix( $course ): string {
		$html = '';

		try {
			if ( $course instanceof LP_Course ) {
				$course = CourseModel::find( $course->get_id(), true );
				if ( ! $course instanceof CourseModel ) {
					return $html;
				}
			}

			$price_prefix_str = $course->get_meta_value_by_key( CoursePostModel::META_KEY_PRICE_PREFIX, '' );
			if ( empty( $price_prefix_str ) ) {
				return $html;
			}

			$html = sprintf( '<span class="course-price-prefix">%s</span>', $price_prefix_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html level course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.5
	 * @version 1.0.0
	 */
	public function html_price_suffix( $course ): string {
		$html = '';

		try {
			$price_suffix_str = $course->get_meta_value_by_key( CoursePostModel::META_KEY_PRICE_SUFFIX, '' );
			if ( empty( $price_suffix_str ) ) {
				return $html;
			}

			$html = sprintf( '<span class="course-price-suffix">%s</span>', $price_suffix_str );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Get html featured course.
	 *
	 * @param CourseModel $courseModel
	 *
	 * @return string
	 * @since 4.2.9
	 * @version 1.0.0
	 */
	public function html_featured( CourseModel $courseModel ): string {
		$html = '';

		try {
			$is_featured = $courseModel->get_meta_value_by_key( CoursePostModel::META_KEY_FEATURED, 'no' );
			if ( $is_featured !== 'yes' ) {
				return $html;
			}

			$html = sprintf( '<span class="course-featured">%s</span>', __( 'Featured', 'learnpress' ) );
		} catch ( Throwable $e ) {
			LP_Debug::error_log( $e );
		}

		return $html;
	}

	/**
	 * GET HTML comment default of WP
	 *
	 * @param CourseModel $courseModel
	 * @param false|UserModel $userModel
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public function html_comment( CourseModel $courseModel, $userModel = false ): string {
		$args     = [
			'id_url'    => 'course-comments',
			'course_id' => $courseModel->get_id(),
			'user_id'   => $userModel instanceof UserModel ? $userModel->get_id() : 0,
		];
		$callBack = [
			'class'  => __CLASS__,
			'method' => 'render_html_comments',
		];
		$html     = TemplateAJAX::load_content_via_ajax( $args, $callBack );

		$section_comment = apply_filters(
			'learn-press/single-course/html-comment',
			[
				'wrapper'     => '<div class="lp-course-comment">',
				'content'     => $html,
				'wrapper_end' => '</div>',
			],
			$courseModel,
			$userModel
		);

		return Template::combine_components( $section_comment );
	}

	/**
	 * Render HTML comments of course.
	 *
	 * @param array $data ['course_id']
	 *
	 * @return stdClass
	 * @since 4.2.7.6
	 * @version 1.0.0
	 */
	public static function render_html_comments( array $data ): stdClass {
		$response          = new stdClass();
		$response->content = '';

		try {
			$course_id = $data['course_id'] ?? 0;
			$user_id   = $data['user_id'] ?? 0;
			if ( empty( $course_id ) ) {
				return $response;
			}

			global $withcomments, $post;

			$post = get_post( $course_id );
			if ( $post->comment_status !== 'open' ) {
				return $response;
			} else {
				$withcomments = true;
			}

			$withcomments = apply_filters(
				'learn-press/single-course/render-html-comment/is-show',
				$withcomments,
				$post,
				$user_id
			);

			//$post->comment_count = 0;

			ob_start();
			add_filter( 'deprecated_file_trigger_error', '__return_false' );
			comments_template();
			remove_filter( 'deprecated_file_trigger_error', '__return_false' );
			$html              = ob_get_clean();
			$response->content = $html;
		} catch ( Throwable $e ) {
			$response->content = Template::print_message( $e->getMessage(), 'error', false );
		}

		return $response;
	}

	/**
	 * Get HTML curriculum of course.
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param LessonPostModel|QuizPostModel|PostModel|mixed $itemModelCurrent
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.3
	 */
	public function html_curriculum( CourseModel $courseModel, $userModel, $itemModelCurrent = false ): string {
		$html = '';

		try {
			// Get current item viewing
			/**
			 * @var $lp_course_item LP_Course_Item
			 * @var $post \WP_Post
			 */
			global $lp_course_item, $post;
			/**
			 * @var $itemModelCurrent LessonPostModel|QuizPostModel|AssignmentPostModel|PostModel...
			 */
			$itemModelCurrent = $courseModel->get_item_model( $post->ID, $post->post_type );
			if ( $lp_course_item ) {
				$itemModelCurrent = $courseModel->get_item_model( $lp_course_item->get_id(), $lp_course_item->get_item_type() );
			}

			if ( $itemModelCurrent ) {
				$item_types = CourseModel::item_types_support();
				// Check item type is support
				if ( in_array( $itemModelCurrent->post_type, $item_types ) ) {
					$this->currentItemModel = $itemModelCurrent;
				}
			}
			// End get current item viewing

			$section_items = $courseModel->get_section_items();
			$html          = Template::print_message(
				esc_html__( 'There are no items in the curriculum yet.', 'learnpress' ),
				'info',
				false
			);
			if ( empty( $section_items ) ) {
				return $html;
			}

			wp_enqueue_script( 'lp-curriculum' );
			$li_section_items = '';
			foreach ( $section_items as $section_item ) {
				$li_section_items .= $this->render_html_section_item( $courseModel, $userModel, $section_item );
			}

			$section = apply_filters(
				'learn-press/course/html-curriculum',
				[
					'wrapper'                   => '<div class="lp-course-curriculum">',
					'title'                     => sprintf(
						'<h3 class="lp-course-curriculum__title">%s</h3>',
						esc_html__( 'Curriculum', 'learnpress' )
					),
					'curriculum_info'           => '<div class="course-curriculum-info">',
					'curriculum_info_left'      => '<ul class="course-curriculum-info__left">',
					'count_sections'            => sprintf(
						'<li class="course-count-section">%s</li>',
						sprintf(
							_n( '%d Section', '%d Sections', $courseModel->get_total_sections(), 'learnpress' ),
							$courseModel->get_total_sections()
						)
					),
					'count_lesson'              => sprintf(
						'<li class="course-count-lesson">%s</li>',
						sprintf(
							_n( '%d Lesson', '%d Lessons', $courseModel->count_items( LP_LESSON_CPT ), 'learnpress' ),
							$courseModel->count_items( LP_LESSON_CPT )
						)
					),
					'duration'                  => sprintf(
						'<li class="course-duration">%s</li>',
						$this->html_duration( $courseModel )
					),
					'curriculum_info_left_end'  => '</ul>',
					'curriculum_info_right'     => '<div class="course-curriculum-info__right">',
					'expand_all'                => sprintf(
						'<span class="course-toggle-all-sections">%s</span>',
						esc_html__( 'Expand all sections', 'learnpress' )
					),
					'collapse_all'              => sprintf(
						'<span class="course-toggle-all-sections lp-collapse lp-hidden">%s</span>',
						esc_html__( 'Collapse all sections', 'learnpress' )
					),
					'curriculum_info_right_end' => '</div>',
					'curriculum_info_end'       => '</div>',
					'curriculum'                => '<div class="course-curriculum">',
					'sections'                  => '<ul class="course-sections">',
					'li_section_items'          => $li_section_items,
					'sections_end'              => '</ul>',
					'curriculum_end'            => '</div>',
					'wrapper_end'               => '</div>',
				],
				$courseModel,
				$userModel,
				$itemModelCurrent
			);

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $html;
	}

	/**
	 * Render HTML section item
	 *
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param $section_item object {section_id, section_name, section_description, items[{item_id, item_order, item_type, title, preview}]}
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.1
	 */
	public function render_html_section_item( CourseModel $courseModel, $userModel, $section_item ): string {
		if ( ! $section_item instanceof stdClass ) {
			return '';
		}

		$section_id               = $section_item->section_id ?? 0;
		$section_name             = $section_item->section_name ?? '';
		$section_description      = $section_item->section_description ?? '';
		$section_order            = $section_item->section_order ?? 1;
		$items                    = $section_item->items ?? [];
		$html_section_description = '';
		if ( ! empty( $section_description ) ) {
			$html_section_description = sprintf(
				'<div class="course-section__description">%s</div>',
				wp_kses_post( $section_description )
			);
		}

		$section_header = [
			'start'       => '<div class="course-section-header">',
			'toggle'      => '<div class="section-toggle">
				<i class="lp-icon-angle-down"></i>
				<i class="lp-icon-angle-up"></i>
			</div>',
			'info'        => '<div class="course-section-info">',
			'title'       => sprintf( '<div class="course-section__title">%s</div>', wp_kses_post( $section_name ) ),
			'description' => $html_section_description,
			'info_end'    => '</div>',
			'count_items' => sprintf(
				'<div class="section-count-items">%d</div>',
				count( $items )
			),
			'end'         => '</div>',
		];

		$li_items = '';
		foreach ( $items as $item ) {
			$li_items .= $this->render_html_course_item( $courseModel, $userModel, $item, $section_item );
		}
		$section_items = [
			'start'    => '<ul class="course-section__items">',
			'li_items' => $li_items,
			'end'      => '</ul>',
		];

		$curriculum_display_setting = LP_Settings::get_option( 'curriculum_display', 'expand_first_section' );
		$class_section_toggle       = '';

		if ( $this->currentItemModel ) {
			$current_section = $courseModel->get_section_of_item( $this->currentItemModel->get_id() );
			if ( $current_section != $section_id ) {
				$class_section_toggle = 'lp-collapse';
			}
		} else {
			if ( $curriculum_display_setting === 'collapse_all' ) {
				$class_section_toggle = 'lp-collapse';
			} elseif ( $curriculum_display_setting === 'expand_first_section' ) {
				if ( $section_order > 1 ) {
					$class_section_toggle = 'lp-collapse';
				}
			}
		}

		$class_section_toggle = apply_filters(
			'learn-press/course/html-section-item/class-section-toggle',
			$class_section_toggle,
			$courseModel,
			$userModel,
			$section_item
		);

		$section_item = apply_filters(
			'learn-press/course/html-section-item',
			[
				'start'  => sprintf(
					'<li class="course-section %s" data-section-id="%s">',
					$class_section_toggle,
					$section_id
				),
				'header' => Template::combine_components( $section_header ),
				'items'  => Template::combine_components( $section_items ),
				'end'    => '</li>',
			],
			$courseModel,
			$userModel,
			$section_item
		);

		return Template::combine_components( $section_item );
	}

	/**
	 * @param CourseModel $courseModel
	 * @param UserModel|false $userModel
	 * @param $item stdClass {item_id, item_order, item_type, title, preview}
	 * @param $section_item stdClass {section_id, section_name, section_description, items[{item_id, item_order, item_type, title, preview}]}
	 *
	 * @return string
	 * @since 4.2.7.6
	 * @version 1.0.2
	 */
	public function render_html_course_item( CourseModel $courseModel, $userModel, $item, $section_item ): string {
		$html = '';

		if ( ! $item instanceof stdClass ) {
			return $html;
		}

		$item_id       = (int) ( $item->item_id ?? $item->id ?? 0 );
		$item_order    = $item->item_order ?? $item->order ?? 0;
		$item_type     = $item->item_type ?? $item->type ?? '';
		$title         = $item->title ?? '';
		$has_preview   = $item->preview ?? '';
		$class_current = '';
		if ( $this->currentItemModel ) {
			$current_item_id = $this->currentItemModel->get_id();
			if ( $current_item_id == $item_id ) {
				$class_current = 'current';
			}
		}

		//LP_Course_Item::get_item( $item_id, $course->get_id() );
		$itemModel = $courseModel->get_item_model( $item_id, $item_type );
		if ( empty( $itemModel ) ) {
			return $html;
		}

		$link_item = $courseModel->get_item_link( $item_id, $item_type );

		$item_duration      = '';
		$html_item_duration = '';
		if ( is_callable( [ $itemModel, 'get_duration' ] ) ) {
			$item_duration = $itemModel->get_duration();
		} else {
			$item_duration = get_post_meta( $item_id, '_lp_duration', true );
		}

		$duration_arr    = explode( ' ', $item_duration );
		$duration_number = floatval( $duration_arr[0] ?? 0 );
		$duration_type   = $duration_arr[1] ?? '';

		if ( $duration_number > 0 ) {
			$item_duration_plural = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );

			$html_item_duration = sprintf(
				'<span class="duration">%s</span>',
				$item_duration_plural
			);
		}

		// Count question of quiz
		$html_item_count_questions = '';
		if ( $item_type === LP_QUIZ_CPT ) {
			$quizPostModel = QuizPostModel::find( $item_id, true );
			if ( $quizPostModel instanceof QuizPostModel ) {
				$question_count      = $quizPostModel->count_questions();
				$html_item_duration .= sprintf(
					'<span class="question-count">%s</span>',
					sprintf(
						_n( '%d Question', '%d Questions', $question_count, 'learnpress' ),
						$question_count
					)
				);
			}
		}

		$user_item_status_ico_flag = 'locked';
		$user_attended_course      = false;
		if ( $userModel instanceof UserModel ) {
			$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( $userCourseModel
				&& $userCourseModel->get_status() !== UserItemModel::STATUS_CANCEL
				&& $userCourseModel->get_status() !== UserCourseModel::STATUS_PURCHASED ) {
				$user_attended_course = true;

				// Check status of item's course
				$userCourseItem = $userCourseModel->get_item_attend( $item_id, $item_type );
				if ( ! $userCourseItem instanceof UserItemModel ) {
					$user_item_status_ico_flag = UserItemModel::GRADUATION_IN_PROGRESS;
				} else {
					$user_item_status_ico_flag = $userCourseItem->get_status();
					$user_item_graduation      = $userCourseItem->get_graduation();
					if ( ! empty( $user_item_graduation ) ) {
						$user_item_status_ico_flag = $user_item_graduation . ' completed';
					}

					if ( empty( $user_item_status_ico_flag ) ) {
						$user_item_status_ico_flag = UserItemModel::GRADUATION_IN_PROGRESS;
					}
				}

				if ( $user_item_status_ico_flag === UserItemModel::GRADUATION_IN_PROGRESS ) {
					if ( $userCourseModel->get_time_remaining() === 0 ) {
						$user_item_status_ico_flag = 'locked';
					} elseif ( $userCourseModel->get_status() === UserItemModel::STATUS_FINISHED
						&& $courseModel->enable_block_when_finished() ) {
						$user_item_status_ico_flag = 'locked';
					}
				}
			}
		}

		if ( $has_preview && ! $user_attended_course ) {
			$user_item_status_ico_flag = 'preview';
		}

		if ( $courseModel->has_no_enroll_requirement()
			&& ! $user_attended_course && ! $userModel ) {
			$user_item_status_ico_flag = UserItemModel::GRADUATION_IN_PROGRESS;
		}

		$user_item_status_ico_flag = apply_filters(
			'learn-press/course/item/status-ico-flag',
			$user_item_status_ico_flag,
			$courseModel,
			$userModel,
			$item,
			$section_item
		);

		$html_item_status = sprintf(
			'<div class="course-item__status"><span class="course-item-ico %1$s"></span></div>',
			$user_item_status_ico_flag
		);

		$html_item_right = [];
		if ( $html_item_count_questions != '' || $html_item_duration != '' ) {
			$html_item_right = [
				'item_right'     => '<div class="course-item__right">',
				'question_count' => $html_item_count_questions,
				'duration'       => $html_item_duration,
				'item_right_end' => '</div>',
			];
		}

		$section_item = apply_filters(
			'learn-press/course/html-course-item',
			[
				'start'            => sprintf(
					'<li class="course-item %s" data-item-id="%s" data-item-order="%s" data-item-type="%s">',
					$class_current,
					$item_id,
					$item_order,
					$item_type
				),
				'link'             => sprintf(
					'<a href="%s" class="course-item__link">',
					esc_url_raw( $link_item )
				),
				'item_info'        => '<div class="course-item__info">',
				'icon'             => sprintf(
					'<span class="course-item-ico %s"></span>',
					esc_attr( $item_type )
				),
				'item_order'       => sprintf(
					'<span class="course-item-order lp-hidden">%s.%s</span>',
					$section_item->section_order,
					$item_order
				),
				'item_info_end'    => '</div>',
				'item_content'     => '<div class="course-item__content">',
				'item_left'        => '<div class="course-item__left">',
				'title'            => sprintf( '<div class="course-item-title">%s</div>', wp_kses_post( $title ) ),
				'item_left_end'    => '</div>',
				'item_right'       => Template::combine_components( $html_item_right ),
				'item_content_end' => '</div>',
				'status'           => $html_item_status,
				'link_end'         => '</a>',
				'end'              => '</li>',
			],
			$courseModel,
			$userModel,
			$item,
			$section_item
		);

		return Template::combine_components( $section_item );
	}

	/**
	 * Render string to data content
	 *
	 * @param CourseModel $course
	 * @param string $data_content
	 *
	 * @return string
	 */
	public function render_data( CourseModel $course, string $data_content = '' ): string {
		$author_of_course         = $course->get_author_model();
		$singleInstructorTemplate = SingleInstructorTemplate::instance();

		// render count items
		$pattern_count_items = '/{{course_count_.*?}}/';
		preg_match_all( $pattern_count_items, $data_content, $matches_count_items );
		if ( ! empty( $matches_count_items ) ) {
			$items = $matches_count_items[0];
			foreach ( $items as $item ) {
				$method         = str_replace( [ '{{', '}}' ], '', $item );
				$post_type_item = str_replace( 'course_count_', '', $method );
				$data_content   = str_replace( $item, $this->html_count_item( $course, $post_type_item ), $data_content );
			}
		}

		return str_replace(
			[
				'{{course_id}}',
				'{{course_title}}',
				'{{course_image}}',
				'{{course_url}}',
				'{{course_short_description}}',
				'{{course_price}}',
				'{{course_categories}}',
				'{{course_count_student}}',
				'{{course_author_display_name}}',
				'{{course_author_url}}',
				'{{course_author_avatar}}',
			],
			[
				$course->get_id(),
				$this->html_title( $course ),
				$this->html_image( $course ),
				$course->get_permalink(),
				$this->html_short_description( $course ),
				$this->html_price( $course ),
				$this->html_categories( $course ),
				$this->html_count_student( $course ),
				$singleInstructorTemplate->html_display_name( $author_of_course ),
				$author_of_course->get_url_instructor(),
				$singleInstructorTemplate->html_avatar( $author_of_course ),
			],
			$data_content
		);
	}
}
