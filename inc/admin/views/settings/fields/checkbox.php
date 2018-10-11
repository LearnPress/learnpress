<?php
$option_value     = $this->get_option( $options['id'], $options['default'] );
$visbility_class = array();
if ( !isset( $options['hide_if_checked'] ) ) {
	$options['hide_if_checked'] = false;
}
if ( !isset( $options['show_if_checked'] ) ) {
	$options['show_if_checked'] = false;
}
if ( 'yes' == $options['hide_if_checked'] || 'yes' == $options['show_if_checked'] ) {
	$visbility_class[] = 'hidden_option';
}
if ( 'option' == $options['hide_if_checked'] ) {
	$visbility_class[] = 'hide_options_if_checked';
}
if ( 'option' == $options['show_if_checked'] ) {
	$visbility_class[] = 'show_options_if_checked';
}

?>
<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
	<th scope="row" class="titledesc"><?php echo esc_html( $options['title'] ) ?></th>
	<td class="forminp forminp-checkbox">
		<fieldset>
			<?php if ( !empty( $options['title'] ) ) { ?>
				<legend class="screen-reader-text">
					<span><?php echo esc_html( $options['title'] ) ?></span>
				</legend>
			<?php } ?>
			<input name="<?php echo esc_attr( $options['id'] ); ?>"	type="hidden" value="no" />
			<label for="<?php echo $options['id'] ?>">
				<input
					name="<?php echo esc_attr( $options['id'] ); ?>"
					id="<?php echo esc_attr( $options['id'] ); ?>"
					type="checkbox"
					class="<?php echo esc_attr( isset( $options['class'] ) ? $options['class'] : '' ); ?>"
					value="yes"
					<?php checked( $option_value, 'yes' ); ?>
					<?php echo implode( ' ', $custom_attributes ); ?>
					/> <?php echo $description ?>
			</label>
		</fieldset>
	</td>
</tr>
