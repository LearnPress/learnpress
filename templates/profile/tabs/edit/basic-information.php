<div class="user-description-wrap info-field">
	<p class="profile-field-name"><?php _e( 'Biographical Info', 'learnpress' ); ?></p>
	<textarea name="description" id="description" rows="5" cols="30"><?php esc_html_e( $user_info->description ); ?></textarea>
	<p class="description"><?php _e( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'learnpress' ); ?></p>
</div>

<h2><?php _e( 'Name', 'learnpress' ); ?></h2>

<div class="user-first-name-wrap info-field">
	<p class="profile-field-name"><?php esc_html_e( 'First Name', 'learnpress' ); ?></p>
	<input type="text" name="first_name" id="first_name" value="<?php echo esc_attr( $first_name ); ?>" class="regular-text">
</div>

<div class="user-last-name-wrap info-field">
	<p class="profile-field-name"><?php esc_html_e( 'Last Name', 'learnpress' ) ?></p>
	<input type="text" name="last_name" id="last_name" value="<?php echo esc_attr( $last_name ); ?>" class="regular-text">
</div>

<div class="user-nickname-wrap info-field">
	<p class="profile-field-name"><?php _e( 'Nickname', 'learnpress' ); ?>
		<span class="description"><?php _e( '(required)', 'learnpress' ); ?></span></p>
	<td>
		<input type="text" name="nickname" id="nickname" value="<?php echo esc_attr( $user_info->nickname ) ?>" class="regular-text" />
	</td>
</div>
<div class="user-last-name-wrap info-field">
	<p class="profile-field-name"><?php esc_html_e( 'Display name publicly as', 'learnpress' ) ?></p>
	<select name="display_name" id="display_name">
		<?php
		$public_display                     = array();
		$public_display['display_nickname'] = $user_info->nickname;
		$public_display['display_username'] = $user_info->user_login;

		if ( !empty( $user_info->first_name ) )
			$public_display['display_firstname'] = $user_info->first_name;

		if ( !empty( $user_info->last_name ) )
			$public_display['display_lastname'] = $user_info->last_name;

		if ( !empty( $user_info->first_name ) && !empty( $user_info->last_name ) ) {
			$public_display['display_firstlast'] = $user_info->first_name . ' ' . $user_info->last_name;
			$public_display['display_lastfirst'] = $user_info->last_name . ' ' . $user_info->first_name;
		}

		if ( !in_array( $user_info->display_name, $public_display ) ) // Only add this if it isn't duplicated elsewhere
		{
			$public_display = array( 'display_displayname' => $user_info->display_name ) + $public_display;
		}

		$public_display = array_map( 'trim', $public_display );
		$public_display = array_unique( $public_display );

		foreach ( $public_display as $id => $item ) {
			?>
			<option <?php selected( $user_info->display_name, $item ); ?>><?php echo $item; ?></option>
			<?php
		}
		?>
	</select>
</div>