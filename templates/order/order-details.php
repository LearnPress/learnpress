<?php
/**
 * Order details
 *
 * @author  WooThemes
 * @package learn_press/Templates
 * @version 2.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

?>
<h2><?php _e( 'Order Details', 'learn_press' ); ?></h2>
<table class="order_table order_details">
    <thead>
    <tr>
        <th class="course-name"><?php _e( 'Course', 'learn_press' ); ?></th>
        <th class="course-total"><?php _e( 'Total', 'learn_press' ); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    if ( $order_items = $order->get_items() ) {
        $items = $order_items->products;
        $currency_symbol = learn_press_get_currency_symbol( $order_items->currency );
        foreach( $items as $item_id => $item ) {
            $_course  = apply_filters( 'learn_press_order_item_course', get_post( $item_id ), $item );
            //$item_meta = new WC_Order_Item_Meta( $item['item_meta'], $_product );
            //print_r($item);
            if ( apply_filters( 'learn_press_order_item_visible', true, $item ) ) {
                ?>
                <tr class="<?php echo esc_attr( apply_filters( 'learn_press_order_item_class', 'order_item', $item, $order ) ); ?>">
                    <td class="course-name">
                        <?php
                        echo apply_filters( 'learn_press_order_item_name', sprintf( '<a href="%s">%s</a>', get_permalink( $item['id'] ), $item['product_name'] ), $item );
                        ?>
                    </td>
                    <td class="product-total">
                        <?php echo !empty( $item['product_subtotal'] ) ? learn_press_format_price($item['product_subtotal'], $currency_symbol) : __('Free!', 'learn_press'); ?>
                    </td>
                </tr>
            <?php
            }
        }
    }
    do_action( 'learn_press_order_items_table', $order );
    ?>
    </tbody>
    <tfoot>

        <tr>
            <th scope="row"><?php _e( 'Subtotal', 'learn_press' ); ?></th>
            <td><?php echo learn_press_format_price($order_items->sub_total, $currency_symbol); ?></td>
        </tr>
        <tr>
            <th scope="row"><?php _e( 'Total', 'learn_press' ); ?></th>
            <td><?php echo learn_press_format_price($order_items->total, $currency_symbol ); ?></td>
        </tr>
    </tfoot>
</table>

<?php do_action( 'learn_press_order_details_after_order_table', $order ); ?>

<div class="clear"></div>
