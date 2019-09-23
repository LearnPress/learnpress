<?php
/**
 * Template for displaying title of section in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/section/title.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user        = learn_press_get_current_user();
$course      = learn_press_get_the_course();
$user_course = $user->get_course_data( get_the_ID() );

if ( ! isset( $section ) ) {
	return;
}

$title = $section->get_title();
?>

<div class="section-header">

    <div class="section-left">

		<?php if ( $title ) { ?>
            <h5 class="section-title"><?php echo $title; ?></h5>
		<?php } ?>

		<?php if ( $description = $section->get_description() ) { ?>
            <p class="section-desc"><?php echo $description; ?></p>
		<?php } ?>

    </div>

	<?php if ( $user->has_enrolled_course( $section->get_course_id() ) ) { ?>

		<?php $percent = $user_course->get_percent_completed_items( '', $section->get_id() ); ?>

        <div class="section-meta">
            <div class="circle-progress"></div>
            <!--
            <div class="learn-press-progress section-progress" title="<?php echo intval( $percent ); ?>%">
                <div class="progress-bg">
                    <div class="progress-active primary-background-color" style="left: <?php echo $percent; ?>%;"></div>
                </div>
            </div>
            -->

<!--            <div class="circle-progress" data-pct="100">-->
<!--                    <svg id="svg" width="20" height="20" viewPort="0 0 10 10" version="1.1" xmlns="http://www.w3.org/2000/svg">-->
<!--                        <circle r="9" cx="10" cy="10" fill="transparent" stroke-dasharray="56.548" stroke-dashoffset="0"></circle>-->
<!--                        <circle class="bar" r="9" cx="10" cy="10" fill="transparent" stroke-dasharray="56.548" stroke-dashoffset="25"></circle>-->
<!--                    </svg>-->
<!--            </div>-->
<!--            <span class="step">--><?php //printf( __( '%d/%d', 'learnpress' ), $user_course->get_completed_items( '', false, $section->get_id() ), $section->count_items( '', false ) ); ?><!--</span>-->
<!--            <span class="collapse"></span>-->
        </div>

	<?php } ?>

</div>
