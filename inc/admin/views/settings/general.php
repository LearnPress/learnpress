<?php
/**
 * Display html for general settings
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
	<?php do_action( 'learn_press_before_general_settings_fields', $settings ); ?>
	<?php foreach( $this->get_settings() as $field ){?>
		<?php $this->output_field( $field );?>
	<?php }?>

	<?php if(1 == 0){?>
	<tr>
		<th scope="row">
			<label><?php _e( 'Instructors registration', 'learnpress' ); ?></label>
		</th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name( 'instructor_registration' ); ?>" value="no">
			<input type="checkbox" name="<?php echo $this->get_field_name( 'instructor_registration' ); ?>" value="yes" <?php checked( $settings->get( 'instructor_registration' ) == 'yes', true ); ?> />

			<p class="description"><?php _e( 'Create option for instructors registration', 'learnpress' ); ?></p>
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Auto update post name', 'learnpress' ); ?></label></th>
		<td>
			<input type="hidden" name="<?php echo $this->get_field_name( 'auto_update_post_name' ); ?>" value="no">
			<input type="checkbox" name="<?php echo $this->get_field_name( 'auto_update_post_name' ); ?>" value="yes" <?php checked( $settings->get( 'auto_update_post_name' ) == 'yes', true ); ?> />
			<p class="description">
				<?php _e( 'The post\'s name will update along with the title when changes title of lesson or quiz  in course curriculum or question in quiz<br />The permalink also is changed, therefore uncheck this if you don\'t want to change the permalink', 'learnpress' );?>
			</p>
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Currency', 'learnpress' ); ?></label></th>
		<td>
			<select name="<?php echo $this->get_field_name( 'currency' ); ?>">
				<?php if ( $payment_currencies = learn_press_get_payment_currencies() ) foreach ( $payment_currencies as $code => $symbol ) { ?>
					<?php $selected = selected( $settings->get( 'currency' ) == $code ? 1 : 0, 1, false ); ?>
					<option <?php echo $selected; ?> value="<?php echo $code; ?>"><?php echo $symbol; ?></option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Currency Position', 'learnpress' ); ?></label>
		</th>
		<td>
			<select name="<?php echo $this->get_field_name( 'currency_pos' ); ?>">
				<?php foreach ( learn_press_currency_positions() as $pos => $text ) { ?>
					<option value="<?php echo $pos; ?>" <?php selected( $settings->get( 'currency_pos' ) == $pos ? 1 : 0, 1 ); ?>>
						<?php
						switch ( $pos ) {
							case 'left':
								printf( '%s ( %s%s )', $text, learn_press_get_currency_symbol(), '69.99' );
								break;
							case 'right':
								printf( '%s ( %s%s )', $text, '69.99', learn_press_get_currency_symbol() );
								break;
							case 'left_with_space':
								printf( '%s ( %s %s )', $text, learn_press_get_currency_symbol(), '69.99' );
								break;
							case 'right_with_space':
								printf( '%s ( %s %s )', $text, '69.99', learn_press_get_currency_symbol() );
								break;
						}
						?>
					</option>
				<?php } ?>
			</select>
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Thousands Separator', 'learnpress' ); ?></label></th>
		<td>
			<input class="regular_text" type="text" name="<?php echo $this->get_field_name( 'thousands_separator' ); ?>" value="<?php echo $settings->get( 'thousands_separator', ',' ); ?>" />
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Decimals Separator', 'learnpress' ); ?></label></th>
		<td>
			<input class="regular_text" type="text" name="<?php echo $this->get_field_name( 'decimals_separator' ); ?>" value="<?php echo $settings->get( 'decimals_separator', '.' ); ?>" />
		</td>
	</tr>
	<tr>
		<th><label><?php _e( 'Number of Decimals', 'learnpress' ); ?></label></th>
		<td>
			<input class="regular_text" type="text" name="<?php echo $this->get_field_name( 'number_of_decimals' ); ?>" value="<?php echo $settings->get( 'number_of_decimals', 2 ); ?>" />
		</td>
	</tr>
	<?php do_action( 'learn_press_after_general_settings_fields', $settings ); ?>
	<?php }?>
	</tbody>
</table>