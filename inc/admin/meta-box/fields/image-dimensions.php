<?php
$size   = $value['value'];
$width  = isset( $size['width'] ) ? $size['width'] : $value['default'][0];
$height = isset( $size['height'] ) ? $size['height'] : $value['default'][1];
$crop   = isset( $size['crop'] ) ? $size['crop'] : $value['default'][2];
?>
<tr valign="top">
	<th scope="row" class="titledesc">
	<label>
		<?php echo esc_html( $value['title'] ); ?>
		<?php echo wp_kses_post( $tooltip_html ); ?>
	</label>
</th>
	<td class="forminp image_width_settings">
		<input name="<?php echo esc_attr( $value['id'] ); ?>[width]"
			id="<?php echo esc_attr( $value['id'] ); ?>-width" type="text"
			size="3" value="<?php echo esc_attr( $width ); ?>" />&times;
		<input name="<?php echo esc_attr( $value['id'] ); ?>[height]"
			id="<?php echo esc_attr( $value['id'] ); ?>-height"
			type="text" size="3"
			value="<?php echo esc_attr( $height ); ?>" />px
		</td>
</tr>
