<?php

/**
 * LP_Meta_Box_Text_Field
 *
 * @author nhamdv
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Text_Field extends LP_Meta_Box_Field {

	/**
	 * Constructor.
	 *
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

		$extra         = $this->extra;
		$placeholder   = $extra['placeholder'] ?? '';
		$class         = ! empty( $extra['class'] ) ? 'class="' . esc_attr( $extra['class'] ) . '"' : '';
		$style         = ! empty( $extra['style'] ) ? 'style="' . esc_attr( $extra['style'] ) . '"' : '';
		$wrapper_class = ! empty( $extra['wrapper_class'] ) ? esc_attr( $extra['wrapper_class'] ) : '';
		$wrapper_attr  = $extra['wrapper_attr'] ?? [];

		$meta_exists = LP_Database::getInstance()->check_key_postmeta_exists( $thepostid, $this->id );
		$meta        = get_post_meta( $thepostid, $this->id, true );
		$value       = $meta_exists ? $meta : ( $this->default ?? '' );
		$value       = esc_attr( $extra['value'] ?? $value );
		$type_input  = $extra['type_input'] ?? 'text';
		$desc_tip    = $extra['desc_tip'] ?? '';

		// Custom attribute handling
		$custom_attributes = array();
		if ( ! empty( $extra['custom_attributes'] ) && is_array( $extra['custom_attributes'] ) ) {
			foreach ( $extra['custom_attributes'] as $attribute => $custom_attribute ) {
				$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
			}
		}

		$dependency_check = $extra['dependency'] ?? [];
		if ( ! empty( $dependency_check ) ) {
			if ( $dependency_check['is_disable'] ) {
				$wrapper_class .= ' lp-option-disabled';
			}

			$wrapper_attr[] = 'data-dependency=' . $dependency_check['name'];
		}

		printf(
			'<div class="form-field %s" %s><label for="%s">%s</label>',
			esc_attr( $this->id . '_field ' . $wrapper_class ),
			esc_attr( implode( ' ', $wrapper_attr ) ),
			esc_attr( $this->id ),
			wp_kses_post( $this->label )
		);

		printf(
			'<input type="%s" %s %s name="%s" id="%s" value="%s" placeholder="%s" %s />',
			esc_attr( $type_input ),
			$class,
			$style,
			$this->id,
			$this->id,
			$value,
			esc_attr( $placeholder ),
			implode( ' ', $custom_attributes )
		);

		if ( ! empty( $this->description ) ) {
			echo '<p class="description">';
			echo '<span>' . wp_kses_post( $this->description ) . '</span>';

			if ( ! empty( $desc_tip ) ) {
				learn_press_quick_tip( $desc_tip );
			}
			echo '</p>';
		}
		echo '</div>';
	}

	public function save( $post_id ) {
		$type_input = $this->extra['type_input'] ?? 'text';
		$meta_value = LP_Request::get_param( $this->id, $this->default ?? '' );

		if ( $meta_value !== '' && $type_input === 'number' ) {
			$meta_value = (float) $meta_value;
			if ( isset( $this->extra['custom_attributes']['max'] ) ) {
				$value_max = (float) $this->extra['custom_attributes']['max'];
				if ( $meta_value > $value_max ) {
					$meta_value = $value_max;
				}
			} elseif ( isset( $this->extra['custom_attributes']['min'] ) ) {
				$value_min = (float) $this->extra['custom_attributes']['min'];
				if ( $meta_value < $value_min ) {
					$meta_value = $value_min;
				}
			}
		}

		update_post_meta( $post_id, $this->id, $meta_value );

		return $meta_value;
	}
}
