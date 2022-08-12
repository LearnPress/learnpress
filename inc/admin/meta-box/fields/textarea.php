<?php $option_value = apply_filters( 'lp_metabox_setting_ouput_textarea', esc_textarea( $value['value'] ), $value, $value['value'] ); ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>">
			<?php echo esc_html( $value['title'] ); ?>
			<?php echo wp_kses_post( $tooltip_html ); ?>
		</label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
	<?php echo wp_kses_post( $description ); ?>

		<textarea
			name="<?php echo esc_attr( $value['id'] ); ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			style="<?php echo esc_attr( $value['css'] ); ?>"
			class="<?php echo esc_attr( $value['class'] ); ?>"
			placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); ?>
			><?php echo wp_kses_post( $option_value ); ?></textarea>
	</td>
</tr>
