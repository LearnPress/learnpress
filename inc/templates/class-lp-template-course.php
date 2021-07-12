<?php

/**
 * Class LP_Course_Template
 *
 * Groups templates related course and items
 *
 * @since 3.x.x
 */
class LP_Template_Course extends LP_Abstract_Template {

	/**
	 * @var LP_Course
	 */
	public $course = null;

	/**
	 * LP_Template_Course constructor.
	 */
	public function __construct() {
		parent::__construct();

		add_action( 'the_post', array( $this, 'get_course' ), 1000 );
	}

	public function get_course() {
		global $post;

		$this->course = LP_Global::course();
	}

	public function course_sidebar_preview() {
		learn_press_get_template( 'single-course/sidebar/preview' );
	}

	public function course_buttons() {
		learn_press_get_template( 'single-course/buttons' );
	}

	public function course_graduation() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! $user || ! $course ) {
			return;
		}

		if ( ! $user->has_finished_course( $course->get_id() ) ) {
			return;
		}

		$graduation = $user->get_course_grade( $course->get_id() );

		learn_press_get_template( 'single-course/graduation', array( 'graduation' => $graduation ) );
	}

	/**
	 * Show button retry course
	 */
	public function button_retry() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! $user || ! $course ) {
			return;
		}

		$can_retake_times = $user->can_retry_course( $course->get_id() );

		//Course has no items
		if ( empty( $course->get_item_ids() ) ) {
			return;
		}

		if ( $can_retake_times ) {
			learn_press_get_template(
				'single-course/buttons/retry',
				array( 'can_retake_times' => $can_retake_times )
			);
		}
	}

	public function course_media_preview() {
		$course = learn_press_get_course();

		echo $course->get_image();
	}

	public function loop_item_user_progress() {
		$course = LP_Global::course();
		$user   = LP_Global::user();

		if ( ! $user || ! $course ) {
			return;
		}

		if ( $user->has_enrolled_course( $course->get_id() ) ) {
			echo $user->get_course_status( $course->get_id() );
		}
	}

	/**
	 * @param LP_Quiz $item
	 */
	public function quiz_meta_questions( $item ) {
		$count = $item->count_questions();
		echo '<span class="item-meta count-questions">' . sprintf(
			$count ? _n(
				'%d question',
				'%d questions',
				$count,
				'learnpress'
			) : __(
				'%d question',
				'learnpress'
			),
			$count
		) . '</span>';
	}

	/**
	 * @param LP_Quiz|LP_Lesson $item
	 */
	public function item_meta_duration( $item ) {
		$duration = $item->get_duration();

		if ( is_a( $duration, 'LP_Duration' ) && $duration->get() ) {
			$format = array(
				'day'    => _x( '%s day', 'duration', 'learnpress' ),
				'hour'   => _x( '%s hour', 'duration', 'learnpress' ),
				'minute' => _x( '%s min', 'duration', 'learnpress' ),
				'second' => _x( '%s sec', 'duration', 'learnpress' ),
			);

			echo '<span class="item-meta duration">' . $duration->to_timer( $format, true ) . '</span>';
		} elseif ( is_string( $duration ) && strlen( $duration ) ) {
			echo '<span class="item-meta duration">' . $duration . '</span>';
		}
	}

	/**
	 * @param LP_Quiz $item
	 */
	public function quiz_meta_final( $item ) {
		$course = LP_Global::course();
		if ( ! $course->is_final_quiz( $item->get_id() ) ) {
			return;
		}
		echo '<span class="item-meta final-quiz">' . __( 'Final', 'learnpress' ) . '</span>';
	}

	public function course_button() {
		echo '[COURSE BUTTON]';
	}

	public function course_title() {
		echo '[COURSE TITLE]';
	}

	public function courses_top_bar() {
		learn_press_get_template( 'courses-top-bar' );
	}

	public function course_pricing() {
		$course = learn_press_get_course();
		$user   = learn_press_get_current_user();

		if ( $user->has_enrolled_course( get_the_ID() ) ) {
			return;
		}

		learn_press_get_template( 'single-course/price' );
	}

	/**
	 * Template purchase course button
	 *
	 * @throws Exception
	 */
	public function course_purchase_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();

		if ( ! $user || ! $course ) {
			return;
		}

		if ( $course->get_external_link() ) {
			return;
		}

		if ( ! $user->can_purchase_course( $course->get_id() ) ) {
			return;
		}

		// Course is not require enrolling.
		if ( $course->is_no_required_enroll() ) {
			return;
		}

		$args_load_tmpl = array(
			'template_name' => 'single-course/buttons/purchase.php',
			'template_path' => '',
			'default_path'  => '',
		);

		$args_load_tmpl = apply_filters( 'learn-press/tmpl-button-purchase-course', $args_load_tmpl, $course );

		learn_press_get_template(
			$args_load_tmpl['template_name'],
			array( 'course' => $course ),
			$args_load_tmpl['template_path'],
			$args_load_tmpl['default_path']
		);
	}

	/**
	 * Show button enroll course
	 *
	 * @TODO check this function - tungnx
	 */
	public function course_enroll_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! $course || ! $user ) {
			return;
		}

		// User can not enroll course.
		if ( ! $user->can_enroll_course( $course->get_id() ) ) {
			return;
		}

		$purchased = $user->has_purchased_course( $course->get_id() );

		// For free course and user does not purchased
		$course_data = $user->get_course_data( $course->get_id() );

		if ( $course->is_free() && ! ( $course_data && $course_data->get_user_item_id() > 0 ) ) {
			learn_press_get_template( 'single-course/buttons/enroll.php' );
		} elseif ( $purchased && $course_data ) {
			if ( in_array( $course_data->get_status(), array( 'purchased', '' ) ) ) {
				learn_press_get_template( 'single-course/buttons/enroll.php' );
			}
		}
	}

	public function course_extra_requirements() {
		$course = LP_Course::get_course( get_the_ID() );

		$requirements = apply_filters(
			'learn-press/course-extra-requirements',
			$course->get_extra_info( 'requirements' ),
			get_the_ID()
		);

		if ( ! $requirements ) {
			return;
		}

		learn_press_get_template(
			'single-course/sidebar/course-extra',
			array(
				'type'    => 'requirements',
				'title'   => esc_html__( 'Requirements', 'learnpress' ),
				'content' => $requirements,
			)
		);
	}

	public function course_extra_key_features() {
		$course = LP_Course::get_course( get_the_ID() );

		$key_features = apply_filters(
			'learn-press/course-extra-key-features',
			$course->get_extra_info( 'key_features' ),
			get_the_ID()
		);

		if ( ! $key_features ) {
			return;
		}

		learn_press_get_template(
			'single-course/sidebar/course-extra',
			array(
				'type'    => 'key-features',
				'title'   => esc_html__( 'Key features', 'learnpress' ),
				'content' => $key_features,
			)
		);
	}

	public function course_extra_target_audiences() {
		$course = LP_Course::get_course( get_the_ID() );

		$target_audiences = apply_filters(
			'learn-press/course-extra-target-audiences',
			$course->get_extra_info( 'target_audiences' ),
			get_the_ID()
		);

		if ( ! $target_audiences ) {
			return;
		}

		learn_press_get_template(
			'single-course/sidebar/course-extra',
			array(
				'type'    => 'target-audiences',
				'title'   => esc_html__( 'Target audiences', 'learnpress' ),
				'content' => $target_audiences,
			)
		);
	}

	public function course_retake_button() {
		if ( ! isset( $course ) ) {
			$course = learn_press_get_course();
		}

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		if ( ! isset( $user ) ) {
			$user = learn_press_get_current_user();
		}

		// If user has not finished course
		if ( ! $user->has_finished_course( $course->get_id() ) ) {
			return;
		}
		learn_press_get_template( 'single-course/buttons/retake.php' );
	}

	public function course_continue_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! $user || ! $course ) {
			return;
		}

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		$course_data = $user->get_course_data( $course->get_id() );

		if ( false === $course_data ) {
			return;
		}

		if ( ! learn_press_is_enrolled_slug( $course_data->get_status() ) ) {
			return;
		}

		if ( ! $course_data->get_item_at( 0 ) ) {
			return;
		}

		//Course has no items
		if ( empty( $course->get_item_ids() ) ) {
			return;
		}

		// Do not display continue button if course is block duration
		if ( $user->can_view_content_course( $course->get_id() )->key === LP_BLOCK_COURSE_DURATION_EXPIRE ) {
			return;
		}

		learn_press_get_template( 'single-course/buttons/continue.php' );
	}

	public function can_show_finish_course_btn( $course, $user ) {
		$return = array(
			'status'  => 'fail',
			'message' => '',
		);

		try {
			if ( ! $course || ! $user ) {
				throw new Exception( esc_html__( 'Error: No Course or User available.', 'learnpress' ) );
			}

			$course_id = $course->get_id();

			$course_data    = $user->get_course_data( $course_id );
			$course_results = $course_data->calculate_course_results();

			$is_all_completed = $user->is_completed_all_items( $course_id );

			if ( ! $user->is_course_in_progress( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: Course is not in-progress.', 'learnpress' ) );
			}

			$has_finish = get_post_meta( $course_id, '_lp_has_finish', true ) ? get_post_meta( $course_id, '_lp_has_finish', true ) : 'yes';
			$is_passed  = $user->has_reached_passing_condition( $course_id );

			if ( ! $is_passed && $has_finish === 'no' ) {
				throw new Exception( esc_html__( 'Error: Course is not has finish.', 'learnpress' ) );
			}

			if ( ! $is_all_completed && $has_finish === 'yes' && ! $is_passed ) {
				throw new Exception( esc_html__( 'Error: Cannot finish course.', 'learnpress' ) );
			}

			if ( ! apply_filters( 'lp_can_finish_course', true ) ) {
				throw new Exception( esc_html__( 'Error: Filter disable finish course.', 'learnpress' ) );
			}

			$return['status'] = 'success';
		} catch ( Exception $e ) {
			$return['message'] = $e->getMessage();
		}

		return $return;
	}

	public function course_finish_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		$check = $this->can_show_finish_course_btn( $course, $user );

		//Course has no items
		if ( empty( $course->get_item_ids() ) ) {
			return;
		}

		if ( $check['status'] === 'success' ) {
			learn_press_get_template(
				'single-course/buttons/finish.php',
				array(
					'course' => $course,
					'user'   => $user,
				)
			);
		}
	}

	public function course_external_button() {
		$course = LP_Global::course();

		if ( ! $course ) {
			return;
		}

		if ( ! $link = $course->get_external_link() ) {
			return;
		}

		$user = learn_press_get_current_user();

		if ( ! $user->has_enrolled_course( $course->get_id() ) ) {
			// Remove all other buttons
			learn_press_remove_course_buttons();
			learn_press_get_template( 'single-course/buttons/external-link.php' );
			// Add back other buttons for other courses
			add_action( 'learn-press/after-course-buttons', 'learn_press_add_course_buttons' );
		}
	}

	public function popup_header() {
		learn_press_get_template( 'single-course/content-item/popup-header' );
	}

	public function popup_sidebar() {
		learn_press_get_template( 'single-course/content-item/popup-sidebar' );
	}

	/**
	 * Get single item's course
	 */
	public function popup_content() {
		learn_press_get_template( 'single-course/content-item/popup-content' );
	}

	public function popup_footer() {
		learn_press_get_template( 'single-course/content-item/popup-footer' );
	}

	public function popup_footer_nav() {
		$course    = LP_Global::course();
		$next_item = $prev_item = false;

		$next_id = $course->get_next_item();
		$prev_id = $course->get_prev_item();

		if ( $next_id ) {
			$next_item = $course->get_item( $next_id );
			$next_item->set_course( $course->get_id() );
		}

		if ( $prev_id ) {
			$prev_item = $course->get_item( $prev_id );
			$prev_item->set_course( $course->get_id() );
		}

		if ( ! $prev_item && ! $next_item ) {
			return;
		}

		learn_press_get_template(
			'single-course/content-item/nav.php',
			array(
				'next_item' => $next_item,
				'prev_item' => $prev_item,
			)
		);
	}

	public function course_curriculum() {
		learn_press_get_template( 'single-course/tabs/curriculum' );
	}

	/**
	 * Get template content item's course
	 */
	public function course_content_item() {
		learn_press_get_template( 'single-course/content-item' );
	}

	public function courses_loop_item_meta() {
		learn_press_get_template( 'loop/course/meta' );
	}

	public function courses_loop_item_info_begin() {
		learn_press_get_template( 'loop/course/info-begin' );
	}

	public function courses_loop_item_info_end() {
		learn_press_get_template( 'loop/course/info-end' );
	}

	public function courses_loop_item_price() {
		learn_press_get_template( 'loop/course/price' );
	}

	public function begin_courses_loop() {
		learn_press_get_template( 'loop/course/loop-begin.php' );
	}

	public function end_courses_loop() {
		learn_press_get_template( 'loop/course/loop-end.php' );
	}

	public function course_item_content() {
		$item = LP_Global::course_item();

		// if ( $item->is_blocked() ) {
		// learn_press_get_template( 'global/block-content.php' );
		//
		// return;
		// }

		/**
		 * Fix only for WPBakery load style inline
		 * custom CSS is provided, load inline style.
		 *
		 * @editor tuanta
		 * @since 3.2.8.1
		 */
		$shortcodes_custom_css = get_post_meta( $item->get_id(), '_wpb_shortcodes_custom_css', true );

		if ( ! empty( $shortcodes_custom_css ) ) {
			$shortcodes_custom_css = strip_tags( $shortcodes_custom_css );
			echo '<style type="text/css" data-type="vc_shortcodes-custom-css">';
			echo $shortcodes_custom_css;
			echo '</style>';
		}
		// End

		// Get timestamp remaining duration of course
		$timestamp_remaining = $item->get_course()->timestamp_remaining_duration();

		if ( $timestamp_remaining > 0 ) {
			echo '<input type="hidden" name="lp-course-timestamp-remaining" value="' . esc_attr( $timestamp_remaining ) . '">';
		}
		// End

		$item_template_name = learn_press_locate_template( 'single-course/content-item-' . $item->get_item_type() . '.php' );

		if ( file_exists( $item_template_name ) ) {
			learn_press_get_template( 'single-course/content-item-' . $item->get_item_type() . '.php' );
		} else {
			echo esc_html( sprintf( 'File %s not exists', $item_template_name ) );
		}
	}

	public function remaining_time() {

		if ( ! $course = LP_Global::course() ) {
			return;
		}

		if ( ! $user = LP_Global::user() ) {
			return;
		}

		if ( false === ( $remain = $user->get_course_remaining_time( $course->get_id() ) ) ) {

			return;
		}

		if ( $user->has_finished_course( $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'single-course/remaining-time.php', array( 'remaining_time' => $remain ) );
	}

	public function item_lesson_title() {
		$item = LP_Global::course_item();

		if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/{$format}/title.php" ) ) ) {
			include $format_template;

			return;
		}
		learn_press_get_template( 'content-lesson/title.php', array( 'lesson' => $item ) );
	}

	public function item_lesson_content() {
		$item = LP_Global::course_item();

		if ( ( 'standard' !== ( $format = $item->get_format() ) ) && file_exists( $format_template = learn_press_locate_template( "content-lesson/{$format}/content.php" ) ) ) {
			include $format_template;

			return;
		}
		do_action( 'learn-press/lesson-start', $item );

		learn_press_get_template( 'content-lesson/content.php', array( 'lesson' => $item ) );
	}

	public function item_quiz_content() {
		$item = LP_Global::course_item();

		learn_press_get_template( 'content-quiz/js.php' );
	}

	public function item_lesson_content_blocked() {
		$item = LP_Global::course_item();

		learn_press_get_template( 'global/block-content.php' );
	}

	/**
	 * Get template button complete lesson
	 */
	public function item_lesson_complete_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();
		$item   = LP_Global::course_item();

		if ( ! $user || ! $course || ! $user->is_course_in_progress( $course->get_id() ) ) {
			return;
		}

		// The complete button is not displayed when the course is locked --hungkv--
		if ( $user->can_view_content_course( $course->get_id() )->key === LP_BLOCK_COURSE_DURATION_EXPIRE ) {
			return;
		}

		learn_press_get_template(
			'content-lesson/button-complete.php',
			array(
				'user'   => $user,
				'course' => $course,
				'item'   => $item,
			)
		);
	}

	public function lesson_comment_form() {
		global $post;

		if ( ! $course = LP_Global::course() ) {
			return;
		}

		if ( ! $lesson = LP_Global::course_item() ) {
			return;
		}

		$user = learn_press_get_current_user();

		if ( $lesson->setup_postdata() ) {

			if ( comments_open() || get_comments_number() ) {
				comments_template();
			}
			$lesson->reset_postdata();
		}

	}

	public function count_object() {
		$course = learn_press_get_course();

		if ( ! $course ) {
			return;
		}

		$lessons = $course->get_items( LP_LESSON_CPT );
		$quizzes = $course->get_items( LP_QUIZ_CPT );

		$lessons  = count( $lessons );
		$quizzes  = count( $quizzes );
		$students = $course->count_students();

		$counts = apply_filters(
			'learn-press/count-meta-objects',
			array(
				'lesson'  => sprintf(
					$lessons > 1 ? __(
						'<span class="meta-number">%d</span> lessons',
						'learnpress'
					) : __(
						'<span class="meta-number">%d</span> lesson',
						'learnpress'
					),
					$lessons
				),
				'quiz'    => sprintf(
					$quizzes > 1 ? __(
						'<span class="meta-number">%d</span> quizzes',
						'learnpress'
					) : __(
						'<span class="meta-number">%d</span> quiz',
						'learnpress'
					),
					$quizzes
				),
				'student' => sprintf(
					$students > 1 ? __(
						'<span class="meta-number">%d</span> students',
						'learnpress'
					) : __(
						'<span class="meta-number">%d</span> student',
						'learnpress'
					),
					$students
				),
			),
			array( $lessons, $quizzes, $students )
		);

		foreach ( $counts as $object => $count ) {
			learn_press_get_template(
				'single-course/meta/count',
				array(
					'count'  => $count,
					'object' => $object,
				)
			);
		}
	}

	public function course_extra_boxes() {
		$course = LP_Course::get_course( get_the_ID() );
		$boxes  = apply_filters(
			'learn-press/course-extra-boxes-data',
			array(
				array(
					'title' => __( 'Requirements', 'learnpress' ),
					'items' => $course->get_extra_info( 'requirements' ),
				),
				array(
					'title' => __( 'Features', 'learnpress' ),
					'items' => $course->get_extra_info( 'key_features' ),
				),
				array(
					'title' => __( 'Target audiences', 'learnpress' ),
					'items' => $course->get_extra_info( 'target_audiences' ),
				),
			)
		);

		$is_checked = 0;
		foreach ( $boxes as $box ) {

			if ( ! isset( $box['items'] ) || ! $box['items'] ) {
				continue;
			}

			if ( ! $is_checked ) {
				$box['checked'] = true;
				$is_checked     = true;
			}

			learn_press_get_template( 'single-course/extra-info', $box );
		}

	}

	public function faqs() {
		$course = LP_Course::get_course( get_the_ID() );

		if ( ! $faqs = $course->get_faqs() ) {
			return;
		}

		foreach ( $faqs as $faq ) {
			learn_press_get_template( 'single-course/tabs/faqs', $faq );
		}
	}

	public function sidebar() {
	}

	public function course_featured_review() {
		$review_content = get_post_meta( $this->course->get_id(), '_lp_featured_review', true );

		if ( ! $review_content ) {
			return;
		}

		$user = LP_Global::user();

		if ( ! $user ) {
			return;
		}

		if ( $user->has_enrolled_course( $this->course->get_id() ) ) {
			return;
		}

		learn_press_get_template(
			'single-course/featured-review',
			array(
				'review_content' => $review_content,
				'review_value'   => 5,
			)
		);
	}

	public function instructor_socials() {
		$instructor = $this->course->get_instructor();
		$socials    = $instructor->get_profile_socials( $instructor->get_id() );

		foreach ( $socials as $social ) {
			echo $social;
		}
	}

	public function has_sidebar() {
		$actions = array(
			'learn-press/before-course-summary-sidebar',
			'learn-press/course-summary-sidebar',
			'learn-press/after-course-summary-sidebar',
		);

		foreach ( $actions as $action ) {
			if ( has_action( $action ) ) {
				return true;
			}
		}

		return false;
	}

	// button readmore in archive courses
	public function course_readmore() {
		?>
		<div class="course-readmore">
			<a href="<?php the_permalink(); ?>"><?php echo esc_html__( 'View More', 'learnpress' ); ?></a>
		</div>
		<?php
	}

	public function course_item_comments() {
		global $post;

		if ( ! $course = LP_Global::course() ) {
			return;
		}

		if ( ! $item = LP_Global::course_item() ) {
			return;
		}

		$user = learn_press_get_current_user();

		// if ( ! $user->is_admin() && ! $user->has_course_status( $course->get_id(), array( 'enrolled', 'finished' ) ) ) {
		// return;
		// }

		if ( $item->setup_postdata() ) {

			if ( comments_open() || get_comments_number() ) {
				learn_press_get_template( 'single-course/item-comments' );
			}
			$item->reset_postdata();
		}
	}

	public function course_comment_template() {
		global $post;

		if ( comments_open() || get_comments_number() ) {
			comments_template();
		}
	}

	public function user_time() {
		$user        = LP_Global::user();
		$course_data = $user->get_course_data( $this->course->get_id() );

		$status = $user->get_course_status( $this->course->get_id() );

		if ( ! in_array( $status, learn_press_course_enrolled_slugs() ) ) {
			return;
		}

		$start_time      = $course_data->get_start_time();
		$end_time        = $course_data->get_end_time();
		$expiration_time = $course_data->get_expiration_time();

		learn_press_get_template(
			'single-course/sidebar/user-time',
			compact( 'status', 'start_time', 'end_time', 'expiration_time' )
		);
	}

	/**
	 * Animation placholder in user-progress file.
	 * Content will show in class-rest-lazy-load-controller file.
	 *
	 * @author Nhamdv.
	 * @return void
	 */
	public function user_progress() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$course = LP_Global::course();
		$user   = LP_Global::user();

		if ( ! $course || ! $user ) {
			return;
		}

		$course_data = $user->get_course_data( $course->get_id() );

		if ( ! $user->has_enrolled_course( $course->get_id() ) || $course_data->get_status() === LP_COURSE_PURCHASED ) {
			return;
		}

		if ( LP_LAZY_LOAD_ANIMATION ) {
			echo '<div class="lp-course-progress-wrapper">';
			echo lp_skeleton_animation_html( 3 );
			echo '</div>';
		} else {
			learn_press_get_template(
				'single-course/sidebar/user-progress',
				array(
					'course' => $course,
					'user'   => $user,
				)
			);
		}
	}

	public function course_extra_boxes_position_control() {
		$course = LP_Course::get_course( get_the_ID() );
		$user   = LP_Global::user();

		if ( ! $user || ! $course ) {
			return;
		}

		$enrolled = $user->has_enrolled_course( $course->get_id() );
		if ( $enrolled ) {
			remove_action(
				'learn-press/course-content-summary',
				LP()->template( 'course' )->func( 'course_extra_boxes' ),
				40
			);
		} else {
			remove_action(
				'learn-press/course-content-summary',
				LP()->template( 'course' )->func( 'course_extra_boxes' ),
				70
			);
		}
	}

	public function no_courses_found() {
		learn_press_get_template( 'global/no-courses-found' );
	}
}

return new LP_Template_Course();
