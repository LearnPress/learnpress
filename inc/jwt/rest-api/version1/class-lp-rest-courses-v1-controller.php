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
						'description' => esc_html__( 'A unique identifier for the resource.', 'learnpress' ),
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
						'description' => esc_html__( 'A unique identifier for the resource.', 'learnpress' ),
						'type'        => 'integer',
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll_course' ),
					'permission_callback' => array( $this, 'get_course_need_login_check' ),
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
						'description' => esc_html__( 'A unique identifier for the resource.', 'learnpress' ),
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
						'description' => esc_html__( 'A unique identifier for the resource.', 'learnpress' ),
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

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/verify-receipt',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'verify_receipt' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'context'      => $this->get_context_param(
						array(
							'default' => 'edit',
						)
					),
					'receipt-data' => array(
						'description' => esc_html__( 'Receipt data.', 'learnpress' ),
						'type'        => 'string',
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

	public function get_course_need_login_check( $request ) {
		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_forbidden_context',
				__( 'Please login to continue' ),
				array( 'status' => 401 )
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

	public function verify_receipt( $request ) {
		$response = new LP_REST_Response();
		$receipt  = ! empty( $request['receipt-data'] ) ? $request['receipt-data'] : '';
		$is_ios   = ! empty( $request['is-ios'] ) ? true : false;
		$password = LP_Settings::instance()->get( 'in_app_purchase_apple_shared_secret', '' );

		try {
			if ( empty( $receipt ) ) {
				throw new Exception( __( 'Receipt data is empty.', 'learnpress' ) );
			}

			if ( $is_ios ) {
				$course_id = ! empty( $request['course-id'] ) ? absint( $request['course-id'] ) : 0;

				if ( empty( $password ) ) {
					throw new Exception( __( 'The secret key is empty.', 'learnpress' ) );
				}

				$url = LP_Settings::instance()->get( 'in_app_purchase_apple_sandbox' ) === 'yes' ? 'https://sandbox.itunes.apple.com/verifyReceipt' : 'https://buy.itunes.apple.com/verifyReceipt';

				$verify = wp_remote_post(
					$url,
					array(
						'method'  => 'POST',
						'timeout' => 60,
						'body'    => wp_json_encode(
							array(
								'receipt-data' => $receipt,
								'password'     => $password,
							)
						),
					)
				);

				if ( is_wp_error( $verify ) ) {
					throw new Exception( $verify->get_error_message() );
				}

				$body = json_decode( wp_remote_retrieve_body( $verify ) );

				if ( $body->status !== 0 ) {
					throw new Exception( __( 'Cannot verify the receipt', 'learnpress' ) );
				}

				$latest_receipt_info = ! empty( $body->latest_receipt_info ) ? $body->latest_receipt_info : array();

				if ( empty( $latest_receipt_info ) ) {
					throw new Exception( __( 'The course ID is invalid.', 'learnpress' ) );
				}

				$course_ids = array_map(
					function( $receipt_id ) {
						return absint( $receipt_id->product_id );
					},
					$latest_receipt_info
				);

				if ( ! in_array( $course_id, $course_ids ) ) {
					throw new Exception( __( 'The course ID is invalid.', 'learnpress' ) );
				}
			} else {
				$receipt        = json_decode( $receipt, true );
				$package_name   = $receipt['packageName'] ?? '';
				$course_id      = ! empty( $receipt['productId'] ) ? absint( $receipt['productId'] ) : 0;
				$purchase_token = $receipt['purchaseToken'] ?? '';

				if ( ! function_exists( 'learnpress_in_app_purchase_get_access_token' ) ) {
					throw new Exception( __( 'Cannot verify the receipt', 'learnpress' ) );
				}

				$access_token = learnpress_in_app_purchase_get_access_token();

				$verify = wp_remote_get(
					'https://androidpublisher.googleapis.com/androidpublisher/v3/applications/' . $package_name . '/purchases/products/' . $course_id . '/tokens/' . $purchase_token,
					array(
						'headers' => array(
							'Content-Type'  => 'application/json',
							'Authorization' => 'Bearer ' . $access_token,
						),
						'timeout' => 60,
					)
				);

				$body = json_decode( wp_remote_retrieve_body( $verify ) );

				if ( isset( $body->error->message ) ) {
					throw new Exception( $body->error->message );
				}
			}

			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( __( 'The course ID is invalid.', 'learnpress' ) );
			}
			$user = learn_press_get_current_user();

			$cart     = LearnPress::instance()->cart;
			$checkout = LP_Checkout::instance();

			if ( ! learn_press_enable_cart() ) {
				// $order_awaiting_payment = LearnPress::instance()->session->get( 'order_awaiting_payment' );
				$cart->empty_cart();
				// LearnPress::instance()->session->set( 'order_awaiting_payment', $order_awaiting_payment );
			}

			$cart_id = $cart->add_to_cart( $course_id, 1, array() );

			if ( ! $cart_id ) {
				throw new Exception( esc_html__( 'Error: The course cannot be added to the cart.', 'learnpress' ) );
			}

			if ( is_user_logged_in() ) {
				$order_id = $checkout->create_order();
				$order    = new LP_Order( $order_id );
				$order->payment_complete();
				$cart->empty_cart();
			}

			$response->status  = 'success';
			$response->message = esc_html__( 'Verify Receipt Data successfully.', 'learnpress' );
		} catch ( \Throwable $th ) {
			error_log( $th->getMessage() );
			$response->status  = 'error';
			$response->message = $th->getMessage();
		}

		return rest_ensure_response( $response );
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
				throw new Exception( $check['message'] ?? esc_html__( 'Cannot finish this course.', 'learnpress' ) );
			}

			$finished = $user->finish_course( $course_id );

			if ( empty( $finished ) ) {
				throw new Exception( esc_html__( 'Error: Cannot finish this course.', 'learnpress' ) );
			}

			$response->status  = 'success';
			$response->message = esc_html__( 'Congrats! You have completed the Course.', 'learnpress' );
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

	/**
	 * @param LP_Course $course
	 *
	 * @throws Exception
	 */
	protected function get_course_data( $course, $context = 'view' ) {
		$request = func_num_args() >= 2 ? func_get_arg( 2 ) : new WP_REST_Request( '', '', array( 'context' => $context ) );
		$fields  = $this->get_fields_for_response( $request );

		$id   = $course->get_id();
		$post = get_post( $course->get_id() );

		$data = array();

		foreach ( $fields as $field ) {
			if ( ! empty( $request['optimize'] ) ) {
				$disables = is_bool( $request['optimize'] ) ? 'sections,course_data,instructor,meta_data,tags,can_finish,can_retake,count_students,rataken,ratake_count' : $request['optimize'];
				$disable  = explode( ',', $disables );

				if ( ! empty( $disable ) && in_array( $field, $disable ) ) {
					continue;
				}
			}

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
					$data['content'] = 'view' === $context ? apply_filters( 'the_content', $post->post_content ) : $post->post_content;
					break;
				case 'excerpt':
					$data['excerpt'] = $post->post_excerpt;
					break;
				case 'count_students':
					$data['count_students'] = $course->count_students();
					break;
				case 'can_finish':
					$data['can_finish'] = $this->check_can_finish( $course );
					break;
				case 'can_retake':
					$data['can_retake'] = $this->check_can_retake( $id );
					break;
				case 'ratake_count':
					$data['ratake_count'] = (int) $course->get_data( 'retake_count' );
					break;
				case 'rataken':
					$data['rataken'] = $this->get_retaken_count( $id );
					break;
				case 'duration':
					$data['duration'] = learn_press_get_post_translated_duration( $id, esc_html__( 'Lifetime', 'learnpress' ) );
					break;
				case 'categories':
					$data['categories'] = $this->get_course_taxonomy( $id, 'course_category' );
					break;
				case 'tags':
					$data['tags'] = $this->get_course_taxonomy( $id, 'course_tag' );
					break;
				case 'instructor':
					$data['instructor'] = $this->get_instructor_info( $id, $request, $course );
					break;
				case 'sections':
					$data['sections'] = $this->get_all_items( $course );
					break;
				case 'course_data':
					$data['course_data'] = $this->get_course_data_for_current_user( $id, $request );
					break;
				case 'rating':
					$data['rating'] = $this->get_course_rating( $id );
					break;
				case 'price':
					$data['price'] = floatval( $course->get_price() );
					break;
				case 'price_rendered':
					$data['price_rendered'] = html_entity_decode( $course->get_price_html() );
					break;
				case 'origin_price':
					$data['origin_price'] = floatval( $course->get_origin_price() );
					break;
				case 'origin_price_rendered':
					$data['origin_price_rendered'] = html_entity_decode( $course->get_origin_price_html() );
					break;
				case 'sale_price':
					$data['sale_price'] = floatval( $course->get_sale_price() );
					break;
				case 'sale_price_rendered':
					$data['sale_price_rendered'] = html_entity_decode( learn_press_format_price( $course->get_sale_price(), true ) );
					break;
			}
		}

		$data['meta_data'] = $this->get_course_meta( $id );

		return $data;
	}

	public function get_retaken_count( $id ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		$user = learn_press_get_user( $user_id );

		if ( ! $user ) {
			return 0;
		}

		$user_course_data = $user->get_course_data( $id );
		if ( ! $user_course_data ) {
			return 0;
		}

		return absint( $user_course_data->get_retaken_count() );
	}

	public function check_can_retake( $id ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return 0;
		}

		$user = learn_press_get_user( $user_id );

		if ( $user ) {
			$can_retake_times = $user->can_retry_course( $id );

			if ( $can_retake_times ) {
				return true;
			}

			return false;
		}

		return false;
	}

	public function get_course_rating( $id ) {
		if ( ! function_exists( 'learn_press_get_course_rate' ) ) {
			return false;
		}

		$course_rate = learn_press_get_course_rate( $id );

		return ! empty( $course_rate ) ? floatval( number_format( $course_rate, 1 ) ) : 0;
	}

	public function check_can_finish( $course ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$user = learn_press_get_user( $user_id );

		if ( $user && $course ) {
			$check = $user->can_show_finish_course_btn( $course );

			if ( $check['status'] === 'success' ) {
				return true;
			}

			return false;
		}

		return false;
	}

	public function get_instructor_info( $id, $request, $course ) {
		$user_id = get_post_meta( $id, '_lp_course_author', true );

		$output = array();

		$extra_info = learn_press_get_user_extra_profile_info( $user_id );

		$instructor = $course->get_instructor();

		$output['avatar'] = $instructor->get_upload_profile_src();

		if ( $user_id ) {
			$user = get_user_by( 'ID', absint( $user_id ) );

			if ( $user ) {
				$output['id']          = absint( $user_id );
				$output['name']        = $user->display_name;
				$output['description'] = $user->description;
				$output['social']      = $extra_info;
			}
		}

		return $output;
	}

	public function get_item_learned_ids( $request ) {
		global $wpdb;

		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return false;
		}

		$filter = ! empty( $request['course_filter'] ) ? $request['course_filter'] : false;
		$where  = $wpdb->prepare( 'user_id=%d AND item_type=%s', $user_id, $this->post_type ); // phpcs:ignore

		if ( $filter ) {
			if ( $filter === 'in-progress' ) {
				$where .= $wpdb->prepare( ' AND status=%s AND graduation=%s', 'enrolled', 'in-progress' );
			} elseif ( in_array( $filter, array( 'passed', 'failed' ) ) ) { // is "passed" or "failed"
				$where .= $wpdb->prepare( ' AND status=%s AND graduation=%s', 'finished', $filter );
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
			'start_time'      => $course_data->get_start_time() ? lp_jwt_prepare_date_response( $course_data->get_start_time()->toSql() ) : null,
			'end_time'        => $course_data->get_end_time() ? lp_jwt_prepare_date_response( $course_data->get_end_time()->toSql() ) : null,
			'expiration_time' => $course_data->get_expiration_time() ? lp_jwt_prepare_date_response( $course_data->get_expiration_time()->toSql() ) : '',
			'result'          => $course_data->calculate_course_results(),
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

	/**
	 * Get Items of sections
	 *
	 * @throws Exception
	 * @editor tungnx
	 * @modify 4.1.3
	 * @version 4.0.1
	 */
	public function get_all_items( $course ): array {
		$curriculum  = $course->get_curriculum();
		$user        = learn_press_get_current_user();
		$user_course = $user ? $user->get_course_data( $course->get_id() ) : false;
		$output      = array();

		if ( ! empty( $curriculum ) ) {
			foreach ( $curriculum as $section ) {
				if ( $section ) {
					$data = array(
						'id'          => $section->get_id(),
						'title'       => $section->get_title(),
						'course_id'   => $section->get_course_id(),
						'description' => $section->get_description(),
						'order'       => $section->get_order(),
					);

					if ( $user_course && $user->has_enrolled_or_finished( $section->get_course_id() ) ) {
						$data['percent'] = $user_course->get_percent_completed_items( '', $section->get_id() );
					}

					$data_item = array();

					$items = $section->get_items();

					if ( ! empty( $items ) ) {
						foreach ( $items as $item ) {
							$post = get_post( $item->get_id() );

							$format = array(
								'day'    => __( '%s days', 'learnpress' ),
								'hour'   => __( '%s hours', 'learnpress' ),
								'minute' => __( '%s mins', 'learnpress' ),
								'second' => __( '%s secs', 'learnpress' ),
							);

							$user_item = $user_course ? $user_course->get_item( $item->get_id() ) : false;

							if ( $user ) {
								$can_view_content_course = $user->can_view_content_course( absint( $section->get_course_id() ) );
								$can_view_item           = $user->can_view_item( $item->get_id(), $can_view_content_course );
							}

							$data_item[] = array(
								'id'         => $item->get_id(),
								'type'       => $item->get_item_type(),
								'title'      => $post->post_title,
								'preview'    => $item->is_preview(),
								'duration'   => $item->get_duration()->to_timer( $format, true ),
								'graduation' => $user_item ? $user_item->get_graduation() : '',
								'status'     => $user_item ? $user_item->get_status() : '',
								'locked'     => ! isset( $can_view_item->flag ) || ! $can_view_item->flag,
							);
						}
					}

					$data['items'] = $data_item;
					$output[]      = $data;
				}
			}
		}

		return $output;
	}

	public function get_course_meta( $id ) {
		$user_id = get_current_user_id();

		if ( ! $user_id ) {
			return array();
		}

		if ( ! class_exists( 'LP_Meta_Box' ) ) {
			include_once LP_PLUGIN_PATH . 'inc/admin/views/meta-boxes/class-lp-meta-box.php';
		}

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

		$orderby = $request->get_param( 'orderby' );
		$order   = $request->get_param( 'order' );

		$orderby = strtolower( is_array( $orderby ) ? (string) current( $orderby ) : (string) $orderby );
		$order   = strtoupper( is_array( $order ) ? (string) current( $order ) : (string) $order );

		switch ( $orderby ) {
			case 'id':
				$args['orderby'] = 'ID';
				break;
			case 'menu_order':
				$args['orderby'] = 'menu_order title';
				break;
			case 'title':
				$args['orderby'] = 'post_title';
				$args['order']   = ( 'DESC' === $order ) ? 'DESC' : 'ASC';
				break;
			case 'relevance':
				$args['orderby'] = 'relevance';
				$args['order']   = 'DESC';
				break;
			case 'rand':
				$args['orderby'] = 'rand'; // @codingStandardsIgnoreLine
				break;
			case 'date':
				$args['orderby'] = 'date ID';
				$args['order']   = ( 'ASC' === $order ) ? 'ASC' : 'DESC';
				break;
			case 'price':
				$args['orderby']  = 'meta_value_num';
				$args['meta_key'] = '_lp_price';
				break;
		}

		if ( is_bool( $request['on_sale'] ) ) {
			$on_sale_key = $request['on_sale'] ? 'post__in' : 'post__not_in';

			$filter              = new LP_Course_Filter();
			$filter->only_fields = array( 'ID' );

			$filter      = LP_Course_DB::getInstance()->get_courses_sort_by_sale( $filter );
			$on_sale_ids = LP_Course_DB::getInstance()->get_courses( $filter );
			$on_sale_ids = LP_Database::get_values_by_key( $on_sale_ids );
			$on_sale_ids = empty( $on_sale_ids ) ? array( 0 ) : $on_sale_ids;

			$args[ $on_sale_key ] += $on_sale_ids;
		} elseif ( is_bool( $request['popular'] ) ) {
			$on_popular_key = $request['popular'] ? 'post__in' : 'post__not_in';

			$filter              = new LP_Course_Filter();
			$filter->only_fields = array( 'ID' );
			$filter->limit       = $request['per_page'] ?? 10;
			$filter->page        = $request['page'] ?? 1;

			$filter         = LP_Course_DB::getInstance()->get_courses_order_by_popular( $filter );
			$on_popular_ids = LP_Course_DB::getInstance()->get_courses( $filter );
			$on_popular_ids = LP_Database::get_values_by_key( $on_popular_ids );

			$on_popular_ids = empty( $on_popular_ids ) ? array( 0 ) : $on_popular_ids;

			$args[ $on_popular_key ] += $on_popular_ids;

			$args['orderby'] = 'post__in';
		}

		return $args;
	}

	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => $this->post_type,
			'type'       => 'object',
			'properties' => array(
				'id'                    => array(
					'description' => __( 'A unique identifier for the resource.', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'name'                  => array(
					'description' => __( 'Course name.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'slug'                  => array(
					'description' => __( 'Course slug.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'permalink'             => array(
					'description' => __( 'Course URL.', 'learnpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'image'                 => array(
					'description' => __( 'Course Image URL.', 'learnpress' ),
					'type'        => 'string',
					'format'      => 'uri',
					'context'     => array( 'view', 'edit' ),
				),
				'date_created'          => array(
					'description' => __( "The date the Course was created, in the site's timezone.", 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_created_gmt'      => array(
					'description' => __( 'The date the Course was created, as GMT.', 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified'         => array(
					'description' => __( "The date the Course was last modified, in the site's timezone.", 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_modified_gmt'     => array(
					'description' => __( 'The date the Course was last modified, as GMT.', 'learnpress' ),
					'type'        => array( 'string', 'null' ),
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'on_sale'               => array(
					'description' => __( 'Display courses if they are on sale.', 'learnpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'                => array(
					'description' => __( 'Course status (post status).', 'learnpress' ),
					'type'        => 'string',
					'default'     => 'publish',
					'enum'        => array_merge( array_keys( get_post_statuses() ), array( 'future' ) ),
					'context'     => array( 'view', 'edit' ),
				),
				'content'               => array(
					'description' => __( 'Course content.', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'excerpt'               => array(
					'description' => __( 'Retrieves the course excerpt..', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'duration'              => array(
					'description' => __( 'Duration', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
				),
				'count_students'        => array(
					'description' => __( 'Count the number of enrolled students.', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'can_finish'            => array(
					'description' => __( 'Can finish the course', 'learnpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'can_retake'            => array(
					'description' => __( 'Can retake the course', 'learnpress' ),
					'type'        => 'boolean',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'ratake_count'          => array(
					'description' => __( 'Total retakes', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'rataken'               => array(
					'description' => __( 'Retaken', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'rating'                => array(
					'description' => __( 'Course Review add-on', 'learnpress' ),
					'type'        => array( 'boolean', 'integer' ),
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'price'                 => array(
					'description' => __( 'Course Price', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'price_rendered'        => array(
					'description' => __( 'Course Price Rendered', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'origin_price'          => array(
					'description' => __( 'Course Origin Price', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'origin_price_rendered' => array(
					'description' => __( 'Course Origin Price Rendered', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'sale_price'            => array(
					'description' => __( 'Course Sale Price', 'learnpress' ),
					'type'        => 'integer',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'sale_price_rendered'   => array(
					'description' => __( 'Course Sale Price Rendered', 'learnpress' ),
					'type'        => 'string',
					'context'     => array( 'view' ),
					'readonly'    => true,
				),
				'categories'            => array(
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
				'tags'                  => array(
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
				'instructor'            => array(
					'description' => __( 'Retrieves the course sections and items..', 'learnpress' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'object',
						'properties' => array(
							'id'          => array(
								'description' => __( 'User ID.', 'learnpress' ),
								'type'        => 'integer',
								'context'     => array( 'view' ),
							),
							'name'        => array(
								'description' => __( 'Display name.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'description' => array(
								'description' => __( 'Tag slug.', 'learnpress' ),
								'type'        => 'string',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
							'social'      => array(
								'description' => __( 'Social Infor.', 'learnpress' ),
								'type'        => 'array',
								'context'     => array( 'view' ),
								'readonly'    => true,
							),
						),
					),
				),
				'sections'              => array(
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
									'id'         => array(
										'description' => __( 'Item ID.', 'learnpress' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
									),
									'type'       => array(
										'description' => __( 'Item Type.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'title'      => array(
										'description' => __( 'Item title.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'preview'    => array(
										'description' => __( 'Item ID.', 'learnpress' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
									'percent'    => array(
										'description' => __( 'Percent.', 'learnpress' ),
										'type'        => 'integer',
										'context'     => array( 'view', 'edit' ),
									),
									'duration'   => array(
										'description' => __( 'Duration.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'graduation' => array(
										'description' => __( 'Graduation.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'status'     => array(
										'description' => __( 'Status.', 'learnpress' ),
										'type'        => 'string',
										'context'     => array( 'view', 'edit' ),
									),
									'locked'     => array(
										'description' => __( 'Locked.', 'learnpress' ),
										'type'        => 'boolean',
										'context'     => array( 'view', 'edit' ),
									),
								),
							),
						),
					),
				),
				'course_data'           => array(
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

		$params['orderby']['enum'] = array_merge( $params['orderby']['enum'], array( 'menu_order', 'price' ) );

		$params['category']      = array(
			'description'       => 'Limit the result set to courses assigned to a specific category ID.',
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['tag']           = array(
			'description'       => 'Limit the result set to courses assigned to a specific tag ID.',
			'type'              => 'string',
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['course_filter'] = array(
			'description'       => 'Filter by course to in-progress, passed, failed.',
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['on_sale'] = array(
			'description'       => 'Get item learned by user.',
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['popular']  = array(
			'description'       => 'Get item popularity.',
			'type'              => 'boolean',
			'validate_callback' => 'rest_validate_request_arg',
		);
		$params['optimize'] = array(
			'description'       => 'Disable some fields in the schema.',
			'type'              => array( 'boolean', 'string' ),
			'validate_callback' => 'wp_parse_id_list',
		);

		return $params;
	}
}
