<?php

/**
 * Class LP_REST_Courses_Controller
 */

use LearnPress\Helpers\Template;

class LP_REST_Courses_Controller extends LP_Abstract_REST_Controller {
	/**
	 * LP_REST_Courses_Controller constructor.
	 */
	public function __construct() {
		$this->namespace = 'lp/v1';
		$this->rest_base = 'courses';
		parent::__construct();
	}

	/**
	 * Register routes API
	 */
	public function register_routes() {
		$this->routes = array(
			'purchase-course' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'purchase_course' ),
					'permission_callback' => '__return_true',
				),
			),
			'enroll-course'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll_courses' ),
					'permission_callback' => '__return_true',
				),
			),
			'retake-course'   => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'retake_course' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
					'schema'              => array(
						'type' => 'int',
					),
				),
			),
			'archive-course'  => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'list_courses' ),
					'permission_callback' => '__return_true',
					'args'                => [],
				),
			),
			'(?P<key>[\w]+)'  => array(
				'args'   => array(
					'id' => array(
						'description' => __( 'A unique identifier for the resource.', 'learnpress' ),
						'type'        => 'string',
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'check_admin_permission' ),
				),
				'schema' => array( $this, 'get_public_item_schema' ),
			),
			'continue-course' => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'continue_course' ),
					'permission_callback' => function () {
						return is_user_logged_in();
					},
				),
			),
		);

		parent::register_routes();
	}

	/**
	 * Check user is Admin
	 *
	 * @return bool
	 */
	public function check_admin_permission(): bool {
		return LP_Abstract_API::check_admin_permission();
	}

	/**
	 * Get list courses
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 */
	public function list_courses( WP_REST_Request $request ): LP_REST_Response {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$filter             = new LP_Course_Filter();
			$filter->page       = absint( $request['paged'] ?? 1 );
			$filter->post_title = LP_Helper::sanitize_params_submitted( $request['c_search'] ?? '' );
			$fields_str         = LP_Helper::sanitize_params_submitted( urldecode( $request['c_fields'] ?? '' ) );
			$fields_exclude_str = LP_Helper::sanitize_params_submitted( urldecode( $request['c_exclude_fields'] ?? '' ) );
			if ( ! empty( $fields_str ) ) {
				$fields         = explode( ',', $fields_str );
				$filter->fields = $fields;
			}

			if ( ! empty( $fields_exclude_str ) ) {
				$fields_exclude         = explode( ',', $fields_exclude_str );
				$filter->exclude_fields = $fields_exclude;
			}

			$filter->post_author = LP_Helper::sanitize_params_submitted( $request['c_author'] ?? 0 );
			$author_ids_str      = LP_Helper::sanitize_params_submitted( $request['c_authors'] ?? 0 );
			if ( ! empty( $author_ids_str ) ) {
				$author_ids           = explode( ',', $author_ids_str );
				$filter->post_authors = $author_ids;
			}

			$term_ids_str = LP_Helper::sanitize_params_submitted( urldecode( $request['term_id'] ?? '' ) );
			if ( ! empty( $term_ids_str ) ) {
				$term_ids         = explode( ',', $term_ids_str );
				$filter->term_ids = $term_ids;
			}

			$on_sale                               = absint( $request['on_sale'] ?? '0' );
			1 === $on_sale ? $filter->sort_by[]    = 'on_sale' : '';
			$on_feature                            = absint( $request['on_feature'] ?? '0' );
			1 === $on_feature ? $filter->sort_by[] = 'on_feature' : '';

			$filter->order_by = LP_Helper::sanitize_params_submitted( ! empty( $request['order_by'] ) ? $request['order_by'] : 'post_date' );
			$filter->order    = LP_Helper::sanitize_params_submitted( ! empty( $request['order'] ) ? $request['order'] : 'DESC' );
			$filter->limit    = $request['limit'] ?? LP_Settings::get_option( 'archive_course_limit', 10 );
			$return_type      = $request['return_type'] ?? 'html';
			if ( 'json' !== $return_type ) {
				$filter->only_fields = array( 'DISTINCT(ID) AS ID' );
			}

			$total_rows  = 0;
			$filter      = apply_filters( 'lp/api/courses/filter', $filter, $request );
			$courses     = LP_Course::get_courses( $filter, $total_rows );
			$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );

			if ( 'json' === $return_type ) {
				$response->data->courses     = $courses;
				$response->data->total_pages = $total_pages;
			} else {
				// For return data has html
				ob_start();
				if ( $courses ) {
					global $wp, $post;

					// Template Pagination.
					$response->data->pagination = learn_press_get_template_content(
						'loop/course/pagination.php',
						array(
							'total' => $total_pages,
							'paged' => $filter->page,
						)
					);
					// End Pagination

					// For custom template
					$template_path = apply_filters( 'lp/api/courses/template', '', $request );
					if ( ! empty( $template_path ) ) {
						Template::instance()->get_template( $template_path, compact( 'courses', 'total_pages', 'request' ) );
					} else {
						foreach ( $courses as $course ) {
							$post = get_post( $course->ID );
							setup_postdata( $post );
							Template::instance()->get_frontend_template( 'content-course.php' );
						}

						wp_reset_postdata();
					}
					// End content items
				} else {
					LearnPress::instance()->template( 'course' )->no_courses_found();
				}

				$response->data->content = ob_get_clean();
				$response->data->totals  = $total_rows;

				$from = 1 + ( $filter->page - 1 ) * $filter->limit;
				$to   = ( $filter->page * $filter->limit > $total_rows ) ? $total_rows : $filter->page * $filter->limit;

				if ( 0 === $total_rows ) {
					$response->data->from_to = '';
				} elseif ( 1 === $total_rows ) {
					$response->data->from_to = esc_html__( 'Showing only one result', 'learnpress' );
				} else {
					if ( $from == $to ) {
						$response->data->from_to = sprintf( esc_html__( 'Showing last course of %s results', 'learnpress' ), $total_rows );
					} else {
						$from_to                 = $from . '-' . $to;
						$response->data->from_to = sprintf( esc_html__( 'Showing %1$s of %2$s results', 'learnpress' ), $from_to, $total_rows );
					}
				}
			}

			$response->status = 'success';
		} catch ( Throwable $e ) {
			ob_end_clean();
			$response->data->content = $e->getMessage();
			$response->message       = $e->getMessage();
		}

		return apply_filters( 'lp/rest-api/frontend/course/archive_course/response', $response );
	}

	/**
	 * Rest API for Enroll in single course.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @author Nhamdv
	 * @editor tungnx
	 * @version 1.0.2
	 * @since 4.0.0
	 */
	public function enroll_courses( WP_REST_Request $request ): LP_REST_Response {
		$response         = new LP_REST_Response();
		$response->data   = new stdClass();
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		try {
			$course_id = absint( $request['id'] ?? 0 );
			$course    = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Invalid course!', 'learnpress' ) );
			}

			$user       = learn_press_get_current_user();
			$can_enroll = $user->can_enroll_course( $course_id, false );

			if ( ! $can_enroll->check ) {
				throw new Exception( $can_enroll->message ?? esc_html__( 'Error: Cannot enroll in the course.', 'learnpress' ) );
			}

			$filter          = new LP_User_Items_Filter();
			$filter->user_id = get_current_user_id();
			$filter->item_id = $course_id;
			$course_item     = $lp_user_items_db->get_last_user_course( $filter );

			// Case: if user bought course - or create order manual with order "completed".
			if ( $course_item && 'purchased' == $course_item->status ) {
				$user_item_data = [
					'user_item_id' => $course_item->user_item_id,
					'graduation'   => LP_COURSE_GRADUATION_IN_PROGRESS,
					'status'       => LP_COURSE_ENROLLED,
					'start_time'   => time(),
				];

				$user_item_new_or_update = new LP_User_Item_Course( $user_item_data );
				$result                  = $user_item_new_or_update->update();

				if ( ! $result ) {
					throw new Exception( esc_html__( 'Error: Cannot Enroll in the course.', 'learnpress' ) );
				}

				do_action( 'learnpress/user/course-enrolled', $course_item->ref_id, $course_id, $user->get_id() );
			} else { // Case enroll course free
				//LearnPress::instance()->session->set( 'order_awaiting_payment', '' );

				$cart     = LearnPress::instance()->cart;
				$checkout = LP_Checkout::instance();

				if ( ! learn_press_enable_cart() ) {
					//$order_awaiting_payment = LearnPress::instance()->session->order_awaiting_payment;
					$cart->empty_cart();
					//LearnPress::instance()->session->order_awaiting_payment = $order_awaiting_payment;
				}

				$cart_id = $cart->add_to_cart( $course_id, 1, array() );

				if ( ! $cart_id ) {
					throw new Exception( esc_html__( 'Error: The course cannot be added to the cart.', 'learnpress' ) );
				}

				if ( is_user_logged_in() ) {
					$order_id = $checkout->create_order();

					if ( is_wp_error( $order_id ) ) {
						throw new Exception( $order_id->get_error_message() );
					}

					$order = new LP_Order( $order_id );
					$order->payment_complete();

					$cart->empty_cart();
				}
			}

			if ( is_user_logged_in() ) {
				$response->status = 'success';
				// Course has no items
				$response->message = esc_html__(
					'Congrats! You have enrolled in the course successfully. Redirecting...',
					'learnpress'
				);

				$response->data->redirect = $course->get_redirect_url_after_enroll();

				if ( empty( $course->count_items() ) ) {
					$response->data->redirect = get_permalink( $course->get_id() );
				}
			} else {
				$redirect_url = apply_filters(
					'learnpress/rest-api/courses/enroll/redirect',
					learn_press_get_page_link( 'checkout' ),
					$course_id
				);

				if ( empty( $redirect_url ) ) {
					throw new Exception( __( 'Error: Please set up a page for checkout.', 'learnpress' ) );
				} elseif ( ! is_user_logged_in() ) { // Fix case: cache page with user anonymous
					$redirect_url = LP_Helper::get_link_no_cache( $redirect_url );
				}

				$response->message        = esc_html__( 'Redirecting...', 'learnpress' );
				$response->data->redirect = $redirect_url;
			}
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

	/**
	 * Rest API for Purchase course in single course.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @return WP_REST_Response|WP_Error
	 * @throws Exception .
	 * @author Nhamdv
	 */
	public function purchase_course( WP_REST_Request $request ) {
		$response         = new LP_REST_Response();
		$response->data   = new stdClass();
		$params           = $request->get_params();
		$lp_user_items_db = LP_User_Items_DB::getInstance();

		try {
			$course_id             = $params['id'];
			$allow_repurchase_type = $params['repurchaseType'] ?? false;

			if ( ! $course_id ) {
				throw new Exception( __( 'Error: Invalid Course ID.', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );
			if ( ! $course ) {
				throw new Exception( __( 'Error: No Course available.', 'learnpress' ) );
			}

			$user = learn_press_get_current_user();
			if ( ! $user->can_purchase_course( $course_id ) ) {
				throw new Exception( esc_html__( 'Error: Cannot purchase the course!', 'learnpress' ) );
			}

			$latest_user_item_id = 0;

			$filter          = new LP_User_Items_Filter();
			$filter->user_id = get_current_user_id();
			$filter->item_id = $course_id;
			$course_item     = $lp_user_items_db->get_last_user_course( $filter );

			if ( $course_item && isset( $course_item->user_item_id ) ) {
				$latest_user_item_id = $course_item->user_item_id;
			}

			if ( $course->allow_repurchase() && ! empty( $latest_user_item_id ) && empty( $allow_repurchase_type ) ) {
				if ( $course->allow_repurchase_course_option() === 'popup' ) {
					ob_start();
					?>
					<div class="lp_allow_repuchase_select">
						<ul>
							<li>
								<label>
									<input name="_lp_allow_repurchase_type" value="reset" type="radio" checked="checked" />
									<?php esc_html_e( 'Reset Course progress', 'learnpress' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input name="_lp_allow_repurchase_type" value="keep" type="radio" />
									<?php esc_html_e( 'Continue Course progress', 'learnpress' ); ?>
								</label>
							</li>
						</ul>
					</div>
					<?php
					$response->data->html       = ob_get_clean();
					$response->data->type       = 'allow_repurchase';
					$response->data->titlePopup = esc_html__( 'Repurchase Options', 'learnpress' );
					$response->status           = 'success';

					return rest_ensure_response( $response );
				} else {
					learn_press_update_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type', $course->allow_repurchase_course_option() );
				}
			}

			//LearnPress::instance()->session->set( 'order_awaiting_payment', '' );

			$cart = LearnPress::instance()->cart;
			//$checkout = LP_Checkout::instance();

			if ( ! learn_press_enable_cart() ) {
				//$order_awaiting_payment = LearnPress::instance()->session->order_awaiting_payment;
				$cart->empty_cart();
				//LearnPress::instance()->session->order_awaiting_payment = $order_awaiting_payment;
			}

			do_action( 'learnpress/rest-api/courses/purchase/before-add-to-cart' );

			$cart_id = $cart->add_to_cart( $course_id, 1, array() );
			if ( empty( $cart_id ) ) {
				throw new Exception( __( 'Error: The course cannot be added to the cart.', 'learnpress' ) );
			}

			if ( ! empty( $allow_repurchase_type ) ) {
				learn_press_update_user_item_meta( $latest_user_item_id, '_lp_allow_repurchase_type', $allow_repurchase_type );
			}

			$redirect_url = apply_filters(
				'learnpress/rest-api/courses/purchase/redirect',
				learn_press_get_page_link( 'checkout' ),
				$course_id,
				$cart_id
			);

			if ( empty( $redirect_url ) ) {
				throw new Exception( __( 'Error: Please set up a page for checkout.', 'learnpress' ) );
			} elseif ( ! is_user_logged_in() ) { // Fix case: cache page with user anonymous
				$redirect_url = LP_Helper::get_link_no_cache( $redirect_url );
			}

			$response->status         = 'success';
			$response->message        = sprintf(
				esc_html__( '"%s" has been added to your cart.', 'learnpress' ),
				$course->get_title()
			);
			$response->data->redirect = $redirect_url;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return rest_ensure_response( $response );
	}

	/**
	 * Rest API for retake course.
	 *
	 * @param WP_REST_Request $request .
	 *
	 * @throws Exception .
	 */
	public function retake_course( WP_REST_Request $request ) {
		$response = new LP_REST_Response();

		try {
			$course_id = $request->get_param( 'id' );

			if ( ! $course_id ) {
				throw new Exception( __( 'Invalid params', 'learnpress' ) );
			}

			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( __( 'Invalid course', 'learnpress' ) );
			}

			$user = learn_press_get_current_user();

			// if ( ! is_user_logged_in() ) {
			// throw new Exception( esc_html__( 'Please login!', 'learnpress' ) );
			// }

			$can_retry = $user->can_retry_course( $course_id );

			if ( ! $can_retry ) {
				throw new Exception( __( 'You can\'t retry the course', 'learnpress' ) );
			}

			$user_course_data = $user->get_course_data( $course_id );
			if ( ! $user_course_data ) {
				throw new Exception( __( 'Invalid course data of user', 'learnpress' ) );
			}

			// Up retaken.
			$user_course_data->increase_retake_count();

			// Set status, start_time, end_time of course to enrol.
			$user_course_data->set_status( LP_COURSE_ENROLLED )
				->set_start_time( time() )
				->set_end_time()
				->set_graduation( LP_COURSE_GRADUATION_IN_PROGRESS )
				->update();

			// Remove items' course user learned.
			$filter_remove            = new LP_User_Items_Filter();
			$filter_remove->parent_id = $user_course_data->get_user_item_id();
			$filter_remove->user_id   = $user_course_data->get_user_id();
			$filter_remove->limit     = - 1;
			LP_User_Items_DB::getInstance()->remove_items_of_user_course( $filter_remove );

			// Create new result in table learnpress_user_item_results.
			LP_User_Items_Result_DB::instance()->insert( $user_course_data->get_user_item_id() );

			$response->status             = 'success';
			$response->message            = esc_html__( 'Now you can begin this course', 'learnpress' );
			$response->data->url_redirect = $course->get_redirect_url_after_enroll();
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		wp_send_json( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_items( $request ) {
		$settings = LP_Settings::instance();
		$response = array(
			'result' => $settings->get(),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_item( $request ) {
		$settings = LP_Settings::instance();
		$response = array(
			'result' => $settings->get( $request['key'] ),
		);

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_item( $request ) {
		$response = array();
		$settings = LP_Settings::instance();
		$option   = $settings->get( $request['key'] );

		$settings->update( $request['key'], $request['data'] );
		$new_option = $settings->get( $request['key'] );
		$success    = maybe_serialize( $option ) !== maybe_serialize( $new_option );

		$response['success'] = $success;
		$response['result']  = $success ? $new_option : $option;

		return rest_ensure_response( $response );
	}

	/**
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function delete_item( $request ) {
		$response = array();

		return rest_ensure_response( $response );
	}

	/**
	 * Rest API for get item continue in single course.
	 *
	 * @param WP_REST_Request $request
	 *
	 * @author minhpd
	 * @editor tungnx
	 * @since 4.1.4
	 * @version 1.0.2
	 * @return LP_REST_Response
	 */
	public function continue_course( WP_REST_Request $request ): LP_REST_Response {
		$params         = $request->get_params();
		$response       = new LP_REST_Response();
		$response->data = '';

		try {
			$flag_found = false;
			$item_link  = '';
			$course_id  = absint( $params['courseId'] ?? 0 );
			$user_id    = absint( $params['userId'] ?? 0 );

			$user   = learn_press_get_user( $user_id );
			$course = learn_press_get_course( $course_id );

			if ( ! $course ) {
				throw new Exception( __( 'Invalid course', 'learnpress' ) );
			}

			$sections_items = $course->get_full_sections_and_items_course();
			$total_items    = $course->count_items();

			if ( ! empty( $total_items ) ) {
				foreach ( $sections_items as $section_items ) {
					if ( $flag_found ) {
						break;
					}

					foreach ( $section_items->items as $item ) {
						$item_now_condition = apply_filters(
							'lp/course/item-continue/condition',
							! $user->has_completed_item( $item->id, $course_id ),
							$item,
							$course,
							$user
						);
						if ( $item_now_condition ) {
							$item_link  = $course->get_item_link( $item->id );
							$flag_found = true;
							break;
						}
					}
				}

				if ( ! $flag_found ) {
					$item_link = $course->get_item_link( $course->get_first_item_id() );
				}
			}

			$response->data    = $item_link;
			$response->status  = 'success';
			$response->message = '';
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
	}

}
