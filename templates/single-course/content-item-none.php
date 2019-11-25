<?php
/**
 * Template for displaying item content in single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/content-item.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.9
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

$user          = LP_Global::user();
$course_item   = LP_Global::course_item();
$course        = LP_Global::course();
$can_view_item = $user->can_view_item( $course_item->get_id(), $course->get_id() );

learn_press_debug($course_item);