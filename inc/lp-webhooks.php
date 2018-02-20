<?php
/**
 * Common functions to process the webhooks
 *
 * @author  ThimPress
 * @package LearnPress/Functions
 * @version 1.0
 */

defined( 'ABSPATH' ) || exit();

/**
 * Register a webhook
 *
 * @param $key
 * @param $param
 */
function learn_press_register_web_hook( $key, $param ) {
<<<<<<< HEAD
<<<<<<< HEAD
	if ( ! $key ) {
=======
	if ( !$key ) {
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
	if ( ! $key ) {
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
		return;
	}
	if ( empty ( $GLOBALS['learn_press']['web_hooks'] ) ) {
		$GLOBALS['learn_press']['web_hooks'] = array();
	}
<<<<<<< HEAD
<<<<<<< HEAD
	$GLOBALS['learn_press']['web_hooks'][ $key ] = $param;
=======
	$GLOBALS['learn_press']['web_hooks'][$key] = $param;
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
	$GLOBALS['learn_press']['web_hooks'][ $key ] = $param;
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
	do_action( 'learn_press_register_web_hook', $key, $param );
}

/**
 * Return all registered webhooks
 *
 * @return mixed|void
 */
function learn_press_get_web_hooks() {
	$web_hooks = empty( $GLOBALS['learn_press']['web_hooks'] ) ? array() : (array) $GLOBALS['learn_press']['web_hooks'];
<<<<<<< HEAD
<<<<<<< HEAD

=======
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======

>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
	return apply_filters( 'learn_press_web_hooks', $web_hooks );
}

/**
 * Return a registered webhook by it's name
 *
 * @param $key
 *
 * @return mixed|void
 */
function learn_press_get_web_hook( $key ) {
	$web_hooks = learn_press_get_web_hooks();
<<<<<<< HEAD
<<<<<<< HEAD
	$web_hook  = empty( $web_hooks[ $key ] ) ? false : $web_hooks[ $key ];

=======
	$web_hook  = empty( $web_hooks[$key] ) ? false : $web_hooks[$key];
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
	$web_hook  = empty( $web_hooks[ $key ] ) ? false : $web_hooks[ $key ];

>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
	return apply_filters( 'learn_press_web_hook', $web_hook, $key );
}

/**
 * Process all registered webhooks
 */
function learn_press_process_web_hooks() {
	$web_hooks           = learn_press_get_web_hooks();
	$web_hooks_processed = array();
	foreach ( $web_hooks as $key => $param ) {
<<<<<<< HEAD
<<<<<<< HEAD
		if ( ! empty( $_REQUEST[ $param ] ) ) {
=======
		if ( !empty( $_REQUEST[$param] ) ) {
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
		if ( ! empty( $_REQUEST[ $param ] ) ) {
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
			//$web_hooks_processed           = true;
			$request_scheme                = is_ssl() ? 'https://' : 'http://';
			$requested_web_hook_url        = untrailingslashit( $request_scheme . $_SERVER['HTTP_HOST'] ) . $_SERVER['REQUEST_URI'];
			$parsed_requested_web_hook_url = parse_url( $requested_web_hook_url );
<<<<<<< HEAD
<<<<<<< HEAD
			$required_web_hook_url         = add_query_arg( $param, '1', trailingslashit( get_home_url() /* SITE_URL */ ) );
=======
			$required_web_hook_url         = add_query_arg( $param, '1', trailingslashit( get_site_url() ) );
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
			$required_web_hook_url         = add_query_arg( $param, '1', trailingslashit( get_home_url() /* SITE_URL */ ) );
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
			$parsed_required_web_hook_url  = parse_url( $required_web_hook_url );
			$web_hook_diff                 = array_diff_assoc( $parsed_requested_web_hook_url, $parsed_required_web_hook_url );

			if ( empty( $web_hook_diff ) ) {
				do_action( 'learn_press_web_hook_' . $param, $_REQUEST );
			} else {

			}
<<<<<<< HEAD
<<<<<<< HEAD
			$web_hooks_processed[ $param ] = $_REQUEST;
=======
			$web_hooks_processed[$param] = $_REQUEST;
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
			$web_hooks_processed[ $param ] = $_REQUEST;
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
			break;
		}
	}
	if ( $web_hooks_processed ) {
		do_action( 'learn_press_web_hooks_processed' );
		ob_start();
		foreach ( $web_hooks_processed as $k => $v ) {
			echo "\n===============================================================\n<br />";
			printf( __( 'LearnPress webhook %s process completed', 'learnpress' ), $k );
			echo "\n<pre>";
			print_r( $v );
			echo "</pre>\n===============================================================\n";
		}
		$output = ob_get_clean();
		wp_die( $output, __( 'LearnPress webhook process Complete', 'learnpress' ), array( 'response' => 200 ) );
	}
}

add_action( 'wp_loaded', 'learn_press_process_web_hooks', 999 );

/**
 * Update status of lesson when view at first time
 */
function learn_press_header_item_only_view_first() {
<<<<<<< HEAD
<<<<<<< HEAD
	if ( is_admin() || ! learn_press_is_course() ) {
=======
	if ( is_admin() || !learn_press_is_course() ) {
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
	if ( is_admin() || ! learn_press_is_course() ) {
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
		return;
	}
	global $wpdb;

	$table  = $wpdb->prefix . 'learnpress_user_items';
	$user   = learn_press_get_current_user();
	$course = learn_press_get_the_course();
	$item   = LP()->global['course-item'];
	$status = $user->get_course_status( $course->id );
	// may be need add condition is not is_admin()
	if ( $status === 'enrolled' && $item ) {

		// If status is not null that means user has viewed this item
<<<<<<< HEAD
<<<<<<< HEAD
		$item_status = learn_press_get_user_item_status_1234( $item->ID, $course->id, $user->id );// $user->get_item_status( $item->ID, $course->id );
		if ( ! ( '' == $item_status || false == $item_status ) ) {
=======
		$item_status = $user->get_item_status( $item->ID, $course->id );
		if ( !( '' == $item_status || false == $item_status ) ) {
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
		$item_status = learn_press_get_user_item_status_1234( $item->ID, $course->id, $user->id );// $user->get_item_status( $item->ID, $course->id );
		if ( ! ( '' == $item_status || false == $item_status ) ) {
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
			return;
		}
		$item_status = 'viewed';
		if ( $parent_id = learn_press_get_user_item_id( $user->id, $course->id ) ) {
			learn_press_update_user_item_field(
				array(
					'user_id'    => $user->id,
					'item_id'    => $item->ID,
					'start_time' => current_time( 'mysql' ),
					'end_time'   => '0000-00-00 00:00:00',
					'item_type'  => $item->post->item_type,
					'status'     => $item_status,
					'ref_id'     => $course->id,
					'ref_type'   => $course->post->post_type,
					'parent_id'  => $parent_id
				)
			);
		}
		// Update cache
<<<<<<< HEAD
<<<<<<< HEAD
		$item_statuses                                                    = LP_Cache::get_item_statuses( false, array() );
		$item_statuses[ $user->id . '-' . $course->id . '-' . $item->ID ] = $item_status;
		LP_Cache::set_item_statuses( $item_statuses );
	}
}

function learn_press_get_user_item_status_1234( $item_id, $course_id, $user_id ) {
	global $wpdb;
	$item_statuses = LP_Cache::get_item_statuses( false, array() );

	if ( ! $item_statuses || ! array_key_exists( $user_id . '-' . $course_id . '-' . $item_id, $item_statuses ) ) {

		$query = $wpdb->prepare( "
	        SELECT o.item_id, o.status
	        FROM {$wpdb->prefix}learnpress_user_items o
	        WHERE user_item_id = (SELECT MAX(user_item_id) FROM {$wpdb->prefix}learnpress_user_items s2 WHERE s2.item_id = o.item_id AND s2.user_id = o.user_id)
	        AND user_id = %d AND ref_id = %d
	        AND item_id IN (%d)
	    ", $user_id, $course_id, $item_id );

		///echo $query;die();

		$item_status = $wpdb->get_var( $query );
	} else {
		$item_status = $item_statuses[ $user_id . '-' . $course_id . '-' . $item_id ];
	}

	return $item_status;
=======
		$item_statuses                                                  = LP_Cache::get_item_statuses( false, array() );
		$item_statuses[$user->id . '-' . $course->id . '-' . $item->ID] = $item_status;
=======
		$item_statuses                                                    = LP_Cache::get_item_statuses( false, array() );
		$item_statuses[ $user->id . '-' . $course->id . '-' . $item->ID ] = $item_status;
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
		LP_Cache::set_item_statuses( $item_statuses );
	}
}

function learn_press_get_user_item_status_1234( $item_id, $course_id, $user_id ) {
	global $wpdb;
	$item_statuses = LP_Cache::get_item_statuses( false, array() );

	if ( ! $item_statuses || ! array_key_exists( $user_id . '-' . $course_id . '-' . $item_id, $item_statuses ) ) {

		$query = $wpdb->prepare( "
	        SELECT o.item_id, o.status
	        FROM {$wpdb->prefix}learnpress_user_items o
	        WHERE user_item_id = (SELECT MAX(user_item_id) FROM {$wpdb->prefix}learnpress_user_items s2 WHERE s2.item_id = o.item_id AND s2.user_id = o.user_id)
	        AND user_id = %d AND ref_id = %d
	        AND item_id IN (%d)
	    ", $user_id, $course_id, $item_id );

		///echo $query;die();

		$item_status = $wpdb->get_var( $query );
	} else {
		$item_status = $item_statuses[ $user_id . '-' . $course_id . '-' . $item_id ];
	}

<<<<<<< HEAD
	// should not flush if we did not do anything
	// LP_Cache::flush();
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
=======
	return $item_status;
>>>>>>> c0452c1ff55dc0d9924ec28a818e89f917285f7f
}

add_action( 'learn_press_print_assets', 'learn_press_header_item_only_view_first' );
