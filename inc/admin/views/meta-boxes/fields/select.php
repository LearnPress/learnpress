<?php

/**
 * LP_Meta_Box_Duration_Attribute
 *
 * @author Nhamdv
 * @version 1.0.0
 * @since 4.0.0
 */
class LP_Meta_Box_Select_Field extends LP_Meta_Box_Field {

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

	public function meta_value( $thepostid ) {
		$multil_meta = isset( $this->extra['multil_meta'] ) ? $this->extra['multil_meta'] : false;
		return $multil_meta ? get_post_meta( $thepostid, $this->id, false ) : get_post_meta( $thepostid, $this->id, true );
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

		$field['multil_meta'] = isset( $field['multil_meta'] ) ? $field['multil_meta'] : false;
		$meta                 = $this->meta_value( $thepostid );

		$default = ( ! $meta && isset( $field['default'] ) ) ? $field['default'] : $meta;

		$field = wp_parse_args(
			$field,
			array(
				'class'             => 'select',
				'style'             => '',
				'wrapper_class'     => '', // Use "lp-select-2" for select2.
				'value'             => isset( $field['value'] ) ? $field['value'] : $default,
				'name'              => $field['id'],
				'desc_tip'          => false,
				'multiple'          => false,
				'custom_attributes' => array(),
			)
		);

		$label_attributes = array(
			'for' => $field['id'],
		);

		$field_attributes          = (array) $field['custom_attributes'];
		$field_attributes['style'] = $field['style'];
		$field_attributes['id']    = $field['id'];
		$field_attributes['name']  = $field['multiple'] ? $field['name'] . '[]' : $field['name'];
		$field_attributes['class'] = $field['class'];

		if ( $field['multiple'] ) {
			$field['wrapper_class']       = 'lp-select-2';
			$field_attributes['multiple'] = true;
		}

		$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
		$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';
		?>

		<p class="form-field <?php echo esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ); ?>"
			<?php learn_press_echo_vuejs_write_on_php( $this->condition ? $this->condition : '' ); ?>>
			<label for="<?php echo esc_attr( $field['id'] ); ?>">
				<?php echo wp_kses_post( $field['label'] ); ?>
			</label>
			<select <?php echo lp_implode_html_attributes( $field_attributes ); ?>>
				<option value="" hidden style="display: none"></option>
				<?php
				foreach ( $field['options'] as $key => $value ) {
					echo '<option value="' . esc_attr( $key ) . '"' . ( is_array( $field['value'] ) ? selected( in_array( (string) $key, $field['value'], true ), true ) : selected( $key, $field['value'], false ) ) . '>' . esc_html( $value ) . '</option>';
				}
				?>
			</select>
			<?php
			if ( ! empty( $field['description'] ) ) {
				echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

				if ( ! empty( $field['desc_tip'] ) ) {
					learn_press_quick_tip( $field['desc_tip'] );
				}
			}
			?>
		</p>
		<?php
	}

	public function save( $post_id ) {
		$multiple_meta = $this->extra['multil_meta'] ?? false;

		if ( ! isset( $_POST[ $this->id ] ) ) {
			return;
		}

		if ( ! empty( $this->extra['custom_save'] ) ) {
			do_action( 'learnpress/admin/metabox/select/save', $this->id, $_POST[ $this->id ], $post_id );
			return;
		}

		if ( $multiple_meta ) {
			$get_values = get_post_meta( $post_id, $this->id ) ?? array();
			$new_values = LP_Helper::sanitize_params_submitted( $_POST[ $this->id ] ?? [] );

			$array_get_values = ! empty( $get_values ) ? array_values( $get_values ) : array();
			$array_new_values = ! empty( $new_values ) ? array_values( $new_values ) : array();

			$del_val = array_diff( $array_get_values, $array_new_values );
			$new_val = array_diff( $array_new_values, $array_get_values );

			foreach ( $del_val as $level_id ) {
				delete_post_meta( $post_id, $this->id, $level_id );
			}

			foreach ( $new_val as $level_id ) {
				add_post_meta( $post_id, $this->id, $level_id, false );
			}
		} else {
			$multiple = ! empty( $this->extra['multiple'] );

			if ( $multiple ) {
				$value_tmp = ! empty( $_POST[ $this->id ] ) ? LP_Helper::sanitize_params_submitted( $_POST[ $this->id ] ) : array();
				// Clear item has value empty.
				$value = [];
				array_map(
					function( $item ) use ( &$value ) {
						if ( $item !== '' ) {
							$value[] = $item;
						}
					},
					$value_tmp
				);
			} else {
				$value = ! empty( $_POST[ $this->id ] ) ? sanitize_text_field( wp_unslash( $_POST[ $this->id ] ) ) : '';
			}

			update_post_meta( $post_id, $this->id, $value );
		}
	}
}
