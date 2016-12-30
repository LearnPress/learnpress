<?php
/**
 * User Information
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 2.1
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_query;
$user = learn_press_get_current_user();
$user_info = get_userdata( $user->id );
$username             = $user_info->user_login;
$nick_name            = $user_info->nickname;
$first_name           = $user_info->first_name;
$last_name            = $user_info->last_name;
$profile_picture_type = $user->profile_picture_type?$user->profile_picture_type:'gravatar';
$profile_picture = $user->profile_picture;
$class_gravatar_selected = ( 'gravatar' === $profile_picture_type ) ? ' lp-menu-item-selected' : '';
$class_picture_selected = ( 'picture' === $profile_picture_type ) ? ' lp-menu-item-selected' : '';
if ( $user ) :
	?>
	<div class="user-profile-edit-form" id="learn-press-user-profile-edit-form">
		<form id="your-profile" action="" method="post" enctype="multipart/form-data" novalidate="novalidate">
			<p>
				<input type="hidden" name="from" value="profile">
				<input type="hidden" name="checkuser_id" value="2">
			</p>
			<h2><?php _e( 'About Yourself', 'learnpress' ); ?></h2>
			<div class="user-profile-picture info-field">
				<p class="profile-field-name"><?php _e( 'Profile Picture', 'learnpress' ); ?></p>
				<div id="profile-picture-wrap">
					<div class="profile-picture profile-avatar-current <?php echo $profile_picture_type == 'gravatar' ? 'avatar-picture' : 'avatar-gravatar'; ?>">
						<?php echo $user->get_profile_picture( $profile_picture_type == 'gravatar' ? 'gravatar' : 'picture' ); ?>
					</div>
					<div class="profile-picture profile-avatar-hidden hide-if-js <?php echo $profile_picture_type != 'gravatar' ? 'avatar-picture' : 'avatar-gravatar'; ?>">
						<?php echo $user->get_profile_picture( $profile_picture_type == 'gravatar' ? 'picture' : 'gravatar' ); ?>
					</div>
					<div class="clear"></div>
					<ul id="lp-menu-change-picture">
						<li class="dropdown">
							<span class="lp-label-change-picture"><?php _e('Change Picture','learnpress'); ?></span>
							<select name="profile_picture_type" id="lp-profile_picture_type" class="hidden">
								<option value="gravatar" <?php selected( 'gravatar', $profile_picture_type ) ?>><?php _e( 'Gravatar', 'learnpress' ); ?></option>
								<option value="picture" <?php selected( 'picture', $profile_picture_type ) ?>><?php _e( 'Picture', 'learnpress' ); ?></option>
							</select>
							<ul class="dropdown-menu" role="menu" >
								<li class="menu-item-use-gravatar<?php echo esc_attr( $class_gravatar_selected ); ?>">
									<span><?php _e('Use Gravatar','learnpress'); ?></span></li>
								<li class="menu-item-use-picture<?php echo esc_attr( $class_picture_selected ); ?>">
									<span><?php _e('Use Picture','learnpress'); ?></span></li>
								<li  class="menu-item-upload-picture">
									<span><?php _e('Upload Picture','learnpress'); ?></span></li>
							</ul>
							
						</li>
					</ul>
				</div>
				<div id="lpbox-upload-crop-profile-picture">
					<input type="hidden" id="lp-user-profile-picture-data" data-current="<?php echo esc_attr( $profile_picture ); ?>" name="profile_picture_data" />
					<div class="lpbox-title"><?php _e('Upload Picture','learnpress'); ?></div>
                    <p class="description"><small><?php _e('Please use an image that\'s at least 250px in width, 250px in height and under 2MB in size', 'learnpress'); ?></small></p>
					<div id="image-editor-wrap">
						<div class="image-editor image-editor-sidebar-left">
							<div class="cropit-preview"></div>
							<div class="image-editor-btn">
<!--							
								<span class="rotate-ccw dashicons dashicons-image-rotate-left"></span>
								<span class="rotate-cw dashicons dashicons-image-rotate-right"></span>
-->
								<input type="range" class="cropit-image-zoom-input">
							</div>
						</div>
						<div class="image-editor-sidebar-right">
							<a href="#" id="lp-button-choose-file"><span class="dashicons dashicons-format-image"></span><?php _e( 'Choose File', 'learnpress' );?></a>
							<a href="#" id="lp-button-apply-changes"><span class="dashicons dashicons-yes"></span>&nbsp;<?php _e( 'Apply Changes', 'learnpress' );?></a>
							<a href="#" id="lp-button-cancel-changes"><span class="dashicons dashicons-no"></span><?php _e( 'Cancel', 'learnpress' );?></a>
							<div id="lp-ocupload-picture"></div>
						</div>
					</div>
					<div class="clear"></div>
				</div>
			</div>
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
				<p class="profile-field-name"><?php _e( 'Nickname','learnpress' ); ?>
					<span class="description"><?php _e( '(required)','learnpress' ); ?></span></p>
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

			<h2><?php _e( 'Account Management', 'learnpress' ); ?></h2>
			<div class="change-password">
				<a href="" id="learn-press-toggle-password"><?php _e( 'Change Password', 'learnpress' ); ?></a>
			</div>
			<div id="user_profile_password_form" class="hide-if-js">

				<p class="profile-field-name"><?php _e( 'Old Password', 'learnpress' ); ?></p>
				<input type="password" id="pass0" name="pass0" autocomplete="off" class="regular-text" disabled="disabled" />

				<p class="profile-field-name"><?php _e( 'New Password', 'learnpress' ); ?></p>
				<input type="password" name="pass1" id="pass1" class="regular-text" value="" disabled="disabled" />

				<p class="profile-field-name"><?php _e( 'Confirmation password', 'learnpress' ); ?></p>
				<input name="pass2" type="password" id="pass2" class="regular-text" value="" disabled="disabled"/>
				<p class="description"><?php _e( 'Type your new password again.', 'learnpress' ); ?></p>

			</div>

			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="user_id" id="user_id" value="<?php echo esc_attr( $user->id ); ?>" />
			<input type="hidden" name="profile-nonce" value="<?php echo esc_attr( wp_create_nonce( 'learn-press-user-profile-' . $user->id ) ); ?>" />
			<p class="submit update-profile">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Update Profile', 'learnpress' ); ?>" />
			</p>
		</form>
	</div>
<script type="text/template" id="learn-press-template-block-content">
 <div id="learn-press-block-content" class="popup-block-content">
  <span></span>
 </div>
</script>
	<?php
endif;
?>