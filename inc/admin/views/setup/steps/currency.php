<?php
/**
 * Template for displaying currency form while setting up.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;

$settings = LP()->settings();

?>

<h2><?php _e( 'Currency', 'learnpress' ); ?></h2>

<table>
    <tr>
        <th><?php _e( 'Currency', 'learnpress' ); ?></th>
        <td>
            <select name="settings[currency][currency]">
				<?php
				if ( $payment_currencies = learn_press_get_payment_currencies() ) {
					foreach ( $payment_currencies as $code => $symbol ) {
						?>
                        <option value="<?php echo $code; ?>"<?php selected( $code == $settings->get( 'currency' ) ); ?>><?php echo $symbol; ?></option>
						<?php
					}
				} ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Currency position', 'learnpress' ); ?></th>
        <td>
            <select name="settings[currency][currency_pos]">
				<?php
				$positions = array();
				foreach ( learn_press_currency_positions() as $pos => $text ) {
					?>
                    <option value="<?php echo $pos; ?>"<?php selected( $pos == $settings->get( 'currency_pos' ) ); ?>><?php echo $text; ?></option><?php
				}
				?>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Thousands Separator', 'learnpress' ); ?></th>
        <td><input type="text" name="settings[currency][thousands_separator]"
                   value="<?php echo $settings->get( 'thousands_separator', ',' ); ?>"></td>
    </tr>
    <tr>
        <th><?php _e( 'Decimals Separator', 'learnpress' ); ?></th>
        <td><input type="text" name="settings[currency][decimals_separator]"
                   value="<?php echo $settings->get( 'decimals_separator', '.' ); ?>"></td>
    </tr>
    <tr>
        <th><?php _e( 'Number of Decimals', 'learnpress' ); ?></th>
        <td><input type="text" name="settings[currency][number_of_decimals]"
                   value="<?php echo $settings->get( 'number_of_decimals', '2' ); ?>"></td>
    </tr>
</table>