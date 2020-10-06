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

defined( 'ABSPATH' ) || exit();
?>

<div class="profile-recover-order">
	<p class="recover-order__title"><?php esc_html_e( 'If you have a valid order key you can recover it here.', 'learnpress' ); ?></p>
	<p class="recover-order__description"><?php esc_html_e( 'When you checkout with Guest order key will send in email, you can use order key to create order.', 'learnpress' ); ?></p>

	<?php learn_press_get_template( 'order/recover-form.php' ); ?>
</div>

