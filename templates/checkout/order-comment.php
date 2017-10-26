<?php
/**
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

?>

<div class="learn-press-checkout-comment">

    <h4><?php _e( 'Additional Information', 'learnpress' ); ?></h4>

    <textarea name="order_comments" class="order-comments"
              placeholder="<?php _e( 'Note to administrator', 'learnpress' ); ?>"></textarea>

</div>
