<?php

/**
 * Class LP_REST_Users_Controller
 *
 * @since 3.3.0
 */
class LP_REST_Admin_Course_Controller extends LP_Abstract_REST_Controller {

	public function __construct() {
		$this->namespace = 'lp/v1/admin';
		$this->rest_base = 'course';

		parent::__construct();
	}

	/**
	 * Register rest routes.
	 */
	public function register_routes() {
		$this->routes = array(
			'get_final_quiz' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'get_final_quiz' ),
					'permission_callback' => function() {
						return current_user_can( 'edit_posts' );
					},
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Get Final Quiz in Course Settings.
	 *
	 * @return void
	 */
	public function get_final_quiz( WP_REST_Request $request ) {
		$params         = $request->get_params();
		$course_id      = $params['courseId'] ?? false;
		$response       = new LP_REST_Response();
		$response->data = '';
		$final_quiz     = '';

		try {
			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'No Course ID available!', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( esc_html__( 'No Course available!', 'learnpress' ) );
			}

			$items = $course->get_item_ids();

			if ( $items ) {
				foreach ( $items as $item ) {
					if ( learn_press_get_post_type( $item ) === LP_QUIZ_CPT ) {
						$final_quiz = $item;
					}
				}
			}

			if ( ! empty( $final_quiz ) ) {
				update_post_meta( $course_id, '_lp_final_quiz', $final_quiz );
				$passing_grade = get_post_meta( $final_quiz, '_lp_passing_grade', true );

				$post_type_object = get_post_type_object( LP_QUIZ_CPT );
				$url              = admin_url( sprintf( $post_type_object->_edit_link . '&action=edit#_lp_passing_grade', $final_quiz ) );

				ob_start();
				?>
				<div class="lp-metabox-evaluate-final_quiz__message">
					<?php printf( esc_html__( 'Passing Grade: %s', 'learpress' ), $passing_grade . '%' ); ?>
					-
					<?php printf( esc_html__( 'Edit: %s', 'learnpress' ), '<a href="' . esc_url_raw( $url ) . '">' . get_the_title( $final_quiz ) . '</a>' ); ?>
				</div>
				<?php
				$response->status = 'success';
				$response->data   = ob_get_clean();
			} else {
				delete_post_meta( $course_id, '_lp_final_quiz' );
				$response->data = '<div class="lp-metabox-evaluate-final_quiz__message lp-metabox-evaluate-final_quiz__message-error">' . esc_html__( 'No Quiz in this course!', 'learnpress' ) . '</div>';
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}
}
