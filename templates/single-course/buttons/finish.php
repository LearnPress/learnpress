<?php
/**
 * Template for displaying Finish button in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/buttons/finish.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
$user   = LP_Global::user();
?>

<form class="lp-form form-button form-button-finish-course" method="post"
      data-confirm="<?php LP_Strings::esc_attr_e( 'confirm-finish-course', '', array( $course->get_title() ) ); ?>">

    <button class="lp-button"><?php _e( 'Finish course', 'learnpress' ); ?></button>
    <input type="hidden" name="course-id" value="<?php echo $course->get_id(); ?>"/>
    <input type="hidden" name="finish-course-nonce"
           value="<?php echo esc_attr( wp_create_nonce( sprintf( 'finish-course-%d-%d', $course->get_id(), $user->get_id() ) ) ); ?>"/>
    <input type="hidden" name="lp-ajax" value="finish-course"/>
    <input type="hidden" name="noajax" value="yes"/>

</form>
