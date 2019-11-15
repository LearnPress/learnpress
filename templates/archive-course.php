<?php
/**
 * Template for displaying content of archive courses page.
 *
 * @version 4.x.x
 * @author  ThimPress
 * @package LearnPress/Templates
 */

defined( 'ABSPATH' ) or die;

/**
 * Page template header
 */
get_header( 'course' );

/**
 * LP Hook
 */
do_action( 'learn-press/before-main-content' );
?>
    <header class="learn-press-courses-header">
        <h1><?php learn_press_page_title(); ?></h1>
    </header>
<?php

//do_action( 'learn-press/before-courses-loop' );

LP()->template('course')->begin_courses_loop();

while ( have_posts() ) : the_post();

	learn_press_get_template_part( 'content', 'course' );

endwhile;

LP()->template('course')->end_courses_loop();

/**
 * @since 3.0.0
 */
do_action( 'learn-press/after-courses-loop' );


/**
 * LP Hook
 */
do_action( 'learn-press/after-main-content' );

/**
 * LP Hook
 */
do_action( 'learn-press/sidebar' );

/**
 * Page template footer
 */
get_footer( 'course' );