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
<div class="learn-press-profile-dashboard">

    <?php

    do_action('learn-press/profile/before-dashboard');
    ?>

    <?php

    do_action('learn-press/profile/dashboard-summary');
    ?>

    <?php

    do_action('learn-press//profile/after-dashboard');
    ?>

</div>