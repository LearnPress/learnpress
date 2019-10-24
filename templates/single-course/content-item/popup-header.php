<?php
/**
 * Template for displaying header of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/header.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user   = learn_press_get_current_user();
$course = LP_Global::course();

?>

<div id="popup-header">

    <!--    <div class="course-item-search">-->
    <!--        <form>-->
    <!--            <input type="text" placeholder="--><?php //esc_attr_e( 'Search item', 'learnpress' ); ?><!--"/>-->
    <!--            <button type="button"></button>-->
    <!--        </form>-->
    <!--    </div>-->

    <h2 class="course-title">
        <a href="<?php echo esc_url( $course->get_permalink() ) ?>"><?php echo $course->get_title(); ?></a>
    </h2>

	<?php if ( $user->can_finish_course( $course->get_id() ) ) { ?>
        <a class="lp-button button"
           href="<?php echo $course->get_permalink(); ?>"><?php _e( 'Back to Course', 'learnpress' ); ?></a>
	<?php } ?>

</div>
