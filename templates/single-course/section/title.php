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
            <div class="section-progress"
                 title="<?php echo esc_attr( sprintf( __( 'Section progress %s%%', 'learnpress' ), round( $percent, 2 ) ) ); ?>"><?php learn_press_circle_progress_html( $percent, 24, 6 ); ?></div>
        </div>

	<?php } ?>

</div>
