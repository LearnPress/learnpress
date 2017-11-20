<?php
/**
 * Template for displaying recover order in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/orders/recover-order.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<div class="profile-recover-order">

    <p><?php _e( 'If you have a valid order key you can recover it here.', 'learnpress' ); ?></p>

	<?php learn_press_get_template( 'order/recover-form.php' ); ?>

</div>

