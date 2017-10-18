<?php
/**
 * Template for displaying Dashboard of user profile.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0
 */

defined( 'ABSPATH' ) or exit;

global $profile;

$user = $profile->get_user();
?>

<p><?php echo sprintf( __( 'Hello <strong>%s</strong> (not <strong>%s</strong>? %s)', 'learnpress' ), $user->get_display_name(), $user->get_display_name(), sprintf( '<a href="%s">%s</a>', $profile->logout_url(), __( 'Sign out', 'learnpress' ) ) ); ?></p>