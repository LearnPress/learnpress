<?php
/**
 * Admin view for displaying details of an order.
 *
 * @author  ThimPress (Nhamdv)
 * @package LearnPress/Admin/Views
 * @version 4.0.0
 */

if ( isset( $order_items ) ) {
	$currency_symbol = learn_press_get_currency_symbol( $order_items->currency );
} else {
	$currency_symbol = learn_press_get_currency_symbol();
}

if ( ! isset( $order ) || ! ( $order instanceof LP_Order ) ) {
	return;
}

$post         = $order->get_post();
$method_title = $order->get_payment_method_title();
$user_ip      = $order->get_user_ip_address();
?>

<div id="learn-press-order" class="order-details">
	<div class="order-data">
		<h3 class="order-data-number"><?php echo sprintf( __( 'Order %s', 'learnpress' ), $order->get_order_number() ); ?></h3>
		<div class="order-data-field payment-method-title">
			<?php
			if ( $method_title && $user_ip ) {
				printf( 'Pay via <strong>%s</strong> at <strong>%s</strong>', $method_title, $user_ip );
			} elseif ( $method_title ) {
				printf( 'Pay via <strong>%s</strong>', $method_title );
			} elseif ( $user_ip ) {
				printf( 'Customer IP <strong>%s</strong>', $user_ip );
			}
			?>
		</div>

		<h3 class="order-data-heading"><?php esc_html_e( 'Order details', 'learnpress' ); ?></h3>

		<div class="order-data-field order-data-date">
			<label><?php esc_html_e( 'Date created:', 'learnpress' ); ?></label>

			<?php learn_press_touch_time( true, 1 ); ?>

			<input type="hidden" name="aa" value="<?php echo gmdate( 'Y', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="mm" value="<?php echo gmdate( 'm', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="jj" value="<?php echo gmdate( 'd', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="ss" value="<?php echo gmdate( 's', $order->get_order_date( 'timestamp' ) ); ?>">

			<input type="text" class="order-date date-picker-backendorder" name="order-date" pattern="[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])" value="<?php echo esc_attr( $order->get_order_date( 'd' ) ); ?>">
			@
			<input type="number" class="order-hour" name="hh" min="0" max="23" value="<?php echo esc_attr( $order->get_order_date( 'h' ) ); ?>">
			:
			<input type="number" class="order-minute" name="mn" min="0" max="59" value="<?php echo esc_attr( $order->get_order_date( 'm' ) ); ?>">
		</div>

		<div class="order-data-field order-data-status <?php echo sanitize_title( $order->post_status ); ?>">
			<label><?php esc_html_e( 'Status:', 'learnpress' ); ?></label>
			<select name="order-status" data-status="<?php echo 'lp-' . $order->get_status(); ?>">
				<?php
				$statuses = learn_press_get_order_statuses();
				foreach ( $statuses as $status => $status_name ) {
					echo '<option data-desc="' . esc_attr( _learn_press_get_order_status_description( $status ) ) . '" value="' . esc_attr( $status ) . '" ' . selected( $status, 'lp-' . $order->get_status(), false ) . '>' . esc_html( $status_name ) . '</option>';
				}
				?>
			</select>

			<?php if ( $order->get_status() === 'completed' ) : ?>
				<div>
					<div style="padding: 10px 18px; margin-top: 10px; border: 2px solid #d80000; border-radius: 4px; display: inline-block;">
						<span class="dashicons dashicons-warning" style="color:#d80000"></span>
						<?php esc_html_e( 'When change Status to "Pending", "Cancelled" or "Failed" will delete all course, lesson, quiz... progress!', 'learnpress' ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="order-data-field order-data-user">
			<div class="order-users">
				<?php if ( $order->is_multi_users() ) : ?>
					<label><?php esc_html_e( 'Customers:', 'learnpress' ); ?></label>
					<ul id="list-users" class="advanced-list <?php echo $order->get_status() === 'completed' ? 'locked' : ''; ?>">
					</ul>

					<?php if ( 'pending' === $order->get_status() ) : ?>
						<a href="" class="change-user" data-multiple="yes"><?php esc_html_e( 'Add multi users', 'learnpress' ); ?></a>
					<?php endif; ?>

					<?php else : ?>

					<label><?php esc_html_e( 'Customer:', 'learnpress' ); ?></label>
						<?php
						$user_email = $order->get_user( 'email' );

						if ( $user_email ) {
							printf( '%s (%s) ', $order->get_customer_name(), $order->get_user( 'email' ) );

							if ( $order->get_user_id( 'edit' ) ) {
								printf( '<a href="%s">%s</a>', esc_url( add_query_arg( 'user_id', absint( $order->get_user_id( 'edit' ) ), admin_url( 'user-edit.php' ) ) ), esc_html__( 'Profile', 'learnpress' ) );
							}
						} else {
							echo $order->get_customer_name();
						}
						?>

						<input type="hidden" name="order-customer" id="order-customer" value="<?php echo $order->get_user( 'id' ); ?>"/>

						<?php if ( 'pending' === $order->get_status() ) : ?>
							<?php echo ' |'; ?>
							<a href="" class="change-user"><?php esc_html_e( 'Change', 'learnpress' ); ?></a>
						<?php endif; ?>
				<?php endif; ?>

				<?php if ( $order->get_post_status() == 'auto-draft' ) : ?>
					<?php esc_html_e( '- Or -', 'learnpress' ); ?>
					<a href="" class="change-user" data-multiple="yes"><?php esc_html_e( 'Add multi users', 'learnpress' ); ?></a>
				<?php endif; ?>

				<?php
				if ( 'pending' !== $order->get_status() ) {
					echo '<p class="description">';
					esc_html_e( 'In order to change the order user, please change the order status to \'Pending\'.', 'learnpress' );
					echo '</p>';

				}

				//learn_press_admin_view( 'meta-boxes/order/child-order', array( 'order' => $order ) );
				?>

			</div>
		</div>

		<div class="order-data-field order-data-key">
			<label><?php esc_html_e( 'Order key:', 'learnpress' ); ?></label>
			<?php echo esc_html( $order->get_order_key() ); ?>
		</div>

		<div class="order-data-field order-data-note">
			<?php $note = $post->post_excerpt; ?>
			<?php if ( ! empty( $note ) ) : ?>
				<label><?php esc_html_e( 'Customer Note', 'learnpress' ); ?></label>
				<p class="order-note description"><?php echo $note; ?></p>
			<?php endif; ?>
		</div>
	</div>

	<div class="order-items">
		<table class="list-order-items">
			<thead>
				<tr>
					<th class="column-name"><?php esc_html_e( 'Item', 'learnpress' ); ?></th>
					<th class="column-price"><?php esc_html_e( 'Cost', 'learnpress' ); ?></th>
					<th class="column-quantity"><?php esc_html_e( 'Quantity', 'learnpress' ); ?></th>
					<th class="column-total align-right"><?php esc_html_e( 'Total', 'learnpress' ); ?></th>
				</tr>
			</thead>

			<tbody>
				<?php $items = $order->get_items(); ?>

				<?php if ( $items ) : ?>
					<?php foreach ( $items as $item ) : ?>
						<?php include learn_press_get_admin_view( 'meta-boxes/order/order-item.php' ); ?>
					<?php endforeach; ?>
				<?php endif; ?>

				<tr class="no-order-items<?php echo $items ? ' hide-if-js' : ''; ?>">
					<td colspan="4"><?php esc_html_e( 'No order items', 'learnpress' ); ?></td>
				</tr>
			</tbody>

			<tfoot>
				<tr>
					<td colspan="2"></td>
					<td colspan="2"></td>
				</tr>
				<tr class="row-subtotal">
					<td width="300" colspan="3" class="align-right">
						<?php esc_html_e( 'Subtotal:', 'learnpress' ); ?>
					</td>
					<td width="100" class="align-right">
						<span class="order-subtotal">
							<?php echo learn_press_format_price( $order->order_subtotal, $currency_symbol ); ?>
						</span>
					</td>
				</tr>
				<tr class="row-total">
					<td class="align-right" colspan="3">
						<?php esc_html_e( 'Total:', 'learnpress' ); ?>
					</td>
					<td class="align-right total">
						<span class="order-total">
							<?php echo learn_press_format_price( $order->order_total, $currency_symbol ); ?>
						</span>
					</td>
				</tr>
				<tr>
					<td colspan="2"></td>
					<td colspan="2" style="border-bottom: 1px dashed #DDD;"></td>
				</tr>
				<tr>
					<td class="align-right" colspan="4" style="border-top: 1px solid #DDD;">
						<?php if ( 'pending' === $order->get_status() ) { ?>
							<button class="button" type="button" id="learn-press-add-order-item">
								<?php esc_html_e( 'Add item(s)', 'learnpress' ); ?>
							</button>
							<?php
						} else {
							echo '<p class="description">';
							esc_html_e( 'In order to change the order item, please change the order status to \'Pending\'.', 'learnpress' );
							echo '</p>';
						}
						?>
					</td>
				</tr>
			</tfoot>
		</table>
	</div>
</div>

<script type="text/html" id="tmpl-order-data-user">
	<# if(!data.multiple){ #>
		<div class="order-data-field order-data-user">
			<label><?php esc_html_e( 'Customer', 'learnpress' ); ?></label>
			<div class="order-users">
				{{data.name}}
				<input type="hidden" name="order-customer" id="order-customer" value="{{data.id}}">
			</div>
			<a href="" class="change-user"><?php esc_html_e( 'Change', 'learnpress' ); ?></a>
		</div>
	<# }else{ #>
		<div class="order-data-field order-data-user">
			<label><?php esc_html_e( 'Customer', 'learnpress' ); ?></label>
			<div class="order-users">
				<ul id="list-users" class="advanced-list <?php echo $order->get_status() === 'completed' ? 'locked' : ''; ?>"></ul>
			</div>
			<a href="" class="change-user" data-multiple="yes"><?php esc_html_e( 'Add multi users', 'learnpress' ); ?></a>
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

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui' );
?>

<script type="text/html" id="tmpl-learn-press-modal-add-order-courses">
	<div id="learn-press-modal-add-order-courses" class="lp-modal-search" data-nonce="<?php echo wp_create_nonce( 'add_item_to_order' ); ?>">
		<div class="lp-search-items">
			<input type="text" id="learn-press-search-item-term" data-nonce="<?php echo wp_create_nonce( 'search_item_term' ); ?>" name="lp-item-name" placeholder="<?php esc_html_e( 'Type here to search the course', 'learnpress' ); ?>"/>
		</div>
		<ul id="learn-press-courses-result">
			<li class="lp-search-no-results hide-if-js" data-id="0"><?php esc_html_e( 'No results', 'learnpress' ); ?></li>
		</ul>
		<button class="lp-close-lightbox button" onclick="LP.MessageBox.hide();"><?php esc_html_e( 'Close', 'learnpress' ); ?></button>
	</div>
</script>
