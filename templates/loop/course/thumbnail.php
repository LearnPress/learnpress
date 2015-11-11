<?php
/**
 * Template for displaying course thumbnail within the loop
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 */

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

global $course;
if( !has_post_thumbnail() ){
	return;
}

?>

<a class="course-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
	<?php
	the_post_thumbnail( 'course_thumbnail', array( 'alt' => get_the_title() ) );
	?>
</a>