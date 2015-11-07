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
?>

	<?php if ( is_singular() ) { ?>
	<div class="course-thumbnail">
		<?php
		$attr = array(
			'itemprop' => 'image'
		);
		the_post_thumbnail( '', $attr );
		?>
	</div>
	<?php } else { ?>
	<a class="course-thumbnail" href="<?php the_permalink(); ?>" aria-hidden="true">
		<?php
		the_post_thumbnail( 'post-thumbnail', array( 'alt' => get_the_title() ) );
		?>
	</a>
	<?php } ?>
