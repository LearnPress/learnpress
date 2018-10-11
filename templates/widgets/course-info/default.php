<?php
/**
 * Template for displaying content of Course Info widget.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/widgets/course-info/default.php.
 *
 * @author   ThimPress
 * @category Widgets
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

if ( ! $course = LP_Global::course() ) {
	return;
}

?>
<div<?php $widget->get_class( $widget->instance ); ?>>

    <div class="widget-body">

        <ul class="lp-course-info-fields">
            <li class="lp-course-info lessons">
                <label><?php _e( 'Lessons', 'learnpress' ); ?></label>
				<?php learn_press_label_html( $course->count_items( LP_LESSON_CPT ) ); ?>
            </li>

            <li class="lp-course-info quizzes">
                <label><?php _e( 'Quizzes', 'learnpress' ); ?></label>
				<?php learn_press_label_html( $course->count_items( LP_QUIZ_CPT ) ); ?>
            </li>

            <li class="lp-course-info preview-items">
                <label><?php _e( 'Preview Lessons', 'learnpress' ); ?></label>
				<?php learn_press_label_html( $course->count_preview_items() ); ?>
            </li>

            <li class="lp-course-info all-items">
                <label><?php _e( 'All', 'learnpress' ); ?></label>
				<?php learn_press_label_html( $course->count_items() ); ?>
            </li>
        </ul>

    </div>

</div>