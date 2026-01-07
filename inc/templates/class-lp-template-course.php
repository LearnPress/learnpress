<?php

use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;
use LearnPress\TemplateHooks\UserItem\UserCourseTemplate;

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

		add_action( 'the_post', array( $this, 'get_course' ) );
	}

	public function get_course() {
		// global $post;

		$this->course = learn_press_get_course();
	}

	public function course_sidebar_preview() {
		learn_press_get_template( 'single-course/sidebar/preview' );
	}

	public function course_buttons() {
		global $lp_user;
		$lp_user = learn_press_get_current_user();
		learn_press_get_template( 'single-course/buttons' );
	}

	public function course_graduation() {
		$user   = learn_press_get_current_user();
		$course = learn_press_get_course();

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
	 *
	 * @throws Exception
	 */
	public function button_retry( $course = null ) {
		_deprecated_function( __METHOD__, '4.3.2.4', 'UserCourseTemplate::html_btn_retake' );
		return;

		$user = learn_press_get_current_user();
		if ( empty( $course ) ) {
			$course = learn_press_get_course();
		}

		if ( ! $user || ! $course ) {
			return;
		}

		$can_retake_times = $user->can_retry_course( $course->get_id() );

		// Course has no items
		if ( empty( $course->count_items() ) ) {
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

		echo wp_kses_post( $course->get_image() );
	}

	/**
	 * @param LP_Quiz $item
	 */
	public function quiz_meta_questions( $item ) {
		$count = $item->count_questions();
		printf(
			'<span class="item-meta count-questions">%s</span>',
			sprintf( _n( '%1$d question', '%1$d questions', $count, 'learnpress' ), $count )
		);
	}

	/**
	 * @param LP_Quiz|LP_Lesson $item
	 */
	public function item_meta_duration( $item ) {
		$duration = learn_press_get_post_translated_duration( $item->get_id(), false );

		if ( $duration ) {
			echo '<span class="item-meta duration">' . $duration . '</span>';
		}
	}

	/**
	 * @var LP_Course_Item $item
	 */
	public function quiz_meta_final( $item ) {
		$course = $item->get_course();

		if ( ! $course || ! $course->is_final_quiz( $item->get_id() )
			|| $course->get_evaluation_type() != 'evaluate_final_quiz' ) {
			return;
		}

		echo '<span class="item-meta final-quiz">' . esc_html__( 'Final', 'learnpress' ) . '</span>';
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

	/**
	 * Display price or free of course, not button, it is label.
	 *
	 * @return void
	 * @since 4.0.0
	 * @version 1.0.3
	 */
	public function course_pricing() {
		$course = learn_press_get_course();
		$user   = learn_press_get_current_user();

		$courseModel     = CourseModel::find( $course->get_id(), true );
		$can_purchase    = $courseModel->can_purchase( UserModel::find( $user->get_id(), true ) );
		$userCourseModel = UserCourseModel::find( $user->get_id(), $course->get_id(), true );
		if ( get_current_user_id() ) {
			if ( $userCourseModel ) {
				if ( $userCourseModel->has_enrolled() ) {
					return;
				} elseif ( $can_purchase instanceof WP_Error ) {
					return;
				}
			}
		}

		$price_html = $course->get_course_price_html();
		learn_press_get_template( 'single-course/price', compact( 'course', 'user', 'price_html' ) );
	}

	/**
	 * Template purchase course button
	 *
	 * @editor tungnx
	 * @modify 4.1.3.1
	 * @throws Exception
	 * @version 4.0.2
	 */
	public function course_purchase_button( $course = null ) {
		_deprecated_function( __METHOD__, '4.3.2', 'SingleCourseTemplate::html_btn_purchase_course' );
		return;

		$singleCourseTemplate = SingleCourseTemplate::instance();
		$course               = CourseModel::find( get_the_ID(), true );
		$user                 = UserModel::find( get_current_user_id(), true );
		echo $singleCourseTemplate->html_btn_purchase_course( $course, $user );
	}

	/**
	 * Show button enroll course
	 *
	 * @editor tungnx
	 * @modify 4.1.3.1
	 * @throws Exception
	 * @version 4.0.3
	 */
	public function course_enroll_button( $course = null ) {
		_deprecated_function( __METHOD__, '4.3.2.4', 'SingleCourseTemplate::html_btn_enroll_course' );
		return;

		$singleCourseTemplate = SingleCourseTemplate::instance();
		$course               = CourseModel::find( get_the_ID(), true );
		$user                 = UserModel::find( get_current_user_id(), true );
		echo $singleCourseTemplate->html_btn_enroll_course( $course, $user );
		return;

		$can_show = true;
		$user     = learn_press_get_current_user();
		if ( empty( $course ) ) {
			$course = learn_press_get_course();
		}
		$error_code = '';

		try {
			if ( ! $course || ! $user ) {
				throw new Exception( 'User or Course is not exists' );
			}

			// User can not enroll course.
			$can_enroll_course = $user->can_enroll_course( $course->get_id(), false );
			if ( ! $can_enroll_course->check ) {
				$error_code = $can_enroll_course->code;
				throw new Exception( $can_enroll_course->message );
			}

			if ( $user->has_finished_course( $course->get_id() ) ) {
				$error_code = 'course_is_finished';
				throw new Exception( __( 'Course is finished', 'learnpress' ) );
			}
		} catch ( Throwable $e ) {
			if ( ! in_array( $error_code, [ 'course_is_enrolled', 'course_can_retry' ] ) ) {
				if ( $course && $course->is_free() ) {
					Template::print_message( $e->getMessage(), 'warning' );
				}
			}
			$can_show = false;
		}

		$can_show = apply_filters( 'learnpress/course/template/button-enroll/can-show', $can_show, $user, $course );
		if ( ! $can_show ) {
			return;
		}

		$args = array(
			'user'   => $user,
			'course' => $course,
		);

		learn_press_get_template( 'single-course/buttons/enroll.php', $args );
	}

	public function course_extra_requirements( $course_id ) {
		$course = LP_Course::get_course( $course_id );
		if ( ! $course ) {
			return;
		}

		$requirements = apply_filters(
			'learn-press/course-extra-requirements',
			$course->get_extra_info( 'requirements' ),
			$course_id
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

	public function course_extra_key_features( $course_id ) {
		$course = LP_Course::get_course( $course_id );
		if ( ! $course ) {
			return;
		}

		$key_features = apply_filters(
			'learn-press/course-extra-key-features',
			$course->get_extra_info( 'key_features' ),
			$course_id
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

	public function course_extra_target_audiences( $course_id ) {
		$course = LP_Course::get_course( $course_id );
		if ( ! $course ) {
			return;
		}

		$target_audiences = apply_filters(
			'learn-press/course-extra-target-audiences',
			$course->get_extra_info( 'target_audiences' ),
			$course_id
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

	/**
	 * Show template "continue" button con single course
	 *
	 * @throws Exception
	 * @modify 4.1.3.1
	 * @version 4.0.4
	 * @since  4.0.0
	 */
	public static function course_continue_button( $args = [] ) {
		_deprecated_function( __METHOD__, '4.3.2.4', 'UserCourseTemplate::html_btn_continue' );
		return;

		$course_id_param = $args['course-id'] ?? 0;
		$course_id       = ! empty( $course_id_param ) ? $course_id_param : get_the_ID();
		$courseModel     = CourseModel::find( $course_id, true );
		$user_id         = get_current_user_id();

		try {
			if ( ! $user_id || ! $courseModel ) {
				throw new Exception( 'User or Course not exists!' );
			}

			$userCourseModel = UserCourseModel::find( $user_id, $courseModel->get_id(), true );
			if ( ! $userCourseModel || ! $userCourseModel->has_enrolled() ) {
				throw new Exception( 'User not enrolled course' );
			}

			if ( $userCourseModel->has_finished() ) {
				throw new Exception( 'User has finished course' );
			}

			// Course has no items
			if ( empty( $courseModel->get_total_items() ) ) {
				throw new Exception( 'Course no any item' );
			}

			// Do not display continue button if course is block duration
			if ( $userCourseModel->timestamp_remaining_duration() === 0 ) {
				throw new Exception( 'Course is blocked' );
			}

			$section = [
				'start' => '<div>',
				'link'  => UserCourseTemplate::instance()->html_btn_continue( $userCourseModel ),
				'end'   => '</div>',
			];

			echo Template::combine_components( $section );
		} catch ( Throwable $e ) {

		}
	}

	public function course_finish_button( $course = null ) {
		_deprecated_function( __METHOD__, '4.3.2.4', 'UserCourseTemplate::html_btn_finish' );
		return;

		$user = learn_press_get_current_user();
		if ( empty( $course ) ) {
			$course = learn_press_get_course();
		}

		if ( ! $course ) {
			return;
		}

		// Course has no items
		if ( empty( $course->count_items() ) ) {
			return;
		}

		$check = $user->can_show_finish_course_btn( $course );

		if ( 'success' !== $check['status'] ) {
			return;
		}

		learn_press_get_template(
			'single-course/buttons/finish.php',
			array(
				'course' => $course,
				'user'   => $user,
			)
		);
	}

	/**
	 * Button course external link
	 *
	 * @throws Exception
	 * @editor tungnx
	 * @modify 4.1.3
	 */
	public function course_external_button( $course = null ) {
		_deprecated_function( __METHOD__, '4.3.2.4', 'SingleCourseTemplate::html_btn_external' );
		return;

		if ( empty( $course ) ) {
			$course = learn_press_get_course();
		}

		$user = learn_press_get_current_user();

		if ( ! $course ) {
			return;
		}

		$link = $course->get_external_link();
		if ( empty( $link ) || $user->has_purchased_course( $course->get_id() ) ) {
			return;
		}

		$user = learn_press_get_current_user();

		if ( $user && ! $user->has_enrolled_or_finished( $course->get_id() ) ) {
			// Remove all another buttons
			// learn_press_remove_course_buttons();
			learn_press_get_template( 'single-course/buttons/external-link.php' );

			// Add back other buttons for other courses
			add_action( 'learn-press/after-course-buttons', 'learn_press_add_course_buttons' );
		}
	}

	public function popup_header() {
		$user   = learn_press_get_current_user();
		$course = learn_press_get_course();

		if ( ! $user || ! $course ) {
			return;
		}

		$percentage      = 0;
		$total_items     = 0;
		$completed_items = 0;
		$course_data     = $user->get_course_data( $course->get_id() );

		if ( $course_data && ! empty( $course_data->get_user_id() ) && ! $course->is_no_required_enroll() ) {
			$course_results  = $course_data->get_result();
			$completed_items = $course_results['completed_items'];
			$total_items     = $course_results['count_items'];
			$percentage      = $course_results['count_items'] ? absint( $course_results['completed_items'] / $course_results['count_items'] * 100 ) : 0;
		}

		learn_press_get_template( 'single-course/content-item/popup-header', compact( 'user', 'course', 'total_items', 'completed_items', 'percentage' ) );
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
		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}

		$next_item = false;
		$prev_item = false;

		$next_id = $course->get_next_item();
		$prev_id = $course->get_prev_item();

		if ( $next_id ) {
			$next_item = $course->get_item( $next_id );
			if ( $next_item instanceof LP_Course_Item ) {
				$next_item->set_course( $course->get_id() );
			}
		}

		if ( $prev_id ) {
			$prev_item = $course->get_item( $prev_id );
			if ( $prev_item instanceof LP_Course_Item ) {
				$prev_item->set_course( $course->get_id() );
			}
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

	/**
	 * Display course curriculum.
	 *
	 * @since 4.1.6
	 * @since 4.2.5.5 remove code load old template user for course curriculum load page instead of via AJAX.
	 * @version 1.0.2
	 * @depreacted 4.2.8.7.1
	 */
	public function course_curriculum() {
		/**
		 * @var CourseModel $lpCourseModel
		 */
		global $lpCourseModel;
		$courseModel = CourseModel::find( get_the_ID(), true );
		if ( $lpCourseModel instanceof CourseModel ) {
			$courseModel = $lpCourseModel;
		}

		$course_item = LP_Global::course_item();
		$userModel   = UserModel::find( get_current_user_id(), true );

		$singleCourseTemplate = SingleCourseTemplate::instance();
		echo $singleCourseTemplate->html_curriculum( $courseModel, $userModel );
	}

	/**
	 * Display course curriculum.
	 *
	 * @since 4.1.6
	 * @since 4.2.5.5 remove code load old template user for course curriculum load page instead of via AJAX.
	 * @version 1.0.2
	 */
	public function course_curriculum_bk() {
		/**
		 * @var CourseModel $lpCourseModel
		 */
		global $lpCourseModel;
		$course_item = LP_Global::course_item();

		if ( $course_item ) { // Check if current item is viewable
			$course_id = 0;
			if ( $lpCourseModel ) {
				$course_id = $lpCourseModel->get_id();
			}

			$item_id    = (int) $course_item->get_id();
			$section_id = LP_Section_DB::getInstance()->get_section_id_by_item_id( $item_id, $course_id );
		}
		?>
		<div class="learnpress-course-curriculum" data-section="<?php echo esc_attr( $section_id ?? '' ); ?>"
			data-id="<?php echo esc_attr( $item_id ?? '' ); ?>">
			<?php lp_skeleton_animation_html( 10 ); ?>
		</div>
		<?php
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
		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}

		$price_html = $course->get_course_price_html();
		learn_press_get_template( 'loop/course/price', compact( 'course', 'price_html' ) );
	}

	public function begin_courses_loop() {
		learn_press_get_template( 'loop/course/loop-begin.php' );
	}

	public function end_courses_loop() {
		learn_press_get_template( 'loop/course/loop-end.php' );
	}

	public function course_item_content() {
		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}

		$item = LP_Global::course_item();

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
			echo '<style data-type="vc_shortcodes-custom-css">';
			echo wp_kses_post( $shortcodes_custom_css );
			echo '</style>';
		}
		// End

		$timestamp_remaining = $course->timestamp_remaining_duration();
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

	public function item_lesson_title() {
		$item            = LP_Global::course_item();
		$format          = $item->get_format();
		$format_template = learn_press_locate_template( "content-lesson/{$format}/title.php" );

		if ( 'standard' !== $format && file_exists( $format_template ) ) {
			include $format_template;

			return;
		}
		learn_press_get_template( 'content-lesson/title.php', array( 'lesson' => $item ) );
	}

	public function item_lesson_content() {
		$item            = LP_Global::course_item();
		$format          = $item->get_format();
		$format_template = learn_press_locate_template( "content-lesson/{$format}/content.php" );

		if ( 'standard' !== $format && file_exists( $format_template ) ) {
			include $format_template;

			return;
		}
		do_action( 'learn-press/lesson-start', $item );

		Template::instance()->get_frontend_template( 'content-lesson/content.php', array( 'lesson' => $item ) );
	}

	/**
	 * Get template button complete lesson
	 */
	public function item_lesson_complete_button() {
		$user   = learn_press_get_current_user();
		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}

		try {
			$item = LP_Global::course_item();
			if ( ! $user || ! $user->is_course_in_progress( $course->get_id() ) ) {
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
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	public function item_lesson_material() {
		try {
			do_action( 'learn-press/course-material/layout', [] );
		} catch ( Throwable $e ) {
			error_log( $e->getMessage() );
		}
	}

	/**
	 * Template show count items
	 *
	 * @since 4.0.0
	 * @version 1.0.2
	 * @editor tungnx
	 */
	public function count_object() {
		$course = CourseModel::find( get_the_ID(), true );
		if ( ! $course ) {
			return;
		}

		$lessons  = $course->count_items( LP_LESSON_CPT );
		$quizzes  = $course->count_items( LP_QUIZ_CPT );
		$students = $course->count_students();

		$counts = apply_filters(
			'learnpress/course/count/items',
			array(
				'lesson'  => sprintf(
					'<span class="meta-number">' . _n( '%d lesson', '%d lessons', $lessons, 'learnpress' ) . '</span>',
					$lessons
				),
				'quiz'    => sprintf(
					'<span class="meta-number">' . _n( '%d quiz', '%d quizzes', $quizzes, 'learnpress' ) . '</span>',
					$quizzes
				),
				'student' => sprintf(
					'<span class="meta-number">' . _n( '%d student', '%d students', $students, 'learnpress' ) . '</span>',
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
		if ( ! $course ) {
			return;
		}

		$boxes = apply_filters(
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

	/*public function metarials() {
		echo wp_kses_post( do_shortcode( '[learn_press_course_materials]' ) );
	}*/

	public function faqs() {
		$course = LP_Course::get_course( get_the_ID() );
		$faqs   = $course->get_faqs();
		if ( ! $faqs ) {
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

		$user = learn_press_get_current_user();

		if ( ! $user ) {
			return;
		}

		if ( $user->has_enrolled_or_finished( $this->course->get_id() ) ) {
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
		$course = learn_press_get_course();
		if ( ! $course ) {
			return;
		}

		$item = LP_Global::course_item();
		if ( ! $item ) {
			return;
		}

		$user                 = learn_press_get_current_user();
		$user_can_view_course = $user->can_view_content_course( $course->get_id() );
		$user_can_view_item   = $user->can_view_item( $item->get_id(), $user_can_view_course );
		if ( ! $user_can_view_item->flag ) {
			return;
		}

		if ( $item->setup_postdata() ) {

			if ( comments_open() || get_comments_number() ) {
				learn_press_get_template( 'single-course/item-comments' );
			}
			$item->reset_postdata();
		}
	}


	public function course_comment_template() {
		if ( comments_open() || get_comments_number() ) {
			add_filter( 'deprecated_file_trigger_error', '__return_false' );
			comments_template();
			remove_filter( 'deprecated_file_trigger_error', '__return_false' );
		}
	}

	/**
	 * Show info time handle of user
	 *
	 * @throws Exception
	 */
	public function user_time() {
		$user = learn_press_get_current_user();

		if ( ! $user ) {
			return;
		}

		if ( ! $user->has_enrolled_or_finished( $this->course->get_id() ) ) {
			return;
		}

		/**
		 * @var LP_User_Item_Course
		 */
		$user_course = $user->get_course_data( $this->course->get_id() );

		if ( ! $user_course ) {
			return;
		}

		$status          = $user_course->get_status();
		$start_time      = $user_course->get_start_time();
		$end_time        = $user_course->get_end_time();
		$expiration_time = $user_course->get_expiration_time();
		$data            = [
			'status'          => $status,
			'start_time'      => $start_time,
			'end_time'        => $end_time,
			'expiration_time' => $expiration_time,
		];

		learn_press_get_template(
			'single-course/sidebar/user-time',
			compact( 'data' )
		);
	}

	/**
	 * Animation placholder in user-progress file.
	 * Content will show in class-rest-lazy-load-controller file.
	 *
	 * @return void
	 * @throws Exception
	 * @author Nhamdv.
	 */
	public function user_progress() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$course = learn_press_get_course();
		$user   = learn_press_get_current_user();

		if ( ! $course ) {
			return;
		}

		if ( ! $user->has_enrolled_or_finished( $course->get_id() ) ) {
			return;
		}

		if ( LP_LAZY_LOAD_ANIMATION ) {
			echo '<div class="lp-course-progress-wrapper">';
			lp_skeleton_animation_html();
			echo '</div>';
		} else {
			$course_data = $user->get_course_data( $course->get_id() );
			if ( ! $course_data ) {
				return;
			}

			$course_results = $course_data->calculate_course_results();

			learn_press_get_template(
				'single-course/sidebar/user-progress',
				compact( 'user', 'course', 'course_data', 'course_results' )
			);
		}
	}

	public function course_extra_boxes_position_control() {
		$course = LP_Course::get_course( get_the_ID() );
		$user   = learn_press_get_current_user();
		if ( ! $user || ! $course ) {
			return;
		}

		$enrolled = $user->has_enrolled_course( $course->get_id() );
		if ( $enrolled ) {
			remove_action(
				'learn-press/course-content-summary',
				LearnPress::instance()->template( 'course' )->func( 'course_extra_boxes' ),
				40
			);
		} else {
			remove_action(
				'learn-press/course-content-summary',
				LearnPress::instance()->template( 'course' )->func( 'course_extra_boxes' ),
				70
			);
		}
	}

	/**
	 * Template for case not any courses
	 *
	 * @author Nhamdv
	 * @since 4.1.2
	 */
	public function no_courses_found() {
		learn_press_get_template( 'global/no-courses-found' );
	}
}

return new LP_Template_Course();
