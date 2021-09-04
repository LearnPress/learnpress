<?php

/**
 * LP_Meta_Box_Repeater
 *
 * @author Nhamdv
 * @version 1.0.0
 * @since 4.1.4
 */
class LP_Meta_Box_Repeater_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param mixed  $default
	 * @param array  $extra : fields for repeater, add_text for Add more text, title_text for Title
	 */
	public function __construct( $label = '', $description = '', $default = '', $extra = array() ) {
		parent::__construct( $label, $description, $default, $extra );
	}

	public function output( $thepostid ) {
		$repeaters = $this->meta_value( $thepostid );
		?>

		<div class="form-field lp_repeater_meta_box">
			<label for="_lp_key_features"><?php echo $this->label; ?></label>
			<div class="lp_repeater_meta_box__wrapper">
				<div class="lp_repeater_meta_box__fields">
					<?php if ( ! empty( $repeaters ) ) : ?>
						<?php foreach ( $repeaters as $key => $repeater ) : ?>
							<?php $this->repeater_html( $thepostid, $repeater, false, $key ); ?>
						<?php endforeach; ?>
					<?php endif; ?>
				</div>

				<a href="#" class="button button-primary lp_repeater_meta_box__add"
					data-add="
					<?php
					ob_start();
					$this->repeater_html( $thepostid, false, true );
					echo esc_attr( ob_get_clean() );
					?>
					">
					<?php echo isset( $this->extra['add_text'] ) ? $this->extra['add_text'] : esc_html__( '+ Add more', 'learnpress' ); ?>
				</a>
			</div>
		</div>

		<?php
	}

	public function save( $post_id ) {
		$data   = isset( $_POST[ $this->id ] ) ? wp_unslash( $_POST[ $this->id ] ) : array();
		$output = array();

		if ( ! empty( $data ) && is_array( $data ) ) {
			foreach ( $data as $key => $val ) {
				foreach ( $this->extra['fields'] as $field_key => $field ) {
					if ( get_class( $field ) === 'LP_Meta_Box_Checkbox_Field' ) {
						$val[ $field_key ] = isset( $val[ $field_key ] ) ? 'yes' : 'no';
					}
				}

				$sort_key = $val['sort'];
				unset( $val['sort'] );

				$output[ $sort_key ] = $val;
			}
		}

		update_post_meta( $post_id, $this->id, $output );
	}

	public function repeater_html( $thepostid, $repeater, $is_attr = false, $key = 'lp_metabox_repeater_key' ) {
		?>
		<div class="lp_repeater_meta_box__field <?php echo $is_attr ? 'lp_repeater_meta_box__field_active' : ''; ?>">
			<input class="lp_repeater_meta_box__field__count" type="hidden" value="<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $this->id ) . '[' . $key . ']' . '[sort]'; ?>">
			<div class="lp_repeater_meta_box__title">
				<span class="lp_repeater_meta_box__title__sort"></span>
				<span class="lp_repeater_meta_box__title__title">
					<?php echo isset( $this->extra['title_text'] ) ? $this->extra['title_text'] : esc_html__( 'Repeater', 'learnpress' ); ?>
					<span><?php echo esc_html( absint( $key ) + 1 ); ?></span>
				</span>
				<a href="#" class="lp_repeater_meta_box__title__delete"></a>
				<a href="#" class="lp_repeater_meta_box__title__toggle"></a>
			</div>
			<div class="lp_repeater_meta_box__content">
				<?php
				if ( isset( $this->extra['fields'] ) ) {
					foreach ( $this->extra['fields'] as $field_key => $field ) {
						$field->id             = $this->id . '[' . $key . '][' . $field_key . ']';
						$field->extra['value'] = $is_attr ? '' : $repeater[ $field_key ];
						echo $field->output( $thepostid );
					}
				}
				?>
			</div>
		</div>
		<?php
	}
}
