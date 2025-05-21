<?php
/**
 * Template hooks Admin Edit Course Curriculum.
 *
 * @since 4.2.8.6
 * @version 1.0.0
 */

namespace LearnPress\TemplateHooks\Course;

use Braintree\Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\CourseModel;

use LP_Background_Single_Course;
use LP_Section_CURD;
use stdClass;
use Throwable;
use WP_Error;

class AdminEditCurriculum {
	use Singleton;

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
		$callbacks[] = get_class( $this ) . ':render_html';

		return $callbacks;
	}

	/**
	 * Render string to data content
	 *
	 * @param array $settings
	 *
	 * @return stdClass
	 */
	public static function render_html( array $settings ): stdClass {
		$content          = new stdClass();
		$content->status  = 'error';
		$content->message = '';

		$action = $settings['action'] ?? '';
		if ( is_callable( self::class, $action ) ) {
			$content->content = call_user_func( [ self::class, $action ], $settings );
		} else {
			$content->content = __( 'Action not found', 'learnpress' );
		}

		if ( $content->content instanceof WP_Error ) {
			$content->message = $content->content->get_error_message();
		} else {
			$content->status = 'success';
		}

		return $content;
	}

	public static function add_section( $settings ) {
		try {
			$course_id   = $settings['course_id'] ?? 0;
			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Course not found', 'learnpress' ) );
			}

			$section_title = $settings['title'] ?? '';
			if ( empty( $section_title ) ) {
				throw new Exception( __( 'Section title is required', 'learnpress' ) );
			}

			$section_curd_new = new LP_Section_CURD( $course_id );
			$data             = array(
				'section_name' => $settings['title'],
			);
			$section_curd_new->create( $data );

			self::save_course( $course_id );

			return __( 'Section added successfully', 'learnpress' );
		} catch ( Throwable $e ) {
			return new WP_Error( 'error', $e->getMessage() );
		}
	}

	/**
	 * Save course when change curriculum
	 *
	 * @param int $course_id
	 */
	public static function save_course( $course_id ) {
		$bg = LP_Background_Single_Course::instance();
		$bg->data(
			array(
				'handle_name' => 'save_post',
				'course_id'   => $course_id,
				'data'        => [],
			)
		)->dispatch();
	}
}
