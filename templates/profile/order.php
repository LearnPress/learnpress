<?php
global $wp_query;
if ( empty( $wp_query->query_vars['order_id'] ) ) {
	return;
}
$current_user = wp_get_current_user();
if( $wp_query->query_vars['user'] != $current_user->user_login ){
	learn_press_get_template( 'profile/private-area.php' );
	return;
}
$order = learn_press_get_order( $wp_query->query_vars['order_id'] );
print_r($order);
?>
<a href="<?php echo learn_press_get_page_link( 'profile' ); ?>"><?php _e( 'My Profile', 'learn_press' ); ?></a>
