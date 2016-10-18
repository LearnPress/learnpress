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
if ($user) :
	?>
	<div class="user-profile-edit-form" id="learn-press-user-profile-edit-form">
		<form id="your-profile" action="" method="post" novalidate="novalidate">
			<p>
				<input type="hidden" name="from" value="profile">
				<input type="hidden" name="checkuser_id" value="2">
			</p>
			<h2>Name</h2>

			<table class="form-table">
				<tbody><tr class="user-user-login-wrap">
						<th><label for="user_login">Username</label></th>
						<td><input type="text" name="user_login" id="user_login" value="<?php esc_attr_e($user->user->data->user_login);?>" disabled="disabled" class="regular-text">
							<span class="description"><?php esc_html_e('Usernames cannot be changed.','learnpress') ?></span></td>
					</tr>


					<tr class="user-first-name-wrap">
						<th><label for="first_name"><?php esc_html_e('First Name','learnpress') ?></label></th>
						<td><input type="text" name="first_name" id="first_name" value="Hi" class="regular-text"></td>
					</tr>

					<tr class="user-last-name-wrap">
						<th><label for="last_name">Last Name</label></th>
						<td><input type="text" name="last_name" id="last_name" value="Hi" class="regular-text"></td>
					</tr>

					<tr class="user-nickname-wrap">
						<th><label for="nickname">Nickname <span class="description">(required)</span></label></th>
						<td><input type="text" name="nickname" id="nickname" value="hihihehe" class="regular-text"></td>
					</tr>

					<tr class="user-display-name-wrap">
						<th><label for="display_name">Display name publicly as</label></th>
						<td>
							<select name="display_name" id="display_name">
								<option>hihihehe</option>
								<option>hihi</option>
								<option>Hi</option>
								<option selected="selected">Hi Hi</option>
							</select>
						</td>
					</tr>
				</tbody></table>

			<h2>Contact Info</h2>

			<table class="form-table">
				<tbody><tr class="user-email-wrap">
						<th><label for="email">Email <span class="description">(required)</span></label></th>
						<td><input type="email" name="email" id="email" value="hihi@foobla.com" class="regular-text ltr">
						</td>
					</tr>

					<tr class="user-url-wrap">
						<th><label for="url">Website</label></th>
						<td><input type="url" name="url" id="url" value="" class="regular-text code"></td>
					</tr>

				</tbody></table>

			<h2>About Yourself</h2>

			<table class="form-table">
				<tbody><tr class="user-description-wrap">
						<th><label for="description">Biographical Info</label></th>
						<td><textarea name="description" id="description" rows="5" cols="30"></textarea>
							<p class="description">Share a little biographical information to fill out your profile. This may be shown publicly.</p></td>
					</tr>

					<tr class="user-profile-picture">
						<th>Profile Picture</th>
						<td>
							<img alt="" src="http://2.gravatar.com/avatar/b82f5287726e78f4f4ab19de21282ee9?s=96&amp;d=mm&amp;r=g" srcset="http://2.gravatar.com/avatar/b82f5287726e78f4f4ab19de21282ee9?s=192&amp;d=mm&amp;r=g 2x" class="avatar avatar-96 photo" height="96" width="96">		<p class="description">You can change your profile picture on <a href="https://en.gravatar.com/">Gravatar</a>.</p>
						</td>
					</tr>

				</tbody></table>

			<h2>Account Management</h2>
			<table class="form-table">
				<tbody><tr id="password" class="user-pass1-wrap">
						<th><label for="pass1-text">New Password</label></th>
						<td>
							<input class="hidden" value=" "><!-- #24364 workaround -->
							<button type="button" class="button button-secondary wp-generate-pw hide-if-no-js">Generate Password</button>
							<div class="wp-pwd hide-if-js" style="display: none;">
								<span class="password-input-wrapper">
									<input type="password" name="pass1" id="pass1" class="regular-text" value="" autocomplete="off" data-pw="fX4j6KMnFHxIGcolKlO7E81M" aria-describedby="pass-strength-result" disabled=""><input type="text" id="pass1-text" name="pass1-text" autocomplete="off" class="regular-text" disabled="">
								</span>
								<button type="button" class="button button-secondary wp-hide-pw hide-if-no-js" data-toggle="0" aria-label="Hide password">
									<span class="dashicons dashicons-hidden"></span>
									<span class="text">Hide</span>
								</button>
								<button type="button" class="button button-secondary wp-cancel-pw hide-if-no-js" data-toggle="0" aria-label="Cancel password change">
									<span class="text">Cancel</span>
								</button>
								<div style="" id="pass-strength-result" aria-live="polite"></div>
							</div>
						</td>
					</tr>
					<tr class="user-pass2-wrap hide-if-js" style="display: none;">
						<th scope="row"><label for="pass2">Repeat New Password</label></th>
						<td>
							<input name="pass2" type="password" id="pass2" class="regular-text" value="" autocomplete="off" disabled="">
							<p class="description">Type your new password again.</p>
						</td>
					</tr>
					<tr class="pw-weak">
						<th>Confirm Password</th>
						<td>
							<label>
								<input type="checkbox" name="pw_weak" class="pw-checkbox">
								<span id="pw-weak-text-label">Confirm use of potentially weak password</span>
							</label>
						</td>
					</tr>

					<tr class="user-sessions-wrap hide-if-no-js">
						<th>Sessions</th>
						<td aria-live="assertive">
							<div class="destroy-sessions"><button type="button" disabled="" class="button button-secondary">Log Out Everywhere Else</button></div>
							<p class="description">
								You are only logged in at this location.			</p>
						</td>
					</tr>

				</tbody></table>



			<input type="hidden" name="action" value="update">
			<input type="hidden" name="user_id" id="user_id" value="2">

			<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="Update Profile"></p>
		</form>
	</div>
	<?php endif;
?>