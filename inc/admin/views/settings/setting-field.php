<?php


// Description handling
//$field_description = self::get_field_description( $options );
//extract( $field_description );

// Switch based on type
switch ( $options['type'] ) {
	case 'text':
	case 'email':
	case 'number':
	case 'color' :
	case 'password' :


		break;

	// Textarea
	case 'textarea':

		$option_value = self::get_option( $options['id'], $options['default'] );

		?>
		<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
			<?php echo $tooltip_html; ?>
		</th>
		<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
			<?php echo $description; ?>

			<textarea
				name="<?php echo esc_attr( $options['id'] ); ?>"
				id="<?php echo esc_attr( $options['id'] ); ?>"
				style="<?php echo esc_attr( $options['css'] ); ?>"
				class="<?php echo esc_attr( $options['class'] ); ?>"
				placeholder="<?php echo esc_attr( $options['placeholder'] ); ?>"
				<?php echo implode( ' ', $custom_attributes ); ?>
				><?php echo esc_textarea( $option_value ); ?></textarea>
		</td>
		</tr><?php
		break;

	// Select boxes
	case 'select' :
	case 'multiselect' :


		break;

	// Radio inputs
	case 'radio' :

		$option_value = self::get_option( $options['id'], $options['default'] );

		?>
		<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
			<?php echo $tooltip_html; ?>
		</th>
		<td class="forminp forminp-<?php echo sanitize_title( $options['type'] ) ?>">
			<fieldset>
				<?php echo $description; ?>
				<ul>
					<?php
					foreach ( $options['options'] as $key => $val ) {
						?>
						<li>
							<label><input
									name="<?php echo esc_attr( $options['id'] ); ?>"
									value="<?php echo $key; ?>"
									type="radio"
									style="<?php echo esc_attr( $options['css'] ); ?>"
									class="<?php echo esc_attr( $options['class'] ); ?>"
									<?php echo implode( ' ', $custom_attributes ); ?>
									<?php checked( $key, $option_value ); ?>
									/> <?php echo $val ?></label>
						</li>
						<?php
					}
					?>
				</ul>
			</fieldset>
		</td>
		</tr><?php
		break;

	// Checkbox input
	case 'checkbox' :


		break;
		// Image width settings
	case
		'image_width' :

		$image_size       = str_replace( '_image_size', '', $options['id'] );
		$size             = learn_press_get_image_size( $image_size );
		$width            = isset( $size['width'] ) ? $size['width'] : $options['default']['width'];
		$height           = isset( $size['height'] ) ? $size['height'] : $options['default']['height'];
		$crop             = isset( $size['crop'] ) ? $size['crop'] : $options['default']['crop'];
		$disabled_attr    = '';
		$disabled_message = '';

		if ( has_filter( 'learn_press_get_image_size_' . $image_size ) ) {
			$disabled_attr    = 'disabled="disabled"';
			$disabled_message = "<p><small>" . __( 'The settings of this image size have been disabled because its values are being overwritten by a filter.', 'learn_press' ) . "</small></p>";
		}

		?>
		<tr valign="top">
		<th scope="row" class="titledesc"><?php echo esc_html( $options['title'] ) ?><?php echo $tooltip_html;
			echo $disabled_message; ?></th>
		<td class="forminp image_width_settings">

			<input name="<?php echo esc_attr( $options['id'] ); ?>[width]" <?php echo $disabled_attr; ?> id="<?php echo esc_attr( $options['id'] ); ?>-width" type="text" size="3" value="<?php echo $width; ?>" /> &times;
			<input name="<?php echo esc_attr( $options['id'] ); ?>[height]" <?php echo $disabled_attr; ?> id="<?php echo esc_attr( $options['id'] ); ?>-height" type="text" size="3" value="<?php echo $height; ?>" />px

			<label><input name="<?php echo esc_attr( $options['id'] ); ?>[crop]" <?php echo $disabled_attr; ?> id="<?php echo esc_attr( $options['id'] ); ?>-crop" type="checkbox" value="1" <?php checked( 1, $crop ); ?> /> <?php _e( 'Hard Crop?', 'learn_press' ); ?>
			</label>

		</td>
		</tr><?php
		break;

	// Single page selects
	case 'single_select_page' :

		$args = array(
			'name'             => $options['id'],
			'id'               => $options['id'],
			'sort_column'      => 'menu_order',
			'sort_order'       => 'ASC',
			'show_option_none' => ' ',
			'class'            => $options['class'],
			'echo'             => false,
			'selected'         => absint( self::get_option( $options['id'] ) )
		);

		if ( isset( $options['args'] ) ) {
			$args = wp_parse_args( $options['args'], $args );
		}

		?>
		<tr valign="top" class="single_select_page">
		<th scope="row" class="titledesc"><?php echo esc_html( $options['title'] ) ?><?php echo $tooltip_html; ?></th>
		<td class="forminp">
			<?php echo str_replace( ' id=', " data-placeholder='" . esc_attr__( 'Select a page&hellip;', 'learn_press' ) . "' style='" . $options['css'] . "' class='" . $options['class'] . "' id=", wp_dropdown_pages( $args ) ); ?><?php echo $description; ?>
		</td>
		</tr><?php
		break;

	// Single country selects
	case 'single_select_country' :
		$country_setting = (string) self::get_option( $options['id'] );

		if ( strstr( $country_setting, ':' ) ) {
			$country_setting = explode( ':', $country_setting );
			$country         = current( $country_setting );
			$state           = end( $country_setting );
		} else {
			$country = $country_setting;
			$state   = '*';
		}
		?>
		<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
			<?php echo $tooltip_html; ?>
		</th>
		<td class="forminp">
			<select name="<?php echo esc_attr( $options['id'] ); ?>" style="<?php echo esc_attr( $options['css'] ); ?>" data-placeholder="<?php esc_attr_e( 'Choose a country&hellip;', 'learn_press' ); ?>" title="<?php esc_attr_e( 'Country', 'learn_press' ) ?>" class="wc-enhanced-select">
				<?php WC()->countries->country_dropdown_options( $country, $state ); ?>
			</select> <?php echo $description; ?>
		</td>
		</tr><?php
		break;

	// Country multiselects
	case 'multi_select_countries' :

		$selections = (array) self::get_option( $options['id'] );

		if ( !empty( $options['options'] ) ) {
			$countries = $options['options'];
		} else {
			$countries = WC()->countries->countries;
		}

		asort( $countries );
		?>
		<tr valign="top">
		<th scope="row" class="titledesc">
			<label for="<?php echo esc_attr( $options['id'] ); ?>"><?php echo esc_html( $options['title'] ); ?></label>
			<?php echo $tooltip_html; ?>
		</th>
		<td class="forminp">
			<select multiple="multiple" name="<?php echo esc_attr( $options['id'] ); ?>[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose countries&hellip;', 'learn_press' ); ?>" title="<?php esc_attr_e( 'Country', 'learn_press' ) ?>" class="wc-enhanced-select">
				<?php
				if ( !empty( $countries ) ) {
					foreach ( $countries as $key => $val ) {
						echo '<option value="' . esc_attr( $key ) . '" ' . selected( in_array( $key, $selections ), true, false ) . '>' . $val . '</option>';
					}
				}
				?>
			</select> <?php echo ( $description ) ? $description : ''; ?> </br>
			<a class="select_all button" href="#"><?php _e( 'Select all', 'learn_press' ); ?></a>
			<a class="select_none button" href="#"><?php _e( 'Select none', 'learn_press' ); ?></a>
		</td>
		</tr><?php
		break;

	// Default: run an action
	default:
		do_action( 'learn_press_admin_field_' . $options['type'], $options );
		break;
}