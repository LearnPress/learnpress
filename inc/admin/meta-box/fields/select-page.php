<?php
$args = array(
	'name'             => $value['id'],
	'id'               => $value['id'],
	'sort_column'      => 'menu_order',
	'sort_order'       => 'ASC',
	'show_option_none' => ' ',
	'class'            => $value['class'],
	'echo'             => false,
	'selected'         => absint( $value['value'] ),
	'post_status'      => 'publish,private,draft',
);

if ( isset( $value['args'] ) ) {
	$args = wp_parse_args( $value['args'], $args );
}
?>

<tr valign="top" class="single_select_page">
	<th scope="row" class="titledesc">
		<label><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
	</th>
	<td class="forminp">
	<?php echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'learnpress' ) . "' style='" . $value['css'] . "' class='" . $value['class'] . "' id=", wp_dropdown_pages( $args ) ); ?> <?php echo wp_kses_post( $description ); ?>
	</td>
</tr>
