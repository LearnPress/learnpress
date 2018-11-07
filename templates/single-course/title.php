<?php
/**
 * Template for displaying title of single course.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/single-course/title.php.
 *
 * @author  ThimPress
 * @package  Learnpress/Templates
 * @version  3.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();

?>

<h1 itemprop="name" class="course-title entry-title"><?php the_title(); ?></h1>