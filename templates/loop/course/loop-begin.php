<?php
/**
 * Template for displaying wrap start of archive course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/loop-begin.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.0.0
 */

defined( 'ABSPATH' ) || exit();

echo wp_kses_post( apply_filters( 'learn_press_course_loop_begin', '<ul class="learn-press-courses" data-layout="' . learn_press_get_courses_layout() . '">' ) );
