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
	<h3><?php _e( 'General', 'learn_press' ); ?></h3>
	<table class="form-table">
		<tbody>
		<?php do_action( 'learn_press_before_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $this ); ?>
		<tr>
			<th scope="row"><label for="lpr_from_name"><?php _e( 'Enable cart', 'learn_press' ); ?></label></th>
			<td>
				<input type="hidden" name="<?php echo $this->get_field_name( 'enable_cart' ); ?>" value="no" />
				<input type="checkbox" name="<?php echo $this->get_field_name( 'enable_cart' ); ?>" value="yes" <?php checked( $settings->get( 'enable_cart' ) == 'yes', true ); ?> />
			</td>
		</tr>
		<tr>
			<th scope="row"><label><?php _e( 'Cart page', 'learn_press' ); ?></label></th>
			<td>
				<?php
				$cart_page_id = $settings->get( 'cart_page_id', 0 );
				learn_press_pages_dropdown( $this->get_field_name( "cart_page_id" ), $cart_page_id );
				?>
			</td>
		</tr>
		<tr>
			<th scope="row"><label><?php _e( 'Checkout page', 'learn_press' ); ?></label></th>
			<td>
				<?php
				$checkout_page_id = $settings->get( 'checkout_page_id', 0 );
				learn_press_pages_dropdown( $this->get_field_name( "checkout_page_id" ), $checkout_page_id );
				?>
			</td>
		</tr>
		<?php do_action( 'learn_press_after_' . $this->id . '_' . $this->section['id'] . '_settings_fields', $this ); ?>
		</tbody>
	</table>
<?php