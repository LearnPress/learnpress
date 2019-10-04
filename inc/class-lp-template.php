<?php

/**
 * Class LP_Template
 *
 * @since 4.x.x
 */
class LP_Template {

	/**
	 * @var LP_Template
	 */
	protected static $instance = null;

	/**
	 * LP_Template constructor.
	 */
	protected function __construct() {
	}

	public function course_sidebar() {
		learn_press_get_template( 'single-course/sidebar' );
	}

	public function course_sidebar_preview() {
		learn_press_get_template( 'single-course/sidebar/preview' );
	}

	public function course_buttons() {
		learn_press_get_template( 'single-course/buttons' );
	}

	public function course_media_preview() {
		echo get_the_post_thumbnail();
	}

	public function course_button() {
		echo "[COURSE BUTTON]";
	}

	public function course_title() {
		echo "[COURSE TITLE]";
	}

	public function courses_top_bar() {
		learn_press_get_template( 'courses-top-bar' );
	}

	public function course_pricing() {
		learn_press_get_template( 'single-course/price' );
	}

	public function course_purchase_button() {
		$course = LP_Global::course();
		$user   = LP_Global::user();

		if ( $course->get_external_link() ) {
			return;
		}

		// If course is not published
		if ( ! $course->is_publish() ) {
			return;
		}

		// Course is not require enrolling
		if ( ! $course->is_required_enroll() || $course->is_free() || $user->has_enrolled_course( $course->get_id() ) ) {
			return;
		}

		// If course is reached limitation.
		if ( ! $course->is_in_stock() ) {
			if ( $message = apply_filters( 'learn-press/maximum-students-reach', __( 'This course is out of stock', 'learnpress' ) ) ) {
				learn_press_display_message( $message );
			}

			return;
		}

		// User can not purchase course
		if ( ! $user->can_purchase_course( $course->get_id() ) ) {
			return;
		}

		// If user has already purchased course but has not finished yet.
		if ( $user->has_purchased_course( $course->get_id() ) && 'finished' !== $user->get_course_status( $course->get_id() ) ) {
			return;
		}

		// If the order contains course is processing
		if ( ( $order = $user->get_course_order( $course->get_id() ) ) && $order->get_status() === 'processing' ) {
			if ( $message = apply_filters( 'learn-press/order-processing-message', __( 'Your order is waiting for processing', 'learnpress' ) ) ) {
				learn_press_display_message( $message );
			}

			return;
		}

		learn_press_get_template( 'single-course/buttons/purchase.php' );
	}

	public function course_enroll_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( $course->get_external_link() ) {
			learn_press_show_log( 'Course has external link' );

			return;
		}

		// If course is not published
		if ( ! $course->is_publish() ) {
			learn_press_show_log( 'Course is not published' );

			return;
		}

		// Locked course for user
		if ( $user->is_locked_course( $course->get_id() ) ) {
			learn_press_show_log( 'Course is locked' );

			return;
		}

		// Course out of stock (full students)
		if ( ! $course->is_in_stock() ) {
			return;
		}

		// Course is not require enrolling
		if ( ! $course->is_required_enroll() ) {
			return;
		}

		// User can not enroll course
		if ( ! $user->can_enroll_course( $course->get_id() ) ) {
			return;
		}

		$purchased = $user->has_purchased_course( $course->get_id() );

