<?php
/**
 * Template for displaying paypal settings of setup wizard.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

$settings = LP()->settings();

?>
<table>
    <tr>
        <th><?php _e( 'Enable', 'learnpress' ); ?></th>
        <td>
            <input type="checkbox" name="settings[paypal][enable]"
                   value="yes" <?php checked( 'yes' == $settings->get( 'paypal.enable' ) ); ?>>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Paypal Email', 'learnpress' ); ?></th>
        <td>
            <input class="regular-text" type="email" name="settings[paypal][paypal_email]"
                   value="<?php echo $settings->get( 'paypal.paypal_email', '' ); ?>">
            <p class="description">
		        <?php _e( 'Your Paypal email in live mode.', 'learnpress'  ); ?>
            </p>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Sandbox Mode', 'learnpress' ); ?></th>
        <td>
            <input type="checkbox" name="settings[paypal][paypal_sandbox]"
                   value="yes" <?php checked( 'yes' == $settings->get( 'paypal.paypal_sandbox' ) ); ?>>
            <p class="description">
				<?php printf( __( 'For testing purpose with Paypal sandbox mode. Create an account <a href="%s">here</a>!', 'learnpress' ), 'https://developer.paypal.com/developer/accounts/' ); ?>
            </p>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Paypal Sandbox Email', 'learnpress' ); ?></th>
        <td>
            <input class="regular-text" type="email" name="settings[paypal][paypal_sandbox_email]"
                   value="<?php echo $settings->get( 'paypal.paypal_sandbox_email', '' ); ?>">
            <p class="description">
		        <?php _e( 'Your Paypal email in sandbox mode.', 'learnpress'  ); ?>
            </p>
        </td>
    </tr>
</table>