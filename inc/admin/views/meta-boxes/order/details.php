<?php
/**
 * Admin view for displaying details of an order.
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 3.x.x
 */
if ( isset( $order_items ) ) {
	$currency_symbol = learn_press_get_currency_symbol( $order_items->currency );
} else {
	$currency_symbol = learn_press_get_currency_symbol();
}
global $post;

$method_title = $order->get_payment_method_title();


//$wc = new WC_Order(0);
//$wc->set_date_created(current_time('timestamp', true));
//
//$wc->get_date_created()->set_utc_offset(1);
//
//echo $wc->get_date_created(), ',', $wc->get_date_created()->getOffset(), "<br />";
//learn_press_debug($wc->get_date_created()->getTimezone());echo  "<br />";
//learn_press_debug( $wc->get_date_created());echo  "<br />";
//echo gmdate('Y-m-d H:i:s', $wc->get_date_created()->getOffsetTimestamp()), "<br />";
//echo gmdate('Y-m-d H:i:s', $wc->get_date_created()->getTimestamp());
//
//learn_press_debug($order);
//learn_press_debug($wc->get_date_created()->getOffsetTimestamp());
//
//$a = new LP_Datetime();
//echo $a->toSql(), "<br />";
//echo $a->getOffset(), "<br />";
//echo $a->getOffset(true), "<br />";
//echo $a;
//die();


