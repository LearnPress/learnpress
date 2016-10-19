<?php
$option_value     = $this->get_option( $options['id'], $options['default'] );
$visbility_class = array();
?>
<tr valign="top" class="<?php echo esc_attr( implode( ' ', $visbility_class ) ); ?>">
	<th scope="row" class="titledesc"><?php echo esc_html( $options['title'] ) ?></th>
	<td class="forminp forminp-checkbox">
		<fieldset>
			<?php if ( $options['options'] ) : foreach ( $options['options'] as $value => $text ) : ?>
                                <p>
                                    <label for="<?php echo esc_attr( $options['id'] . $value ) ?>">
                                        <input type="radio" name="<?php echo esc_attr( $options['id'] ) ?>" value="<?php echo esc_attr( $value ) ?>" class="<?php echo esc_attr( isset( $options['class'] ) ? $options['class'] : '' ); ?>" id="<?php echo esc_attr( $options['id'] . $value ) ?>" <?php checked( $option_value, $value ) ?>/>
                                        <?php echo esc_html( $text ) ?>
                                    </label>
                                </p>
                        <?php endforeach; endif; ?>
		</fieldset>
	</td>
</tr>
