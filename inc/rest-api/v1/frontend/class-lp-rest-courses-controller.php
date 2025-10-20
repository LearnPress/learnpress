<?php

/**
 * Class LP_REST_Courses_Controller
 */

use LearnPress\Background\LPBackgroundAjax;
use LearnPress\ExternalPlugin\Elementor\Widgets\Course\ListCoursesByPageElementor;
use LearnPress\Helpers\Template;
use LearnPress\Models\CourseModel;
use LearnPress\Models\CoursePostModel;
use LearnPress\Models\Courses;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\UserItems\UserItemModel;
use LearnPress\Models\UserModel;
use LearnPress\TemplateHooks\Course\ListCoursesTemplate;
use LearnPress\TemplateHooks\Course\SingleCourseTemplate;

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
			''                       => array(
				array(
					'methods'             => WP_REST_Server::ALLMETHODS,
					'callback'            => array( $this, 'get_courses' ),
					'permission_callback' => '__return_true',
					'args'                => array(),
				),
			),
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
	 * Get list courses, return JSON data, not to handle HTML
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return LP_REST_Response
	 * @since 4.2.8.2
	 * @version 1.0.0
	 */
	public function get_courses( WP_REST_Request $request ): LP_REST_Response {
		$response = new LP_REST_Response();

		try {
			$filter     = new LP_Course_Filter();
			$params     = $request->get_params();
			$total_rows = 0;

			Courses::handle_params_for_query_courses( $filter, $params );
			$filter->only_fields = [ 'ID' ];
			$coursesRs           = Courses::get_courses( $filter, $total_rows );

			$courses              = [];
			$singleCourseTemplate = SingleCourseTemplate::instance();
			foreach ( $coursesRs as $course ) {
				$courseModel = CourseModel::find( $course->ID, true );
				if ( ! $courseModel ) {
					continue;
				}

				$courseItem              = new stdClass();
				$courseItem->ID          = $course->ID;
				$courseItem->description = $singleCourseTemplate->html_short_description( $courseModel );
				$courseItem->price       = $singleCourseTemplate->html_price( $courseModel );
				$courseItem->title       = $courseModel->get_title();
				$courseItem->student     = $singleCourseTemplate->html_count_student( $courseModel );
				$courseItem->lesson      = $singleCourseTemplate->html_count_item( $courseModel, LP_LESSON_CPT );
				$courseItem->duration    = $singleCourseTemplate->html_duration( $courseModel );
				$courseItem->quiz        = $singleCourseTemplate->html_count_item( $courseModel, LP_QUIZ_CPT );
				$courseItem->level       = $singleCourseTemplate->html_level( $courseModel );
				$courseItem->image       = $singleCourseTemplate->html_image( $courseModel );
				$courseItem->instructor  = $singleCourseTemplate->html_instructor( $courseModel, false, [ 'is_link' => false ] );
				$courseItem->category    = $singleCourseTemplate->html_categories( $courseModel );
				$courseItem->button      = __( 'Read more', 'learnpress' );

				$courses[] = apply_filters( 'lp/rest-api/frontend/course/archive_course/courses', $courseItem, $courseModel );
			}

			$response->status            = 'success';
			$response->data->courses     = $courses;
			$response->data->total       = $total_rows;
			$response->data->page        = $filter->page;
			$response->data->total_pages = LP_Database::get_total_pages( $filter->limit, $total_rows );
		} catch ( Throwable $e ) {
			$response->message = $e->getMessage();
		}

		return apply_filters( 'lp/rest-api/frontend/course/archive_course/response', $response );
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
		$pagination_type     = LP_Settings::get_option( 'course_pagination_type' );

		try {
			$filter = new LP_Course_Filter();
			Courses::handle_params_for_query_courses( $filter, $request->get_params() );

			// Check is in category page.
			/*if ( ! empty( $request->get_param( 'page_term_id_current' ) ) &&
				empty( $request->get_param( 'term_id' ) ) ) {
				$filter->term_ids[] = $request->get_param( 'page_term_id_current' );
			} // Check is in tag page.
			elseif ( ! empty( $request->get_param( 'page_tag_id_current' ) ) &&
					empty( $request->get_param( 'tag_id' ) ) ) {
				$filter->tag_ids[] = $request->get_param( 'page_tag_id_current' );
			}*/

			$total_rows = 0;
			$filter     = apply_filters( 'lp/api/courses/filter', $filter, $request );

			$courses     = Courses::get_courses( $filter, $total_rows );
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
						switch ( $pagination_type ) {
							case 'load-more':
							case 'infinite':
								$from_to = $filter->page * $filter->limit;
								if ( $from_to > $total_rows ) {
									$from_to = $total_rows;
								}
								break;
							default:
								$from_to = $from . '-' . $to;
								break;
						}

						$response->data->from_to = sprintf( esc_html__( 'Showing %1$s of %2$s results', 'learnpress' ), $from_to, $total_rows );
					}
				}
			}

			$response->status = 'success';
		} catch ( Throwable $e ) {
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
	 * @version 1.0.3
	 * @since 4.0.0
	 */
	public function enroll_courses( WP_REST_Request $request ): LP_REST_Response {
		$response       = new LP_REST_Response();
		$response->data = new stdClass();

		try {
			$course_id = absint( $request['id'] ?? 0 );
			$course    = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				throw new Exception( esc_html__( 'Invalid course!', 'learnpress' ) );
			}

			$user_id    = get_current_user_id();
			$user       = UserModel::find( $user_id, true );
			$can_enroll = $course->can_enroll( $user );
			if ( $can_enroll instanceof WP_Error ) {
				throw new Exception( $can_enroll->get_error_message() );
			}

			// Case: if user bought course
			$userCourse = UserCourseModel::find( $user_id, $course_id, true );
			if ( $userCourse && $userCourse->has_purchased() ) {
				$userCourse->graduation = LP_COURSE_GRADUATION_IN_PROGRESS;
				$userCourse->status     = LP_COURSE_ENROLLED;
				$userCourse->start_time = gmdate( LP_Datetime::$format, time() );
				$userCourse->save();

				do_action( 'learnpress/user/course-enrolled', $userCourse->ref_id, $course_id, $user->get_id() );

				/**
				 * Send mail user enrolled course
				 * @uses SendEmailAjax::send_mail_user_enrolled_course()
				 */
				$data_send = [
					$userCourse->ref_id,
					$course_id,
					$user->get_id(),
				];
				LPBackgroundAjax::handle(
					[
						'params'       => $data_send,
						'lp-load-ajax' => 'send_mail_user_enrolled_course',
					]
				);
			} else { // Case enroll course free
				$cart     = LearnPress::instance()->cart;
				$checkout = LP_Checkout::instance();

				if ( ! learn_press_enable_cart() ) {
					$cart->empty_cart();
				}

				$cart_id = $cart->add_to_cart( $course_id );
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
				$response->message = esc_html__(
					'Congrats! You have enrolled in the course successfully. Redirecting...',
					'learnpress'
				);

				$first_item_id            = $course->get_first_item_id();
				$response->data->redirect = $first_item_id ? $course->get_item_link( $first_item_id ) : get_the_permalink( $course->get_id() );
			} else {
				$redirect_url = LP_Page_Controller::get_link_page( 'checkout', [], true );
				$redirect_url = apply_filters(
					'learnpress/rest-api/courses/enroll/redirect',
					$redirect_url,
					$course_id
				);

				if ( empty( $redirect_url ) ) {
					throw new Exception( __( 'Error: Please set up a page for checkout.', 'learnpress' ) );
				}

				$redirect_url             = LP_Helper::get_link_no_cache( $redirect_url );
				$response->message        = esc_html__( 'Redirecting...', 'learnpress' );
				$response->data->redirect = esc_url_raw( $redirect_url );
			}

			$response->status = 'success';
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
		$response       = new LP_REST_Response();
		$response->data = new stdClass();
		$params         = $request->get_params();

		try {
			$course_id             = $params['id'];
			$allow_repurchase_type = $params['repurchaseType'] ?? false;

			if ( ! $course_id ) {
				throw new Exception( __( 'Error: Invalid Course ID.', 'learnpress' ) );
			}

			$course = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				throw new Exception( __( 'Error: No Course available.', 'learnpress' ) );
			}

			$user_id      = get_current_user_id();
			$user         = UserModel::find( $user_id, true );
			$can_purchase = $course->can_purchase( $user );
			if ( $can_purchase instanceof WP_Error ) {
				throw new Exception( $can_purchase->get_error_message() );
			}

			$userCourse         = UserCourseModel::find( $user_id, $course_id, true );
			$option_repurchase  = [
				'reset' => esc_html__( 'Reset course progress', 'learnpress' ),
				'keep'  => esc_html__( 'Keep course progress', 'learnpress' ),
			];
			$html_li_repurchase = '';
			foreach ( $option_repurchase as $key => $value ) {
				$html_li_repurchase .= sprintf(
					'<li><label>%s</label></li>',
					sprintf(
						'<input name="_lp_allow_repurchase_type" value="%s" type="radio" checked="checked"/> %s',
						$key,
						$value
					)
				);
			}

			$repurchase_type          = $course->get_type_repurchase();
			$section_popup_repurchase = [
				'wrapper'     => '<div class="lp_allow_repurchase_select">',
				'ul'          => '<ul>',
				'list'        => $html_li_repurchase,
				'ul_end'      => '</ul>',
				'wrapper_end' => '</div>',
			];
			if ( $course->enable_allow_repurchase()
				&& $user_id && $userCourse
				&& empty( $allow_repurchase_type ) ) {
				if ( $repurchase_type === 'popup' ) {
					$response->data->html       = Template::combine_components( $section_popup_repurchase );
					$response->data->type       = 'allow_repurchase';
					$response->data->titlePopup = esc_html__( 'Repurchase Options', 'learnpress' );
					$response->status           = 'success';

					return rest_ensure_response( $response );
				}
			}

			$cart = LearnPress::instance()->cart;
			if ( ! learn_press_enable_cart() ) {
				$cart->empty_cart();
			}

			// @deprecated hook since v4.2.7.3
			//do_action( 'learnpress/rest-api/courses/purchase/before-add-to-cart' );

			$cart_id = $cart->add_to_cart( $course_id, 1, $params );
			if ( empty( $cart_id ) ) {
				throw new Exception( __( 'Error: The course cannot be added to the cart.', 'learnpress' ) );
			}

			if ( ! empty( $allow_repurchase_type ) && $userCourse ) {
				learn_press_update_user_item_meta( $userCourse->get_user_item_id(), '_lp_allow_repurchase_type', $allow_repurchase_type );
			}

			$redirect_url = LP_Page_Controller::get_link_page( 'checkout', [], true );
			$redirect_url = apply_filters(
				'learnpress/rest-api/courses/purchase/redirect',
				$redirect_url,
				$course_id,
				$cart_id
			);

			if ( empty( $redirect_url ) ) {
				throw new Exception( __( 'Error: Please set up a page for checkout.', 'learnpress' ) );
			}

			$redirect_url             = LP_Helper::get_link_no_cache( $redirect_url );
			$response->status         = 'success';
			$response->message        = sprintf(
				esc_html__( '"%s" has been added to your cart. Redirecting...', 'learnpress' ),
				$course->get_title()
			);
			$response->data->redirect = esc_url_raw( $redirect_url );
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

			$courseModel = CourseModel::find( $course_id, true );
			if ( ! $courseModel ) {
				throw new Exception( __( 'Invalid course', 'learnpress' ) );
			}

			$userModel = UserModel::find( get_current_user_id(), true );
			if ( ! $userModel ) {
				throw new Exception( __( 'Invalid user', 'learnpress' ) );
			}

			$userCourseModel = UserCourseModel::find( $userModel->get_id(), $courseModel->get_id(), true );
			if ( ! $userCourseModel ) {
				throw new Exception( __( 'Invalid user course', 'learnpress' ) );
			}

			$userCourseModel->handle_retake();
			$item_continue = $userCourseModel->get_item_continue();
			if ( $item_continue ) {
				$link_continue = $courseModel->get_item_link( $item_continue->ID );
			} else {
				$link_continue = $courseModel->get_permalink();
			}

			$response->status             = 'success';
			$response->message            = esc_html__( 'Now you can begin this course', 'learnpress' );
			$response->data->url_redirect = $link_continue;
		} catch ( Exception $e ) {
			$response->message = $e->getMessage();
		}

		return $response;
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
	 * @version 1.0.4
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

			$user   = UserModel::find( $user_id, true );
			$course = CourseModel::find( $course_id, true );
			if ( ! $course ) {
				throw new Exception( __( 'Invalid course', 'learnpress' ) );
			}

			if ( ! $user ) {
				return $response;
			}

			$userCourseModel = UserCourseModel::find( $user_id, $course_id, true );
			if ( ! $userCourseModel ) {
				throw new Exception( __( 'Invalid user course', 'learnpress' ) );
			}

			$sections_items = $course->get_section_items();
			$total_items    = $course->get_total_items();

			if ( ! empty( $total_items ) ) {
				foreach ( $sections_items as $section_items ) {
					if ( $flag_found ) {
						break;
					}

					foreach ( $section_items->items as $item ) {
						$item_id   = $item->id ?? $item->item_id;
						$item_type = $item->type ?? $item->item_type;

						$userItemModel = UserItemModel::find_user_item(
							$user_id,
							$item_id,
							$item_type,
							$course_id,
							LP_COURSE_CPT,
							true
						);
						if ( ! $userItemModel || $userItemModel->get_status() !== LP_ITEM_COMPLETED ) {
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
