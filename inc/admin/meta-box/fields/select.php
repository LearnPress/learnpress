<?php $option_value = $value['value']; ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
		<select
			name="<?php echo esc_attr( $value['id'] ); ?><?php echo ( 'multiselect' === $value['type'] ) ? '[]' : ''; ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			style="<?php echo esc_attr( $value['css'] ); ?>"
			class="<?php echo esc_attr( $value['class'] ); ?>"
	<?php echo implode( ' ', $custom_attributes ); ?>
	<?php echo 'multiselect' === $value['type'] ? 'multiple="multiple"' : ''; ?>
			>
	<?php foreach ( $value['options'] as $key => $val ) { ?>
			<option value="<?php echo esc_attr( $key ); ?>"
			<?php

			if ( is_array( $option_value ) ) {
				selected( in_array( (string) $key, $option_value, true ), true );
			} else {
				selected( $option_value, (string) $key );
			}

			?>
				><?php echo esc_html( $val ); ?></option>
				<?php
	}
	?>
		</select> <?php echo $description; ?>
	</td>
</tr>
