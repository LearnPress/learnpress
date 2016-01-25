<?php
$option_value = self::get_option( $options['id'], $options['default'] );
?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
		<?php //echo $tooltip_html; ?>
	</th>
	<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
		<?php if( !empty( $options['editor'] ) && $options['editor'] == true ){?>
			<?php wp_editor( $option_value, $options['id'], array( 'textarea_name' => $options['id'] ) );?>
		<?php }else{ ?>
		<textarea
			name="<?php echo esc_attr( $options['id'] ); ?>"
			id="<?php echo esc_attr( $options['id'] ); ?>"
			style="<?php echo esc_attr( $options['css'] ); ?>"
			class="<?php echo esc_attr( $options['class'] ); ?>"
			placeholder="<?php echo esc_attr( $options['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); ?>
			><?php echo esc_textarea( $option_value ); ?></textarea>
		<?php }?>
		<?php echo $description; ?>
	</td>
</tr>