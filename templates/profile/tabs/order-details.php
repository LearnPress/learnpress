<?php
/**
 * @author        ThimPress
 * @package       LearnPress/Templates
 * @version       3.x.x
 */


defined( 'ABSPATH' ) || exit();
global $wp_query, $wp;

$order = learn_press_get_order(486);

print_r($order);
//learn_press_get_template( 'order/order-details.php', array( 'order' => $order ) );
?>
<a href="<?php echo learn_press_get_page_link( 'profile' ); ?>"><?php _e( 'My Profile', 'learnpress' ); ?></a>
