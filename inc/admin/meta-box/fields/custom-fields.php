<?php $option_value = $value['value']; ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo esc_html( $value['title'] ); ?> <?php echo wp_kses_post( $tooltip_html ); ?></label>
	</th>
	<td class="forminp forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?> lp-metabox__custom-fields">
		<table class="widefat">
			<thead>
				<tr>
					<th class="sort">&nbsp;</th>
					<?php
					if ( $value['options'] ) {
						foreach ( $value['options'] as $key => $val ) {
							?>
							<th><?php echo wp_kses_post( $value['options'][ $key ]['title'] ); ?> <?php echo isset( $value['options'][ $key ]['desc_tip'] ) ? learn_press_quick_tip( $value['options'][ $key ]['desc_tip'], false ) : ''; ?></th>
							<?php
						}
					}
					?>
					<th>&nbsp;</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ( $option_value ) {
					foreach ( $option_value as $key => $values ) {
						lp_metabox_custom_fields( $value, $values, $key );
					}
				}
				?>
			</tbody>
			<tfoot>
				<tr>
					<th colspan="5">
						<a href="#" class="button insert lp-metabox-custom-field-button" data-row="
							<?php
							ob_start();
							lp_metabox_custom_fields( $value, '', 'lp_metabox_custom_fields_key' );
							echo esc_attr( ob_get_clean() );
							?>
						"><?php esc_html_e( 'Add new', 'learnpress' ); ?></a>
					</th>
				</tr>
			</tfoot>
		</table>
		<?php echo wp_kses_post( $description ); ?>
	</td>
</tr>
