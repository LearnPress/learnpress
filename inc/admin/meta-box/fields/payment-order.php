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
		static function html( $meta, $field = '' ) {
			$gateways = LP_Gateways::instance()->get_gateways();
			ob_start();
			?>
            <table class="learn-press-payments">
                <thead>
                <tr>
                    <th></th>
                    <th><?php _e( 'Payment', 'learnpress' ); ?></th>
                    <th><?php _e( 'ID', 'learnpress' ); ?></th>
                    <th class="status"><?php _e( 'Status', 'learnpress' ); ?></th>
                </tr>
                </thead>
                <tbody>
				<?php foreach ( $gateways as $gateway ) { ?>
                    <tr>
                        <td class="order"><span class="dashicons dashicons-menu"></span> </td>
                        <td class="name"><?php echo $gateway->method_title; ?></td>
                        <td class="description"><?php echo $gateway->method_description; ?></td>
                        <td class="status<?php echo $gateway->enabled ? ' enabled' : ' enabled';?>">
                            <span class="dashicons dashicons-yes"></span>
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