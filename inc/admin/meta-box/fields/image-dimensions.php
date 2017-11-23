<?php

/**
 * Class RWMB_Thumbnail_Dimensions
 */
class RWMB_Image_Dimensions_Field extends RWMB_Field {

	/**
	 * Get field HTML
	 *
	 * @param mixed $meta
	 * @param mixed $field
	 *
	 * @return string
	 */
	public static function html( $meta, $field ) {
		$meta = self::sanitize_meta( $meta );
		ob_start();
		?>
        <input type="text" size="4" name="<?php echo $field['id']; ?>[width]"
               value="<?php echo $meta['width']; ?>"
               placeholder=""/>
        <span class="lp-sign-times">&times;</span>
        <input type="text" size="4" name="<?php echo $field['id']; ?>[height]"
               value="<?php echo $meta['height']; ?>"
               placeholder=""/>
        <span><?php _e( 'px', 'learnpress' ); ?></span>
        <span class="lp-sign-times">&nbsp;&nbsp;&nbsp;</span>
        <input type="hidden" name="<?php echo $field['id']; ?>[crop]" value="no"/>
		<?php
		return ob_get_clean();
	}

	public static function value( $new, $old, $post_id, $field ) {
		return empty( $new ) ? 'no' : 'yes';
	}

	public static function begin_html( $html, $meta, $field = '' ) {
		if ( is_array( $field ) && isset( $field['field_name'] ) ) {
			return RW_Meta_Box::begin_html( $html, $meta, $field );
		} else {
			return RWMB_Field::begin_html( $html, $meta );
		}

	}

	protected static function sanitize_meta( $meta ) {
		settype( $meta, 'array' );
		if ( sizeof( $meta ) === 3 && empty( $meta['width'] ) ) {
		    print_r($meta); echo 'xxxxxxx';
			$meta = array(
				'width'  => $meta[0],
				'height' => $meta[1],
				'crop'   => $meta[2]
			);
		}

		return $meta;
	}
}