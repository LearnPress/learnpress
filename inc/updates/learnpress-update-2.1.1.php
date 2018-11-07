<?php
/**
 * Upgrade user profile picture used for avatar
 */
$user    = learn_press_get_current_user();
$user_id = $user->get_id();
if ( ! empty( $user_id ) && $user->profile_picture_type == 'picture' ) {
	$thumb     = $user->profile_picture_thumbnail_url;
	$origin    = $user->profile_picture_url;
	$wp_upload = wp_upload_dir();
	$thumb     = preg_replace( '!' . untrailingslashit( $wp_upload['baseurl'] ) . '/!', '', $thumb );
	if ( file_exists( $wp_upload['basedir'] . '/' . $thumb ) ) {
		// Update new user meta key value and remove unused meta data
		update_user_meta( $user->get_id(), '_lp_profile_picture', $thumb );
		delete_user_meta( $user->get_id(), '_lp_profile_picture_thumbnail_url' );
		delete_user_meta( $user->get_id(), '_lp_profile_picture_url' );
		delete_user_meta( $user->get_id(), '_lp_profile_picture_type' );
	}
}
delete_option( 'learnpress_updater_step' );
delete_option( 'learnpress_updater' );
LP_Install::update_db_version('2.1.1');
return array( 'done' => true, 'percent' => 100 );