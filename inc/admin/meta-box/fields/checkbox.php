<?php
$option_value     = $value['value'];
$visibility_class = array();

if ( ! isset( $value['hide_if_checked'] ) ) {
	$value['hide_if_checked'] = false;
}

if ( ! isset( $value['show_if_checked'] ) ) {
	$value['show_if_checked'] = false;
}

if ( 'yes' === $value['hide_if_checked'] || 'yes' === $value['show_if_checked'] ) {
	$visibility_class[] = 'hidden_option';
}

if ( 'option' === $value['hide_if_checked'] ) {
	$visibility_class[] = 'hide_options_if_checked';
}

if ( 'option' === $value['show_if_checked'] ) {
	$visibility_class[] = 'show_options_if_checked';
}
?>

<?php if ( ! isset( $value['checkboxgroup'] ) || 'start' === $value['checkboxgroup'] ) : ?>
	<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
		<th scope="row" class="titledesc"><?php echo $value['title']; ?></th>
		<td class="forminp forminp-checkbox">
			<fieldset>
<?php else : ?>
	<fieldset class="<?php echo esc_attr( implode( ' ', $visibility_class ) ); ?>">
<?php endif; ?>

<?php if ( ! empty( $value['title'] ) ) : ?>
	<legend class="screen-reader-text"><span><?php echo $value['title']; ?></span></legend>
<?php endif; ?>

	<label for="<?php echo esc_attr( $value['id'] ); ?>">
		<input
			name="<?php echo esc_attr( $value['id'] ); ?>"
			id="<?php echo esc_attr( $value['id'] ); ?>"
			type="checkbox"
			class="<?php echo esc_attr( isset( $value['class'] ) ? $value['class'] : '' ); ?>"
			value="1"
		<?php checked( $option_value, 'yes' ); ?>
		<?php echo implode( ' ', $custom_attributes ); ?>
		/> <?php echo $description; ?>
	</label> <?php echo $tooltip_html; ?>

<?php if ( ! isset( $value['checkboxgroup'] ) || 'end' === $value['checkboxgroup'] ) : ?>
			</fieldset>
		</td>
	</tr>
<?php else : ?>
	</fieldset>
	<?php
endif;
