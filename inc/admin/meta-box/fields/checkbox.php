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

<?php if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) : ?>
	<tr class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
		<th scope="row" class="titledesc"><?php echo esc_html( $value['title'] ); ?></th>
		<td class="forminp forminp-checkbox">
			<fieldset>
<?php else : ?>
	<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
<?php endif; ?>

<?php if ( ! empty( $value['title'] ) ) : ?>
	<legend class="screen-reader-text"><span><?php echo esc_html( $value['title'] ); ?></span></legend>
<?php endif; ?>

	<label for="<?php echo esc_attr( $value['id'] ); ?>">
		<input
			name="<?php echo esc_attr( $value['id'] ); ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			type="checkbox"
			class="<?php echo esc_attr( $value['class'] ?? '' ); ?>"
			value="1"
		<?php checked( $option_value, 'yes' ); ?>
		<?php echo implode( ' ', $custom_attributes ?? array() ); ?>
		/> <?php echo wp_kses_post( $description ?? '' ); ?>
	</label> <?php echo wp_kses_post( $tooltip_html ?? '' ); ?>

<?php if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) : ?>
			</fieldset>
		</td>
	</tr>
<?php else : ?>
	</fieldset>
	<?php
endif;
