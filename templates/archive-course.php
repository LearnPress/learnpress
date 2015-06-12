<?php

if ( !defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

get_header(); ?>
<?php //echo __FILE__;?>

<section id="primary" class="content-area">
	<main id="main" class="site-main" role="main">

		<?php if ( have_posts() ) : ?>

			<header class="page-header">
				<?php
				the_archive_title( '<h1 class="page-title">', '</h1>' );
				the_archive_description( '<div class="taxonomy-description">', '</div>' );
				?>
			</header><!-- .page-header -->

			<?php
			// Start the Loop.
			while ( have_posts() ) : the_post();
				learn_press_get_template( 'archive-course-content.php' );
			endwhile;

			// Previous/next page navigation.
			learn_press_course_paging_nav();
		endif;
		?>

	</main>
	<!-- .site-main -->
</section><!-- .content-area -->
<?php get_footer(); ?>
