<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Textarea_Field extends LP_Meta_Box_Field {

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
		if ( empty( $this->id ) ) {
			return;
		}

		$field                = $this->extra;
		$field['id']          = $this->id;
		$field['default']     = $this->default;
		$field['description'] = $this->description;
		$field['label']       = $this->label;

		$field['placeholder'] = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field['class']       = isset( $field['class'] ) ? $field['class'] : 'short';
		$field['style']       = isset( $field['style'] ) ? $field['style'] : '';
		$field['default']     = ( ! $this->meta_value( $thepostid ) && isset( $field['default'] ) ) ? $field['default'] : $this->meta_value( $thepostid );
		$field['value']       = isset( $field['value'] ) ? $field['value'] : $field['default'];
		$field['desc_tip']    = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
		$field['name']        = isset( $field['name'] ) ? $field['name'] : $field['id'];

		// Custom attribute handling
		$custom_attributes = array();
		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
			}
		}

		echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . '">
			<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		echo '<textarea class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '"  name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" rows="5" ' . implode(
			' ',
			$custom_attributes
		) . '>' . esc_textarea( $field['value'] ) . '</textarea> ';

		if ( ! empty( $field['description'] ) ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

			if ( ! empty( $field['desc_tip'] ) ) {
				learn_press_quick_tip( $field['desc_tip'] );
			}
		}

		echo '</p>';
	}

	public function save( $post_id ) {
		$value = ! empty( $_POST[ $this->id ] ) ? wp_kses_post( wp_unslash( $_POST[ $this->id ] ) ) : '';

		update_post_meta( $post_id, $this->id, $value );
	}
}
