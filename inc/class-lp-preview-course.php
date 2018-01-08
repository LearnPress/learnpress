<?php

/**
 * Class LP_Preview_Course
 *
 * Helper class for preview course/lesson/quiz
 *
 * @since 3.0.0
 */
class LP_Preview_Course {

	/**
	 * @var int
	 */
	protected static $_item_id = 0;

	/**
	 * @var int
	 */
	protected static $_preview_course = 0;

	/**
	 * Get a FAKE course for preview.
	 *
	 * @return LP_Course|mixed
	 */
	public static function get_preview_course() {

		if ( empty( self::$_preview_course ) ) {
			global $wpdb;

			$query = $wpdb->prepare( "
				SELECT ID
				FROM {$wpdb->posts} p 
				INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = %s 
				WHERE pm.meta_value = %s
			", '_lp_preview_course', 'yes' );

			if ( ! $course_id = $wpdb->get_var( $query ) ) {
				self::$_preview_course = wp_insert_post(
					array(
						'post_type'   => LP_COURSE_CPT,
						'post_title'  => __( 'Preview Course', 'learnpress' ),
						'post_status' => 'publish',
					)
				);

				update_post_meta( self::$_preview_course, '_lp_preview_course', 'yes' );
			} else {
				self::$_preview_course = $course_id;
			}
		}

		return self::$_preview_course;
	}

	/**
	 * Setup preview environment.
	 */
	public static function setup_preview() {
		try {

			if ( ! $post_id = LP_Request::get_int( 'lp-preview' ) ) {
				return;
			}

			if ( ! $post_item = get_post( $post_id ) ) {
				throw new Exception( __( 'Invalid preview item.', 'learnpress' ) );
			}

			if ( ! in_array( $post_item->post_type, learn_press_course_get_support_item_types( true ) ) ) {
				throw new Exception( __( 'Invalid preview item.', 'learnpress' ) );
			}

			// Access forbidden
			if ( ! current_user_can( 'manage_options' ) ) {
				if ( $post_item->post_author != get_current_user_id() ) {
					throw new Exception( __( 'Access forbidden.', 'learnpress' ) );
				}
			}

			if ( empty( $post_item->post_name ) ) {
				wp_update_post(
					array(
						'ID'        => $post_id,
						'post_name' => sanitize_title( $post_item->post_title )
					)
				);

				wp_redirect( learn_press_get_current_url() );
				exit();
			}

			self::$_item_id = $post_id;

			$preview_course = self::get_preview_course();
			$post_course    = get_post( $preview_course );

			$post = wp_cache_get( self::$_preview_course, 'posts' );
			$post->post_status = 'publish';
			wp_cache_set( self::$_preview_course, $post, 'posts' );

			/**
			 * Set FAKE url of preview course to request uri so WP will parse
			 * the request as a real course.
			 */
			$_SERVER['REQUEST_URI'] = self::build_course_url( $post_course, $post_item );

			// Should not redirect canonical to real course
			add_filter( 'redirect_canonical', '__return_false' );

			// Prevent 404 because the preview item is not inside a course
			add_filter( 'learn-press/query/404', '__return_false' );
		}
		catch ( Exception $ex ) {
			learn_press_add_message( $ex->getMessage(), 'error' );
			wp_redirect( get_home_url() );
			exit();
		}
	}

	/**
	 * Build a FAKE url of a course.
	 *
	 * @param WP_Post $post_course
	 * @param WP_Post $post_item
	 *
	 * @return string
	 */
	public static function build_course_url( $post_course, $post_item ) {
		$post_types         = get_post_types( '', 'objects' );
		$slug               = preg_replace( '!^/!', '', $post_types[ LP_COURSE_CPT ]->rewrite['slug'] );
		$custom_slug_lesson = sanitize_title_with_dashes( LP()->settings->get( 'lesson_slug' ) );
		$custom_slug_quiz   = sanitize_title_with_dashes( LP()->settings->get( 'quiz_slug' ) );

		if ( ! empty( $custom_slug_lesson ) ) {
			$post_types[ LP_LESSON_CPT ]->rewrite['slug'] = urldecode( $custom_slug_lesson );
		}

		if ( ! empty( $custom_slug_quiz ) ) {
			$post_types[ LP_QUIZ_CPT ]->rewrite['slug'] = urldecode( $custom_slug_quiz );
		}

		$request_uri = $_SERVER['REQUEST_URI'];
		$arr         = parse_url( $request_uri );

		return join(
			'/',
			array(
				untrailingslashit( $arr['path'] ),
				$slug,
				$post_course->post_name,
				$post_types[ $post_item->post_type ]->rewrite['slug'],
				$post_item->post_name
			)
		);
	}

	public static function init() {
		add_action( 'init', array( __CLASS__, 'setup_preview' ) );
	}

}

LP_Preview_Course::init();