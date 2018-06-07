<?php
$type         = $options['type'];
$option_value = self::get_option( $options['id'], $options['default'] );

if ( $options['type'] == 'color' ) {
	$type = 'text';
	$options['class'] .= 'colorpick';
	$description .= '<div id="colorPickerDiv_' . esc_attr( $options['id'] ) . '" class="colorpickdiv" style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>';
}

?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
		<?php
		if ( 'color' == $options['type'] ) {
			echo '<span class="colorpickpreview" style="background: ' . esc_attr( $option_value ) . ';"></span>';
		}
		?>
		<input
			name="<?php echo esc_attr( $options['id'] ); ?>"
			id="<?php echo esc_attr( $options['id'] ); ?>"
			type="<?php echo esc_attr( $type ); ?>"
			style="<?php echo esc_attr( $options['css'] ); ?>"
			value="<?php echo esc_attr( $option_value ); ?>"
			class="<?php echo esc_attr( $options['class'] ); ?>"
			placeholder="<?php echo esc_attr( $options['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); ?>
			/> <?php echo $description; ?>
	</td>
</tr>