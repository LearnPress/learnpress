<?php
/**
 * Expose the global LearnPress settings that the App needs.
 *
 * Those settings live in wp_options (LP_Settings), not in the post meta of a course,
 * so they cannot be read from the "meta_data" of the courses endpoints.
 *
 * @package LearnPress/JWT/RESTAPI
 */

class LP_Jwt_Settings_V1_Controller extends LP_REST_Jwt_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'settings';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/**
	 * Get the global settings.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_settings( WP_REST_Request $request ) {
		$response = new LP_REST_Response();

		try {
			$response->data->course   = (object) $this->get_course_settings();
			$response->data->currency = (object) $this->get_currency_settings();

			/**
			 * Allow add-ons to append their own settings.
			 *
			 * @param stdClass $data
			 * @param WP_REST_Request $request
			 */
			$response->data = apply_filters( 'lp_jwt_rest_settings_data', $response->data, $request );

			$response->status = 'success';
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Settings of the courses (LP Settings > Courses).
	 *
	 * @return array
	 */
	protected function get_course_settings(): array {
		$thumbnail = LP_Settings::get_option( 'course_thumbnail_dimensions', array( 500, 300, 'yes' ) );
		if ( ! is_array( $thumbnail ) ) {
			$thumbnail = array( 500, 300, 'yes' );
		}

		return array(
			// The layout of the courses archive page: "grid" or "list".
			'archive_layout'         => LP_Settings::get_option( 'archive_courses_layout', 'list' ),
			'archive_layouts'        => learn_press_courses_layouts(),
			'archive_limit'          => absint( LP_Settings::get_option( 'archive_course_limit', 8 ) ),
			'pagination_type'        => LP_Settings::get_option( 'course_pagination_type', 'standard' ),
			'layout_single_course'   => LP_Settings::get_option( 'layout_single_course', 'classic' ),
			'curriculum_display'     => LP_Settings::get_option( 'curriculum_display', 'expand_first_section' ),
			'section_per_page'       => (int) LP_Settings::get_option( 'section_per_page', -1 ),
			'course_item_per_page'   => (int) LP_Settings::get_option( 'course_item_per_page', -1 ),
			'auto_enroll'            => 'yes' === LP_Settings::get_option( 'auto_enroll', 'yes' ),
			'popup_confirm_finish'   => 'yes' === LP_Settings::get_option( 'enable_popup_confirm_finish', 'yes' ),
			'courses_of_subcategory' => 'yes' === LP_Settings::get_option( 'get_courses_of_subcategory', 'no' ),
			'thumbnail_dimensions'   => array(
				'width'  => absint( $thumbnail[0] ?? 500 ),
				'height' => absint( $thumbnail[1] ?? 300 ),
				'crop'   => 'yes' === ( $thumbnail[2] ?? 'yes' ),
			),
		);
	}

	/**
	 * Settings used to format a price (LP Settings > General > Currency).
	 *
	 * @return array
	 */
	protected function get_currency_settings(): array {
		return array(
			'currency'            => learn_press_get_currency(),
			'symbol'              => html_entity_decode( learn_press_get_currency_symbol() ),
			'position'            => LP_Settings::get_option( 'currency_pos', 'left' ),
			'thousands_separator' => LP_Settings::get_option( 'thousands_separator', ',' ),
			'decimals_separator'  => LP_Settings::get_option( 'decimals_separator', '.' ),
			'number_of_decimals'  => absint( LP_Settings::get_option( 'number_of_decimals', 2 ) ),
		);
	}
}
