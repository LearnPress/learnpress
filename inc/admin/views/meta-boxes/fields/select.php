<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author Nhamdv
 * @version 1.0.1
 * @since 4.0.0
 */
class LP_Meta_Box_Select_Field extends LP_Meta_Box_Field {

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

	public function meta_value( $post_id ) {
		$multil_meta = $this->extra['multil_meta'] ?? false;

		return $multil_meta ? get_post_meta( $post_id, $this->id, false ) : get_post_meta( $post_id, $this->id, true );
	}

	public function output( $post_id ) {
		if ( empty( $this->id ) ) {
			return;
		}

		$field                = $this->extra;
		$field['id']          = $this->id;
		$field['default']     = $this->default;
		$field['description'] = $this->description;
		$field['label']       = $this->label;

		$field['multil_meta'] = $field['multil_meta'] ?? false;
		$meta                 = $this->meta_value( $post_id );

		$default = ( ! $meta && isset( $field['default'] ) ) ? $field['default'] : $meta;

		$field = wp_parse_args(
			$field,
			array(
				'class'             => 'select',
				'style'             => '',
				'wrapper_class'     => '', // Use "lp-select-2" for select2.
				'value'             => $field['value'] ?? $default,
				'name'              => $field['id'],
				'desc_tip'          => false,
				'multiple'          => false,
				'custom_attributes' => array(),
				'tom_select'        => false,
				'wrapper_attr'      => [],
			)
		);

		$field_attributes          = (array) $field['custom_attributes'];
		$field_attributes['style'] = $field['style'];
		$field_attributes['id']    = $field['id'];
		$field_attributes['name']  = $field['multiple'] ? $field['name'] . '[]' : $field['name'];
		if ( isset( $field['name_no_bracket'] ) ) {
			$field_attributes['name'] = $field['name'];
		}
		$field_attributes['class'] = $field['class'];

		if ( $field['multiple'] ) {
			$field_attributes['multiple'] = true;
		}

		if ( $field['tom_select'] ) {
			$field_attributes['class'] .= ' lp-tom-select';
			if ( ! empty( $field['ts-remove-button'] ) ) {
				$field_attributes['data-ts-remove-button'] = $field['ts-remove-button'];
			}
		} elseif ( $field['multiple'] ) {
			$field['wrapper_class'] = 'lp-select-2';
		}

		if ( isset( $field['data-saved'] ) ) {
			$field_attributes['data-saved'] = htmlentities2( json_encode( $field['data-saved'] ) );
		} elseif ( ! empty( $meta ) ) {
			$field_attributes['data-saved'] = htmlentities2( json_encode( $meta ) );
		} else {
			$field_attributes['data-saved'] = htmlentities2( json_encode( $default ) );
		}

		$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
		$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';

		$dependency_check = $field['dependency'] ?? [];
		if ( ! empty( $dependency_check ) ) {
			if ( $dependency_check['is_disable'] ) {
				$field['wrapper_class'] .= ' lp-option-disabled';
			}

			$field['wrapper_attr'][] = 'data-dependency=' . $dependency_check['name'];
		}
		?>

		<p class="form-field <?php echo esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ); ?>"
			<?php echo esc_attr( implode( ' ', $field['wrapper_attr'] ) ) ?>
			<?php learn_press_echo_vuejs_write_on_php( $this->condition ? $this->condition : '' ); ?>>
			<label for="<?php echo esc_attr( $field['id'] ); ?>">
				<?php echo wp_kses_post( $field['label'] ); ?>
			</label>
			<select <?php echo lp_implode_html_attributes( $field_attributes ); ?>>
				<option value="" hidden style="display: none"></option>
				<?php
				foreach ( $field['options'] as $key => $value ) {
					if ( is_array( $field['value'] ) ) {
						$selected = in_array( $key, $field['value'] ) ? 'selected="selected"' : '';
					} else {
						$selected = selected( $key, $field['value'], false );
					}
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $key ),
						esc_attr( $selected ),
						esc_html( $value )
					);
				}
				?>
			</select>
			<?php
			if ( ! empty( $field['description'] ) ) {
				echo '<span class="description">' . wp_kses_post( $description ) . '</span>';

				if ( ! empty( $field['desc_tip'] ) ) {
					learn_press_quick_tip( $tooltip );
				}
			}
			?>
		</p>
		<?php
	}

	public function save( $post_id ) {
		$multiple_meta = $this->extra['multil_meta'] ?? false;

		if ( ! empty( $this->extra['custom_save'] ) ) {
			do_action( 'learnpress/admin/metabox/select/save', $this->id, $_POST[ $this->id ] ?? '', $post_id );

			return '';
		}

		if ( $multiple_meta ) {
			$data       = LP_Request::get_param( $this->id, $this->default ?? [] );
			$get_values = get_post_meta( $post_id, $this->id ) ?? [];
			$new_values = $data;

			$array_get_values = ! empty( $get_values ) ? array_values( $get_values ) : [];
			$array_new_values = ! empty( $new_values ) ? array_values( $new_values ) : [];

			$del_val = array_diff( $array_get_values, $array_new_values );
			$new_val = array_diff( $array_new_values, $array_get_values );

			foreach ( $del_val as $level_id ) {
				delete_post_meta( $post_id, $this->id, $level_id );
			}

			foreach ( $new_val as $level_id ) {
				add_post_meta( $post_id, $this->id, $level_id, false );
			}

			return $data;
		} else {
			$multiple = ! empty( $this->extra['multiple'] );
			if ( $multiple ) {
				$data = LP_Request::get_param( $this->id, $this->default ?? [] );
				// Clear item has value empty.
				$value = [];
				array_map(
					function ( $item ) use ( &$value ) {
						if ( $item !== '' ) {
							$value[] = $item;
						}
					},
					$data
				);
			} else {
				$data  = LP_Request::get_param( $this->id, $this->default ?? '' );
				$value = $data;
			}

			update_post_meta( $post_id, $this->id, $value );

			return $value;
		}
	}
}
