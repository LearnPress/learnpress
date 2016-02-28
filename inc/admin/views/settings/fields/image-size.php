<?php
if( empty( $options['default'] ) ){
	$options['default'] = array( null, null, null );
}
?>
<tr>
	<th scope="row" class="titledesc"><?php echo esc_html( $options['title'] ) ?></th>
	<td>
		<input type="text" size="4" name="<?php echo $options['id'];?>[width]" value="<?php echo $this->get_option( $options['id'] . '.width', $options['default'][0] ); ?>" placeholder="" />
		&times;
		<input type="text" size="4" name="<?php echo $options['id'];?>[height]" value="<?php echo $this->get_option( $options['id'] . '.height', $options['default'][1] ); ?>" placeholder="" />
		<?php _e( 'px', 'learnpress' );?>
		&nbsp;&nbsp;&nbsp;
		<input type="hidden" name="<?php echo $options['id'];?>[crop]" value="no" />
		<label>
			<input type="checkbox" name="<?php echo $options['id'];?>[crop]" value="yes" <?php checked( $this->get_option( $options['id'] . '.crop', $options['default'][2] ) == 'yes' ); ?> />
			<?php _e( 'Crop?', 'learn_pres' );?>
		</label>
	</td>
</tr>