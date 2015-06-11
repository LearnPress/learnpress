<?php ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php do_action( 'learn_press_before_course_header' ); ?>
	<header class="entry-header">
		<?php
		do_action( 'learn_press_before_the_title' );		
		the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );		
		do_action( 'learn_press_after_the_title' );
		?>
	</header>
	<!-- .entry-header -->
    <?php do_action( 'learn_press_before_course_content' ); ?>
	<div class="entry-content">
		<?php
		do_action( 'learn_press_before_the_content' );		
		the_excerpt();		
		do_action( 'learn_press_after_the_content' );
		?>
	</div>
	<!-- .entry-content -->
    <?php do_action( 'learn_press_before_course_footer' ); ?>
	<footer class="entry-footer">		
		<?php 
			do_action( 'learn_press_entry_footer_archive' );		
			edit_post_link( __( 'Edit', 'learn_press' ), '<span class="edit-link">', '</span>' );
		?>
	</footer>
	<!-- .entry-footer -->

</article><!-- #post-## -->
