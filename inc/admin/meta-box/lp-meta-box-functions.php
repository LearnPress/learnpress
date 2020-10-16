<?php
/**
 * Output a text input box.
 *
 * @param array $field
 */
function lp_meta_box_text_input_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['default']       = ( ! get_post_meta( $thepostid, $field['id'], true ) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta( $thepostid, $field['id'], true );
	$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['type']          = isset( $field['type'] ) ? $field['type'] : 'text';
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$data_type              = empty( $field['data_type'] ) ? '' : $field['data_type'];

	// Custom attribute handling
	$custom_attributes = array();

	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	echo '<input type="' . esc_attr( $field['type'] ) . '" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['value'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		learn_press_quick_tip( $field['description'] );
	}

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Output a textarea input box.
 *
 * @param array $field
 */
function lp_meta_box_textarea_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['default']       = ( ! get_post_meta( $thepostid, $field['id'], true ) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta( $thepostid, $field['id'], true );
	$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['rows']          = isset( $field['rows'] ) ? $field['rows'] : 2;
	$field['cols']          = isset( $field['cols'] ) ? $field['cols'] : 20;

	// Custom attribute handling
	$custom_attributes = array();
	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	echo '<textarea class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '"  name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" rows="' . esc_attr( $field['rows'] ) . '" cols="' . esc_attr( $field['cols'] ) . '" ' . implode( ' ', $custom_attributes ) . '>' . esc_textarea( $field['value'] ) . '</textarea> ';

	if ( ! empty( $field['desc_tip'] ) && false !== $field['description'] ) {
		learn_press_quick_tip( $field['description'] );
	}

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Output a checkbox input box.
 *
 * @param array $field
 */
function lp_meta_box_checkbox_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'checkbox';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['default']       = ( ! get_post_meta( $thepostid, $field['id'], true ) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta( $thepostid, $field['id'], true );
	$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field['cbvalue']       = isset( $field['cbvalue'] ) ? $field['cbvalue'] : 'yes';
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	// Custom attribute handling
	$custom_attributes = array();
	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

	echo '<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		learn_press_quick_tip( $field['description'] );
	}

	echo '<input type="checkbox" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $field['cbvalue'] ) . '" ' . checked( $field['value'], $field['cbvalue'], false ) . '  ' . implode( ' ', $custom_attributes ) . '/> ';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Output a select input box.
 *
 * @param array $field Data about the field to render.
 */
function lp_meta_box_select_field( $field ) {
	global $thepostid, $post;

	$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
	$field     = wp_parse_args(
		$field,
		array(
			'class'             => 'select short',
			'style'             => '',
			'wrapper_class'     => '',
			'value'             => get_post_meta( $thepostid, $field['id'], true ),
			'name'              => $field['id'],
			'desc_tip'          => false,
			'custom_attributes' => array(),
		)
	);

	$label_attributes = array(
		'for' => $field['id'],
	);

	$field_attributes          = (array) $field['custom_attributes'];
	$field_attributes['style'] = $field['style'];
	$field_attributes['id']    = $field['id'];
	$field_attributes['name']  = $field['name'];
	$field_attributes['class'] = $field['class'];

	$tooltip     = ! empty( $field['description'] ) && false !== $field['desc_tip'] ? $field['description'] : '';
	$description = ! empty( $field['description'] ) && false === $field['desc_tip'] ? $field['description'] : '';
	?>
	<p class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '"><?php echo wp_kses_post( $field['label'] ); ?></label>
		<?php if ( $tooltip ) : ?>
			<?php learn_press_quick_tip( $tooltip ); ?>
		<?php endif; ?>
		<select <?php echo implode( '', $field_attributes ); ?>>
			<?php
			foreach ( $field['options'] as $key => $value ) {
				echo '<option value="' . esc_attr( $key ) . '"' . selected( $key, $field['value'], false ) . '>' . esc_html( $value ) . '</option>';
			}
			?>
		</select>
		<?php if ( $description ) : ?>
			<span class="description"><?php echo wp_kses_post( $description ); ?></span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Output a radio input box.
 *
 * @param array $field
 */
function lp_meta_box_radio_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend>';

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
				/> ' . esc_html( $value ) . '</label>
		</li>';
	}
	echo '</ul>';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</fieldset>';
}

/**
 * Output a duration input box.
 *
 * @param array $field
 */
function lp_meta_box_duration_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder']   = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['default']       = ( ! get_post_meta( $thepostid, $field['id'], true ) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta( $thepostid, $field['id'], true );
	$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;
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
		foreach ( $field['custom_attributes'] as $attribute => $value ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $value ) . '"';
		}
	}

	$html_option = '';
	foreach ( $duration as $k => $v ) {
		$html_option .= sprintf( '<option value="%s" %s>%s</option>', $k, selected( $k, $a2, false ), $v );
	}

	echo '<p class="lp-meta-box__duration form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		learn_press_quick_tip( $field['description'] );
	}

	echo '<input type="number" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '[]" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $a1 ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode( ' ', $custom_attributes ) . ' /> ';

	echo '<select name="' . esc_attr( $field['name'] ) . '[]" class="lp-meta-box__duration-select">' . $html_option . '</select>';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}

	echo '</p>';
}

/**
 * Use for Type: custom_fields in LP4.
 *
 * @param [type] $value
 * @param [type] $values
 * @param [type] $key
 * @return void
 */
function lp_metabox_custom_fields( $value, $values, $key ) {
	?>
	<tr>
		<td class="sort">
			<input class="count" type="hidden" value="<?php echo $key; ?>" name="<?php echo esc_attr( $value['id'] ) . '[' . $key . ']' . '[sort]'; ?>">
		</td>
		<?php
		if ( $value['options'] ) {
			foreach ( $value['options'] as $cfk => $val ) {
				$name = $value['id'] . '[' . $key . ']' . '[' . $cfk . ']';

				switch ( $val['type'] ) {
					case 'text':
					case 'password':
					case 'datetime':
					case 'datetime-local':
					case 'date':
					case 'month':
					case 'time':
					case 'week':
					case 'number':
					case 'email':
					case 'url':
					case 'tel':
						?>
						<td>
							<input name="<?php echo esc_attr( $name ); ?>" type="<?php echo $val['type']; ?>" class="input-text" placeholder="<?php echo isset( $val['placeholder'] ) ? $val['placeholder'] : ''; ?>" value="<?php echo ! empty( $values[ $cfk ] ) ? $values[ $cfk ] : ''; ?>">
						</td>
						<?php
						break;

					case 'select':
						?>
						<td>
							<select name="<?php echo esc_attr( $name ); ?>">
								<?php
								if ( isset( $val['options'] ) ) {
									foreach ( $val['options'] as $cfks => $cfselect ) {
										?>
										<option value="<?php echo $cfks; ?>" <?php echo ! empty( $values[ $cfk ] ) ? selected( $values[ $cfk ], (string) $cfks ) : ''; ?>><?php echo $cfselect; ?></option>
										<?php
									}
								}
								?>
							</select>
						</td>
						<?php
						break;

					case 'checkbox':
						?>
						<td>
							<input name="<?php echo esc_attr( $name ); ?>" type="checkbox" name="" value="1" <?php echo ! empty( $values[ $cfk ] ) ? checked( $values[ $cfk ], 'yes' ) : ''; ?>>
						</td>
						<?php
						break;
				}
			}
		}
		?>
		<td width="2%"><a href="#" class="delete"></a></td>
	</tr>
	<?php
}
