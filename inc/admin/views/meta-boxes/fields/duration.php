<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Duration_Field extends LP_Meta_Box_Field {

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

		$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['default']       = ( ! $this->meta_value( $thepostid ) && isset( $field['default'] ) ) ? $field['default'] : $this->meta_value( $thepostid );
		$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$data_type              = empty( $field['data_type'] ) ? '' : $field['data_type'];
		$duration               = learn_press_get_course_duration_support();

		$duration_keys = array_keys( $duration );
		$default_time  = ! empty( $field['default_time'] ) ? $field['default_time'] : end( $duration_keys );

		if ( preg_match_all( '!([0-9]+)\s*(' . join( '|', $duration_keys ) . ')?!', $field['value'], $matches ) ) {
			$a1 = $matches[1][0];
			$a2 = in_array( $matches[2][0], $duration_keys ) ? $matches[2][0] : $default_time;
		} else {
			$a1 = absint( $field['value'] );
			$a2 = $default_time;
		}

		// Custom attribute handling
		$custom_attributes = array();

		if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
			foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
			}
		}

		$html_option = '';
		foreach ( $duration as $k => $v ) {
			$html_option .= sprintf( '<option value="%s" %s>%s</option>', $k, selected( $k, $a2, false ), $v );
		}

		echo '<p class="lp-meta-box__duration form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

		echo '<input type="number" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '[]" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $a1 ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';

		echo '<select name="' . esc_attr( $field['name'] ) . '[]" class="lp-meta-box__duration-select">' . $html_option . '</select>';

		if ( ! empty( $field['description'] ) ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

			if ( ! empty( $field['desc_tip'] ) ) {
				learn_press_quick_tip( $field['desc_tip'] );
			}
		}

		echo '</p>';
	}

	public function save( $post_id ) {
		$duration      = learn_press_get_course_duration_support();
		$duration_keys = array_keys( $duration );
		$default_time  = ! empty( $this->extra['default_time'] ) ? $this->extra['default_time'] : end( $duration_keys );

		$duration = isset( $_POST[ $this->id ][0] ) && $_POST[ $this->id ][0] !== '' ? implode( ' ', wp_unslash( $_POST[ $this->id ] ) ) : absint( $this->default ) . ' ' . trim( $default_time );

		update_post_meta( $post_id, $this->id, $duration );
	}
}
