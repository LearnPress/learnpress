<?php

/**
 * Class LP_REST_Courses_Controller
 */

use LearnPress\ExternalPlugin\Elementor\Widgets\Course\ListCoursesByPageElementor;
use LearnPress\Helpers\Template;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;

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
			'purchase-course'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'purchase_course' ),
					'permission_callback' => '__return_true',
				),
			),
			'enroll-course'          => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'enroll_courses' ),
					'permission_callback' => '__return_true',
				),
			),
			'retake-course'          => array(
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
			'archive-course'         => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'list_courses' ),
					'permission_callback' => '__return_true',
					'args'                => [],
				),
			),
			'courses-widget-by-page' => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'courses_widget_by_page' ),
					'permission_callback' => '__return_true',
					'args'                => [],
				),
			),
			'(?P<key>[\w]+)'         => array(
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
			'continue-course'        => array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'continue_course' ),
					'permission_callback' => '__return_true',
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
		$response            = new LP_REST_Response();
		$response->data      = new stdClass();
		$listCoursesTemplate = ListCoursesTemplate::instance();

		try {
			$filter = new LP_Course_Filter();
			LP_course::handle_params_for_query_courses( $filter, $request->get_params() );

			$total_rows = 0;
			$filter     = apply_filters( 'lp/api/courses/filter', $filter, $request );

			$courses     = LP_Course::get_courses( $filter, $total_rows );
			$total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );
			$return_type = $request['return_type'] ?? 'html';
			if ( 'json' === $return_type ) {
				$response->data->courses     = $courses;
				$response->data->total_pages = $total_pages;
			} else {
				// For return data has html
				ob_start();
				if ( $courses ) {
					if ( ! empty( $request['c_suggest'] ) ) {
						$data = array(
							'courses'      => $courses,
							'keyword'      => $request['c_search'],
							'total_course' => $total_rows,
						);
						do_action( 'learn-press/rest-api/courses/suggest/layout', $data );
					} else {
						global $wp, $post;

						// Template Pagination.
						$pagination_type = LP_Settings::get_option( 'course_pagination_type' );
						switch ( $pagination_type ) {
							case 'load-more':
								if ( $filter->page < $total_pages ) {
									$response->data->pagination = $listCoursesTemplate->html_pagination_load_more();
								}
								break;
							case 'infinite':
								if ( $filter->page < $total_pages ) {
									$response->data->pagination = $listCoursesTemplate->html_pagination_infinite();
								}
								break;
							default:
								$pagination_args            = [
									'total_pages' => $total_pages,
									'paged'       => $filter->page,
									'base'        => add_query_arg( 'paged', '%#%', learn_press_get_page_link( 'courses' ) ),
								];
								$response->data->pagination = $listCoursesTemplate->html_pagination_number( $pagination_args );
								break;
						}
						$response->data->pagination_type = $pagination_type;
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
					}
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
	 * Get list courses - Widget Elementor
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 */
	public function courses_widget_by_page( WP_REST_Request $request ): LP_REST_Response {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$settings                = array_merge(
				$request->get_params(),
				[
					'courses_ul_classes' => [ 'list-courses-elm' ],
				]
			);
			$response->data->content = ListCoursesByPageElementor::render_data_from_setting( $settings );

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
					$order    = new LP_Order( $order_id );
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

			$user         = learn_press_get_current_user();
			$can_purchase = $user->can_purchase_course( $course_id );
			if ( is_wp_error( $can_purchase ) ) {
				throw new Exception( $can_purchase->get_error_message() );
			}

			$filter              = new LP_User_Items_Filter();
			$filter->user_id     = get_current_user_id();
			$filter->item_id     = $course_id;
			$course_item         = $lp_user_items_db->get_last_user_course( $filter );
			$latest_user_item_id = 0;
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
									<input name="_lp_allow_repurchase_type" value="reset" type="radio"
										checked="checked"/>
									<?php esc_html_e( 'Reset Course progress', 'learnpress' ); ?>
								</label>
							</li>
							<li>
								<label>
									<input name="_lp_allow_repurchase_type" value="keep" type="radio"/>
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

			$cart = LearnPress::instance()->cart;
			if ( ! learn_press_enable_cart() ) {
				$cart->empty_cart();
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
	 * @return LP_REST_Response
	 * @since 4.1.4
	 * @version 1.0.2
	 * @author minhpd
	 * @editor tungnx
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
