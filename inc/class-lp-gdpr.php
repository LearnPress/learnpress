<?php

/**
 * Class LP_GDPR
 *
 * Personal data export and removal for LearnPress
 *
 * @since 4.9.6
 */
class LP_GDPR {

	/**
	 * LP_GDPR constructor.
	 */
	public function __construct() {
		// Filter to wp privacy personal data exporter
		add_filter(
			'wp_privacy_personal_data_exporters',
			array( $this, 'register_exporter' ),
			10
		);

		add_filter(
			'wp_privacy_personal_data_erasers',
			array( $this, 'register_data_eraser' )
		);
	}

	public function register_data_eraser( $erasers ) {
		$erasers['learnpress'] = array(
			'eraser_friendly_name' => __( 'LearnPress' ),
			'callback'             => array( $this, 'personal_data_eraser' ),
		);

		return $erasers;
	}

	/**
	 * @param array $exporters
	 *
	 * @return mixed
	 */
	public function register_exporter( $exporters ) {

		/**
		 * Owned courses
		 */
		$exporters['learnpress-owned-courses'] = array(
			'exporter_friendly_name' => __( 'LearnPress Owned Courses', 'learnpress' ),
			'callback'               => array( $this, 'user_owned_courses' ),
		);

		/**
		 * Orders
		 */
		$exporters['learnpress-orders'] = array(
			'exporter_friendly_name' => __( 'LearnPress Orders', 'learnpress' ),
			'callback'               => array( $this, 'user_orders' ),
		);

		/**
		 * Purchased courses
		 */
		$exporters['learnpress-purchased-courses'] = array(
			'exporter_friendly_name' => __( 'LearnPress Purchased Courses', 'learnpress' ),
			'callback'               => array( $this, 'user_purchased_courses' ),
		);

		/**
		 * Profile
		 */
		$exporters['learnpress-profile'] = array(
			'exporter_friendly_name' => __( 'LearnPress User Profile', 'learnpress' ),
			'callback'               => array( $this, 'user_profile' ),
		);

		return $exporters;
	}

	public function user_profile( $email_address, $page ) {
		$export_data = array(
			'data' => array(),
			'done' => true,
		);

		$wp_user = get_user_by( 'email', $email_address );

		if ( ! $wp_user ) {
			return $export_data;
		}

		$user         = learn_press_get_user( $wp_user->ID );
		$profile      = LP_Profile::instance( $wp_user->ID );
		$export_items = array();

		$user = $profile->get_user();

		if ( $user ) {
			$privacy = $user->get_data( 'profile_privacy' );
			if ( $privacy ) {
				$privacy = $user->get_data( 'profile_privacy' );

				$export_item = array(
					'group_id'    => 'lp-profile',
					'group_label' => __( 'Profile Settings', 'learnpress' ),
					'item_id'     => 'profile-' . $wp_user->ID,
					'data'        => array(),
				);
				foreach ( $privacy as $key => $item ) {
					$export_item['data'][] = array(
						'name'  => $key,
						'value' => $item,
					);
				}
				$export_items[] = $export_item;
			}
		}

		$export_data['data'] = $export_items;

		return $export_data;
	}

	public function user_orders( $email_address, $page ) {
		$number      = 10; // Limit us to avoid timing out
		$page        = (int) $page;
		$export_data = array(
			'data' => array(),
			'done' => true,
		);

		$wp_user = get_user_by( 'email', $email_address );

		if ( ! $wp_user ) {
			return $export_data;
		}

		$profile = LP_Profile::instance( $wp_user->ID );

		$query_orders = $profile->query_orders(
			array(
				'paged' => $page,
				'limit' => $number,
			)
		);

		if ( ! $query_orders ) {
			return $export_data;
		}

		$orders = $query_orders->get_items();

		if ( ! $orders ) {
			return $export_data;
		}

		$export_items = array();
		foreach ( $orders as $order ) {
			if ( ! $order instanceof LP_Order ) {
				$order = learn_press_get_order( $order );
			}

			$data           = array(
				array(
					'name'  => __( 'Order ID', 'learnpress' ),
					'value' => $order->get_order_number(),
				),
				array(
					'name'  => __( 'Order Date', 'learnpress' ),
					'value' => $order->get_order_date(),
				),
				array(
					'name'  => __( 'Order status', 'learnpress' ),
					'value' => $order->get_order_status(),
				),
				array(
					'name'  => __( 'Order Total', 'learnpress' ),
					'value' => $order->get_formatted_order_total(),
				),
			);
			$export_items[] = array(
				'group_id'    => 'lp-order',
				'group_label' => __( 'Orders', 'learnpress' ),
				'item_id'     => "order-{$order->get_id()}",
				'data'        => $data,
			);
		}

		$done                = count( $orders ) < $number;
		$export_data['done'] = $done;
		$export_data['data'] = $export_items;

		return $export_data;
	}

