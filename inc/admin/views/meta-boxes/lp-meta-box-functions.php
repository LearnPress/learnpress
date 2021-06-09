<?php
/**
 * Output a text input box.
 *
 * @param array $field
 */
function lp_meta_box_text_input_field( $field ) {
	global $thepostid, $post;

	$thepostid            = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder'] = $field['placeholder'] ?? '';
	$class                = ! empty( $field['class'] ) ? 'class="' . esc_attr( $field['class'] ) . '"' : '';
	$style                = ! empty( $field['style'] ) ? 'style="' . esc_attr( $field['style'] ) . '"' : '';
	$wrapper_class        = ! empty( $field['wrapper_class'] ) ? esc_attr( $field['wrapper_class'] ) : '';

	/**
	 * If you want to set default value for input text
	 * You must us hook default_{$meta_type}_metadata | Read more get_metadata_default() function
	 */
	$field['default']    = ( ! get_post_meta( $thepostid, $field['id'], true ) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta( $thepostid, $field['id'], true );
	$field['value']      = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field_id            = isset( $field['id'] ) ? esc_attr( $field['id'] ) : '';
	$field['type_input'] = $field['type_input'] ?? 'text';
	$field['desc_tip']   = $field['desc_tip'] ?? '';

	// Custom attribute handling
	$custom_attributes = array();

	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
		}
	}

	echo '<div class="form-field ' . $field_id . '_field ' . $wrapper_class . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	echo '<input type="' . esc_attr( $field['type_input'] ) . '" ' . $class . ' ' . $style . ' name="' . $field['id'] . '" id="' . $field['id'] . '" value="' . $field['value'] . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode(
		' ',
		$custom_attributes
	) . ' /> ';

	if ( ! empty( $field['description'] ) ) {
		echo '<p class="description">';
		echo '<span>' . wp_kses_post( $field['description'] ) . '</span>';

		if ( ! empty( $field['desc_tip'] ) ) {
			learn_press_quick_tip( $field['desc_tip'] );
		}
		echo '</p>';
	}
	echo '</div>';
}

/**
 * Output a textarea input box.
 *
 * @param array $field
 */
function lp_meta_box_textarea_field( $field ) {
	global $thepostid, $post;

	$thepostid            = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['placeholder'] = isset( $field['placeholder'] ) ? $field['placeholder'] : '';
	$field['class']       = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']       = isset( $field['style'] ) ? $field['style'] : '';
	$field['default']     = ( ! get_post_meta(
		$thepostid,
		$field['id'],
		true
	) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta(
		$thepostid,
		$field['id'],
		true
	);
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

/**
 * Output a checkbox input box.
 *
 * @param array $field
 */
function lp_meta_box_checkbox_field( $field ) {
	global $thepostid, $post;

	$thepostid     = empty( $thepostid ) ? $post->ID : $thepostid;
	$class         = ! empty( $field['class'] ) ? 'class="' . esc_attr( $field['class'] ) . '"' : '';
	$style         = ! empty( $field['style'] ) ? 'style="' . esc_attr( $field['style'] ) . '"' : '';
	$wrapper_class = ! empty( $field['wrapper_class'] ) ? esc_attr( $field['wrapper_class'] ) : '';
	$name          = ! empty( $field['name'] ) ? esc_attr( $field['name'] ) : esc_attr( $field['id'] );
	$name          = 'name="' . $name . '"';

	$value_db = get_post_meta( $thepostid, $field['id'], true );

	$checked = '';
	if ( 'yes' === $value_db ) {
		$checked = 'checked="checked"';
	}

	// Custom attribute handling
	$custom_attributes = array();
	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
		}
	}

	echo '<div class="form-field ' . esc_attr( $field['id'] ) . '_field ' . $wrapper_class . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	echo '<input type="checkbox" ' .
		 $class . ' ' . $style . ' ' . $name . ' ' . $checked . '
		 id="' . esc_attr( $field['id'] ) . '" ' .
		 implode( ' ', $custom_attributes ) . '/> ';

	if ( ! empty( $field['description'] ) ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

		if ( ! empty( $field['desc_tip'] ) ) {
			learn_press_quick_tip( $field['desc_tip'] );
		}
	}

	echo '</div>';
}

