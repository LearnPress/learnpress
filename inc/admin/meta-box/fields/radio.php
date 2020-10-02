<?php $option_value = $value['value']; ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo $tooltip_html; ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
		<fieldset>
			<?php echo $description; ?>
			<ul>
			<?php
			foreach ( $value['options'] as $key => $val ) {
				?>
				<li>
					<label><input
						name="<?php echo esc_attr( $value['id'] ); ?>"
						value="<?php echo esc_attr( $key ); ?>"
						type="radio"
						style="<?php echo esc_attr( $value['css'] ); ?>"
						class="<?php echo esc_attr( $value['class'] ); ?>"
						<?php echo implode( ' ', $custom_attributes ); ?>
						<?php checked( $key, $option_value ); ?>
						/> <?php echo esc_html( $val ); ?></label>
				</li>
				<?php
			}
			?>
			</ul>
		</fieldset>
	</td>
</tr>

