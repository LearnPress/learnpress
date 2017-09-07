<?php
/**
 * Template for displaying progress of current quiz user are doing.
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 3.x.x
 */

defined( 'ABSPATH' ) or die();

$user        = LP_Global::user();
$quiz        = LP_Global::course_item_quiz();
$course_data = $user->get_course_data( get_the_ID() );

$quiz_item = $course_data->get_item_quiz( $quiz->get_id() );// $user->get_quiz_data( $quiz->get_id() );

//learn_press_debug( $quiz_item->get_result() );