/**
 * Output a select input box.
 *
 * @param array $field Data about the field to render.
 */
function lp_meta_box_select_field( $field = array() ) {
	global $thepostid, $post;

	$thepostid = empty( $thepostid ) ? $post->ID : $thepostid;
	$default   = ( ! get_post_meta(
		$thepostid,
		$field['id'],
		true
	) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta(
		$thepostid,
		$field['id'],
		true
	);

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

	<p class="form-field <?php echo esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ); ?>">
		<label for="<?php echo esc_attr( $field['id'] ); ?>"><?php echo wp_kses_post( $field['label'] ); ?></label>
		<select <?php echo lp_implode_html_attributes( $field_attributes ); ?>>
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

/**
 * Output a radio input box.
 *
 * @param array $field
 */
function lp_meta_box_radio_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['default']       = ( ! get_post_meta(
		$thepostid,
		$field['id'],
		true
	) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta(
		$thepostid,
		$field['id'],
		true
	);
	$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><h4>' . wp_kses_post( $field['label'] ) . '</h4>';

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

function lp_meta_box_file_input_field( $field ) {
	global $thepostid, $post;

	$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
	$field['class']         = isset( $field['class'] ) ? $field['class'] : 'short';
	$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
	$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
	$field['default']       = ( ! get_post_meta(
		$thepostid,
		$field['id'],
		true
	) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta(
		$thepostid,
		$field['id'],
		true
	);
	$field['value']         = isset( $field['value'] ) ? $field['value'] : $field['default'];
	$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];
	$field['mime_type']     = isset( $field['mime_type'] ) ? implode( ',', $field['mime_type'] ) : '';
	$field['multil']        = ( isset( $field['multil'] ) && $field['multil'] ) ? true : false;
	$field['desc_tip']      = isset( $field['desc_tip'] ) ? $field['desc_tip'] : false;

	// Custom attribute handling
	$custom_attributes = array();

	if ( ! empty( $field['custom_attributes'] ) && is_array( $field['custom_attributes'] ) ) {
		foreach ( $field['custom_attributes'] as $attribute => $custom_attribute ) {
			$custom_attributes[] = esc_attr( $attribute ) . '="' . esc_attr( $custom_attribute ) . '"';
		}
	}

	echo '<div class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '">
		<label for="' . esc_attr( $field['id'] ) . '">' . wp_kses_post( $field['label'] ) . '</label>';

	echo '<div id="' . esc_attr( $field['id'] ) . '" class="lp-meta-box__file ' . esc_attr( $field['class'] ) . '" data-mime="' . $field['mime_type'] . '" data-multil="' . $field['multil'] . '" style="' . esc_attr( $field['style'] ) . '" ' . implode(
		' ',
		$custom_attributes
	) . '>';
	echo '<ul class="lp-meta-box__file_list">';

	if ( ! empty( $field['value'] ) ) {
		foreach ( (array) $field['value'] as $attachment_id ) {
			$url = wp_get_attachment_url( $attachment_id );

			if ( $url ) {
				$check_file = wp_check_filetype( $url );

				echo '<li class="lp-meta-box__file_list-item image" data-attachment_id="' . $attachment_id . '">';

				if ( in_array( $check_file['ext'], array( 'jpg', 'png', 'gif', 'bmp', 'tif', 'jpeg' ), true ) ) {
					echo wp_get_attachment_image( $attachment_id, 'thumbnail' );
				} else {
					echo '<img class="is_file" src="' . wp_mime_type_icon( $check_file['type'] ) . '" />';
					echo '<span>' . wp_basename( get_attached_file( $attachment_id ) ) . '</span>';
				}
				echo '<ul class="actions"><li><a href="#" class="delete"></a></li></ul>';
				echo '</li>';
			}
		}
	}

	echo '</ul>';
	echo '<input class="lp-meta-box__file_input" type="hidden" name="' . esc_attr( $field['id'] ) . '" value="' . esc_attr(
		( ! empty( $field['value'] ) && is_array( $field['value'] ) ) ? implode(
			',',
			$field['value']
		) : $field['value']
	) . '" />';
	echo '<p>';
	echo '<a href="#" class="button btn-upload">' . esc_html__( '+ Add media', 'learnpress' ) . '</a>';
	if ( ! empty( $field['description'] ) && false !== $field['desc_tip'] ) {
		learn_press_quick_tip( $field['description'] );
	}
	echo '</p>';

	if ( ! empty( $field['description'] ) && false === $field['desc_tip'] ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
	}
	echo '</div>';
	echo '</div>';
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
	$field['default']       = ( ! get_post_meta(
		$thepostid,
		$field['id'],
		true
	) && isset( $field['default'] ) ) ? $field['default'] : get_post_meta(
		$thepostid,
		$field['id'],
		true
	);
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

	echo '<input type="number" class="' . esc_attr( $field['class'] ) . '" style="' . esc_attr( $field['style'] ) . '" name="' . esc_attr( $field['name'] ) . '[]" id="' . esc_attr( $field['id'] ) . '" value="' . esc_attr( $a1 ) . '" placeholder="' . esc_attr( $field['placeholder'] ) . '" ' . implode(
		' ',
		$custom_attributes
	) . ' /> ';

	echo '<select name="' . esc_attr( $field['name'] ) . '[]" class="lp-meta-box__duration-select">' . $html_option . '</select>';

	if ( ! empty( $field['description'] ) ) {
		echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';

		if ( ! empty( $field['desc_tip'] ) ) {
			learn_press_quick_tip( $field['desc_tip'] );
		}
	}

	echo '</p>';
}

/**
 * Use for Type: custom_fields in LP4.
 *
 * @param [type] $field get ID, options....
 * @param [type] $values get_option() value
 * @param [type] $key
 *
 * @return void
 */
function lp_metabox_custom_fields( $field, $values, $key ) {
	?>
	<tr>
		<td class="sort">
			<input class="count" type="hidden" value="<?php echo $key; ?>" name="<?php echo esc_attr( $field['id'] ) . '[' . $key . ']' . '[sort]'; ?>">
			<input type="hidden" value="<?php echo ! empty( $values['id'] ) ? $values['id'] : wp_rand( 1, 10000 ) . $key; ?>" name="<?php echo esc_attr( $field['id'] ) . '[' . $key . ']' . '[id]'; ?>">
		</td>
		<?php
		if ( $field['options'] ) {
			foreach ( $field['options'] as $cfk => $val ) {
				$name = $field['id'] . '[' . $key . ']' . '[' . $cfk . ']';

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
							<input name="<?php echo esc_attr( $name ); ?>" type="<?php echo $val['type']; ?>"
								   class="input-text"
								   placeholder="<?php echo isset( $val['placeholder'] ) ? $val['placeholder'] : ''; ?>"
								   value="<?php echo ! empty( $values[ $cfk ] ) ? $values[ $cfk ] : ''; ?>">
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
										<option
											value="<?php echo $cfks; ?>"
															  <?php
																echo ! empty( $values[ $cfk ] ) ? selected(
																	$values[ $cfk ],
																	(string) $cfks
																) : '';
																?>
											><?php echo $cfselect; ?></option>
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

function lp_implode_html_attributes( $raw_attributes ) {
	$attributes = array();
	foreach ( $raw_attributes as $name => $value ) {
		$attributes[] = esc_attr( $name ) . '="' . esc_attr( $value ) . '"';
	}

	return implode( ' ', $attributes );
}

function lp_meta_box_output( $metaboxes = array() ) {
	if ( ! empty( $metaboxes ) ) {
		foreach ( $metaboxes as $id => $field ) {
			$field['id'] = $id;

			switch ( $field['type'] ) {
				case 'text':
				case 'number':
				case 'url':
					lp_meta_box_text_input_field( $field );
					break;

				case 'textarea':
					lp_meta_box_textarea_field( $field );
					break;

				case 'checkbox':
					lp_meta_box_checkbox_field( $field );
					break;

				case 'duration':
					lp_meta_box_duration_field( $field );
					break;

				case 'select':
					lp_meta_box_select_field( $field );
					break;

				case 'radio':
					lp_meta_box_radio_field( $field );
					break;

				case 'file':
					lp_meta_box_file_input_field( $field );
					break;
			}
		}
	}
}
