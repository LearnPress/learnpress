<?php
/**
 * Support for type:
 * text, Password, datetime, number, email, url, tel..
 *
 * @version 4.0.0
 * @author Nhamdv <email@email.com>
 */

$option_value     = $value['value'];
$visibility_class = [];
if ( isset( $value['show_if_checked'] ) ) {
	$visibility_class[] = 'show_if_' . $value['show_if_checked'];

	if ( 'no' === LP_Settings::get_option( $value['show_if_checked'] ) ) {
		$visibility_class[] = 'hidden';
	}
}
?>

<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>">
			<?php echo wp_kses_post( $value['title'] ); ?>
			<?php echo wp_kses_post( $tooltip_html ); ?>
		</label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">
		<input
			name="<?php echo esc_attr( $value['id'] ); ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			type="<?php echo esc_attr( $value['type'] ); ?>"
			style="<?php echo esc_attr( $value['css'] ); ?>"
			value="<?php echo esc_attr( $option_value ); ?>"
			class="<?php echo esc_attr( $value['class'] ); ?>"
			placeholder="<?php echo esc_attr( $value['placeholder'] ); ?>"
			<?php echo isset( $value['min'] ) ? 'min="' . $value['min'] . '"' : ''; ?>
			<?php echo isset( $value['max'] ) ? 'max="' . $value['max'] . '"' : ''; ?>
			<?php echo implode( ' ', $custom_attributes ); ?>
			/><?php echo esc_html( $value['suffix'] ); ?> <?php echo wp_kses_post( $description ); ?>
	</td>
</tr>
