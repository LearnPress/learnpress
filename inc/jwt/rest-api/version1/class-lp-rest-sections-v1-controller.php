<?php
class LP_Jwt_Sections_V1_Controller extends LP_REST_Jwt_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'sections';

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/sections-by-course-id/(?P<course_id>[\d]+)',
			array(
				'args'   => array(
					'course_id' => array(
						'description' => esc_html__( 'Course ID.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_sections_by_course' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);
	}

	public function get_items_permissions_check( $request ) {
		if ( empty( $request['course_id'] ) ) {
			return new WP_Error( 'lp_section_not_course_id', __( 'Sorry, Invalid course ID param.', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$course_id = absint( $request['course_id'] );
		$post      = get_post( $course_id );

		$post_status_obj = get_post_status_object( $post->post_status );

		if ( ! $post_status_obj || ! $post_status_obj->public ) {
			return new WP_Error( 'lp_section_not_public', __( 'Course is not public', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	public function get_sections_by_course( $request ) {
		$filters                    = new LP_Section_Filter();
		$filters->section_course_id = $request['course_id'];
		$filters->limit             = $request['per_page'];
		$filters->page              = $request['page'];
		$filters->order             = $request['order'];
		$filters->search_section    = $request['search'];
		$filters->section_ids       = $request['include'];
		$filters->section_not_ids   = $request['exclude'];

		$query_results = LP_Section_DB::getInstance()->get_sections_by_course_id( $filters );

		if ( is_wp_error( $query_results ) ) {
			return $query_results;
		}

		$results = $query_results['results'];

		$return = array();

		foreach ( $results as $result ) {
			$data     = $this->prepare_object_for_response( $result, $request );
			$return[] = $this->prepare_response_for_collection( $data );
		}

		$response = rest_ensure_response( $return );

		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $query_results['pages'] );

		return $response;
	}

	public function prepare_object_for_response( $section_data, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = $this->get_sections_data( $section_data, $context, $request );

		$response = rest_ensure_response( $data );

		return apply_filters( 'lp_jwt_rest_prepare_sections_object', $response, $section_data, $request );
	}

	public function get_sections_data( $section_data, $context, $request ) {
		$fields = $this->get_fields_for_response( $request );

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = $section_data['section_id'] ?? 0;
					break;
				case 'name':
					$data['name'] = $section_data['section_name'] ?? '';
					break;
				case 'description':
					$data['description'] = $section_data['section_description'] ?? '';
					break;
			}
		}

		return $data;
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'learnpress_sections',
			'type'       => 'object',
			'properties' => array(
				'id'          => array(
					'description' => __( 'Unique identifier for the resource.', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'        => array(
					'description' => __( 'Section name.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'description' => array(
					'description' => __( 'Section description.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	public function get_collection_params() {
		$params                       = array();
		$params['context']            = $this->get_context_param();
		$params['context']['default'] = 'view';

		$params['page']     = array(
			'description'       => __( 'Current page of the collection.', 'learnpress' ),
			'type'              => 'integer',
			'default'           => 1,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
			'minimum'           => 1,
		);
		$params['per_page'] = array(
			'description'       => __( 'Maximum number of items to be returned in result set.', 'learnpress' ),
			'type'              => 'integer',
			'default'           => 10,
			'minimum'           => 1,
			'maximum'           => 100,
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']    = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'learnpress' ),
			'type'              => 'string',
			'default'           => 'asc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['search']   = array(
			'description'       => __( 'Limit results to those matching a string.', 'learnpress' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['exclude']  = array(
			'description'       => __( 'Ensure result set excludes specific IDs.', 'learnpress' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);
		$params['include']  = array(
			'description'       => __( 'Limit result set to specific ids.', 'learnpress' ),
			'type'              => 'array',
			'items'             => array(
				'type' => 'integer',
			),
			'default'           => array(),
			'sanitize_callback' => 'wp_parse_id_list',
		);

		return apply_filters( 'lp_rest_sections_collection_params', $params );
	}
}
