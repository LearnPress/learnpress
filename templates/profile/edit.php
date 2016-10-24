<?php
/**
 * User Information
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}
global $wp_query;
$user = learn_press_get_current_user();

$user_info = get_userdata( $user->id );
//var_dump($user_info);
//$vars = get_object_vars($user_info);
//echo '<pre>'.print_r($vars, true).'</pre>';
//$profileuser = get_user_to_edit($user_id);

$username             = $user_info->user_login;
$nick_name				= $user_info->nickname;
$first_name           = $user_info->first_name;
$last_name            = $user_info->last_name;
$profile_picture_type = get_user_meta( $user->id, '_lp_profile_picture_type', true );
if ( !$profile_picture_type ) {
	$profile_picture_type = 'gravatar';
}
$profile_picture_src = '';
if ( $profile_picture_type == 'picture' ) {
	$profile_picture     = get_user_meta( $user->id, '_lp_profile_picture', true );
	$profile_picture_src = wp_get_attachment_image_src( $profile_picture, 'thumbnail' )[0];
} else {
	$profile_picture_src = 'http://2.gravatar.com/avatar/' . md5( $user_info->user_email ) . '?s=96&amp;d=mm&amp;r=g';
}

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
				<img alt="" src="<?php esc_attr_e( $profile_picture_src ); ?>" class="avatar avatar-96 photo" height="96" width="96" />
				<div class="change-picture">
					<select name="profile_picture_type">
						<option value="gravatar" <?php echo $profile_picture_type == 'gravatar' ? ' selected="selected"' : ''; ?>><?php _e( 'Gavatar', 'learnpress' ); ?></option>
						<option value="picture" <?php echo $profile_picture_type == 'picture' ? ' selected="selected"' : ''; ?>><?php _e( 'Picture', 'learnpress' ); ?></option>
					</select>
					<div id="profile-picture-gravatar">
						<p class="description"><?php _e( 'You can change your profile picture on', 'learnpress' ); ?>
							<a href="https://en.gravatar.com/"><?php _e( 'Gravatar', 'learnpress' ); ?></a>.</p>
					</div>
					<div id="profile-picture-picture">
						<input type="file" name="profile_picture" />
					</div>
				</div>
			</div>

			<div class="user-description-wrap info-field">
				<p class="profile-field-name"><?php _e( 'Biographical Info', 'learnpress' ); ?></p>
				<textarea name="description" id="description" rows="5" cols="30"><?php esc_html_e( $user_info->description ); ?></textarea>
				<p class="description"><?php _e( 'Share a little biographical information to fill out your profile. This may be shown publicly.', 'learnpress' ); ?></p>
			</div>

			<h2><?php _e( 'Name', 'learnpress' ); ?></h2>

			<div class="user-user-login-wrap info-field">
				<p class="profile-field-name"><?php esc_html_e( 'Username', 'learnpress' ) ?></p>
				<input type="text" name="user_login" id="user_login" value="<?php esc_attr_e( $user->user->data->user_login ); ?>" disabled="disabled" class="regular-text">
				<p class="description"><?php esc_html_e( 'Username cannot be changed.', 'learnpress' ) ?></p>
			</div>

			<div class="user-first-name-wrap info-field">
				<p class="profile-field-name"><?php esc_html_e( 'First Name', 'learnpress' ); ?></p>
				<input type="text" name="first_name" id="first_name" value="<?php esc_attr_e( $first_name ); ?>" class="regular-text">
			</div>

			<div class="user-last-name-wrap info-field">
				<p class="profile-field-name"><?php esc_html_e( 'Last Name', 'learnpress' ) ?></p>
				<input type="text" name="last_name" id="last_name" value="<?php esc_attr_e( $last_name ); ?>" class="regular-text">
			</div>
			
			<div class="user-nickname-wrap info-field">
				<p class="profile-field-name"><?php _e('Nickname'); ?><span class="description"><?php _e('(required)'); ?></span></p>
				<td><input type="text" name="nickname" id="nickname" value="<?php echo esc_attr($user_info->nickname) ?>" class="regular-text" /></td>
			</div>
			<div class="user-last-name-wrap info-field">
				<p class="profile-field-name"><?php esc_html_e( 'Display name publicly as', 'learnpress' ) ?></p>
				<select name="display_name" id="display_name">
				<?php
					$public_display = array();
					$public_display['display_nickname']  = $user_info->nickname;
					$public_display['display_username']  = $user_info->user_login;

					if ( !empty($user_info->first_name) )
						$public_display['display_firstname'] = $user_info->first_name;

					if ( !empty($user_info->last_name) )
						$public_display['display_lastname'] = $user_info->last_name;

					if ( !empty($user_info->first_name) && !empty($user_info->last_name) ) {
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

			<h2><?php _e( 'Contact Info', 'learnpress' ); ?></h2>

			<div class="user-email-wrap info-field">
				<p class="profile-field-name"><?php _e( 'Email', 'learnpress' ); ?>
					<span class="description">(<?php _e( 'required', 'learnpress' ); ?>)</span></p>
				<input type="email" name="email" id="email" value="<?php esc_attr_e( $user_info->user_email ); ?>" class="regular-text ltr">
			</div>

			<div class="user-url-wrap info-field">
				<p class="profile-field-name"><?php _e( 'Website', 'learnpress' ); ?></p>
				<input type="url" name="url" id="url" value="<?php esc_attr_e( $user_info->user_url ); ?>" class="regular-text code">
			</div>

			<h2><?php _e( 'Account Management', 'learnpress' ); ?></h2>
			<div class="change-password">
				<a href="javascript:jQuery('#user_profile_password_form').toggle();"><?php _e( 'Change Password', 'learnpress' ); ?></a>
			</div>
			<div id="user_profile_password_form" style="display: none">

				<p class="profile-field-name"><?php _e( 'Old Password', 'learnpress' ); ?></p>
				<input type="password" id="pass0" name="pass0" autocomplete="off" class="regular-text" />

				<p class="profile-field-name"><?php _e( 'New Password', 'learnpress' ); ?></p>
				<input type="password" name="pass1" id="pass1" class="regular-text" value="" />

				<p class="profile-field-name"><?php _e( 'Repeat New Password', 'learnpress' ); ?></p>
				<input name="pass2" type="password" id="pass2" class="regular-text" value="" />
				<p class="description"><?php _e( 'Type your new password again.', 'learnpress' ); ?></p>

			</div>

			<input type="hidden" name="action" value="update">
			<input type="hidden" name="user_id" id="user_id" value="<?php esc_attr_e( $user->id ); ?>">

			<p class="submit update-profile">
				<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e( 'Update Profile', 'learnpress' ); ?>">
			</p>
		</form>
	</div>
	<?php
endif;
?>