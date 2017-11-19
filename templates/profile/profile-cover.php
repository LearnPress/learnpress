<?php
/**
 * Template for displaying user profile cover image.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/profile/profile-cover.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php global $profile; ?>

<?php
$user = $profile->get_user();
learn_press_get_course();
?>

<div id="learn-press-profile-header" class="lp-profile-header">
    <div class="lp-profile-cover">
        <div class="lp-profile-avatar">
			<?php echo $user->get_profile_picture(); ?>
            <span class="profile-name"><?php echo $user->get_data( 'user_login' ); ?></span>
        </div>
    </div>
</div>


