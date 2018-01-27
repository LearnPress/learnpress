<?php
/**
 * Template for displaying header of single course popup.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/header.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$course = LP_Global::course();
?>

<div id="course-item-content-header">

    <div class="course-item-search">
        <form>
            <input type="text" placeholder="<?php esc_attr_e( 'Search item', 'learnpress' ); ?>"/>
            <button type="button"></button>
        </form>
    </div>

    <h2 class="course-title">
        <a href="<?php echo esc_url( $course->get_permalink() ) ?>"><?php echo $course->get_title(); ?></a>
    </h2>

    <a class="toggle-content-item" href=""></a>

    <form class="lp-form form-button lp-button-back" method="post" action="<?php echo $course->get_permalink(); ?>">
        <button class="lp-button button"><?php _e( 'Back to Course', 'learnpress' ); ?></button>
    </form>


</div>
