<?php
/**
 * Display single row of order items.
 */
?>
<tr class="order-item-row" data-item_id="<?php echo $item['id']; ?>" data-id="<?php echo $item['course_id']; ?>"
    data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
    <td class="column-name">
	    <?php if ( 'pending' === $order->get_status() ) { ?>
            <a class="remove-order-item" href="">
                <span class="dashicons dashicons-trash"></span>
            </a>
	    <?php } ?>
		<?php do_action( 'learn_press_before_order_details_item_title', $item ); ?>
		<?php do_action( 'learn_press/before_order_details_item_title', $item ); ?>
        <!-- <a href="" class="remove-order-item">&times;</a> -->
        <a href="<?php echo apply_filters( 'learn_press/order_item_link', get_the_permalink( $item['course_id'] ), $item ); ?>"><?php echo apply_filters( 'learn_press/order_item_name', $item['name'], $item ); ?></a>
		<?php do_action( 'learn_press_after_order_details_item_title', $item ); ?>
		<?php do_action( 'learn_press/after_order_details_item_title', $item ); ?>


    </td>
    <td class="column-price align-right">
		<?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?>
    </td>
    <td class="column-quantity align-right">
        <small class="times">×</small>
		<?php echo $item['quantity']; ?>
    </td>
    <td class="column-total align-right"><?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?></td>
</tr>
