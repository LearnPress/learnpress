<?php
/**
 * Template for displaying orders tab in user profile page.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/tabs/orders.php.
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

<div class="profile-orders">
	<?php
	/**
	 * LP Hook
     *
     * @hooked profile/tabs/orders/list.php             - 10
     * @hooked profile/tabs/orders/recover-order.php    - 20
	 */
    do_action( 'learn-press/profile/orders' );
    ?>
</div>
