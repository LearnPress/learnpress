<?php
$option_value = self::get_option( $options['id'], $options['default'] );
?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
		<?php //echo $tooltip_html; ?>
	</th>
	<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
		<?php if ( !empty( $options['editor'] ) && $options['editor'] !== false ) { ?>
			<?php
			$editor_args = array( 'textarea_name' => $options['id'] );
			if ( is_array( $options['editor'] ) ) {
				$editor_args = array_merge( $editor_args, $options['editor'] );
			}
			wp_editor( $option_value, str_replace( array( '[', '][', ']' ), array( '_', '_', '' ), $options['id'] ), $editor_args );
			?>
		<?php } else { ?>
			<textarea
				name="<?php echo esc_attr( $options['id'] ); ?>"
				id="<?php echo esc_attr( $options['id'] ); ?>"
				style="<?php echo esc_attr( $options['css'] ); ?>"
				class="<?php echo esc_attr( $options['class'] ); ?>"
				placeholder="<?php echo esc_attr( $options['placeholder'] ); ?>"
				<?php echo implode( ' ', $custom_attributes ); ?>
			><?php echo esc_textarea( $option_value ); ?></textarea>
		<?php } ?>
		<?php echo $description; ?>
	</td>
</tr>