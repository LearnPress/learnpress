<?php
function learn_press_test_cache() {
	global $wp_object_cache;
	if ( !empty( $_REQUEST['xxxxx'] ) ) {
		learn_press_debug( $wp_object_cache );
		die();
	}
}


add_action( 'wp_footer', function () {
	echo '1234';
	$p = get_post_types( );
	print_r( $p );
	echo 'xxxxxxxxxx';

	echo 'Test gitflow';
	echo 'Test gitflow';
	echo "giartrrp";
} );

