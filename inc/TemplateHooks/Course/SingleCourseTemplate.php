<?php
/**
 * Template hooks Single Course.
 *
 * @since 4.2.3
 * @version 1.0.3
 */

namespace LearnPress\TemplateHooks\Course;

use LearnPress\Helpers\Config;
use LearnPress\Helpers\Singleton;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LearnPress\TemplateHooks\UserTemplate;
use LP_Checkout;
use LP_Course;
use LP_Datetime;
use LP_Material_Files_DB;
use LP_Settings;
use Throwable;

class SingleCourseTemplate {
	use Singleton;

	public function init() {
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
		$tag_html     = sanitize_key( $tag_html );
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
		if ( empty( $short_description ) || $number_words === 0 ) {
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
	 * Get display title course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 */
	public function html_categories( $course ): string {
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

		$cat_names = [];
		array_map(
			function ( $cat ) use ( &$cat_names ) {
				$term        = sprintf( '<a href="%s">%s</a>', get_term_link( $cat->term_id ), $cat->name );
				$cat_names[] = $term;
			},
			$cats
		);

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

		return Template::combine_components( $section );
	}

	/**
	 * Get display tags course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 * @since 4.2.6
	 * @version 1.0.1
	 */
	public function html_tags( $course ): string {
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

		$cat_names = [];
		array_map(
			function ( $cat ) use ( &$cat_names ) {
				$term        = sprintf( '<a href="%s">%s</a>', get_term_link( $cat->term_id ), $cat->name );
				$cat_names[] = $term;
			},
			$tags
		);

		$content = implode( ', ', $cat_names );

		$section = apply_filters(
			'learn-press/course/html-tags',
			[
				'wrapper'     => '<div class="course-tags">',
				'content'     => $content,
				'wrapper_end' => '</div>',
			],
			$course,
			$tags,
			$cat_names
		);

		return Template::combine_components( $section );
	}

	/**
	 * Get display title course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 */
	public function html_image( $course ): string {
		$content = '';

		try {
			if ( $course instanceof LP_Course ) {
				$content = $course->get_image();
			} elseif ( $course instanceof CourseModel ) {
				$content = sprintf(
					'<img src="%s" alt="%s">',
					esc_url_raw( $course->get_image_url() ),
					_x( 'course thumbnail', 'no course thumbnail', 'learnpress' )
				);
			}

			$section = apply_filters(
				'learn-press/course/html-image',
				[
					'wrapper'     => '<div class="course-img">',
					'content'     => $content,
					'wrapper_end' => '</div>',
				],
				$course
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
	 * @version 1.0.1
	 */
	public function html_instructor( $course, bool $with_avatar = false ): string {
		$content = '';

		try {
			$instructor = $course->get_author_model();
			if ( ! $instructor ) {
				return '';
			}

			$singleInstructorTemplate = SingleInstructorTemplate::instance();

			$link_instructor = sprintf(
				'<a href="%s">%s %s</a>',
				$instructor->get_url_instructor(),
				$with_avatar ? UserTemplate::instance()->html_avatar( $instructor, [], 'instructor' ) : '',
				$singleInstructorTemplate->html_display_name( $instructor )
			);

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
	 * @deprecated 4.2.7.3 Move to SingleCourseOfflineTemplate
	 */
	public function html_deliver_type( CourseModel $course ): string {
		return '';
		$content = '';

		$html_wrapper = [
			'<span class="course-deliver-type">' => '</span>',
		];

		$deliver_type_options = Config::instance()->get( 'course-deliver-type' );
		$key                  = $course->get_meta_value_by_key( CoursePostModel::META_KEY_DELIVER, 'private_1_1' );
		$content              = $deliver_type_options[ $key ] ?? '';

		return Template::instance()->nest_elements( $html_wrapper, $content );
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
	 *
	 * @return string
	 */
	public function html_feature_review( CourseModel $course ): string {
		$feature_review = $course->get_meta_value_by_key( CoursePostModel::META_KEY_FEATURED_REVIEW, '' );
		if ( empty( $feature_review ) ) {
			return '';
		}
		ob_start();
		?>
		<div class="course-featured-review">
			<div class="featured-review__title">
				<?php echo esc_html__( 'Featured Review', 'learnpress' ); ?>
			</div>
			<div class="featured-review__stars">
				<i class="lp-icon-star"></i>
				<i class="lp-icon-star"></i>
				<i class="lp-icon-star"></i>
				<i class="lp-icon-star"></i>
				<i class="lp-icon-star"></i>
			</div>
			<div class="featured-review__content">
				<?php echo wp_kses_post( wpautop( $feature_review ) ); ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * Get html address of course offline
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 * @deprecated 4.2.7.3 Move to SingleCourseOfflineTemplate
	 */
	public function html_address( CourseModel $course ): string {
		return '';
		$content = '';

		try {
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
	 * Get button external
	 *
	 * @param CourseModel $course
	 * @param UserModel|false $user
	 *
	 * @return string
	 * @since 4.2.7
	 * @version 1.0.1
	 */
	public function html_btn_external( CourseModel $course, $user ): string {
		$external_link = $course->get_meta_value_by_key( CoursePostModel::META_KEY_EXTERNAL_LINK_BY_COURSE, '' );
		if ( empty( $external_link ) ) {
			return '';
		}

		// Check user has enrolled, finished or purchased course
		if ( $user instanceof UserModel ) {
			$userCourse = UserCourseModel::find( $user->get_id(), $course->get_id(), true );
			if ( $userCourse && ( $userCourse->has_enrolled_or_finished() || $userCourse->has_purchased() ) ) {
				return '';
			}
		}

		$content = sprintf(
			'<a href="%s" class="lp-button course-btn-extra">%s</a>',
			esc_url_raw( $external_link ),
			__( 'Contact To Request', 'learnpress' )
		);

		return apply_filters( 'learn-press/course/html-address', $content, $course, $user );
	}

	/**
	 * HTML button purchase course
	 *
	 * @param CourseModel $course
	 * @param false|UserModel $user
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.1
	 */
	public function html_btn_purchase_course( CourseModel $course, $user ): string {
		$html_btn     = '';
		$can_purchase = $course->can_purchase( $user );
		if ( is_wp_error( $can_purchase ) ) {
			$error_code_show = apply_filters(
				'learn-press/course/html-button-purchase/show-messages',
				[]
			);
			if ( in_array( $can_purchase->get_error_code(), $error_code_show )
				&& ! empty( $can_purchase->get_error_message() ) ) {
				ob_start();
				Template::print_message( $can_purchase->get_error_message(), 'warning' );
				$html_btn = ob_get_clean();
			}
		} else {
			$html_btn = sprintf(
				'<button class="lp-button button button-purchase-course">%s</button>',
				__( 'Buy Now', 'learnpress' )
			);
		}

		if ( empty( $html_btn ) ) {
			return apply_filters(
				'learn-press/course/html-button-purchase/empty',
				$html_btn,
				$course,
				$user
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
			if ( $user instanceof UserModel ) {
				$user_id = $user->get_id();
			}
			$user_old   = learn_press_get_user( $user_id );
			$course_old = learn_press_get_course( $course->get_id() );
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
					esc_attr( $course->get_id() )
				),
				'btn'      => $html_btn,
				'hook_old' => $html_hook_old,
				'form_end' => '</form>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML button enroll course
	 *
	 * @param CourseModel $course
	 * @param false|UserModel $user
	 *
	 * @return string
	 * @since 4.2.7.3
	 * @version 1.0.0
	 */
	public function html_btn_enroll_course( CourseModel $course, $user ): string {
		$html_btn   = '';
		$can_enroll = $course->can_enroll( $user );
		if ( is_wp_error( $can_enroll ) ) {
			$error_code_show = apply_filters(
				'learn-press/course/html-button-enroll/show-messages',
				[ 'course_is_no_required_enroll_not_login', 'course_out_of_stock' ]
			);
			if ( in_array( $can_enroll->get_error_code(), $error_code_show )
				&& ! empty( $can_enroll->get_error_message() ) ) {
				ob_start();
				Template::print_message( $can_enroll->get_error_message(), 'warning' );
				$html_btn = ob_get_clean();
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
			if ( $user instanceof UserModel ) {
				$user_id = $user->get_id();
			}
			$user_old   = learn_press_get_user( $user_id );
			$course_old = learn_press_get_course( $course->get_id() );
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
					esc_attr( $course->get_id() )
				),
				'btn'             => $html_btn,
				'hook_after_old'  => $html_hook_after_old,
				'form_end'        => '</form>',
			],
			$course,
			$user
		);

		return Template::combine_components( $section );
	}

	/**
	 * Sidebar
	 *
	 * @param CourseModel $course
	 *
	 * @return void
	 * @version 1.0.0
	 * @since 4.2.7
	 */
	public function html_sidebar( CourseModel $course ): string {
		$html = '';

		try {
			if ( is_active_sidebar( 'course-sidebar' ) ) {
				$html_wrapper = [
					'<div class="lp-single-course-sidebar">' => '</div>',
				];

				ob_start();
				dynamic_sidebar( 'course-sidebar' );
				$html = Template::instance()->nest_elements( $html_wrapper, ob_get_clean() );
			}
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}

		return $html;
	}

	/**
	 * HTML meta faqs
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 */
	public function html_faqs( CourseModel $course ): string {
		$html = '';

		try {
			$faqs = $course->get_meta_value_by_key( CoursePostModel::META_KEY_FAQS, [] );
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
					'title'       => sprintf( '<h3 class="course-faqs__title">%s</h3>', __( 'FAQs', 'learnpress' ) ),
					'content'     => $html,
					'wrapper_end' => '</div>',
				],
				$course
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
	 * @param CourseModel $course
	 * @param $title
	 * @param string $html_list
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_course_box_extra( CourseModel $course, $title, string $html_list ): string {
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
			$course
		);

		return Template::combine_components( $section );
	}

	/**
	 * HTML meta requirements
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_requirements( CourseModel $course ): string {
		$html = '';

		try {
			$requirements = $course->get_meta_value_by_key( CoursePostModel::META_KEY_REQUIREMENTS, [] );
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
				$course,
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
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_features( CourseModel $course ): string {
		$html = '';

		try {
			$features = $course->get_meta_value_by_key( CoursePostModel::META_KEY_FEATURES, [] );
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
				$course,
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
	 * @param CourseModel $course
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_target( CourseModel $course ): string {
		$html = '';

		try {
			$targets = $course->get_meta_value_by_key( CoursePostModel::META_KEY_TARGET, [] );
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
				$course,
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
	 * @param CourseModel $course
	 * @param UserModel|null $user
	 *
	 * @return string
	 * @since 4.2.7.2
	 * @version 1.0.0
	 */
	public function html_material( CourseModel $course, UserModel $user = null ): string {
		$html = '';
		if ( ! $user ) {
			$user = UserModel::find( get_current_user_id(), true );
			if ( ! $user ) {
				return $html;
			}
		}

		$userCourse = UserCourseModel::find( $user->get_id(), $course->get_id(), true );

		try {
			$can_show = false;
			if ( $course->has_no_enroll_requirement()
				|| ( $userCourse && $userCourse->has_enrolled_or_finished() )
				|| ( $userCourse && $userCourse->has_purchased() )
				|| user_can( $user->get_id(), LP_TEACHER_ROLE ) || user_can( $user->get_id(), ADMIN_ROLE ) ) {
				$can_show = true;
			}

			$file_per_page = LP_Settings::get_option( 'material_file_per_page', - 1 );
			$count_files   = LP_Material_Files_DB::getInstance()->get_total( $course->get_id() );
			if ( ! $can_show || $file_per_page == 0 || $count_files <= 0 ) {
				return $html;
			}

			ob_start();
			do_action( 'learn-press/course-material/layout', [] );
			$html_content = ob_get_clean();

			$section = [
				'wrapper'     => '<div class="course-material">',
				'title'       => sprintf( '<h3 class="course-material__title">%s</h3>', __( 'Course Material', 'learnpress' ) ),
				'content'     => $html_content,
				'wrapper_end' => '</div>',
			];

			$html = Template::combine_components( $section );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
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
	public function html_price_prefix( $course ): string {
		$html = '';

		try {
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
	 * Render string to data content
	 *
	 * @param LP_Course $course
	 * @param string $data_content
	 *
	 * @return string
	 */
	public function render_data( LP_Course $course, string $data_content = '' ): string {
		$author_of_course         = $course->get_author();
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