	/**
	 * Course list
	 *
	 * @param string $email_address
	 * @param int    $page
	 *
	 * @return array
	 */
	public function user_owned_courses( $email_address, $page ) {

		$number       = 10; // Limit us to avoid timing out
		$page         = (int) $page;
		$export_items = array();

		$query = $this->get_courses_by_email(
			'own',
			$email_address,
			array(
				'paged'  => $page,
				'limit'  => $number,
				'status' => '*',
			)
		);

		if ( ! $query ) {
			return array(
				'data' => array(),
				'done' => true,
			);
		}

		$courses = (array) $query->get_items();

		if ( $courses ) {
			foreach ( $courses as $course_id ) {
				$course = learn_press_get_course( $course_id );

				if ( ! $course ) {
					continue;
				}

				$data           = array(
					array(
						'name'  => __( 'Course Author', 'learnpress' ),
						'value' => $course->get_author_display_name(),
					),
					array(
						'name'  => __( 'Course Name', 'learnpress' ),
						'value' => $course->get_title(),
					),
					array(
						'name'  => __( 'Course Date', 'learnpress' ),
						'value' => get_post_field( 'post_date', $course_id ),
					),
					array(
						'name'  => __( 'Course URL', 'learnpress' ),
						'value' => $course->get_permalink(),
					),
				);
				$export_items[] = array(
					'group_id'    => 'lp-owned-course',
					'group_label' => __( 'Owned Course', 'learnpress' ),
					'item_id'     => "course-{$course->get_id()}",
					'data'        => $data,
				);

				$this->_export_course_items( $export_items, $course_id );
			}
		}
		$done = count( $courses ) < $number;

		return array(
			'data' => $export_items,
			'done' => $done,
		);
	}

	/**
	 * @param array $export_items
	 */
	protected function _export_course_items( &$export_items, $course_id ) {
		global $post;

		$post = get_post( $course_id );
		setup_postdata( $post );
		$course = learn_press_get_course( $course_id );

		$items = $course->get_items();

		if ( ! $items ) {
			return;
		}

		foreach ( $items as $item_id ) {
			$item             = $course->get_item( $item_id );
			$export_item_data = array(
				array(
					'name'  => __( 'Item Name', 'learnpress' ),
					'value' => $item->get_title(),
				),
				array(
					'name'  => __( 'Item Type', 'learnpress' ),
					'value' => $item->get_item_type( 'display' ),
				),
				array(
					'name'  => __( 'Item URL', 'learnpress' ),
					'value' => $item->get_permalink(),
				),
			);

			$export_items[] = array(
				'group_id'    => 'lp-owned-course-items-' . $course_id,
				'group_label' => __( 'Course Items', 'learnpress' ),
				'item_id'     => "course-items-{$course_id}-{$item_id}",
				'data'        => $export_item_data,
			);
		}

		wp_reset_postdata();
	}

	/**
	 * @param string $email_address
	 * @param string $page
	 *
	 * @return array
	 */
	public function user_purchased_courses( $email_address, $page ) {

		$number      = 10; // Limit us to avoid timing out
		$page        = (int) $page;
		$export_data = array(
			'data' => array(),
			'done' => true,
		);

		$wp_user = get_user_by( 'email', $email_address );

		if ( ! $wp_user ) {
			return $export_data;
		}

		$user  = learn_press_get_user( $wp_user->ID );
		$query = $this->get_courses_by_email(
			'purchased',
			$email_address,
			array(
				'paged' => $page,
				'limit' => $number,
			)
		);

		if ( ! $query ) {
			return $export_data;
		}

		$export_items = array();
		$courses      = (array) $query->get_items();

		if ( $courses ) {
			foreach ( $courses as $course_data ) {

				$course = learn_press_get_course( $course_data->get_id() );
				// if ( $course_data = $user->get_course_data( $course_id ) ) {
				$enrolled_date = $course_data->get_start_time();
				$finished_date = $course_data->get_end_time();
				$status        = $course_data->get_status();
				$grade         = $course_data->get_grade();
				// }

				$data           = array(
					array(
						'name'  => __( 'Course Author', 'learnpress' ),
						'value' => $course->get_author_display_name(),
					),
					array(
						'name'  => __( 'Course Name', 'learnpress' ),
						'value' => $course->get_title(),
					),
					array(
						'name'  => __( 'Course Date', 'learnpress' ),
						'value' => get_post_field( 'post_date', $course_data->get_id() ),
					),
					array(
						'name'  => __( 'Course URL', 'learnpress' ),
						'value' => $course->get_permalink(),
					),
					array(
						'name'  => __( 'Enrolled Date', 'learnpress' ),
						'value' => $enrolled_date,
					),
					array(
						'name'  => __( 'Finished Date', 'learnpress' ),
						'value' => $finished_date ? $finished_date : '-',
					),
					array(
						'name'  => __( 'Course Status', 'learnpress' ),
						'value' => $status,
					),
					array(
						'name'  => __( 'Course Grade', 'learnpress' ),
						'value' => $status == 'finished' ? $grade : __( 'Ungraded', 'learnpress' ),
					),
				);
				$export_items[] = array(
					'group_id'    => 'lp-purchased-course-' . $course_data->get_id(),
					'group_label' => __( 'Purchased Course', 'learnpress' ),
					'item_id'     => "course-{$course_data->get_id()}",
					'data'        => $data,
				);

				$this->_export_purchased_course_items( $export_items, $course_data );
			}
		}

		$done                = count( $courses ) < $number;
		$export_data['data'] = $export_items;
		$export_data['done'] = $done;

		return $export_data;
	}

