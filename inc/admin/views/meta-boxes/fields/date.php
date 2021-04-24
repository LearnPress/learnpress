<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Date_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param mixed  $default
	 * @param array  $extra
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		$date = $this->meta_value( $thepostid );
		?>

		<div class="lp_sale_dates_fields">
			<p class="form-field <?php echo esc_attr( $this->extra['wrapper_class'] ); ?>">
				<label for="_lp_sale_start"><?php echo $this->label; ?></label>
				<input type="text" class="short" name="<?php echo $this->id; ?>" id="<?php echo $this->id; ?>" value="<?php echo esc_attr( $date ); ?>" placeholder="<?php echo esc_attr( $this->extra['placeholder'] ); ?>" style="width:320px;" />

				<?php if ( ! empty( $this->extra['cancel'] ) ) : ?>
					<a href="#" class="description lp_cancel_sale_schedule"><?php esc_html_e( 'Cancel', 'learnpress' ); ?></a>
				<?php endif; ?>
			</p>
		</div>

		<?php
	}

	public function save( $post_id ) {
		$value = isset( $_POST[ $this->id ] ) ? wp_unslash( $_POST[ $this->id ] ) : '';
		update_post_meta( $post_id, $this->id, $value );
	}
}
