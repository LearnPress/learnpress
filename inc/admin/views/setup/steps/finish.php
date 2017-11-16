<?php
/**
 * Template for displaying finish step.
 *
 * @author  ThimPres
 * @package LearnPress/Admin/Views
 * @version 3.0.0
 */

defined( 'ABSPATH' ) or exit;
?>
<h2><?php _e( 'Finish', 'learnpress' ); ?></h2>

<p><?php _e( 'Congrats! You are almost done your settings.' ); ?></p>

<h3><?php _e( 'What\'s next?', 'learnpress' ); ?></h3>

<p class="finish-buttons">
    <a class="button"
       href="<?php echo admin_url( 'post-new.php?post_type=lp_course' ); ?>"><?php _e( 'Create new course', 'learnpress' ); ?></a>
    <a class="button"
       href="<?php echo admin_url( 'index.php' ); ?>"><?php _e( 'Back to Dashboard', 'learnpress' ); ?></a>
</p>