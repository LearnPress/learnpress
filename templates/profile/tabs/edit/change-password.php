<?php
/**
 * Created by PhpStorm.
 * User: Tu
 * Date: 12/30/2016
 * Time: 9:10 AM
 */
?>
<h2><?php _e( 'Account Management', 'learnpress' ); ?></h2>
<div class="change-password">
	<a href="" id="learn-press-toggle-password"><?php _e( 'Change Password', 'learnpress' ); ?></a>
</div>
<div id="user_profile_password_form" class="hide-if-js">

	<p class="profile-field-name"><?php _e( 'Old Password', 'learnpress' ); ?></p>
	<input type="password" id="pass0" name="pass0" autocomplete="off" class="regular-text" />

	<p class="profile-field-name"><?php _e( 'New Password', 'learnpress' ); ?></p>
	<input type="password" name="pass1" id="pass1" class="regular-text" value="" />

	<p class="profile-field-name"><?php _e( 'Confirmation password', 'learnpress' ); ?></p>
	<input name="pass2" type="password" id="pass2" class="regular-text" value="" />
	<p class="description"><?php _e( 'Type your new password again.', 'learnpress' ); ?></p>

</div>
