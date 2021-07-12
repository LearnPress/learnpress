<?php
class LP_Jwt_Courses_V1_Controller extends LP_REST_Jwt_Posts_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = 'courses';

	protected $post_type = LP_COURSE_CPT;

	protected $hierarchical = true;

	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'   => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/enroll',
			array(
				'args' => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll_course' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'edit',
							)
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/finish',
			array(
				'args' => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'finish_course' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'edit',
							)
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/retake',
			array(
				'args' => array(
					'id' => array(
						'description' => esc_html__( 'Unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'retake_course' ),
					'permission_callback' => '__return_true',
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'edit',
							)
						),
					),
				),
			)
		);
	}

	public function get_items_permissions_check( $request ) {
		$post_type = get_post_type_object( $this->post_type );

		if ( 'edit' === $request['context'] && ! current_user_can( $post_type->cap->edit_posts ) ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Sorry, you are not allowed to edit posts in this post type.' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Checks if a course can be read.
	 *
	 * Correctly handles courses with the inherit status.
	 *
	 * @author Nhamdv
	 *
	 * @return bool Whether the post can be read.
	 * */
	public function check_read_permission( $post_id ) {
		if ( empty( absint( $post_id ) ) ) {
			return false;
		}

		$post = get_post( $post_id );

		if ( ! $post ) {
			return false;
		}

		// Is the post readable?
		if ( 'publish' === $post->post_status || current_user_can( 'read_post', $post->ID ) ) {
			return true;
		}

		$post_status_obj = get_post_status_object( $post->post_status );
		if ( $post_status_obj && $post_status_obj->public ) {
			return true;
		}

		// Can we read the parent if we're inheriting?
		if ( 'inherit' === $post->post_status && $post->post_parent > 0 ) {
			$parent = get_post( $post->post_parent );

			if ( $parent ) {
				return $this->check_read_permission( $parent );
			}
		}

		/*
		 * If there isn't a parent, but the status is set to inherit, assume
		 * it's published (as per get_post_status()).
		 */
		if ( 'inherit' === $post->post_status ) {
			return true;
		}

		return false;
	}

	protected function get_object( $course = 0 ) {
		global $post;

		if ( false === $course && isset( $post, $post->ID ) && LP_COURSE_CPT === get_post_type( $post->ID ) ) {
			$id = absint( $post->ID );
		} elseif ( is_numeric( $course ) ) {
			$id = $course;
		} elseif ( $course instanceof LP_Course ) {
			$id = $course->get_id();
		} elseif ( ! empty( $course->ID ) ) {
			$id = $course->ID;
		}

		return learn_press_get_course( $id );
	}

	public function enroll_course( $request ) {
		if ( ! class_exists( 'LP_REST_Courses_Controller' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/rest-api/v1/frontend/class-lp-rest-courses-controller.php';
		}

		$course_controller = new LP_REST_Courses_Controller();

		return $course_controller->enroll_courses( $request );
	}

	public function finish_course( $request ) {
		$response = new LP_REST_Response();

		try {
			$user      = learn_press_get_current_user();
			$course_id = isset( $request['id'] ) ? wp_unslash( $request['id'] ) : false;

			if ( empty( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: No Course ID available.', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			$check  = $user->can_show_finish_course_btn( $course );

			if ( $check['status'] !== 'success' ) {
				throw new Exception( $check['message'] );
			}

			$finished = $user->finish_course( $course_id );

			if ( empty( $finished ) ) {
				throw new Exception( esc_html__( 'Error: Cannot finish this course.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = esc_html__( 'Congrats! You complete Course is successfully', 'learnpress' );
		} catch ( \Throwable $th ) {
			$response->status  = 'error';
			$response->message = $th->getMessage();
		}

		return rest_ensure_response( $response );
	}

	public function retake_course( $request ) {
		$response = new LP_REST_Response();

		if ( ! class_exists( 'LP_REST_Courses_Controller' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/rest-api/v1/frontend/class-lp-rest-courses-controller.php';
		}

		$course_controller = new LP_REST_Courses_Controller();

		return $course_controller->retake_course( $request );
	}

	/**
	 * Create a single product.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			return new WP_Error( "lp_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'learnpress' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$prepared_post = $this->prepare_item_for_database( $request );

		if ( is_wp_error( $prepared_post ) ) {
			return $prepared_post;
		}

		$prepared_post->post_type = $this->post_type;

		$post_id = wp_insert_post( wp_slash( (array) $prepared_post ), true, false );

		if ( is_wp_error( $post_id ) ) {
			if ( 'db_insert_error' === $post_id->get_error_code() ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}

			return $post_id;
		}

		$post = get_post( $post_id );

		do_action( "lp_rest_insert_{$this->post_type}", $post, $request, true );

		$fields_update = $this->update_additional_fields_for_object( $post, $request );

		if ( is_wp_error( $fields_update ) ) {
			return $fields_update;
		}

		$request->set_param( 'context', 'edit' );

		do_action( "lp_rest_after_insert_{$this->post_type}", $post, $request, true );

		wp_after_insert_post( $post, false, null );

		$object = $this->get_object( $post_id );

		$response = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $response );

		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $object->get_id() ) ) );

		return $response;
	}

	public function prepare_object_for_response( $object, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';
		$data    = $this->get_course_data( $object, $context, $request );

		$response = rest_ensure_response( $data );

		return apply_filters( "lp_jwt_rest_prepare_{$this->post_type}_object", $response, $object, $request );
	}

	protected function get_course_data( $course, $context = 'view' ) {
		$request = func_num_args() >= 2 ? func_get_arg( 2 ) : new WP_REST_Request( '', '', array( 'context' => $context ) );
		$fields  = $this->get_fields_for_response( $request );

		$id   = $course->get_id();
		$post = get_post( $course->get_id() );

		$data = array();

		foreach ( $fields as $field ) {
			switch ( $field ) {
				case 'id':
					$data['id'] = $course->get_id();
					break;
				case 'name':
					$data['name'] = $post->post_title;
					break;
				case 'slug':
					$data['slug'] = $post->post_name;
					break;
				case 'permalink':
					$data['permalink'] = $course->get_permalink();
					break;
				case 'image':
					$data['image'] = $course->get_image_url( 'full' );
					break;
				case 'date_created':
					$data['date_created'] = lp_jwt_prepare_date_response( $post->post_date_gmt, $post->post_date );
					break;
				case 'date_created_gmt':
					$data['date_created_gmt'] = lp_jwt_prepare_date_response( $post->post_date_gmt );
					break;
				case 'date_modified':
					$data['date_modified'] = lp_jwt_prepare_date_response( $post->post_modified_gmt, $post->post_modified );
					break;
				case 'date_modified_gmt':
					$data['date_modified_gmt'] = lp_jwt_prepare_date_response( $post->post_modified_gmt );
					break;
				case 'on_sale':
					$data['on_sale'] = $course->has_sale_price();
					break;
				case 'status':
					$data['status'] = $post->post_status;
					break;
				case 'content':
					$data['content'] = 'view' === $context ? wpautop( do_shortcode( $post->post_content ) ) : $post->post_content;
					break;
				case 'excerpt':
					$data['excerpt'] = $post->post_excerpt;
					break;
				case 'categories':
					$data['categories'] = $this->get_course_taxonomy( $id, 'course_category' );
					break;
				case 'tags':
					$data['tags'] = $this->get_course_taxonomy( $id, 'course_tag' );
					break;
				case 'sections':
					$data['sections'] = $this->get_all_items( $course );
					break;
				case 'course_data':
					$data['course_data'] = $this->get_course_data_for_current_user( $id, $request );
					break;
			}
		}

		$data['meta_data'] = $this->get_course_meta( $id );

		return $data;
	}

	public function get_item_learned_ids( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return;
		}

		$profile = learn_press_get_profile( $user_id );

		$orders = $profile->get_user_orders( array( 'status' => 'completed' ) );

		$course_ids = array();

		if ( $orders ) {
			foreach ( $orders as $order ) {
				$course_ids = array_merge( array_values( $order ), $course_ids );
			}
		}

		if ( empty( $course_ids ) ) {
			return false;
		}

		$ids = implode( ',', $course_ids );

		$filter = ! empty( $request['course_filter'] ) ? $request['course_filter'] : false;
		$where  = $wpdb->prepare( 'user_id=%d AND item_type=%s AND item_id IN (%1s)', $user_id, $this->post_type, $ids ); // phpcs:ignore

		if ( $filter ) {
			if ( $filter === 'in-progress' ) {
				$where .= $wpdb->prepare( ' AND status=%s', 'enrolled' );
			} elseif ( in_array( $filter, array( 'passed', 'failed' ) ) ) { // is "passed" or "failed"
				$where .= $wpdb->prepare( ' AND graduation=%s', $filter );
			}
		}

		$query = "SELECT item_id FROM {$wpdb->prefix}learnpress_user_items WHERE {$where}";

		$item_ids = $wpdb->get_col( $query );

		return $item_ids;
	}

	public function get_course_data_for_current_user( $id, $request ) {
		$user = learn_press_get_user( get_current_user_id() );

		if ( empty( $user ) || empty( $id ) ) {
			return array();
		}

		$course_data = $user->get_course_data( $id );

		if ( ! $course_data ) {
			return;
		}

		return array(
			'graduation'      => $course_data->get_graduation() ?? '',
			'status'          => $course_data->get_status() ?? '',
			'start_time'      => $course_data->get_start_time() ? lp_jwt_prepare_date_response( $course_data->get_start_time()->toSql( false ) ) : null,
			'end_time'        => $course_data->get_end_time() ? lp_jwt_prepare_date_response( $course_data->get_end_time()->toSql( false ) ) : null,
			'expiration_time' => $course_data->get_expiration_time() ? lp_jwt_prepare_date_response( $course_data->get_expiration_time()->toSql( false ) ) : '',
			'result'          => LP_User_Items_Result_DB::instance()->get_result( $course_data->get_user_item_id() ),
		);
	}

	public function get_course_taxonomy( $id, $taxonomy ) {
		$terms  = get_the_terms( $id, $taxonomy );
		$output = array();

		if ( $terms ) {
			foreach ( $terms as $term ) {
				$output[] = array(
					'id'   => $term->term_id,
					'name' => $term->name,
					'slug' => $term->slug,
				);
			}
		}

		return $output;
	}

	public function get_all_items( $course ) {
		$curriculum = $course->get_curriculum();
		$output     = array();

		if ( ! empty( $curriculum ) ) {
			foreach ( $curriculum as $section ) {
				if ( $section ) {
					$output[] = $section->to_array();
				}
			}
		}

		return $output;
	}

	public function get_course_meta( $id ) {
		if ( ! class_exists( 'LP_Meta_Box_Course' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/course/settings.php';
		}

		$metabox = new LP_Meta_Box_Course();

		$output = array();
		foreach ( $metabox->metabox( $id ) as $key => $tab ) {
			if ( isset( $tab['content'] ) ) {
				foreach ( $tab['content'] as $meta_key => $object ) {
					if ( is_a( $object, 'LP_Meta_Box_Field' ) ) {
						$object->id          = $meta_key;
						$output[ $meta_key ] = $object->meta_value( $id );
					}
				}
			}
		}

		return $output;
	}

	protected function prepare_objects_query( $request ) {
		$args = parent::prepare_objects_query( $request );

		$taxonomies = array(
			'course_category' => 'category',
			'course_tag'      => 'tag',
		);

		foreach ( $taxonomies as $taxonomy => $key ) {
			if ( ! empty( $request[ $key ] ) ) {
				$tax_query[] = array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $request[ $key ],
				);
			}
		}

		if ( ! empty( $tax_query ) ) {
			$args['tax_query'] = $tax_query;
		}

		return $args;
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'description' => __( 'Unique identifier for the resource.', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'              => array(
					'description' => __( 'Course name.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'              => array(
					'description' => __( 'Course slug.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'         => array(
					'description' => __( 'Course URL.', 'learnpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'image'             => array(
					'description' => __( 'Course Image URL.', 'learnpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'      => array(
					'description' => __( "The date the Course was created, in the site's timezone.", 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'  => array(
					'description' => __( 'The date the Course was created, as GMT.', 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'     => array(
					'description' => __( "The date the Course was last modified, in the site's timezone.", 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt' => array(
					'description' => __( 'The date the Course was last modified, as GMT.', 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'on_sale'           => array(
					'description' => __( 'Shows if the course is on sale.', 'learnpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'            => array(
					'description' => __( 'Course status (post status).', 'learnpress' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'content'           => array(
					'description' => __( 'Content course.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'excerpt'           => array(
					'description' => __( 'Retrieves the course excerpt..', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'categories'        => array(
					'description' => __( 'List of categories.', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Category ID.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Category name.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Category slug.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'tags'              => array(
					'description' => __( 'List of tags.', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'   => array(
								'description' => __( 'Tag ID.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'name' => array(
								'description' => __( 'Tag name.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
							'slug' => array(
								'description' => __( 'Tag slug.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
								'readonly'    => true,
							),
						),
					),
				),
				'sections'          => array(
					'description' => __( 'Retrieves the course sections and items..', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array(
								'description' => __( 'Section ID.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'title'       => array(
								'description' => __( 'Section name.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'course_id'   => array(
								'description' => __( 'Course ID.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view', 'edit' ),
							),
							'description' => array(
								'description' => __( 'Section description.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view', 'edit' ),
							),
							'items'       => array(
								'description' => __( 'Section items.', 'learnpress' ),
								'type'        => 'array',
								'context'     => array( 'view', 'edit' ),
								'items'       => array(
									'id'      => array(
										'description' => __( 'Item ID.', 'learnpress' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
									),
									'type'    => array(
										'description' => __( 'Item Type.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'title'   => array(
										'description' => __( 'Item title.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'preview' => array(
										'description' => __( 'Item ID.', 'learnpress' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
					),
				),
				'course_data'       => array(
					'description' => __( 'List of course user data.', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'graduation'      => array(
								'description' => __( 'Graduation', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
							),
							'status'          => array(
								'description' => __( 'Status', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'start_time'      => array(
								'description' => __( 'Start time', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'end_time'        => array(
								'description' => __( 'End time', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'expiration_time' => array(
								'description' => __( 'Expiration time', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
			),
		);

		return $this->add_additional_fields_schema( $schema );
	}

	public function get_collection_params() {
		$params = parent::get_collection_params();

		$params['category']      = array(
			'description'       => __( 'Limit result set to courses assigned a specific category ID.', 'learnpress' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['tag']           = array(
			'description'       => __( 'Limit result set to courses assigned a specific tag ID.', 'learnpress' ),
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['course_filter'] = array(
			'description'       => __( 'Filter by course to in-progress, passed, failed.', 'learnpress' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $params;
	}
}
