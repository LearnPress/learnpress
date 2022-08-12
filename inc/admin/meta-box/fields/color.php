<?php $option_value = $value['value']; ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>">
			<?php echo wp_kses_post( $value['title'] ); ?><?php echo wp_kses_post( $tooltip_html ); ?>
		</label>
	</th>
	<td class="forminp lp-metabox-field__color forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
		<span class="colorpickpreview" style="background: <?php echo esc_attr( $option_value ); ?>">&nbsp;</span>
		<input
			name="<?php echo esc_attr( $value['id'] ); ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			type="text"
			dir="ltr"
			style="<?php echo esc_attr( $value['css'] ); ?>"
			value="<?php echo esc_attr( $option_value ); ?>"
			class="<?php echo esc_attr( $value['class'] ); ?> colorpick lp-metabox__colorpick"
			placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); ?>
			/>
		<?php echo wp_kses_post( $description ); ?>
		<div id="colorPickerDiv_<?php echo esc_attr( $value['id'] ); ?>"
			class="colorpickdiv"
			style="z-index: 100;background:#eee;border:1px solid #ccc;position:absolute;display:none;"></div>
	</td>
</tr>
