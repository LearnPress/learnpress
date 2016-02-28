<?php
/**
 * Display settings for checkout
 *
 * @author  ThimPress
 * @package LearnPress/Admin/Views
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$settings = LP()->settings;
?>
	<table class="form-table">
		<tbody>
		<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $this ); ?>
		<?php foreach( $this->get_settings() as $field ){?>
			<?php $this->output_field( $field );?>
		<?php }?>
		<?php if( 1 == 0 ){?>
		<tr>
			<th scope="row"><label for="learn-press-checkout-enable-cart"><?php _e( 'Enable cart', 'learnpress' ); ?></label></th>
			<td>
				<input type="hidden" name="<?php echo $this->get_field_name( 'enable_cart' ); ?>" value="no" />
				<input id="learn-press-checkout-enable-cart" type="checkbox" name="<?php echo $this->get_field_name( 'enable_cart' ); ?>" value="yes" <?php checked( $settings->get( 'enable_cart' ) == 'yes', true ); ?> />
				<p class="description"><?php _e( 'Check this option to enable user can purchase multiple course at one time', 'learnpress' );?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="learn-press-checkout-add-to-cart-redirect"><?php _e( 'Add to cart redirect', 'learnpress' ); ?></label></th>
			<td>
				<input type="hidden" name="<?php echo $this->get_field_name( 'redirect_after_add' ); ?>" value="no" />
				<input id="learn-press-checkout-add-to-cart-redirect" type="checkbox" name="<?php echo $this->get_field_name( 'redirect_after_add' ); ?>" value="yes" <?php checked( $settings->get( 'redirect_after_add' ) == 'yes', true ); ?> />
				<p class="description"><?php _e( 'Redirect to checkout immediately after add course to cart', 'learnpress' );?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label for="learn-press-checkout-add-to-cart-ajax"><?php _e( 'AJAX add to cart', 'learnpress' ); ?></label></th>
			<td>
				<input type="hidden" name="<?php echo $this->get_field_name( 'ajax_add_to_cart' ); ?>" value="no" />
				<input id="learn-press-checkout-add-to-cart-ajax" type="checkbox" name="<?php echo $this->get_field_name( 'ajax_add_to_cart' ); ?>" value="yes" <?php checked( $settings->get( 'ajax_add_to_cart' ) == 'yes', true ); ?> />
				<p class="description"><?php _e( 'Using AJAX to add course to the cart', 'learnpress' );?></p>
			</td>
		</tr>
		<tr>
			<th scope="row"><label><?php _e( 'Cart page', 'learnpress' ); ?></label></th>
			<td>
				<?php
				$cart_page_id = $settings->get( 'cart_page_id', 0 );
				learn_press_pages_dropdown( $this->get_field_name( "cart_page_id" ), $cart_page_id );
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label><?php _e( 'Checkout page', 'learnpress' ); ?></label></th>
			<td>
				<?php
				$checkout_page_id = $settings->get( 'checkout_page_id', 0 );
				learn_press_pages_dropdown( $this->get_field_name( "checkout_page_id" ), $checkout_page_id );
				?>
			</td>
		</tr>
		<?php }?>
	</tbody>

	</table>
<?php if( 1 == 0 ){?>
	<h3><?php _e( 'Checkout Endpoints', 'learnpress' );?></h3>
	<table class="form-table">
		<tbody>
		<tr>
			<th scope="row"><label><?php _e( 'Order received', 'learnpress' ); ?></label></th>
			<td>
				<input type="text" class="regular-text" name="<?php echo $this->get_field_name( 'checkout_endpoints[lp_order_received]' );?>" value="<?php echo $settings->get( 'checkout_endpoints.lp_order_received', 'lp-order-received' );?>" />
			</td>
		</tr>
		<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $this ); ?>
		</tbody>
	</table>
	<?php } ?>