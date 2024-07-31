<?php
/**
 * Admin view for displaying details of an order.
 *
 * @author  ThimPress (Nhamdv)
 * @package LearnPress/Admin/Views
 * @version 4.0.3
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
$user_ids     = $order->get_user_id();
?>

<div id="learn-press-order" class="order-details">
	<div class="order-data">
		<h3 class="order-data-number"><?php echo sprintf( __( 'Order %s', 'learnpress' ), $order->get_order_number() ); ?></h3>
		<div class="order-data-field payment-method-title">
			<?php
			if ( $order->is_manual() ) {
				printf( '<strong>%s</strong>', __( 'Created manually', 'learnpress' ) );
			}
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

			<input type="date" class="order-date" name="order-date"
					value="<?php echo esc_attr( $order->get_order_date( 'd' ) ); ?>">
			@
			<input type="number" class="order-hour" name="order-hour" min="0" max="23"
					value="<?php echo esc_attr( $order->get_order_date( 'h' ) ); ?>">
			:
			<input type="number" class="order-minute" name="order-minute" min="0" max="59"
					value="<?php echo esc_attr( $order->get_order_date( 'm' ) ); ?>">

			<!-- Hidden fields for date only for default save post of Wordpress -->
			<input type="hidden" name="aa" value="<?php echo gmdate( 'Y', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="mm" value="<?php echo gmdate( 'm', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="jj" value="<?php echo gmdate( 'd', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="ss" value="<?php echo gmdate( 's', $order->get_order_date( 'timestamp' ) ); ?>">
			<input type="hidden" name="hh" value="<?php echo gmdate( 'h', $order->get_order_date( 'h' ) ); ?>">
			<input type="hidden" name="mn" value="<?php echo gmdate( 'm', $order->get_order_date( 'm' ) ); ?>">
			<!-- Hidden fields for date only for default save post of Wordpress -->
		</div>

		<div class="order-data-field order-data-status <?php echo sanitize_title( $order->get_post_status() ); ?>">
			<label><?php esc_html_e( 'Status:', 'learnpress' ); ?></label>
			<select name="order-status" data-status="<?php echo 'lp-' . $order->get_status(); ?>">
				<?php
				$statuses = LP_Order::get_order_statuses();
				foreach ( $statuses as $status => $status_name ) {
					?>
					<option data-desc="<?php echo esc_attr( _learn_press_get_order_status_description( $status ) ); ?>"
							value="<?php echo esc_attr( $status ); ?>"
						<?php echo selected( $status, 'lp-' . $order->get_status(), false ); ?>>
						<?php echo esc_html( LP_Order::get_status_label( $status_name ) ); ?>
					</option>;
					<?php
				}
				?>
			</select>

			<?php if ( $order->get_status() === 'completed' ) : ?>
				<div>
					<div
						style="padding: 10px 18px; margin-top: 10px; border: 2px solid #d80000; border-radius: 4px; display: inline-block;">
						<span class="dashicons dashicons-warning" style="color:#d80000"></span>
						<?php esc_html_e( 'When the Status is changed to "Pending", "Cancelled", or "Failed" all courses, lessons, quizzes, and other progress are deleted!', 'learnpress' ); ?>
					</div>
				</div>
			<?php endif; ?>
		</div>

		<div class="order-data-field order-data-user">
			<div class="order-users">
				<label><?php esc_html_e( 'Customers:', 'learnpress' ); ?></label>
				<?php
				if ( LP_ORDER_PENDING === $order->get_status() && $order->is_manual() ) {
					$data_struct = [
						'urlApi'      => get_rest_url( null, 'lp/v1/admin/tools/search-user' ),
						'dataType'    => 'users',
						'keyGetValue' => [
							'value'      => 'ID',
							'text'       => '{{display_name}}(#{{ID}}) - {{user_email}}',
							'key_render' => [
								'display_name' => 'display_name',
								'user_email'   => 'user_email',
								'ID'           => 'ID',
							],
						],
						'setting'     => [
							'placeholder' => esc_html__( 'Choose User', 'learnpress' ),
						],
					];

					$select_field = new LP_Meta_Box_Select_Field(
						'',
						'',
						'',
						array(
							'options'           => array(),
							'style'             => 'min-width:200px;',
							'tom_select'        => true,
							'multiple'          => true,
							'custom_attributes' => [ 'data-struct' => htmlentities2( json_encode( $data_struct ) ) ],
							'data-saved'        => $user_ids,
						)
					);

					$select_field->id = 'order-customer';

					$select_field->output( $order->get_id() );
				} else {
					?>
					<div class="advanced-list">
						<div class="ts-control">
							<?php
							if ( ! $order->is_manual() && $order->is_guest() ) {
								printf(
									'<li>
										<div class="item">%s</div>
									</li>',
									sprintf( '%s (%s)', $order->get_checkout_email(), __( 'Guest', 'learnpress' ) )
								);
							} elseif ( $order->is_manual() && empty( $order->get_users() ) && $order->is_guest() ) {
								printf(
									'<li>
										<div class="item">%s</div>
									</li>',
									__( 'No customer', 'learnpress' )
								);
							} else {
								if ( ! is_array( $user_ids ) ) {
									$user_ids = (array) $user_ids;
								}

								foreach ( $user_ids as $user_id ) {
									$user = learn_press_get_user( $user_id );
									if ( ! $user ) {
										continue;
									}
									printf(
										'<li>
											<div class="item" data-ts-item="">%s</div>
										</li>',
										sprintf( '%s (#%d) - %s', $user->get_display_name(), $user->get_id(), $user->get_email() )
									);
								}
							}
							?>
						</div>
					</div>
					<?php
					if ( $order->is_manual() ) {
						printf(
							'<p class="description">%s</p>',
							esc_html__( 'In order to change the order user, please change the order status to "Pending".', 'learnpress' )
						);
					}
				}
				?>
			</div>
		</div>

		<div class="order-data-field order-data-key">
			<label><?php esc_html_e( 'Order key:', 'learnpress' ); ?></label>
			<?php echo esc_html( $order->get_order_key() ); ?>
		</div>

		<?php do_action( 'lp/admin/order/detail/after-order-key', $order ); ?>

		<div class="order-data-field order-data-note">
			<?php $note = $post->post_excerpt; ?>
			<?php if ( ! empty( $note ) ) : ?>
				<label><?php esc_html_e( 'Customer Note', 'learnpress' ); ?></label>
				<p class="order-note description"><?php echo wp_kses_post( $note ); ?></p>
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

			<tr class="no-order-items<?php echo esc_attr( $items ? ' hide-if-js' : '' ); ?>">
				<td colspan="4"><?php esc_html_e( 'There are no order items', 'learnpress' ); ?></td>
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
							<?php echo learn_press_format_price( $order->get_data( 'order_subtotal' ), $currency_symbol ); ?>
						</span>
				</td>
			</tr>
			<?php do_action( 'learn-press/admin/order/detail/before-total', $order ); ?>
			<tr class="row-total">
				<td class="align-right" colspan="3">
					<?php esc_html_e( 'Total:', 'learnpress' ); ?>
				</td>
				<td class="align-right total">
						<span class="order-total">
							<?php echo learn_press_format_price( $order->get_data( 'order_total' ), $currency_symbol ); ?>
						</span>
				</td>
			</tr>
			<tr>
				<td colspan="2"></td>
				<td colspan="2" style="border-bottom: 1px dashed #DDD;"></td>
			</tr>
			<?php if ( $order->is_manual() ) { ?>
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
			<?php } ?>
			</tfoot>
		</table>
	</div>
</div>

<?php
$assets = LP_Admin_Assets::instance();
$assets->add_localize( 'learn-press-meta-box-order', 'users', $order->get_user_data() );
$assets->add_localize( 'learn-press-meta-box-order', 'userTextFormat', '{{display_name}} ({{email}})' );

wp_enqueue_script( 'jquery-ui-datepicker' );
wp_enqueue_style( 'jquery-ui' );
?>

<script type="text/html" id="tmpl-learn-press-modal-add-order-courses">
	<div id="learn-press-modal-add-order-courses" class="lp-modal-search"
		data-nonce="<?php echo wp_create_nonce( 'add_item_to_order' ); ?>">
		<div class="lp-search-items">
			<input type="text" id="learn-press-search-item-term"
					data-nonce="<?php echo wp_create_nonce( 'search_item_term' ); ?>" name="lp-item-name"
					placeholder="<?php esc_html_e( 'Type here to search for the course', 'learnpress' ); ?>"/>
		</div>
		<ul id="learn-press-courses-result">
			<li class="lp-search-no-results hide-if-js"
				data-id="0"><?php esc_html_e( 'No results', 'learnpress' ); ?>
			</li>
		</ul>
		<button class="lp-close-lightbox button"
				onclick="LP.MessageBox.hide();">
			<?php esc_html_e( 'Close', 'learnpress' ); ?>
		</button>
	</div>
</script>
