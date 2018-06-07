<?php
/**
 * @author  ThimPress
 * @package LearnPress/Classes
 * @version 3.0.0
 */

// Prevent loading this file directly
defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'RWMB_Payment_Order_Field' ) ) {
	class RWMB_Payment_Order_Field extends RWMB_Field {
		/**
		 * Get field HTML
		 *
		 * @param mixed $meta
		 * @param mixed $field
		 *
		 * @return string
		 */
		public static function html( $meta, $field = '' ) {
			$gateways = LP_Gateways::instance()->get_gateways( true );
			ob_start();
			?>
            <table class="learn-press-payments<?php echo sizeof( $gateways ) > 1 ? ' sortable' : ''; ?>">
                <thead>
                <tr>
                    <th class="order"></th>
                    <th class="name"><?php _e( 'Payment', 'learnpress' ); ?></th>
                    <th class="id"><?php _e( 'ID', 'learnpress' ); ?></th>
                    <th class="description"><?php _e( 'Description', 'learnpress' ); ?></th>
                    <th class="status"><?php _e( 'Status', 'learnpress' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $gateways as $gateway ) { ?>
                    <tr id="payment-<?php echo $gateway->get_id(); ?>" data-payment="<?php echo $gateway->get_id(); ?>">
                        <td class="order"><span class="dashicons dashicons-menu"></span></td>
                        <td class="name">
                            <a href="<?php echo esc_url( admin_url( 'admin.php?page=learn-press-settings&tab=payments&section=' . $gateway->get_id() ) ); ?>"><?php echo $gateway->get_method_title(); ?></a>
                        </td>
                        <td class="id"><?php echo $gateway->get_id(); ?></td>
                        <td class="description"><?php echo $gateway->get_method_description(); ?></td>
                        <td class="status<?php echo $gateway->is_enabled() ? ' enabled' : ''; ?>">
                            <span class="dashicons dashicons-yes"></span>
                            <input type="hidden" name="payment-order" value="<?php echo $gateway->get_id(); ?>"/>
                        </td>
                    </tr>
				<?php } ?>
                </tbody>
            </table>
			<?php
			return ob_get_clean();
		}
	}
}