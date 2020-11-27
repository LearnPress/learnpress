<?php $option_value = $value['value']; ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['title']; ?> <?php echo $tooltip_html; ?></label>
	</th>
	<td class="forminp lp-metabox-field__image forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
		<div class="lp-metabox-field__image--image">
				<?php if ( ! empty( $option_value ) ) : ?>
					<div class="lp-metabox-field__image--inner">
						<?php echo wp_get_attachment_image( $option_value, 'thumbnail' ); ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<input type="hidden" class="lp-metabox-field__image--id" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( $option_value ); ?>" />

		<p class="lp-metabox-field__image--upload hide-if-no-js">
			<a href="#" class="lp-metabox-field__image--add button" data-choose="<?php esc_attr_e( 'Select images', 'learnpress' ); ?>" data-update="<?php esc_attr_e( 'Select', 'learnpress' ); ?>"><?php esc_html_e( 'Upload', 'learnpress' ); ?></a>
			<a href="#" class="lp-metabox-field__image--delete button" style="display: none;"><?php esc_html_e( 'Delete', 'learnpress' ); ?></a>
		</p>
	</td>
</tr>
