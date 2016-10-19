<?php
/**
 * User Information
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */
if (!defined('ABSPATH')) {
	exit;
}
global $wp_query;
$user = learn_press_get_current_user();

$user_info = get_userdata($user->id);

$username = $user_info->user_login;
$first_name = $user_info->first_name;
$last_name = $user_info->last_name;
$profile_picture_type	= get_user_meta($user->id,'_lp_profile_picture_type',true);
if(!$profile_picture_type){
	$profile_picture_type = 'gavatar';
}
$profile_picture_src	= '';
if($profile_picture_type == 'picture'){
	$profile_picture	= get_user_meta($user->id,'_lp_profile_picture',true);
	$profile_picture_src = wp_get_attachment_image_src($profile_picture, 'thumbnail')[0];
} else {
	$profile_picture_src = 'http://2.gravatar.com/avatar/'.  md5($user_info->user_email).'?s=96&amp;d=mm&amp;r=g';
}

if ($user) :
?>
	<div class="user-profile-edit-form" id="learn-press-user-profile-edit-form">
		<form id="your-profile" action="" method="post" enctype="multipart/form-data" novalidate="novalidate">
			<p>
				<input type="hidden" name="from" value="profile">
				<input type="hidden" name="checkuser_id" value="2">
			</p>
			
			<h2><?php _e('About Yourself', 'learnpress'); ?></h2>
			<table class="form-table">
				<tbody>
					<tr class="user-profile-picture">
						<th><?php _e( 'Profile Picture', 'learnpress' ); ?></th>
						<td>
							<img alt="" src="<?php esc_attr_e($profile_picture_src);?>" class="avatar avatar-96 photo" height="96" width="96"/>
							<br>
							<select name="profile_picture_type">
								<option value="gavatar" <?php echo $profile_picture_type == 'gavatar'?' selected="selected"':''; ?>><?php _e('Gavatar','learnpress');?></option>
								<option value="picture" <?php echo $profile_picture_type == 'picture'?' selected="selected"':''; ?>><?php _e('Picture','learnpress');?></option>
							</select>
							<div id="profile-picture-gavatar">
								<p class="description"><?php _e( 'You can change your profile picture on', 'learnpress' );?> <a href="https://en.gravatar.com/"><?php _e('Gravatar', 'learnpress');?></a>.</p>
							</div>
							<div id="profile-picture-picture">
								<input type="file" name="profile_picture"/>
							</div>
						</td>
					</tr>
					<tr class="user-description-wrap">
						<th><label for="description"><?php _e('Biographical Info','learnpress');?></label></th>
						<td><textarea name="description" id="description" rows="5" cols="30"><?php esc_html_e($user_info->description); ?></textarea>
							<p class="description"><?php _e('Share a little biographical information to fill out your profile. This may be shown publicly.','learnpress'); ?></p></td>
					</tr>
				</tbody>
			</table>

			<h2><?php _e('Name','learnpress');?></h2>

			<table class="form-table">
				<tbody><tr class="user-user-login-wrap">
						<th><label for="user_login"><?php esc_html_e('Username','learnpress') ?></label></th>
						<td><input type="text" name="user_login" id="user_login" value="<?php esc_attr_e($user->user->data->user_login);?>" disabled="disabled" class="regular-text">
							<span class="description"><?php esc_html_e('Usernames cannot be changed.','learnpress') ?></span></td>
					</tr>
					<tr class="user-first-name-wrap">
						<th><label for="first_name"><?php esc_html_e('First Name','learnpress'); ?></label></th>
						<td><input type="text" name="first_name" id="first_name" value="<?php esc_attr_e($first_name); ?>" class="regular-text"></td>
					</tr>
					<tr class="user-last-name-wrap">
						<th><label for="last_name"><?php esc_html_e('Last Name','learnpress') ?></label></th>
						<td><input type="text" name="last_name" id="last_name" value="<?php esc_attr_e($last_name); ?>" class="regular-text"></td>
					</tr>
				</tbody>
			</table>

			<h2>Contact Info</h2>

			<table class="form-table">
				<tbody><tr class="user-email-wrap">
						<th><label for="email"><?php _e('Email', 'learnpress'); ?> <span class="description">(<?php _e('required','learnpress'); ?>)</span></label></th>
						<td><input type="email" name="email" id="email" value="<?php esc_attr_e($user_info->user_email);?>" class="regular-text ltr">
						</td>
					</tr>

					<tr class="user-url-wrap">
						<th><label for="url"><?php _e('Website', 'learnpress'); ?></label></th>
						<td><input type="url" name="url" id="url" value="<?php esc_attr_e($user_info->user_url);?>" class="regular-text code"></td>
					</tr>

				</tbody></table>

			<h2><?php _e('Account Management', 'learnpress'); ?></h2>
			<div><button type="button" class="button button-secondary" onclick="jQuery('#user_profile_password_form').show();return;"><?php _e('Change Password', 'learnpress'); ?></button></div>
			<table id="user_profile_password_form" class="form-table" style="display: none;">
				<tbody>
					<tr id="password" class="user-pass1-wrap">
						<th><label for="pass0"><?php _e('Old Password', 'learnpress'); ?></label></th>
						<td>
							<input type="password" id="pass0" name="pass0" autocomplete="off" class="regular-text" />
						</td>
					</tr>
					<tr id="password" class="user-pass1-wrap">
						<th><label for="pass1"><?php _e('New Password', 'learnpress'); ?></label></th>
						<td>
							<input type="password" name="pass1" id="pass1" class="regular-text" value="" />
						</td>
					</tr>
					<tr class="user-pass2-wrap">
						<th scope="row"><label for="pass2"><?php _e('Repeat New Password', 'learnpress'); ?></label></th>
						<td>
							<input name="pass2" type="password" id="pass2" class="regular-text" value="" />
							<p class="description"><?php _e('Type your new password again.', 'learnpress'); ?></p>
						</td>
					</tr>
				</tbody>
			</table>
			<input type="hidden" name="action" value="update">
			<input type="hidden" name="user_id" id="user_id" value="<?php esc_attr_e($user->id);?>">

			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _e('Update Profile', 'learnpress'); ?>"></p>
		</form>
	</div>
<?php 
endif;
?>