	/**
	 * @param array               $export_items
	 * @param LP_User_Item_Course $course_data
	 */
	protected function _export_purchased_course_items( &$export_items, $course_data ) {
		global $post;
		$post = get_post( $course_data->get_id() );
		setup_postdata( $post );
		$course = learn_press_get_course( $course_data->get_id() );

		$items = $course_data->get_items();

		if ( ! $items ) {
			return;
		}

		foreach ( $items as $user_course_item ) {
			$item             = $course->get_item( $user_course_item->get_id() );
			$export_item_data = array(
				array(
					'name'  => __( 'Item Name', 'learnpress' ),
					'value' => $item->get_title(),
				),
				array(
					'name'  => __( 'Item Type', 'learnpress' ),
					'value' => $item->get_item_type( 'display' ),
				),
				array(
					'name'  => __( 'Item URL', 'learnpress' ),
					'value' => $item->get_permalink(),
				),
			);

			if ( $item->get_item_type() == LP_QUIZ_CPT ) {
				$export_item_data[] = array(
					'name'  => __( 'Status', 'learnpress' ),
					'value' => $user_course_item->get_result() . '%',
				);

				$export_item_data[] = array(
					'name'  => __( 'Grade', 'learnpress' ),
					'value' => $user_course_item->get_graduation(),
				);
			} elseif ( $item->get_item_type() == LP_LESSON_CPT ) {
				$export_item_data[] = array(
					'name'  => __( 'Completed', 'learnpress' ),
					'value' => $user_course_item->get_status() === 'completed' ? __( 'Yes', 'learnpress' ) : __(
						'No',
						'learnpress'
					),
				);
			}

			$export_items[] = array(
				'group_id'    => 'lp-purchased-course-items-' . $course_data->get_id(),
				'group_label' => __( 'Course Items', 'learnpress' ),
				'item_id'     => "course-{$course_data->get_id()}-{$user_course_item->get_id()}",
				'data'        => $export_item_data,
			);
		}

		wp_reset_postdata();
	}

	/**
	 * Query all courses by user email
	 *
	 * @param string $type
	 * @param string $email
	 * @param array  $args
	 *
	 * @return array|bool|LP_Query_List_Table
	 */
	protected function get_courses_by_email( $type = 'own', $email = '', $args = array() ) {
		$user = get_user_by( 'email', $email );

		if ( ! $user ) {
			return false;
		}

		$profile = LP_Profile::instance( $user->ID );

		return $profile->query_courses(
			$type,
			$args
		);
	}

	/**
	 * Eraser personal data
	 *
	 * @param string $email
	 * @param int    $page
	 *
	 * @return array
	 */
	public function personal_data_eraser( $email, $page ) {
		$number = 500;
		$page   = (int) $page;

		$eraser_data = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => 1,
		);

		$wp_user = get_user_by( 'email', $email );

		if ( ! $wp_user ) {
			return $eraser_data;
		}

		$this->_eraser_orders( $wp_user->ID, $page );
		$this->_eraser_user_items( $wp_user->ID, $page );
		$this->_eraser_courses( $wp_user->ID, $page );

		delete_user_meta( $wp_user->ID, '_lp_profile_privacy' );
		$eraser_data['items_removed'] = true;

		return $eraser_data;
	}

	/**
	 * Eraser orders
	 *
	 * @param int $user_id
	 * @param int $page
	 */
	protected function _eraser_orders( $user_id, $page ) {
		$curd       = new LP_User_CURD();
		$order_curd = new LP_Order_CURD();

		$orders = $curd->get_orders( $user_id, array( 'group_by_order' => true ) );

		if ( ! $orders ) {
			return;
		}

		foreach ( $orders as $order_id => $course_ids ) {
			$order = learn_press_get_order( $order_id );
			wp_delete_post( $order_id );
		}

	}

	/**
	 * Eraser user items
	 *
	 * @param int $user_id
	 * @param int $page
	 */
	protected function _eraser_user_items( $user_id, $page ) {
		$curd = new LP_User_CURD();
		$curd->delete_user_item( array( 'user_id' => $user_id ) );
	}

	/**
	 * Eraser courses
	 *
	 * @param int $user_id
	 * @param int $page
	 */
	protected function _eraser_courses( $user_id, $page ) {
		global $wpdb;

		$query = $wpdb->prepare(
			"
			SELECT *
			FROM {$wpdb->posts}
			WHERE post_author = %d
			AND post_type = %s
		",
			$user_id,
			LP_COURSE_CPT
		);

		$post_ids = $wpdb->get_col( $query );

		if ( ! $post_ids ) {
			return;
		}

		$api = new LP_Course_CURD();
		foreach ( $post_ids as $post_id ) {
			$api->delete_course( $post_id, true );
		}
	}

}

return new LP_GDPR();
