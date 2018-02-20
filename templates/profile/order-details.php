<<<<<<< HEAD
<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
global $wp_query;

learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
?>
<a href="<?php echo learn_press_get_page_link( 'profile' ); ?>"><?php _e( 'My Profile', 'learnpress' ); ?></a>
=======
<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       1.0
 */

defined( 'ABSPATH' ) || exit();
global $wp_query;

learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
?>
<a href="<?php echo learn_press_get_page_link( 'profile' ); ?>"><?php _e( 'My Profile', 'learnpress' ); ?></a>
>>>>>>> f52771a835602535f6aecafadff0e2b5763a4f73