		// For free course and user does not purchased
		if ( $course->is_free() && ! $purchased ) {
			learn_press_get_template( 'single-course/buttons/enroll.php' );
		} elseif ( $purchased && $course_data = $user->get_course_data( $course->get_id() ) ) {
			if ( in_array( $course_data->get_status(), array( 'purchased', '' ) ) ) {
				learn_press_get_template( 'single-course/buttons/enroll.php' );
			}
		}
	}

	public function course_extra_requirements() {
		$requirements = apply_filters(
			'learn-press/course-extra-requirements',
			get_post_meta( get_the_ID(), '_lp_requirements', true ),
			get_the_ID()
		);

		if ( ! $requirements ) {
			return;
		}

		learn_press_get_template(
			'single-course/sidebar/course-extra',
			array(
				'type'    => 'requirements',
				'title'   => __( 'Requirements', 'learnpress' ),
				'content' => $requirements
			)
		);
	}

	public function course_extra_key_features() {
		$key_features = apply_filters(
			'learn-press/course-extra-key-features',
			get_post_meta( get_the_ID(), '_lp_key_features', true ),
			get_the_ID()
		);

		if ( ! $key_features ) {
			return;
		}

		learn_press_get_template(
			'single-course/sidebar/course-extra',
			array(
				'type'    => 'key-features',
				'title'   => __( 'Key features', 'learnpress' ),
				'content' => $key_features
			)
		);
	}

	public function course_extra_target_audiences() {
		$target_audiences = apply_filters(
			'learn-press/course-extra-target-audiences',
			get_post_meta( get_the_ID(), '_lp_target_audience', true ),
			get_the_ID()
		);

		if ( ! $target_audiences ) {
			return;
		}

		learn_press_get_template(
			'single-course/sidebar/course-extra',
			array(
				'type'    => 'target-audiences',
				'title'   => __( 'Target audiences', 'learnpress' ),
				'content' => $target_audiences
			)
		);
	}

	public function course_categories( $post = 0 ) {
		$post = get_post( $post );

		$categories = get_object_term_cache( $post->ID, 'course_category' );
		if ( false === $categories ) {
			$categories = wp_get_object_terms( $post->ID, 'course_category' );
		}

		if ( ! $categories ) {
			return;
		}

		//learn_press_get_template( 'single-course/categories' );
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

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		if ( false === ( $course_data = $user->get_course_data( $course->get_id() ) ) ) {
			return;
		}

		if ( ! $course_data->is_available() ) {
			return;
		}

		if ( $course_data->get_status() !== 'enrolled' ) {
			return;
		}

		if ( ! $course_data->get_item_at( 0 ) ) {
			return;
		}

		learn_press_get_template( 'single-course/buttons/continue.php' );
	}

	public function course_finish_button() {
		$user   = LP_Global::user();
		$course = LP_Global::course();

		if ( ! learn_press_current_user_enrolled_course() && $course->get_external_link() ) {
			return;
		}

		if ( false === ( $course_data = $user->get_course_data( $course->get_id() ) ) ) {
			return;
		}

		if ( ! $user->can_finish_course( $course->get_id() ) ) {
			return;
		}

		learn_press_get_template( 'single-course/buttons/finish.php' );
	}

	public function course_external_button() {
		$course = LP_Global::course();

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

	////
	public function popup_header() {
		learn_press_get_template( 'single-course/content-item/popup-header' );
	}

	public function popup_sidebar() {
		learn_press_get_template( 'single-course/content-item/popup-sidebar' );
	}

	public function popup_content() {
		learn_press_get_template( 'single-course/content-item/popup-content' );
	}

	public function popup_footer() {
		learn_press_get_template( 'single-course/content-item/popup-footer' );
	}

	public function popup_footer_nav() {
		$course    = LP_Global::course();
		$next_item = $prev_item = false;

		if ( $next_id = $course->get_next_item() ) {
			$next_item = $course->get_item( $next_id );
		}
		if ( $prev_id = $course->get_prev_item() ) {
			$prev_item = $course->get_item( $prev_id );
		}

		if ( ! $prev_item && ! $next_item ) {
			return;
		}

		learn_press_get_template(
			'single-course/content-item/nav.php',
			array(
				'next_item' => $next_item,
				'prev_item' => $prev_item
			)
		);
	}

	public function course_curriculum() {
		learn_press_get_template( 'single-course/tabs/curriculum' );
	}

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

	public function courses_loop_item_students() {
		learn_press_get_template( 'loop/course/students' );
	}

	public function begin_courses_loop(){
		learn_press_get_template( 'loop/course/loop-begin.php' );
	}

	public function end_courses_loop(){
		learn_press_get_template( 'loop/course/loop-end.php' );
	}

	public function clearfix() {
		learn_press_get_template( 'global/clearfix' );
	}

	public function callback( $template, $args = array() ) {
		return array( new LP_Template_Callback( $template, $args ), 'display' );
	}

	/**
	 * Return is callable method of self class.
	 *
	 * @since 4.x.x
	 *
	 * @param string $callback
	 *
	 * @return array
	 */
	public function func( $callback ) {
		return array( $this, $callback );
	}

	/**
	 * Add callable method of self class to a hook of template.
	 *
	 * @param string $name
	 * @param string $callback
	 * @param int    $priority
	 * @param int    $number_args
	 */
	public function hook( $name, $callback, $priority = 10, $number_args = 1 ) {
		add_action( $name, $this->func( $callback ), $priority, $number_args );
	}

	/**
	 * Remove hooked callable method.
	 *
	 * @param string $tag
	 * @param string $function_to_remove - '*' will remove all methods.
	 * @param int    $priority
	 */
	public function remove( $tag, $function_to_remove, $priority = 10 ) {
		global $wp_filter;

		if ( $function_to_remove === '*' ) {
			if ( ! empty( $wp_filter[ $tag ] ) ) {
				unset( $wp_filter[ $tag ] );
			}

			return;
		}

		if ( $priority === '*' ) {

			if ( ! empty( $wp_filter[ $tag ]->callbacks ) ) {
				$priorities = array_keys( $wp_filter[ $tag ]->callbacks );

				foreach ( $priorities as $priority ) {
					remove_action( $tag, $this->func( $function_to_remove ), $priority );
				}
			}

			return;
		}
		remove_action( $tag, $this->func( $function_to_remove ), $priority );
	}

	/**
	 * @return LP_Template
	 */
	public static function instance() {
		if ( ! self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}


class LP_Template_Callback {
	/**
	 * @var string
	 */
	protected $template = '';

	/**
	 * @var array
	 */
	protected $args = array();

	/**
	 * LP_Template_Caller constructor.
	 *
	 * @param       $template
	 * @param array $args
	 */
	public function __construct( $template, $args = array() ) {
		$this->template = $template;
		$this->args     = $args;
	}

	/**
	 *
	 */
	public function display() {
		learn_press_get_template( $this->template, func_get_args() );
	}
}