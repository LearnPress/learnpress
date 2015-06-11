<?php ?>
<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
	<?php do_action( 'learn_press_before_course_header' ); ?>
	<header class="entry-header">
		<?php
		do_action( 'learn_press_before_the_title' );		
		the_title( '<h1 class="entry-title">', '</h1>' );		
		do_action( 'learn_press_after_the_title' );
		?>
	</header>
	<!-- .entry-header -->
    <?php do_action( 'learn_press_before_course_content' ); ?>
	<div class="entry-content">
		<?php
		do_action( 'learn_press_before_the_content' );		
		if ( learn_press_is_enrolled_course() ) {
			learn_press_get_template_part( 'course_content', 'learning_page' );
		} else
			learn_press_get_template_part( 'course_content', 'landing_page' );
		do_action( 'learn_press_after_the_content' );
		?>
	</div>
	<!-- .entry-content -->
    <?php do_action( 'learn_press_before_course_footer' ); ?>
	<footer class="entry-footer">
		<?php
		edit_post_link( __( 'Edit', 'learn_press' ), '<span class="edit-link">', '</span>' );
		?>
	</footer>
	<!-- .entry-footer -->

</article><!-- #post-## -->
