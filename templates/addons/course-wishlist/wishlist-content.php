<div>
	<?php
	do_action( 'learn_press_before_wishlist_course_title' );
	the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
	do_action( 'learn_press_after_wishlist_course_title' );

	do_action( 'learn_press_before_wishlist_course_content' );
	the_excerpt();
	do_action( 'learn_press_after_wishlist_course_content' );

	?>
</div>