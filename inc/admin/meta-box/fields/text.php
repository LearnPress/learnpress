<?php
/**
 * Support for type:
 * text, Password, datetime, number, email, url, tel..
 *
 * @version 4.0.0
 * @author Nhamdv <email@email.com>
 */

$option_value = $value['value'];
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['title']; ?> <?php echo $tooltip_html; ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
		<input
			name="<?php echo esc_attr( $value['id'] ); ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			type="<?php echo esc_attr( $value['type'] ); ?>"
			style="<?php echo esc_attr( $value['css'] ); ?>"
			value="<?php echo esc_attr( $option_value ); ?>"
			class="<?php echo esc_attr( $value['class'] ); ?>"
			placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
		<?php echo implode( ' ', $custom_attributes ); ?>
			/><?php echo esc_html( $value['suffix'] ); ?> <?php echo $description; ?>
	</td>
</tr>
