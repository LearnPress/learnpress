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
use LearnPress\TemplateHooks\Instructor\SingleInstructorTemplate;
use LP_Course;
use LP_Datetime;
use Throwable;

class SingleCourseTemplate {
	use Singleton;

	public function init() {
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
			'address'                => $this->html_address( $course ),
			'wrapper_info_one_close' => '</div>',
		];
		$html_info_one    = Template::combine_components( $section_info_one );

		$html_wrapper_section_left = [
			'<div class="lp-single-offline-course__left">' => '</div>'
		];
		$section_left              = [
			'breadcrumb'  => $html_breadcrumb,
			'title'       => sprintf( '<h1>%s</h1>', $this->html_title( $course ) ),
			'info_one'    => $html_info_one,
			'image'       => $this->html_image( $course ),
			'description' => $this->html_description( $course ),
			'instructor'  => $html_instructor
		];
		$html_section_left         = Template::combine_components( $section_left );
		$html_section_left         = Template::instance()->nest_elements( $html_wrapper_section_left, $html_section_left );

		// Section right

		// Info two
		$data_info_meta =
			apply_filters(
				'learn-press/course/info-meta',
				[
					'price'        => [
						'label' => sprintf( '%s %s', learn_press_get_currency(), __( 'Price', 'learnpress' ) ),
						'value' => $this->html_price( $course )
					],
					'deliver_type' => [
						'label' => sprintf( '<span class="lp-icon-bookmark-o"></span> %s', __( 'Deliver type', 'learnpress' ) ),
						'value' => $this->html_deliver_type( $course )
					],
					'capacity'     => [
						'label' => sprintf( '<span class="lp-icon-students"></span> %s', __( 'Capacity', 'learnpress' ) ),
						'value' => $this->html_capacity( $course )
					],
					'level'        => [
						'label' => sprintf( '<span class="lp-icon-signal"></span> %s', __( 'Level', 'learnpress' ) ),
						'value' => $this->html_deliver_type( $course )
					],
					'duration'     => [
						'label' => sprintf( '<span class="lp-icon-clock-o"></span> %s', __( 'Duration', 'learnpress' ) ),
						'value' => $this->html_duration( $course )
					],
					'lessons'      => [
						'label' => sprintf( '<span class="lp-icon-book"></span> %s', __( 'Lessons', 'learnpress' ) ),
						'value' => $this->html_deliver_type( $course )
					],
				],
				$course
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
			'btn_contact' 			=> $this->html_btn_external( $course ),
			'btn_buy'     			=> $this->html_btn_purchase_course( $course ),
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
			'featured_review'             => $this->html_feature_review( $course ),
			'wrapper_section_right_end'   => '</div>',
		];
		$html_section_right = Template::combine_components( $section_right );
		// End section right

		$sections = [
			'wrapper_section_offline_course_start' => '<div class="lp-single-offline-course">',
			'section_left'                         => $html_section_left,
			'section_right'                        => $html_section_right,
			'wrapper_section_offline_course_end'   => '</div>',
		];

		echo Template::combine_components( $sections );
	}

	public function sections( $data = [] ) {
	}

	/**
	 * Get display title course.
	 *
	 * @param LP_Course|CourseModel $course
	 *
	 * @return string
	 */
	public function html_title( $course ): string {
		$html_wrapper = [
			'<span class="course-title">' => '</span>',
		];

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
	 * @param LP_Course $course
	 *
	 * @return string
	 */
	public function html_categories( LP_Course $course ): string {
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
			// @since 4.2.7
			$price_html = apply_filters( 'learn-press/course/html-price', $price_html, $course );
		}

		return sprintf( '<span class="course-price">%s</span>', $price_html );
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
	 * @param LP_Course $course
	 *
	 * @return string
	 * @since 4.2.3.4
	 * @version 1.0.1
	 */
	public function html_count_student( LP_Course $course ): string {
		$count_student = $course->get_total_user_enrolled_or_purchased();
		$fake_student  = $course->get_fake_students();
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
	 * @param LP_Course $course
	 * @param string $string_type not has prefix 'lp_'
	 * @param array $data
	 *
	 * @return string
	 */
	public function html_count_item( LP_Course $course, string $string_type, array $data = [] ): string {
		$post_type_item = 'lp_' . $string_type;
		$count_item     = $course->count_items( $post_type_item );

		switch ( $post_type_item ) {
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

		$html_wrapper = [
			'<div class="course-count-' . $string_type . '">' => '</div>',
		];

		return Template::instance()->nest_elements( $html_wrapper, $content );
	}

	/**
	 * Get html level course.
	 *
	 * @param LP_Course $course
	 *
	 * @return string
	 * @since 4.2.3.5
	 * @version 1.0.0
	 */
	public function html_level( LP_Course $course ): string {
		$content = '';

		try {
			$level  = $course->get_level();
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

			$html_wrapper = [
				'<span class="course-address">' => '</span>',
			];
			$content      = Template::instance()->nest_elements( $html_wrapper, $address );
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

	public function html_btn_purchase_course( CourseModel $course ) {
		$html_btn = sprintf( '<button class="lp-button button button-purchase-course">%s</button>', __( 'Buy Now', 'learnpress' ) );

		return $html_btn;
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
