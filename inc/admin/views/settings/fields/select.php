<?php
$option_value = self::get_option( $options['id'], $options['default'] );
?>
<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
		<select
			name="<?php echo esc_attr( $options['id'] ); ?><?php if ( $options['type'] == 'multiselect' ) echo '[]'; ?>"
			id="<?php echo esc_attr( $options['id'] ); ?>"
			style="<?php echo esc_attr( $options['css'] ); ?>"
			class="<?php echo esc_attr( $options['class'] ); ?>"
			<?php echo implode( ' ', $custom_attributes ); ?>
			<?php echo ( 'multiselect' == $options['type'] ) ? 'multiple="multiple"' : ''; ?>
			>
			<?php
			foreach ( $options['options'] as $key => $val ) {
				?>
				<option value="<?php echo esc_attr( $key ); ?>" <?php

				if ( is_array( $option_value ) ) {
					selected( in_array( $key, $option_value ), true );
				} else {
					selected( $option_value, $key );
				}

				?>><?php echo $val ?></option>
				<?php
			}
			?>
		</select> <?php echo $description; ?>
	</td>
</tr>