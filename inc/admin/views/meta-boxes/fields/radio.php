<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author tungnx
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Radio_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
	 * @param string $id
	 * @param string $label
	 * @param string $description
	 * @param mixed $default
	 * @param array $extra
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

		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$wrapper_attr           = $field['wrapper_attr'] ?? [];
		$field['default']       = ( ! $this->meta_value( $thepostid ) && isset( $field['default'] ) ) ? $field['default'] : $this->meta_value( $thepostid );
		$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
		$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

		$dependency_check = $this->extra['dependency'] ?? [];
		if ( ! empty( $dependency_check ) ) {
			if ( $dependency_check['is_disable'] ) {
				$field['wrapper_class'] .= ' lp-option-disabled';
			}

			$wrapper_attr[] = 'data-dependency=' . $dependency_check['name'];
		}

		printf(
			'<fieldset class="form-field %s" %s><label for="%s">%s</label>',
			esc_attr( $this->id . '_field ' . $field['wrapper_class'] ),
			implode( ' ', $wrapper_attr ),
			esc_attr( $this->id ),
			wp_kses_post( $this->label )
		);

		if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
			learn_press_quick_tip( $field['description'] );
		}

		echo '<ul class="lp-radios-field-meta-box">';

		foreach ( $field['options'] as $key => $value ) {
			echo '<li><label><input
				name="' . esc_attr( $field['name'] ) . '"
				value="' . esc_attr( $key ) . '"
				type="radio"
				class="' . esc_attr( $field['class'] ) . '"
				style="' . esc_attr( $field['style'] ) . '"
				' . checked( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '
				/> ' . ( $value ) . '</label>
		</li>';
		}
		echo '</ul>';

		if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
			echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
		}

		echo '</fieldset>';
	}

	public function save( $post_id ) {
		$value = LP_Request::get_param( $this->id, $this->default ?? '' );
		update_post_meta( $post_id, $this->id, $value );

		return $value;
	}
}
