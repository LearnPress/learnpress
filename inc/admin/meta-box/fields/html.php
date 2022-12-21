<?php
if ( ! isset( $value ) || ! isset( $tooltip_html ) ) {
	return;
}
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label><?php echo wp_kses_post( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( $value['type'] ); ?>">
		<?php learn_press_echo_vuejs_write_on_php( $value['default'] ); ?>
	</td>
</tr>
