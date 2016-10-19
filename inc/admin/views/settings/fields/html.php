<?php
$visbility_class = array();
if ( 'yes' == $options['hide_if_checked'] ) {
	$visbility_class[] = 'hide-if-js';
}
$visbility_class[] = $options['class'];
?>
<tr valign="top"class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
		<?php
		if( !empty( $options['html'] ) ){
			echo $options['html'];
		}
		?>
		<?php echo $description; ?>
	</td>
</tr>