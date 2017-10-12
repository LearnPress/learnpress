<h2><?php _e( 'Currency', 'learnpress' ); ?></h2>

<table>
    <tr>
        <th><?php _e( 'Currency', 'learnpress' ); ?></th>
        <td>
            <select name="setup[currency]">
				<?php
				if ( $payment_currencies = learn_press_get_payment_currencies() ) {
					foreach ( $payment_currencies as $code => $symbol ) {
						?>
                        <option value="<?php echo $code; ?>"><?php echo $symbol; ?></option>
						<?php
					}
				} ?>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Currency position', 'learnpress' ); ?></th>
        <td>
            <select name="setup[currency_position]">
				<?php
				$positions = array();
				foreach ( learn_press_currency_positions() as $pos => $text ) {
					switch ( $pos ) {
						case 'left':
							$text = sprintf( '%s ( %s%s )', $text, learn_press_get_currency_symbol(), '69.99' );
							break;
						case 'right':
							$text = sprintf( '%s ( %s%s )', $text, '69.99', learn_press_get_currency_symbol() );
							break;
						case 'left_with_space':
							$text = sprintf( '%s ( %s %s )', $text, learn_press_get_currency_symbol(), '69.99' );
							break;
						case 'right_with_space':
							$text = sprintf( '%s ( %s %s )', $text, '69.99', learn_press_get_currency_symbol() );
							break;
					}
					?>
                    <option value="<?php echo $pos; ?>"><?php echo $text; ?></option><?php
				}
				?>
            </select>
        </td>
    </tr>
    <tr>
        <th><?php _e( 'Thousands Separator', 'learnpress' ); ?></th>
        <td><input type="text" name="setup[thousands_separator]" value=","></td>
    </tr>
    <tr>
        <th><?php _e( 'Decimals Separator', 'learnpress' ); ?></th>
        <td><input type="text" name="setup[decimals_separator]" value="."></td>
    </tr>
    <tr>
        <th><?php _e( 'Number of Decimals', 'learnpress' ); ?></th>
        <td><input type="text" name="setup[number_of_decimals]" value="2"></td>
    </tr>
</table>