<?php
/**
 * Template for displaying profile header.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or exit;

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


