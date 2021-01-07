<?php

/**
 * Class LP_Submenu_Statistics
 *
 * @since 3.0.0
 */
class LP_Submenu_Statistics extends LP_Abstract_Submenu {

	/**
	 * LP_Submenu_Statistics constructor.
	 */
	public function __construct() {
		$this->id         = 'learn-press-statistics';
		$this->menu_title = __( 'Statistics', 'learnpress' );
		$this->page_title = __( 'LearnPress Statistics', 'learnpress' );
		$this->priority   = 10;

		if ( current_user_can( LP_TEACHER_ROLE ) ) {
			$tabs = array(
				'courses' => __( 'Courses', 'learnpress' ),
				'orders'  => __( 'Orders', 'learnpress' ),
			);
		} else {
			$tabs = array(
				'general' => __( 'General', 'learnpress' ),
				'users'   => __( 'Users', 'learnpress' ),
				'courses' => __( 'Courses', 'learnpress' ),
				'orders'  => __( 'Orders', 'learnpress' ),
			);
		}

		$this->tabs = apply_filters(
			'learn-press/admin/page-statistic-tabs',
			$tabs
		);

		parent::__construct();
	}

	public function load_chart() {
		$type     = learn_press_get_request( 'type' );
		$response = null;
		switch ( $type ) {
			case 'user-last-7-days':
				$response = learn_press_get_chart_users( null, 'days', 7 );
				break;
			case 'user-last-30-days':
				$response = learn_press_get_chart_users( null, 'days', 30 );
				break;
			case 'user-last-12-months':
				$response = learn_press_get_chart_users( null, 'months', 12 );
				break;
			case 'user-custom-time':
				$range     = learn_press_get_request( 'range' );
				$from_time = strtotime( $range[0] );
				$to_time   = strtotime( $range[1] );
				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$response = learn_press_get_chart_users( $to_time, 'years', $to_y - $from_y + 1 );
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_users( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_users( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'user-all':
				global $wpdb;
				$results = $wpdb->get_row( "
					SELECT min(u.user_registered) as `from`, max(u.user_registered) as `to`
					FROM {$wpdb->users} u
				" );

				if ( $results ) {
					$_POST['range'] = array(
						date( 'Y/m/d', strtotime( $results->from ) ),
						date( 'Y/m/d', strtotime( $results->to ) )
					);
					$_POST['type']  = 'user-custom-time';
					$this->load_chart();

					return;
				}
				break;
			//////////////////
			case 'course-last-7-days':
				$response = learn_press_get_chart_courses( null, 'days', 7 );
				break;
			case 'course-last-30-days':
				$response = learn_press_get_chart_courses( null, 'days', 30 );
				break;
			case 'course-last-12-months':
				$response = learn_press_get_chart_courses( null, 'months', 12 );
				break;
			case 'course-custom-time':
				$range = learn_press_get_request( 'range' );

				$from_time = date( 'd m Y' );
				$to_time   = date( 'd m Y' );

				if ( ! empty( $range ) && is_array( $range ) ) {
					$from_time = strtotime( $range[0] );
					$to_time   = strtotime( $range[1] );
				}

				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$months = abs( ( date( 'Y', $to_time ) - date( 'Y', $from_time ) ) * 12 + ( date( 'm', $to_time ) - date( 'm', $from_time ) ) ) + 1;
					if ( $months > 12 ) {
						$response = learn_press_get_chart_courses( $to_time, 'years', $to_y - $from_y + 1 );
					} else {
						$response = learn_press_get_chart_courses( $to_time, 'months', $months );
					}
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_courses( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_courses( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'course-all':
				global $wpdb;
				$results = $wpdb->get_row(
					$wpdb->prepare( "
						SELECT min(c.post_date) as `from`, max(c.post_date) as `to`
						FROM {$wpdb->posts} c
						WHERE c.post_date <> %s
						AND c.post_type = %s
					", '0000-00-00 00:00:00', 'lp_course' )
				);

				if ( $results ) {
					$_POST['range'] = array(
						date( 'Y/m/d', strtotime( $results->from ) ),
						date( 'Y/m/d', strtotime( $results->to ) )
					);
					$_POST['type']  = 'course-custom-time';
					$this->load_chart();

					return;
				}
				break;

			//////////////////
			case 'order-last-7-days':
				$response = learn_press_get_chart_orders( null, 'days', 7 );
				break;
			case 'order-last-30-days':
				$response = learn_press_get_chart_orders( null, 'days', 30 );
				break;
			case 'order-last-12-months':
				$response = learn_press_get_chart_orders( null, 'months', 12 );
				break;
			case 'order-custom-time':
				$range     = learn_press_get_request( 'range' );
				$from_time = strtotime( $range[0] );
				$to_time   = strtotime( $range[1] );
				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$months = abs( ( date( 'Y', $to_time ) - date( 'Y', $from_time ) ) * 12 + ( date( 'm', $to_time ) - date( 'm', $from_time ) ) ) + 1;
					if ( $months > 12 ) {
						$response = learn_press_get_chart_orders( $to_time, 'years', $to_y - $from_y + 1 );
					} else {
						$response = learn_press_get_chart_orders( $to_time, 'months', $months );
					}
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_orders( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_orders( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'order-all':
				global $wpdb;
				$results = $wpdb->get_row(
					$wpdb->prepare( "
						SELECT min(c.post_date) as `from`, max(c.post_date) as `to`
						FROM {$wpdb->posts} c
						WHERE c.post_date <> %s
						AND c.post_type = %s
					", '0000-00-00 00:00:00', 'lp_order' )
				);
				if ( $results ) {
					$_POST['range'] = array(
						date( 'Y/m/d', strtotime( $results->from ) ),
						date( 'Y/m/d', strtotime( $results->to ) )
					);
					$_POST['type']  = 'order-custom-time';
					$this->load_chart();

					return;
				}
				break;
			//////////////////
			case 'general-last-7-days':
				$response = learn_press_get_chart_general( null, 'days', 7 );
				break;
			case 'general-last-30-days':
				$response = learn_press_get_chart_general( null, 'days', 30 );
				break;
			case 'general-last-12-months':
				$response = learn_press_get_chart_general( null, 'months', 12 );
				break;
			case 'general-custom-time':
				$range     = learn_press_get_request( 'range' );
				$from_time = strtotime( $range[0] );
				$to_time   = strtotime( $range[1] );
				list( $from_d, $from_m, $from_y ) = explode( ' ', date( 'd m Y', $from_time ) );
				list( $to_d, $to_m, $to_y ) = explode( ' ', date( 'd m Y', $to_time ) );
				if ( $from_y != $to_y ) {
					$months = abs( ( date( 'Y', $to_time ) - date( 'Y', $from_time ) ) * 12 + ( date( 'm', $to_time ) - date( 'm', $from_time ) ) ) + 1;
					if ( $months > 12 ) {
						$response = learn_press_get_chart_general( $to_time, 'years', $to_y - $from_y + 1 );
					} else {
						$response = learn_press_get_chart_general( $to_time, 'months', $months );
					}
				} else {
					if ( $from_m != $to_m ) {
						$response = learn_press_get_chart_general( $to_time, 'months', $to_m - $from_m + 1 );
					} else {
						$response = learn_press_get_chart_general( $to_time, 'days', $to_d - $from_d + 1 );
					}
				}
				break;
			case 'general-all':
				global $wpdb;
				$results = $wpdb->get_row(
					$wpdb->prepare( "
						SELECT min(c.user_registered) as `from`, NOW() as `to`
						FROM {$wpdb->users} c
						WHERE c.user_registered <> %s 				
					", '0000-00-00 00:00:00' )
				);

				if ( $results ) {
					$_POST['range'] = array(
						date( 'Y/m/d', strtotime( $results->from ) ),
						date( 'Y/m/d', strtotime( $results->to ) )
					);
					$_POST['type']  = 'general-custom-time';
					$this->load_chart();

					return;
				}
		}
		learn_press_send_json( $response );
	}

	public function page_content_courses() {
		learn_press_admin_view( 'statistics/courses' );
	}

	public function page_content_general() {
		learn_press_admin_view( 'statistics/general' );
	}

	public function page_content_users() {
		learn_press_admin_view( 'statistics/users' );
	}

	public function page_content_orders() {
		learn_press_admin_view( 'statistics/orders' );
	}


}

return new LP_Submenu_Statistics();