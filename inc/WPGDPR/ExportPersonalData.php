<?php
namespace LearnPress\WPGDPR;

use Exception;
use LearnPress\Helpers\Singleton;
use LearnPress\Models\UserItems\UserCourseModel;
use LearnPress\Models\CourseModel;
use LearnPress\Models\UserModel;
use LP_Post_DB;
use LP_Order_Filter;
use LP_User_Items_Filter;
use LP_User_Items_DB;
use LP_Datetime;
use LP_Course_DB;
use LP_Course_Filter;

/**
 * Class ExportPersonalData
 *
 * @since 4.2.9.4
 * @version 1.0.0
 */
class ExportPersonalData {

	use Singleton;

	public function init() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporters' ), 6 );
	}

	public function register_exporters( $exporters ) {
		$exporters['learnpress-user-meta']        = array(
			'exporter_friendly_name' => __( 'LearnPress User Meta Data Exporter', 'learnpress' ),
			'callback'               => array( $this, 'export_user_meta' ),
		);
		$exporters['learnpress-created-courses']  = array(
			'exporter_friendly_name' => __( 'LearnPress Created Courses Data Exporter', 'learnpress' ),
			'callback'               => array( $this, 'export_user_created_courses' ),
		);
		$exporters['learnpress-attended-courses'] = array(
			'exporter_friendly_name' => __( 'LearnPress Attended Courses Data Exporter', 'learnpress' ),
			'callback'               => array( $this, 'export_user_attended_courses' ),
		);
		$exporters['learnpress-orders']           = array(
			'exporter_friendly_name' => __( 'LearnPress Orders Data Exporter', 'learnpress' ),
			'callback'               => array( $this, 'export_user_orders' ),
		);
		return $exporters;
	}

	public function export_user_meta( $email, $page = 1 ) {
		$export_items = array();

		$user = get_user_by( 'email', $email );
		if ( $user ) {
			$user_id   = $user->ID;
			$data      = array();
			$userModel = UserModel::find( $user_id, true );

			if ( $userModel->is_instructor() ) {
				$instructor_statistic = $userModel->get_instructor_statistic();
				$data                 = $this->map_instructor_statistics( $instructor_statistic, $data );
			}
			$student_statistic = $userModel->get_student_statistic();
			$data              = $this->map_student_statistics( $student_statistic, $data );

			$social_fields = learn_press_social_profiles();
			$socials       = learn_press_get_user_extra_profile_info( $user_id );
			foreach ( $socials as $key => $value ) {
				if ( ! empty( $value ) && isset( $social_fields[ $key ] ) ) {
					$data[] = array(
						'name'  => $social_fields[ $key ],
						'value' => $value,
					);
				}
			}
			$export_items[] = array(
				'group_id'          => 'learnpress-user-meta',
				'group_label'       => __( 'LearnPress Profile Info', 'learnpress' ),
				'group_description' => __( 'User&#8217;s LearnPress User Profile Data.', 'learnpress' ),
				'item_id'           => 'user_id-' . $user_id,
				'data'              => $data,
			);
		}

		return array(
			'data' => $export_items,
			'done' => true,
		);
	}

	/**
	 * Map instructor statistics to exportable data
	 *
	 * @param array $statistic
	 * @param array $data
	 *
	 * @return array
	 */
	public function map_instructor_statistics( array $statistic, array $data ): array {
		foreach ( $statistic as $key => $value ) {
			switch ( $key ) {
				case 'total_course':
					$data[] = array(
						'name'  => __( 'Total Course', 'learnpress' ),
						'value' => $value,
					);
					break;
				case 'published_course':
					$data[] = array(
						'name'  => __( 'Published Course', 'learnpress' ),
						'value' => $value,
					);
					break;
				case 'pending_course':
					$data[] = array(
						'name'  => __( 'Pending Course', 'learnpress' ),
						'value' => $value,
					);
					break;
				case 'total_student':
					$data[] = array(
						'name'  => __( 'Total Student', 'learnpress' ),
						'value' => $value,
					);
					break;
				case 'student_completed':
					$data[] = array(
						'name'  => __( 'Student Completed', 'learnpress' ),
						'value' => $value,
					);
					break;
				case 'student_in_progress':
					$data[] = array(
						'name'  => __( 'Student In-progress', 'learnpress' ),
						'value' => $value,
					);
					break;
			}
		}
		return $data;
	}

	/**
	 * Map student statistics to exportable data
	 *
	 * @param array $statistic
	 * @param array $data
	 *
	 * @return array
	 */
	public function map_student_statistics( array $statistic, array $data ): array {
		foreach ( $statistic as $key => $value ) {
			if ( $value ) {
				switch ( $key ) {
					case 'enrolled_courses':
						$data[] = array(
							'name'  => __( 'Enrolled Course', 'learnpress' ),
							'value' => $value,
						);
						break;
					case 'in_progress_course':
						$data[] = array(
							'name'  => __( 'Inprogress Course', 'learnpress' ),
							'value' => $value,
						);
						break;
					case 'finished_courses':
						$data[] = array(
							'name'  => __( 'Finished Course', 'learnpress' ),
							'value' => $value,
						);
						break;
					case 'passed_courses':
						$data[] = array(
							'name'  => __( 'Passed Course', 'learnpress' ),
							'value' => $value,
						);
						break;
					case 'failed_courses':
						$data[] = array(
							'name'  => __( 'Failed Course', 'learnpress' ),
							'value' => $value,
						);
						break;
				}
			}
		}

		return $data;
	}

	/**
	 * Export user attended courses data
	 *
	 * @param string $email
	 * @param int $page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function export_user_attended_courses( $email, $page = 1 ) {
		$export_items = array();
		$done         = true;
		$user         = get_user_by( 'email', $email );
		if ( $user ) {
			$user_id = $user->ID;
			$lpuidb  = LP_User_Items_DB::getInstance();

			$ui_filter              = new LP_User_Items_Filter();
			$ui_filter->user_id     = $user_id;
			$ui_filter->item_type   = LP_COURSE_CPT;
			$ui_filter->only_fields = array( 'DISTINCT (item_id) AS item_id', 'ui.user_item_id' );
			$ui_filter->field_count = 'ui.item_id';
			$ui_filter->limit       = 10;
			$ui_filter->page        = $page ?? 1;
			$lp_ui_ids              = $lpuidb->get_user_items( $ui_filter );
			if ( ! empty( $lp_ui_ids ) ) {
				foreach ( $lp_ui_ids as $useritem ) {
					$export_items[] = array(
						'group_id'          => 'learnpress-attended-courses',
						'group_label'       => __( 'LearnPress Attended Courses', 'learnpress' ),
						'group_description' => __( 'User&#8217;s LearnPress Attended Courses Data.', 'learnpress' ),
						'item_id'           => 'user_item_id-' . $useritem->item_id,
						'data'              => $this->get_user_course_personal_data( $useritem, $user_id ),
					);
				}
			}
			$done = 10 > count( $lp_ui_ids );
		}

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Get user course personal data
	 *
	 * @param object $useritem
	 * @param int $user_id
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_user_course_personal_data( $useritem, $user_id ) {
		$data            = array();
		$userCourseModel = UserCourseModel::find( $user_id, $useritem->item_id );
		$props           = apply_filters(
			'learnpress/export-user-attended-courses-personal-data/props',
			array(
				'course_name' => __( 'Course name', 'learnpress' ),
				'start_time'  => __( 'Start time', 'learnpress' ),
				'end_time'    => __( 'End time', 'learnpress' ),
				'status'      => __( 'Status', 'learnpress' ),
				'result'      => __( 'Result', 'learnpress' ),
			),
			$userCourseModel
		);
		$date_format     = get_option( 'date_format' );
		foreach ( $props as $prop => $name ) {
			$value = '';
			switch ( $prop ) {
				case 'course_name':
					$value = get_the_title( $useritem->item_id );
					break;
				case 'status':
					$value = $userCourseModel->is_finished() ? $userCourseModel->get_string_i18n( $userCourseModel->get_graduation() ) : $userCourseModel->get_string_i18n( $userCourseModel->get_status() );
					break;
				case 'start_time':
					$value = wp_date( $date_format, strtotime( $userCourseModel->start_time ) );
					break;
				case 'end_time':
					$value = ! empty( $userCourseModel->end_time ) ? wp_date( $date_format, strtotime( $userCourseModel->end_time ) ) : '-';
					break;
				case 'result':
					$result = $userCourseModel->calculate_course_results();
					$value  = ! empty( $result['result'] ) ? $result['result'] . '%' : '-';
					break;
			}

			$value = apply_filters( 'learnpress/export-user-attended-courses-personal-data-prop', $value, $prop, $userCourseModel );
			if ( $value ) {
				$data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		return $data;
	}

	/**
	 * Export user created courses data
	 *
	 * @param string $email
	 * @param int $page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function export_user_created_courses( $email, $page = 1 ) {
		$export_items = array();
		$done         = true;
		$user         = get_user_by( 'email', $email );
		$userModel    = $user ? UserModel::find( $user->ID, true ) : null;
		if ( $userModel && $userModel->is_instructor() ) {
			$user_id                 = $user->ID;
			$filter                  = new LP_Course_Filter();
			$course_db               = LP_Course_DB::getInstance();
			$filter->post_author     = $user_id;
			$filter->only_fields[]   = 'p.ID as ID';
			$filter->limit           = 10;
			$filter->page            = (int) $page;
			$filter->run_query_count = false;
			$course_ids              = $course_db->get_courses( $filter );
			if ( ! empty( $course_ids ) ) {
				foreach ( $course_ids as $c ) {
					$export_items[] = array(
						'group_id'          => 'learnpress-created-courses',
						'group_label'       => __( 'LearnPress Created Courses', 'learnpress' ),
						'group_description' => __( 'User&#8217;s LearnPress Created Courses Data.', 'learnpress' ),
						'item_id'           => 'course_id-' . $c->ID,
						'data'              => $this->get_user_created_courses_personal_data( $c->ID, $user_id ),
					);
				}
			}
			$done = 10 > count( $course_ids );
		}

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Get user created courses personal data
	 *
	 * @param int $course_id
	 * @param int $user_id
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_user_created_courses_personal_data( $course_id, $user_id ): array {
		$data        = array();
		$courseModel = CourseModel::find( $course_id, true );
		$props       = apply_filters(
			'learn-press/export-user-created-courses-personal-data/props',
			array(
				'title'         => __( 'Course name', 'learnpress' ),
				'price'         => __( 'Price', 'learnpress' ),
				'duration'      => __( 'Duration', 'learnpress' ),
				'level'         => __( 'Level', 'learnpress' ),
				'students'      => __( 'Total Students', 'learnpress' ),
				'lp_lesson'     => __( 'Lessons', 'learnpress' ),
				'lp_quiz'       => __( 'Quizzes', 'learnpress' ),
				'lp_assignment' => __( 'Assignments', 'learnpress' ),
				'lp_h5p'        => __( 'H5P Quizzes', 'learnpress' ),
			),
			$courseModel,
			$user_id
		);
		foreach ( $props as $prop => $name ) {
			$value = '';
			switch ( $prop ) {
				case 'lp_lesson':
				case 'lp_quiz':
				case 'lp_assignment':
				case 'lp_h5p':
					$value = $courseModel->count_items( $prop );
					break;
				case 'students':
					$value = $courseModel->count_students();
					break;
				case 'duration':
					$duration        = $courseModel->get_meta_value_by_key( '_lp_duration', '' );
					$duration_arr    = explode( ' ', $duration );
					$duration_number = floatval( $duration_arr[0] ?? 0 );
					$duration_type   = $duration_arr[1] ?? '';
					if ( empty( $duration_number ) ) {
						$value = __( 'Lifetime', 'learnpress' );
					} else {
						$value = LP_Datetime::get_string_plural_duration( $duration_number, $duration_type );
					}
					break;
				case 'level':
					$level  = $courseModel->get_meta_value_by_key( '_lp_level', '' );
					$levels = lp_course_level();
					$value  = $levels[ $level ] ?? $levels['all'];
					break;
				case 'price':
					$value = $courseModel->is_free() ? __( 'Free', 'learnpress' ) : learn_press_format_price( $courseModel->get_price() );
					break;
				default:
					if ( is_callable( array( $courseModel, 'get_' . $prop ) ) ) {
						$value = $courseModel->{"get_$prop"}();
					}
					break;
			}
			$value = apply_filters( 'learnpress/export-user-created-courses-personal-data/prop', $value, $prop, $courseModel, $user_id );
			if ( $value ) {
				$data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		return $data;
	}

	/**
	 * Export user orders data
	 *
	 * @param string $email
	 * @param int $page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function export_user_orders( $email, $page = 1 ) {
		$export_items = array();
		$done         = true;
		$user         = get_user_by( 'email', $email );
		if ( $user ) {
			$order_data   = $this->export_order_via_user( $user, $page );
			$export_items = $order_data['data'];
			$done         = $order_data['done'];
		} else {
			$order_data   = $this->export_order_via_email( $email, $page );
			$export_items = $order_data['data'];
			$done         = $order_data['done'];
		}
		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * Export order via user ID
	 *
	 * @param \WP_User $user
	 * @param int $page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function export_order_via_user( $user, $page = 1 ) {
		$user_id                 = $user->ID;
		$data_to_export          = array();
		$done                    = true;
		$lp_postdb               = LP_Post_DB::getInstance();
		$filter                  = new LP_Order_Filter();
		$filter->only_fields[]   = 'p.ID as ID';
		$filter->join[]          = "INNER JOIN $lp_postdb->tb_postmeta AS pm ON p.ID = pm.post_id";
		$filter->where           = array(
			$lp_postdb->wpdb->prepare(
				'AND pm.meta_key=%s AND ( pm.meta_value=%s OR pm.meta_value LIKE %s)',
				'_user_id',
				$user_id,
				'%' . $lp_postdb->wpdb->esc_like( '"' . $user_id . '"' ) . '%'
			),
		);
		$filter->limit           = 10;
		$filter->page            = $page ?? 1;
		$filter->run_query_count = false;
		$lp_order_ids            = $lp_postdb->get_posts( $filter );
		if ( ! empty( $lp_order_ids ) ) {
			$data_to_export = $this->process_order_data( $lp_order_ids );
		}
		$done = 10 > count( $lp_order_ids );

		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Export order via email
	 *
	 * @param string $email
	 * @param int $page
	 *
	 * @return array
	 * @throws Exception
	 */
	public function export_order_via_email( $email, $page = 1 ) {
		$data_to_export          = array();
		$done                    = true;
		$lp_postdb               = LP_Post_DB::getInstance();
		$filter                  = new LP_Order_Filter();
		$filter->only_fields[]   = 'p.ID as ID';
		$filter->join[]          = "INNER JOIN $lp_postdb->tb_postmeta AS pm ON p.ID = pm.post_id";
		$filter->where           = array(
			$lp_postdb->wpdb->prepare(
				'AND pm.meta_key=%s AND pm.meta_value=%s',
				'_checkout_email',
				$email
			),
		);
		$filter->limit           = 10;
		$filter->page            = $page ?? 1;
		$filter->run_query_count = false;
		$lp_order_ids            = $lp_postdb->get_posts( $filter );
		if ( ! empty( $lp_order_ids ) ) {
			$data_to_export = $this->process_order_data( $lp_order_ids );
		}
		$done = 10 > count( $lp_order_ids );
		return array(
			'data' => $data_to_export,
			'done' => $done,
		);
	}

	/**
	 * Process order data to export
	 *
	 * @param array $lp_order_ids
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function process_order_data( $lp_order_ids ): array {
		$data_to_export = array();
		foreach ( $lp_order_ids as $order ) {
			$data_to_export[] = array(
				'group_id'          => 'learnpress-orders',
				'group_label'       => __( 'LearnPress Orders', 'learnpress' ),
				'group_description' => __( 'User&#8217;s LearnPress orders data.', 'learnpress' ),
				'item_id'           => 'order-' . $order->ID,
				'data'              => $this->get_order_personal_data( $order->ID ),
			);
		}
		return $data_to_export;
	}

	/**
	 * Get order personal data
	 *
	 * @param int $order_id
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function get_order_personal_data( $order_id ): array {
		$order = learn_press_get_order( $order_id );
		$data  = array();
		$props = apply_filters(
			'learnpress/export-order-personal-data/props',
			array(
				'order_number'    => __( 'Order Number', 'learnpress' ),
				'order_date'      => __( 'Order Date', 'learnpress' ),
				'total'           => __( 'Order Total', 'learnpress' ),
				'items'           => __( 'Items Purchased', 'learnpress' ),
				'status'          => __( 'Status', 'learnpress' ),
				'payment_method'  => __( 'Payment method', 'learnpress' ),
				'user_ip_address' => __( 'IP Address', 'learnpress' ),
				'user_agent'      => __( 'Browser User Agent', 'learnpress' ),
			),
			$order
		);
		foreach ( $props as $prop => $name ) {
			$value = '';
			switch ( $prop ) {
				case 'items':
					$item_names = array();
					if ( ! empty( $order->get_items() ) ) {
						foreach ( $order->get_items() as $item ) {
							$item_names[] = $item['name'] . ' x ' . $item['quantity'];
						}
					}
					$value = implode( ', ', $item_names );
					break;
				case 'status':
					$value = $order::get_status_label( $order->get_status() );
					break;
				case 'total':
					$value = $order->get_formatted_order_total();
					break;
				default:
					if ( is_callable( array( $order, 'get_' . $prop ) ) ) {
						$value = $order->{"get_$prop"}();
					}
					break;
			}

			$value = apply_filters( 'learnpress/export-order-personal-data-prop', $value, $prop, $order );
			if ( $value ) {
				$data[] = array(
					'name'  => $name,
					'value' => $value,
				);
			}
		}

		return $data;
	}
}
