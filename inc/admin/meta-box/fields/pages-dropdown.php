<?php
$option_value = $value['value'];

$page_dropdown_args = array(
	'echo'     => true,
	'name'     => $value['id'],
	'selected' => $option_value,
);
?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo wp_kses_post( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
		<?php learn_press_pages_dropdown( $page_dropdown_args ); ?>
	</td>
</tr>
