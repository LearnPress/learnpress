<?php

if ( ! isset( $value ) ) {
	return;
}

$option_value     = $value['value'];
$visibility_class = array();

if ( isset( $value['show_if_checked'] ) ) {
	$visibility_class[] = 'show_if_' . $value['show_if_checked'];

	if ( 'no' === LP_Settings::get_option( $value['show_if_checked'] ) ) {
		$visibility_class[] = 'lp-option-disabled';
	}
}
?>
<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
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
			<?php if ( isset( $value['is_optgroup'] ) && ! empty( $value['is_optgroup'] ) ) : ?>
				<?php foreach ( $value['options'] as $optgroup_label => $optgroup ) : ?>
					<optgroup label="<?php echo esc_html( ucfirst( $optgroup_label ) ); ?>">
						<?php foreach ( $optgroup as $key => $val ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>"
								<?php
								if ( is_array( $option_value ) ) {
									selected( in_array( (string) $key, $option_value, true ), true );
								} else {
									selected( $option_value, (string) $key );
								}
								?>
								><?php echo esc_html( $val ); ?></option>
						<?php endforeach; ?>
					</optgroup>
				<?php endforeach; ?>
			<?php else : ?>
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
			<?php endif; ?>
		</select><?php echo wp_kses_post( $description ); ?>
	</td>
</tr>
