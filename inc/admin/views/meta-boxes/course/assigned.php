<?php
/**
 * Admin View: Lesson, Quiz assigned Meta box
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php global $post; ?>

<?php $courses = learn_press_get_item_courses( $post->ID ); ?>

<?php if ( $courses ) { ?>

	<?php foreach ( $courses as $course ) { ?>
        <div>
            <a href="<?php echo get_edit_post_link( $course->ID ); ?> "
               target="_blank"><?php echo get_the_title( $course->ID ); ?></a>
        </div>
	<?php } ?>

<?php } else { ?>

	<?php _e( 'Not assigned yet', 'learnpress' ); ?>

<?php } ?>