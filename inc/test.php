<?php


/**
 * The code below are used for testing purpose
 *
 * TODO: Remove these code before releasing.
 */
add_filter( 'learn-press/checkout-no-payment-result', function ( $results, $order_id ) {
	$order = learn_press_get_order( $order_id );
	if ( $order->is_completed() ) {
		$order_users = $order->get_users();
		$users       = array();
		foreach ( $order->get_items() as $item ) {
			$course = learn_press_get_course( $item['course_id'] );
			if ( $course->is_publish() ) {
				foreach ( $order_users as $user_id ) {
					if ( empty( $users[ $user_id ] ) ) {
						$user = learn_press_get_user( $user_id );
						if ( ! $user->is_exists() ) {
							continue;
						}

						$users[ $user_id ] = $user;
					}

					$users[ $user_id ]->enroll( $course->get_id(), $order->get_id() );
				}
			}
		}
	}

	return $results;
}, 10, 2 );
function xyz() {
	if ( empty( $_REQUEST['xxxxx'] ) ) {
		return;
	}
	remove_action( 'get_header', 'xyz' );
	do_action( 'wp_head' );
	learn_press_get_template( 'single-course/tabs/curriculum.php' );
	learn_press_get_template( 'single-course/content-item.php' );
	do_action( 'wp_footer' );
	die();
}

add_action( 'get_header', 'xyz' );

add_action( 'learn_press/before_course_item_content', function ( $a, $b ) {
	echo '<a href="' . get_permalink( $b ) . '">' . __( 'Course', 'learnpress' ) . '</a>';
}, 10, 2 );

//add_action( 'init', function () {
//	$file = get_cache_file();
//
//	if ( file_exists( $file ) && strtolower( $_SERVER['REQUEST_METHOD'] ) !== 'post' ) {
//		echo file_get_contents( $file );
//		die();
//	}
//	ob_start( 'xxxxx' );
//} );
//
//function xxxxx( $buffer ) {
//	$file = get_cache_file();
//	file_put_contents( $file, $buffer );
//
//	return $buffer;
//}
//
//function get_cache_file() {
//	$dir  = wp_upload_dir();
//	@mkdir($dir['basedir'] . '/cache/');
//	$file = $dir['basedir'] . '/cache/' . md5( learn_press_get_current_url() ) . '.lp';
//
//	return $file;
//}
//function rrmdir($dir) {
//	if (is_dir($dir)) {
//		$objects = scandir($dir);
//		foreach ($objects as $object) {
//			if ($object != "." && $object != "..") {
//				if (is_dir($dir."/".$object))
//					rrmdir($dir."/".$object);
//				else
//					unlink($dir."/".$object);
//			}
//		}
//		rmdir($dir);
//	}
//}
//add_filter('query', function($query){
//	if(preg_match( '/^\s*(insert|delete|update|replace)\s/i', $query ) ){
//		$dir  = wp_upload_dir();
//		$file = $dir['basedir'] . '/cache/';
//		rrmdir($file);
//	}
//	return $query;
//});
//function shutdown() {
//	global $wpdb;
//
//	// This is our shutdown function, in
//	// here we can do any last operations
//	// before the script is complete.
//}
//
//register_shutdown_function( 'shutdown' );