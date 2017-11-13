<?php
/**
 * Template for displaying form allow user get back their order by the key
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or die();

?>
<div class="profile-recover-order">
    <p><?php _e( 'If you have a valid order key you can recover it here.' ); ?></p>
	<?php learn_press_get_template( 'order/recover-form.php' ); ?>
</div>

