<?php
if ( empty( $options['default'] ) ) {
	$options['default'] = array( null, null, null );
}
?>
<tr>
	<th scope="row" class="titledesc"><?php echo esc_html( $options['title'] ) ?></th>
	<td>
		<div class="lp-setting-field lp-setting-field-image-size">
			<input type="text" size="4" name="<?php echo $options['id']; ?>[width]" value="<?php echo $this->get_option( $options['id'] . '.width', $options['default'][0] ); ?>" placeholder="" />
			<span class="lp-sign-times">&times;</span>
			<input type="text" size="4" name="<?php echo $options['id']; ?>[height]" value="<?php echo $this->get_option( $options['id'] . '.height', $options['default'][1] ); ?>" placeholder="" />
			<span><?php _e( 'px', 'learnpress' ); ?></span>
			<span class="lp-sign-times">&nbsp;&nbsp;&nbsp;</span>
			<input type="hidden" name="<?php echo $options['id']; ?>[crop]" value="no" />
			<!--<label>
				<input type="checkbox" name="<?php echo $options['id']; ?>[crop]" value="yes" <?php checked( $this->get_option( $options['id'] . '.crop', $options['default'][2] ) == 'yes' ); ?> />
				<?php _e( 'Crop?', 'learn_pres' ); ?>
			</label>-->
		</div>
	</td>
</tr>