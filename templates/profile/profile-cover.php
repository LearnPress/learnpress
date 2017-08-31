<?php
/**
 * Template for displaying user profile cover image.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit;
?>

<?php
global $profile;

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


