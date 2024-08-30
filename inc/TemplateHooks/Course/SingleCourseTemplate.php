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
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Datetime;
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
	 * @param LP_Course $course
	 * @param int $number_words
	 *
	 * @return string
	 */
	public function html_short_description( LP_Course $course, int $number_words = 0 ): string {
		$html_wrapper = [
			'<p class="course-short-description">' => '</p>',
		];

		$short_description = $course->get_data( 'excerpt' );
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
		$content      = '';
		$html_wrapper = [
			'<p class="course-description">' => '</p>',
		];

		if ( $course instanceof LP_Course ) {
			$content = $course->get_data( 'description' );
		} elseif ( $course instanceof CourseModel ) {
			$content = $course->get_description();
		}

		return Template::instance()->nest_elements( $html_wrapper, $content );
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

		$html_wrapper = [
			'<div class="course-categories">' => '</div>',
		];

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

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get display tags course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 * @since 4.2.6
	 * @version 1.0.0
	 */
	public function html_tags( LP_Course $course ): string {
		$html_wrapper = [
			'<div class="course-tags">' => '</div>',
		];

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

		return Template::instance()->nest_elements( $html_wrapper, $content );
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
			$html_wrapper = [
				'<div class="course-img">' => '</div>',
			];

			if ( $course instanceof LP_Course ) {
				$content = $course->get_image();
			} elseif ( $course instanceof CourseModel ) {
				$content = sprintf(
					'<img src="%s" alt="%s">',
					esc_url_raw( $course->get_image_url() ),
					_x( 'course thumbnail', 'no course thumbnail', 'learnpress' )
				);
			}

			$content = Template::instance()->nest_elements( $html_wrapper, $content );
		} catch ( Throwable $e ) {
			error_log( __METHOD__ . ': ' . $e->getMessage() );
		}

		return $content;
	}

	/**
	 * Get display instructor course.
	 *
	 * @param LP_Course $course
	 * @param bool $with_avatar
	 *
	 * @return string
	 * @since 4.2.5.8
	 * @version 1.0.0
	 */
	public function html_instructor( LP_Course $course, bool $with_avatar = false ): string {
		$content = '';

		try {
			$html_wrapper = [
				'<span class="course-instructor">' => '</span>',
			];

			$content = $course->get_instructor_html( $with_avatar );
			$content = Template::instance()->nest_elements( $html_wrapper, $content );
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
			$price_html = apply_filters( 'learn_press_course_price_html_free', $price_html, $this );
		} elseif ( $course->has_no_enroll_requirement() ) {
			$price_html .= '';
		} else {
			if ( $course->has_sale_price() ) {
				$price_html .= $this->html_regular_price( $course );
			}

			$price_html .= sprintf( '<span class="price">%s</span>', learn_press_format_price( $course->get_price(), true ) );
			$price_html = apply_filters( 'learn_press_course_price_html', $price_html, $course->has_sale_price(), $course->get_id() );
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
	public function html_deliver_type( CourseModel $course ): string {
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

		$count_student = $course->get_total_user_enrolled_or_purchased();
		$fake_student  = $course->get_meta_value_by_key( CoursePostModel::META_KEY_STUDENTS );
		if ( $fake_student ) {
			$count_student += $fake_student;
		}
		$content      = sprintf( '%d %s', $count_student, _n( 'Student', 'Students', $count_student, 'learnpress' ) );
		$html_wrapper = [
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
					$content = '';
					break;
			}
		}

		$item_type_class = str_replace( 'lp_', '', $item_type );
		$html_wrapper    = [
			'<div class="course-count-' . $item_type_class . '">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
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

			$level  = $course->get_meta_value_by_key( CoursePostModel::META_KEY_LEVEL, '' );
			$levels = lp_course_level();
			$level  = $levels[ $level ] ?? $levels[''];

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
			$duration_str    = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );

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
	 * Get address of course
	 *
	 * @param CourseModel $course
	 *
	 * @return string
	 */
	public function html_address( CourseModel $course ): string {
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
	 *
	 * @return string
	 */
	public function html_btn_external( CourseModel $course ): string {
		$external_link = $course->get_meta_value_by_key( CoursePostModel::META_KEY_EXTERNAL_LINK_BY_COURSE, '' );
		if ( empty( $external_link ) ) {
			return '';
		}

		$content = sprintf( '<a href="%s" class="lp-button course-btn-extra">%s</a>', $external_link, __( 'Contact To Request', 'learnpress' ) );

		return apply_filters( 'learn-press/course/html-address', $content );
	}

	/**
	 * @param CourseModel $course
	 * @param false|UserModel $user
	 *
	 * @return string
	 */
	public function html_btn_purchase_course( CourseModel $course, $user ) {
		$can_show = true;

		if ( $course->is_free() ) {
			return '';
		}

		$user         = learn_press_get_current_user();
		$can_purchase = $user->can_purchase_course( $course->get_id() );
		if ( is_wp_error( $can_purchase ) ) {
			if ( in_array( $can_purchase->get_error_code(),
				[ 'order_processing', 'course_out_of_stock', 'course_is_no_required_enroll_not_login' ] ) ) {
				Template::print_message( $can_purchase->get_error_message(), 'warning' );
			}

			$can_show = false;
		}

		// Hook since 4.1.3
		$can_show = apply_filters( 'learnpress/course/template/button-purchase/can-show', $can_show, $user, $course );
		if ( ! $can_show ) {
			return '';
		}

		$args_load_tmpl = array(
			'template_name' => 'single-course/buttons/purchase.php',
			'template_path' => '',
			'default_path'  => '',
		);

		$args_load_tmpl = apply_filters( 'learn-press/tmpl-button-purchase-course', $args_load_tmpl, $course );

		ob_start();
		learn_press_get_template(
			$args_load_tmpl['template_name'],
			array(
				'user'   => $user,
				'course' => $course,
			),
			$args_load_tmpl['template_path'],
			$args_load_tmpl['default_path']
		);
		$html_btn = ob_get_clean();

		//$html_btn = sprintf( '<button class="lp-button button button-purchase-course">%s</button>', __( 'Buy Now', 'learnpress' ) );

		return $html_btn;
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
