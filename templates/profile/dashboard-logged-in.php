<?php
/**
 * Template for displaying a message in profile dashboard if user is logged in.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

defined( 'ABSPATH' ) or exit;

global $profile;

if ( ! $profile->is_current_user() ) {
	return;
}
$user = $profile->get_user();

?>
<p><?php echo sprintf( __( 'Hello <strong>%s</strong> (not %s? %s)', 'learnpress' ), $user->get_display_name(), $user->get_display_name(), sprintf( '<a href="%s">%s</a>', $profile->logout_url(), __( 'Sign out', 'learnpress' ) ) ); ?></p>