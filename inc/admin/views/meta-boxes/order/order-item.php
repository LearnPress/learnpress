<?php
/**
 *
 */
?>
<tr data-item_id="<?php echo $item['id']; ?>" data-remove_nonce="<?php echo wp_create_nonce( 'remove_order_item' ); ?>">
	<td>
		<a href="" class="remove-order-item">&times;</a>
		<a href="<?php echo get_the_permalink( $item['course_id'] ); ?>"><?php echo $item['name']; ?></a>
	</td>
	<td class="align-right">
		<?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?>
	</td>
	<td class="align-right"><?php echo $item['quantity']; ?></td>
	<td class="align-right"><?php echo learn_press_format_price( $item['total'], $currency_symbol ); ?></td>
</tr>
