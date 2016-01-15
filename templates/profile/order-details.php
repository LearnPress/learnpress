<?php
global $wp_query;

learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
?>
<a href="<?php echo learn_press_get_page_link( 'profile' ); ?>"><?php _e( 'My Profile', 'learn_press' ); ?></a>
