<?php $option_value = $value['value']; ?>

<tr valign="top">
	<th scope="row" class="titledesc">
		<label for="<?php echo esc_attr( $value['id'] ); ?>"><?php echo $value['title']; ?> <?php echo $tooltip_html; ?></label>
	</th>
	<td class="forminp lp-metabox-field__image-advanced forminp-<?php echo esc_attr( sanitize_title( $value['type'] ) ); ?>">&lrm;
		<ul class="lp-metabox-field__image-advanced-images">
			<?php
			$update_meta         = false;
			$updated_gallery_ids = array();

			if ( ! empty( $option_value ) ) {
				foreach ( $option_value as $attachment_id ) {
					$attachment = wp_get_attachment_image( $attachment_id, 'thumbnail' );

					if ( empty( $attachment ) ) {
						$update_meta = true;
						continue;
					}
					?>
					<li class="image" data-attachment_id="<?php echo esc_attr( $attachment_id ); ?>">
						<?php echo $attachment; ?>
						<ul class="actions">
							<li><a href="#" class="delete"><?php esc_html_e( 'Delete', 'learnpress' ); ?></a></li>
						</ul>
					</li>
					<?php
					$updated_gallery_ids[] = $attachment_id;
				}
			}
			?>
		</ul>

		<input type="hidden" id="lp-gallery-images-ids" name="<?php echo esc_attr( $value['id'] ); ?>" value="<?php echo esc_attr( implode( ',', $updated_gallery_ids ) ); ?>" />

		<p class="lp-metabox-field__image-advanced-upload hide-if-no-js">
			<a href="#" data-choose="<?php esc_attr_e( 'Add images', 'learnpress' ); ?>" data-update="<?php esc_attr_e( 'Add to gallery', 'learnpress' ); ?>" data-delete="<?php esc_attr_e( 'Delete image', 'learnpress' ); ?>" data-text="<?php esc_attr_e( 'Delete', 'learnpress' ); ?>"><?php esc_html_e( 'Add images', 'learnpress' ); ?></a>
		</p>
	</td>
</tr>
