<?php
/**
 * Template for displaying Finish button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/finish.php.
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

<?php
$course = LP_Global::course();
$user   = LP_Global::user();
?>

<form class="lp-form form-button" method="post">

    <button class="lp-button"><?php _e( 'Finish', 'learnpress' ); ?></button>
    <input type="hidden" name="course-id" value="<?php echo $course->get_id(); ?>"/>
    <input type="hidden" name="finish-course-nonce"
           value="<?php echo esc_attr( wp_create_nonce( sprintf( 'finish-course-%d-%d', $course->get_id(), $user->get_id() ) ) ); ?>"/>
    <input type="hidden" name="lp-ajax" value="finish-course"/>
    <input type="hidden" name="noajax" value="yes"/>

</form>
