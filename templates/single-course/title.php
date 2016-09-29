<?php
/**
 * Single course title
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>

<h1 itemprop="name" class="course-title entry-title"><?php the_title(); ?></h1>