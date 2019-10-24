<?php
/**
 * Template for displaying wrap start of archive course within the loop.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/loop/course/loop-begin.php.
 *
 * @author   ThimPress
 * @package  Learnpress/Templates
 * @version  4.x.x
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<ul class="learn-press-courses" data-layout="<?php echo learn_press_get_courses_layout(); ?>">
