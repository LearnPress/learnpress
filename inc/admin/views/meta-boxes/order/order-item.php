<?php
/**
 *
 */
?>
<tr data-item_id="<?php echo $item['id']; ?>" data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
	<td>
		<?php do_action('learn_press_before_order_details_item_title', $item);?>
        <?php do_action('learn_press/before_order_details_item_title', $item);?>
		<a href="" class="remove-order-item">&times;</a>
		<a href="<?php echo get_the_permalink( $item['course_id'] ); ?>"><?php echo $item['name']; ?></a>
		<?php do_action('learn_press_after_order_details_item_title', $item);?>
	</td>
		<?php do_action('learn_press/after_order_details_item_title', $item);?>
    </td>
	<td class="align-right">
		<?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?>
	</td>
	<td class="align-right"><?php echo $item['quantity']; ?></td>
	<td class="align-right"><?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?></td>
</tr>
