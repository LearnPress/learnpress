<?php
/**
 * Template for displaying Continue button of course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/button/continue.php
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) or die();
?>

<?php $user = LP_Global::user(); ?>

<form name="continue-course" class="continue-course form-button lp-form" method="post"
      action="<?php echo $user->get_current_item( get_the_ID(), true ); ?>">
    <button type="submit" class="button"><?php _e( 'Continue', 'learnpress' ); ?></button>
</form>
