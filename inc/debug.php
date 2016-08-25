<?php
//add_action( 'wp_head', 'learn_press_head_head' );
function learn_press_head_head() {
	learn_press_debug( $_REQUEST, false );
}


function test_mail() {
	$user = learn_press_get_user( 1 );

	//do_action( 'learn_press_course_submit_rejected', 1673, $user );
	//do_action( 'learn_press_course_submit_approved', 1673, $user );
	//do_action( 'learn_press_course_submit_for_reviewer', 1673, $user );
	//do_action( 'learn_press_user_enrolled_course', $user, 1673, 3 );
	//do_action( 'learn_press_order_status_pending_to_processing' );
	//do_action( 'learn_press_order_status_pending_to_completed' );
	//do_action( 'learn_press_order_status_processing_to_completed' );*/
	//do_action( 'learn_press_course_submitted', 920, $user );
	//do_action( 'learn_press_course_approved', 920, $user );
}

add_action( 'admin_footer', 'test_mail' );

function learn_press_log_query( $query ) {
	if ( is_admin() ) {
		return $query;
	}
	if ( empty( $GLOBALS['lp_queries'] ) ) {
		$GLOBALS['lp_queries']       = array();
		$GLOBALS['lp_total_queries'] = 0;
	}
	$debug_backtrace = debug_backtrace();

	if ( !preg_match_all( '!woocommerce!', $query ) ) {
		//return $query;
	}
	$q = preg_replace( '/(\r\n|\r|\n|\s)+/', ' ', $query );
	$k = md5( $q );
	if ( empty( $GLOBALS['lp_queries'][$k] ) ) {
		$GLOBALS['lp_queries'][$k] = array(
			$q,
			1,
			'file' => $debug_backtrace[4]['file'],
			'line' => $debug_backtrace[4]['line'],
			'func' => $debug_backtrace[5]['function']
		);
	} else {
		$GLOBALS['lp_queries'][$k][1] ++;
	}

	$GLOBALS['lp_total_queries'] += 1;

	return $query;
}

add_filter( 'query', 'learn_press_log_query' );

function learn_press_save_queries() {
	if ( is_admin() ) {
		return;
	}
	$queries = $GLOBALS['lp_queries'];
	usort( $queries, create_function( '$a, $b', 'return $a[0] < $b[0];' ) );
	LP_Debug::instance()->add( $queries, 'query', true );
	LP_Debug::instance()->add( "Total of queries " . $GLOBALS['lp_total_queries'], 'query' );
}

add_action( 'wp_footer', 'learn_press_save_queries', 9999999 );