//echo $order->get_meta('xx');
?>
<div id="learn-press-order" class="order-details">
    <div class="order-data">
        <h3 class="order-data-number"><?php echo sprintf( __( 'Order %s', 'learnpress' ), $order->get_order_number() ); ?></h3>
        <div class="order-data-field payment-method-title">
			<?php
			if ( $order->order_total == 0 ) {
				printf( '<strong>%s</strong> at <strong>%s</strong>', $method_title, $order->get_data( 'user_ip_address' ) );
			} else {
				printf( 'Pay via <strong>%s</strong> at <strong>%s</strong>', $method_title, $order->get_data( 'user_ip_address' ) );
			} ?>
        </div>
        <h3 class="order-data-heading"><?php _e( 'Order details', 'learnpress' ); ?></h3>
        <div class="order-data-field order-data-date">
            <label><?php _e( 'Date', 'learnpress' ); ?></label>
			<?php learn_press_touch_time( true, 1 ); ?>
            <input type="hidden" name="aa" value="<?php echo date( 'Y', $order->get_order_date( 'timestamp' ) ); ?>">
            <input type="hidden" name="mm" value="<?php echo date( 'm', $order->get_order_date( 'timestamp' ) ); ?>">
            <input type="hidden" name="jj" value="<?php echo date( 'd', $order->get_order_date( 'timestamp' ) ); ?>">
            <input type="hidden" name="ss" value="<?php echo date( 's', $order->get_order_date( 'timestamp' ) ); ?>">

            <input type="text" class="order-date date-picker" name="order-date"
                   pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])"
                   value="<?php echo esc_attr( $order->get_order_date( 'd' ) ); ?>">
            @
            <input type="number" class="order-hour" name="hh" min="0" max="23"
                   value="<?php echo esc_attr( $order->get_order_date( 'h' ) ); ?>">
            :
            <input type="number" class="order-minute" name="mn" min="0" max="59"
                   value="<?php echo esc_attr( $order->get_order_date( 'm' ) ); ?>">
        </div>

        <div class="order-data-field order-data-status <?php echo sanitize_title( $order->post_status ); ?>">
            <label><?php _e( 'Status', 'learnpress' ); ?></label>
            <select name="order-status" data-status="<?php echo 'lp-' . $order->get_status(); ?>">
				<?php
				$statuses = learn_press_get_order_statuses();
				foreach ( $statuses as $status => $status_name ) {
					echo '<option data-desc="' . esc_attr( _learn_press_get_order_status_description( $status ) ) . '" value="' . esc_attr( $status ) . '" ' . selected( $status, 'lp-' . $order->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
				}
				?>
            </select>
<!--            <label><input type="checkbox">--><?php //_e( 'Force to trigger order action', 'learnpress' ); ?><!--</label>-->
        </div>

        <div class="order-data-field order-data-user">
			<?php if ( $order->is_multi_users() ) { ?>
                <label><?php _e( 'Customers', 'learnpress' ); ?></label>
                <div class="order-users">
                    <ul id="list-users" class="advanced-list">
                    </ul>
                    <a href="" class="change-user"
                       data-multiple="yes"><?php _e( 'Add multi users', 'learnpress' ); ?></a>
                </div>
			<?php } else { ?>
                <label><?php _e( 'Customer', 'learnpress' ); ?></label>
                <div class="order-users">
					<?php
					if ( $user_email = $order->get_user( 'email' ) ) {
						printf( '%s (%s)', $order->get_customer_name(), $order->get_user( 'email' ) );
					} else {
						echo $order->get_customer_name();
					}
					?>
                    <input type="hidden" name="order-customer" id="order-customer"
                           value="<?php echo $order->get_user( 'id' ); ?>"/>
                </div>
                <a href="" class="change-user"><?php _e( 'Change', 'learnpress' ); ?></a>
			<?php } ?>
			<?php if ( $order->get_status() == 'auto-draft' ) { ?>
				<?php _e( '- Or -', 'learnpress' ); ?>
                <a href="" class="change-user" data-multiple="yes"><?php _e( 'Add multi users', 'learnpress' ); ?></a>
			<?php } ?>
        </div>
    </div>
    <h3 class="order-data-heading"><?php _e( 'Order Items', 'learnpress' ); ?></h3>
    <div class="order-items">
        <table class="list-order-items">
            <thead>
            <tr>
                <th class="column-name"><?php _e( 'Item', 'learnpress' ); ?></th>
                <th class="column-price"><?php _e( 'Cost', 'learnpress' ); ?></th>
                <th class="column-quantity"><?php _e( 'Quantity', 'learnpress' ); ?></th>
                <th class="column-total align-right"><?php _e( 'Amount', 'learnpress' ); ?></th>
            </tr>
            </thead>
            <tbody>
			<?php if ( $items = $order->get_items() ): ?>
				<?php foreach ( $items as $item ) : ?>
					<?php include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' ); ?>
				<?php endforeach; ?>
			<?php endif; ?>
            <tr class="no-order-items<?php echo $items ? ' hide-if-js' : ''; ?>">
                <td colspan="4"><?php _e( 'No order items', 'learnpress' ); ?></td>
            </tr>
            </tbody>
            <tfoot>
            <tr class="row-subtotal">
                <td width="300" colspan="3" class="align-right"><?php _e( 'Sub Total', 'learnpress' ); ?></td>
                <td width="100" class="align-right">
					<span class="order-subtotal">
						<?php echo learn_press_format_price( $order->order_subtotal, $currency_symbol ); ?>
					</span>
                </td>
            </tr>
            <tr class="row-total">
                <td class="align-right" colspan="3"><?php _e( 'Total', 'learnpress' ); ?></td>
                <td class="align-right total">
					<span class="order-total">
						<?php echo learn_press_format_price( $order->order_total, $currency_symbol ); ?>
					</span>
                </td>
            </tr>
            <tr>
                <td class="align-right" colspan="4">
                    <button class="button" type="button"
                            id="learn-press-add-order-item"><?php _e( 'Add Item', 'learnpress' ); ?></button>
                    <!--<button class="button" type="button" id="learn-press-calculate-order-total"><?php _e( 'Calculate Total', 'learnpress' ); ?></button>-->
                </td>
            </tr>
            </tfoot>
        </table>
    </div>
	<?php if ( $note = get_the_excerpt() ) { ?>
        <br/>
        <h3><?php _e( 'Customer Note', 'learnpress' ); ?></h3>
        <p class="order-note description"><?php echo $note; ?></p>
	<?php } ?>
</div>
<script type="text/html" id="tmpl-order-data-user">
    <# if(!data.multiple){ #>
        <div class="order-data-field order-data-user">
            <label><?php _e( 'Customer', 'learnpress' ); ?></label>
            <div class="order-users">
                {{data.name}}
                <input type="hidden" name="order-customer" id="order-customer" value="{{data.id}}">
            </div>
            <a href="" class="change-user"><?php _e( 'Change', 'learnpress' ); ?></a>
        </div>
        <# }else{ #>
            <div class="order-data-field order-data-user">
                <label><?php _e( 'Customer', 'learnpress' ); ?></label>
                <div class="order-users">
                    <ul id="list-users" class="advanced-list">
                    </ul>
                </div>
                <a href="" class="change-user" data-multiple="yes"><?php _e( 'Add multi users', 'learnpress' ); ?></a>
            </div>
            <# } #>
</script>
<script type="text/html" id="tmpl-order-advanced-list-item">
    <li data-id="{{id}}">
        <span class="remove-item"></span><span>{{text}}</span>
        <input type="hidden" name="order-customer[]" value="{{id}}">
    </li>
</script>

<?php
$assets = learn_press_admin_assets();
$assets->add_localize( 'learn-press-meta-box-order', 'users', $order->get_user_data() );
$assets->add_localize( 'learn-press-meta-box-order', 'userTextFormat', '{{display_name}} ({{email}})' );
RWMB_Datetime_Field::admin_enqueue_scripts();
wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui-datepicker' );
?>

<script type="text/html" id="tmpl-learn-press-modal-add-order-courses">
    <div id="learn-press-modal-add-order-courses" class="lp-modal-search"
         data-nonce="<?php echo wp_create_nonce( 'add_item_to_order' ); ?>">
        <div class="lp-search-items">
            <input type="text" id="learn-press-search-item-term"
                   data-nonce="<?php echo wp_create_nonce( 'search_item_term' ); ?>" name="lp-item-name"
                   placeholder="<?php _e( 'Type here to search the course', 'learnpress' ); ?>"/>
        </div>
        <ul id="learn-press-courses-result">
            <li class="lp-search-no-results hide-if-js" data-id="0"><?php _e( 'No results', 'learnpress' ); ?></li>
        </ul>
        <button class="lp-close-lightbox button"
                onclick="LP.MessageBox.hide();"><?php _e( 'Close', 'learnpress' ); ?></button>
    </div>
</script>