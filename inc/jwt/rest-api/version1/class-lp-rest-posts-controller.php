<?php
abstract class LP_REST_Jwt_Posts_Controller extends LP_REST_Jwt_Controller {
	protected $namespace = 'learnpress/v1';

	protected $rest_base = '';

	protected $post_type = '';

	protected $public = false;

	protected $hierarchical = false;

	protected function get_object( $id ) {
		return new WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'learnpress' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	public function get_items_permissions_check( $request ) {
		if ( ! lp_rest_check_post_permissions( $this->post_type, 'read' ) ) {
			return new WP_Error( 'lp_jwt_rest_cannot_view', __( 'Sorry, you cannot get lists', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	/**
	 * Check if a given request has access to create an item.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function create_item_permissions_check( $request ) {
		if ( ! lp_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new WP_Error( 'lp_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	public function get_item_permissions_check( $request ) {
		if ( $this->post_type !== get_post_type( (int) $request['id'] ) ) {
			return new WP_Error( 'lp_jwt_rest_not_in_post_type', __( 'Sorry, You cannot view this item.', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		$object = $this->get_object( (int) $request['id'] );

		if ( $object && 0 !== $object->get_id() && ! $this->check_read_permission( $object->get_id() ) ) {
			return new WP_Error( 'lp_jwt_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'learnpress' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}

	public function check_read_permission( $post_id ) {
		if ( ! lp_rest_check_post_permissions( $this->post_type, 'read', $post_id ) ) {
			return false;
		}

		return true;
	}

	public function get_item( $request ) {
		$object = $this->get_object( (int) $request['id'] );

		if ( ! $object || 0 === $object->get_id() ) {
			return new WP_Error( "lp_rest_{$this->post_type}_invalid_id", esc_html__( 'Invalid ID.', 'learnpress' ), array( 'status' => 404 ) );
		}

		$data     = $this->prepare_object_for_response( $object, $request );
		$response = rest_ensure_response( $data );

		if ( $this->public ) {
			$response->link_header( 'alternate', $this->get_permalink( $object ), array( 'type' => 'text/html' ) );
		}

		return $response;
	}

	public function get_items( $request ) {
		$query_args    = $this->prepare_objects_query( $request );
		$query_results = $this->get_objects( $query_args );

		$objects = array();
		foreach ( $query_results['objects'] as $object ) {
			$object_id = ! empty( $object->ID ) ? $object->ID : $object->get_id();

			if ( ! $this->check_read_permission( $object_id ) ) {
				continue;
			}

			$data      = $this->prepare_object_for_response( $object, $request );
			$objects[] = $this->prepare_response_for_collection( $data );
		}

		$page      = (int) $query_args['paged'];
		$max_pages = $query_results['pages'];

		$response = rest_ensure_response( $objects );
		$response->header( 'X-WP-Total', $query_results['total'] );
		$response->header( 'X-WP-TotalPages', (int) $max_pages );

		$base          = $this->rest_base;
		$attrib_prefix = '(?P<';

		if ( strpos( $base, $attrib_prefix ) !== false ) {
			$attrib_names = array();
			preg_match( '/\(\?P<[^>]+>.*\)/', $base, $attrib_names, PREG_OFFSET_CAPTURE );

			foreach ( $attrib_names as $attrib_name_match ) {
				$beginning_offset = strlen( $attrib_prefix );
				$attrib_name_end  = strpos( $attrib_name_match[0], '>', $attrib_name_match[1] );
				$attrib_name      = substr( $attrib_name_match[0], $beginning_offset, $attrib_name_end - $beginning_offset );

				if ( isset( $request[ $attrib_name ] ) ) {
					$base = str_replace( "(?P<$attrib_name>[\d]+)", $request[ $attrib_name ], $base );
				}
			}
		}

		$base = add_query_arg( $request->get_query_params(), rest_url( sprintf( '/%s/%s', $this->namespace, $base ) ) );

		if ( $page > 1 ) {
			$prev_page = $page - 1;
			if ( $prev_page > $max_pages ) {
				$prev_page = $max_pages;
			}
			$prev_link = add_query_arg( 'page', $prev_page, $base );
			$response->link_header( 'prev', $prev_link );
		}

		if ( $max_pages > $page ) {
			$next_page = $page + 1;
			$next_link = add_query_arg( 'page', $next_page, $base );
			$response->link_header( 'next', $next_link );
		}

		return $response;
	}

	/**
	 * Create a single item.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|WP_REST_Response
	 */
	public function create_item( $request ) {
		if ( ! empty( $request['id'] ) ) {
			/* translators: %s: post type */
			return new WP_Error( "lp_rest_{$this->post_type}_exists", sprintf( __( 'Cannot create existing %s.', 'learnpress' ), $this->post_type ), array( 'status' => 400 ) );
		}

		$post = $this->prepare_item_for_database( $request );
		if ( is_wp_error( $post ) ) {
			return $post;
		}

		$post->post_type = $this->post_type;
		$post_id         = wp_insert_post( $post, true );

		if ( is_wp_error( $post_id ) ) {
			if ( in_array( $post_id->get_error_code(), array( 'db_insert_error' ) ) ) {
				$post_id->add_data( array( 'status' => 500 ) );
			} else {
				$post_id->add_data( array( 'status' => 400 ) );
			}
			return $post_id;
		}

		$post->ID = $post_id;
		$post     = get_post( $post_id );

		$this->update_additional_fields_for_object( $post, $request );

		// Add meta fields.
		$meta_fields = $this->add_post_meta_fields( $post, $request );
		if ( is_wp_error( $meta_fields ) ) {
			// Remove post.
			$this->delete_post( $post );

			return $meta_fields;
		}

		/**
		 * Fires after a single item is created or updated via the REST API.
		 *
		 * @param WP_Post         $post      Post object.
		 * @param WP_REST_Request $request   Request object.
		 * @param boolean         $creating  True when creating item, false when updating.
		 */
		do_action( "lp_rest_insert_{$this->post_type}", $post, $request, true );

		$request->set_param( 'context', 'edit' );
		$response = $this->prepare_item_for_response( $post, $request );
		$response = rest_ensure_response( $response );
		$response->set_status( 201 );
		$response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $post_id ) ) );

		return $response;
	}

	public function prepare_item_for_database( $request, $creating = false ) {
		$prepared_post  = new stdClass();
		$current_status = '';

		$id = isset( $request['id'] ) ? absint( $request['id'] ) : 0;

		if ( isset( $request['id'] ) ) {
			$post = get_post( $id );

			if ( empty( $post ) || empty( $post->ID ) || $this->post_type !== $post->post_type ) {
				return new WP_Error( 'rest_invalid_course', __( 'Invalid Course' ), array( 'status' => 404 ) );
			}

			$prepared_post->ID = $existing_post->ID;
			$current_status    = $existing_post->post_status;
		}

		$schema = $this->get_item_schema();

		// Post title.
		if ( ! empty( $schema['properties']['name'] ) && isset( $request['name'] ) ) {
			if ( is_string( $request['name'] ) ) {
				$prepared_post->post_title = $request['name'];
			} elseif ( ! empty( $request['name']['raw'] ) ) {
				$prepared_post->post_title = $request['name']['raw'];
			}
		}

		// Post content.
		if ( ! empty( $schema['properties']['content'] ) && isset( $request['content'] ) ) {
			if ( is_string( $request['content'] ) ) {
				$prepared_post->post_content = $request['content'];
			} elseif ( isset( $request['content']['raw'] ) ) {
				$prepared_post->post_content = $request['content']['raw'];
			}
		}

		// Post excerpt.
		if ( ! empty( $schema['properties']['excerpt'] ) && isset( $request['excerpt'] ) ) {
			if ( is_string( $request['excerpt'] ) ) {
				$prepared_post->post_excerpt = $request['excerpt'];
			} elseif ( isset( $request['excerpt']['raw'] ) ) {
				$prepared_post->post_excerpt = $request['excerpt']['raw'];
			}
		}

		// Post type.
		if ( empty( $request['id'] ) ) {
			// Creating new post, use default type for the controller.
			$prepared_post->post_type = $this->post_type;
		} else {
			// Updating a post, use previous type.
			$prepared_post->post_type = get_post_type( $request['id'] );
		}

		$post_type = get_post_type_object( $prepared_post->post_type );

		// Post status.
		if (
			! empty( $schema['properties']['status'] ) &&
			isset( $request['status'] ) &&
			( ! $current_status || $current_status !== $request['status'] )
		) {
			$status = $this->handle_status_param( $request['status'], $post_type );

			if ( is_wp_error( $status ) ) {
				return $status;
			}

			$prepared_post->post_status = $status;
		}

		// Post date.
		if ( ! empty( $schema['properties']['date'] ) && ! empty( $request['date'] ) ) {
			$current_date = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID )->post_date : false;
			$date_data    = rest_get_date_with_gmt( $request['date'] );

			if ( ! empty( $date_data ) && $current_date !== $date_data[0] ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
				$prepared_post->edit_date                                        = true;
			}
		} elseif ( ! empty( $schema['properties']['date_gmt'] ) && ! empty( $request['date_gmt'] ) ) {
			$current_date = isset( $prepared_post->ID ) ? get_post( $prepared_post->ID )->post_date_gmt : false;
			$date_data    = rest_get_date_with_gmt( $request['date_gmt'], true );

			if ( ! empty( $date_data ) && $current_date !== $date_data[1] ) {
				list( $prepared_post->post_date, $prepared_post->post_date_gmt ) = $date_data;
				$prepared_post->edit_date                                        = true;
			}
		}

		// Sending a null date or date_gmt value resets date and date_gmt to their
		// default values (`0000-00-00 00:00:00`).
		if (
			( ! empty( $schema['properties']['date_gmt'] ) && $request->has_param( 'date_gmt' ) && null === $request['date_gmt'] ) ||
			( ! empty( $schema['properties']['date'] ) && $request->has_param( 'date' ) && null === $request['date'] )
		) {
			$prepared_post->post_date_gmt = null;
			$prepared_post->post_date     = null;
		}

		// Post slug.
		if ( ! empty( $schema['properties']['slug'] ) && isset( $request['slug'] ) ) {
			$prepared_post->post_name = $request['slug'];
		}

		// Author.
		if ( ! empty( $schema['properties']['author'] ) && ! empty( $request['author'] ) ) {
			$post_author = (int) $request['author'];

			if ( get_current_user_id() !== $post_author ) {
				$user_obj = get_userdata( $post_author );

				if ( ! $user_obj ) {
					return new WP_Error(
						'rest_invalid_author',
						__( 'Invalid author ID.' ),
						array( 'status' => 400 )
					);
				}
			}

			$prepared_post->post_author = $post_author;
		}

		// Post password.
		if ( ! empty( $schema['properties']['password'] ) && isset( $request['password'] ) ) {
			$prepared_post->post_password = $request['password'];

			if ( '' !== $request['password'] ) {
				if ( ! empty( $schema['properties']['sticky'] ) && ! empty( $request['sticky'] ) ) {
					return new WP_Error(
						'rest_invalid_field',
						__( 'A post can not be sticky and have a password.' ),
						array( 'status' => 400 )
					);
				}

				if ( ! empty( $prepared_post->ID ) && is_sticky( $prepared_post->ID ) ) {
					return new WP_Error(
						'rest_invalid_field',
						__( 'A sticky post can not be password protected.' ),
						array( 'status' => 400 )
					);
				}
			}
		}

		return apply_filters( "lp_rest_pre_insert_{$this->post_type}", $prepared_post, $request );
	}

	/**
	 * Determines validity and normalizes the given status parameter.
	 *
	 * @since 4.7.0
	 *
	 * @param string       $post_status Post status.
	 * @param WP_Post_Type $post_type   Post type.
	 * @return string|WP_Error Post status or WP_Error if lacking the proper permission.
	 */
	protected function handle_status_param( $post_status, $post_type ) {

		switch ( $post_status ) {
			case 'draft':
			case 'pending':
				break;
			case 'private':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new WP_Error(
						'rest_cannot_publish',
						__( 'Sorry, you are not allowed to create private posts in this post type.' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				break;
			case 'publish':
			case 'future':
				if ( ! current_user_can( $post_type->cap->publish_posts ) ) {
					return new WP_Error(
						'rest_cannot_publish',
						__( 'Sorry, you are not allowed to publish posts in this post type.' ),
						array( 'status' => rest_authorization_required_code() )
					);
				}
				break;
			default:
				if ( ! get_post_status_object( $post_status ) ) {
					$post_status = 'draft';
				}
				break;
		}

		return $post_status;
	}

	protected function prepare_objects_query( $request ) {
		global $wpdb;

		$args                        = array();
		$args['offset']              = $request['offset'];
		$args['order']               = $request['order'];
		$args['orderby']             = $request['orderby'];
		$args['paged']               = $request['page'];
		$args['post__in']            = $request['include'];
		$args['post__not_in']        = $request['exclude'];
		$args['posts_per_page']      = $request['per_page'];
		$args['name']                = $request['slug'];
		$args['post_parent__in']     = $request['parent'];
		$args['post_parent__not_in'] = $request['parent_exclude'];
		$args['s']                   = $request['search'];
		$args['fields']              = $this->get_fields_for_response( $request );

		if ( 'date' === $args['orderby'] ) {
			$args['orderby'] = 'date ID';
		}

		$args['date_query'] = array();

		if ( isset( $request['before'] ) ) {
			$args['date_query'][0]['before'] = $request['before'];
		}

		if ( isset( $request['after'] ) ) {
			$args['date_query'][0]['after'] = $request['after'];
		}

		// Get item user is learned
		if ( ! empty( $request['learned'] ) ) {
			$item_ids = $this->get_item_learned_ids( $request );

			// Force WP_Query return empty if don't found any order.
			$item_ids = ! empty( $item_ids ) ? $item_ids : array( 0 );

			$args['post__in'] = $item_ids;
		}

		$args['post_type'] = $this->post_type;

		$args = apply_filters( "lp_jwt_rest_{$this->post_type}_object_query", $args, $request );

		return $this->prepare_items_query( $args, $request );
	}

	/** Get all items has in database learnpress_user_items by post_type */
	public function get_item_learned_ids( $request ) {
		return array();
	}

	protected function get_objects( $query_args ) {
		$query  = new WP_Query();
		$result = $query->query( $query_args );

		$total_posts = $query->found_posts;

		if ( $total_posts < 1 ) {
			unset( $query_args['paged'] );
			$count_query = new WP_Query();
			$count_query->query( $query_args );
			$total_posts = $count_query->found_posts;
		}

		return array(
			'objects' => array_filter( array_map( array( $this, 'get_object' ), $result ) ),
			'total'   => (int) $total_posts,
			'pages'   => (int) ceil( $total_posts / (int) $query->query_vars['posts_per_page'] ),
		);
	}

	protected function add_post_meta_fields( $post, $request ) {
		return true;
	}

	protected function prepare_object_for_response( $object, $request ) {
		return new WP_Error( 'invalid-method', sprintf( __( "Method '%s' not implemented. Must be overridden in subclass.", 'learnpress' ), __METHOD__ ), array( 'status' => 405 ) );
	}

	protected function prepare_items_query( $prepared_args = array(), $request = null ) {
		$query_args = array();

		foreach ( $prepared_args as $key => $value ) {
			$query_args[ $key ] = apply_filters( "rest_query_var-{$key}", $value ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
		}

		if ( 'post' !== $this->post_type || ! isset( $query_args['ignore_sticky_posts'] ) ) {
			$query_args['ignore_sticky_posts'] = true;
		}

		if ( 'include' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'post__in';
		} elseif ( 'id' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'ID';
		} elseif ( 'slug' === $query_args['orderby'] ) {
			$query_args['orderby'] = 'name';
		}

		return $query_args;
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
		$params['search']   = array(
			'description'       => __( 'Limit results to those matching a string.', 'learnpress' ),
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['after']    = array(
			'description'       => __( 'Limit response to resources published after a given ISO8601 compliant date.', 'learnpress' ),
			'type'              => 'string',
			'format'            => 'date-time',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['before']   = array(
			'description'       => __( 'Limit response to resources published before a given ISO8601 compliant date.', 'learnpress' ),
			'type'              => 'string',
			'format'            => 'date-time',
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
		$params['offset']   = array(
			'description'       => __( 'Offset the result set by a specific number of items.', 'learnpress' ),
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['order']    = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'learnpress' ),
			'type'              => 'string',
			'default'           => 'desc',
			'enum'              => array( 'asc', 'desc' ),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['orderby']  = array(
			'description'       => __( 'Sort collection by object attribute.', 'learnpress' ),
			'type'              => 'string',
			'default'           => 'date',
			'enum'              => array(
				'date',
				'id',
				'include',
				'title',
				'slug',
				'modified',
			),
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['learned']  = array(
			'description'       => __( 'Get item learned by user.', 'learnpress' ),
			'type'              => 'boolean',
			'default'           => false,
			'validate_callback' => 'rest_validate_request_arg',
		);

		if ( $this->hierarchical ) {
			$params['parent']         = array(
				'description'       => __( 'Limit result set to those of particular parent IDs.', 'learnpress' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'sanitize_callback' => 'wp_parse_id_list',
				'default'           => array(),
			);
			$params['parent_exclude'] = array(
				'description'       => __( 'Limit result set to all items except those of a particular parent ID.', 'learnpress' ),
				'type'              => 'array',
				'items'             => array(
					'type' => 'integer',
				),
				'sanitize_callback' => 'wp_parse_id_list',
				'default'           => array(),
			);
		}

		return apply_filters( "rest_{$this->post_type}_collection_params", $params, $this->post_type );
	}
}
