<?php
/**
 * Template for displaying the thumbnail of a course
 */

learn_press_prevent_access_directly();
do_action( 'learn_press_before_course_thumbnail' );
if ( is_singular() ) {
	?>
	<div class="course-thumbnail">
		<?php 
			$attr = array(
				'itemprop' => 'image'
			);
			the_post_thumbnail( '', $attr );
		?>
	</div>
<?php
} else {
	?>
	<a class="course-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
		<?php
		the_post_thumbnail( 'post-thumbnail', array( 'alt' => get_the_title() ) );
		?>
	</a>
<?php

}
do_action( 'learn_press_after_course_thumbnail' );
